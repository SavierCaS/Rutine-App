<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - SECURE AI COACH CHAT BRIDGE (GROQ LLAMA 3.3)
 * ==============================================================================
 * Endpoint del Coach AI para consultas de entrenamiento, técnica y nutrición.
 *
 * Principios de seguridad aplicados:
 * - Credenciales obtenidas exclusivamente desde .env (GROQ_API_KEY).
 * - La IA NO interactúa con la base de datos directamente.
 * - Validación y sanitización de mensajes de entrada y salida contra inyecciones y XSS.
 * - Manejo seguro de timeouts y respuestas de error genéricas.
 *
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

$user = resolveAuthUser($conn);
if ($user['isGuest'] || $user['id'] <= 0) {
    http_response_code(401);
    echo json_encode(['response' => 'Debes iniciar sesión para consultar al Coach Kynap AI.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = (int)$user['id'];

// Obtener datos (admite tanto JSON como urlencoded)
$input = getJsonInput();
$prompt = sanitizeString($input['message'] ?? $_POST['message'] ?? '');

// Reset de contador si se solicita
if (!empty($input['reset_messages']) || !empty($_POST['reset_messages'])) {
    if ($dbAvailable) {
        $stmt_update = $conn->prepare("UPDATE users SET mensajes_enviados = 0 WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("i", $userId);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    echo json_encode(['response' => 'Contador de mensajes restablecido con éxito.'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($prompt)) {
    echo json_encode(['response' => 'Por favor escribe una consulta para tu entrenador virtual.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Control de cuota de mensajes
$mensajesEnviados = 0;
if ($dbAvailable) {
    $stmt = $conn->prepare("SELECT mensajes_enviados FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($mensajesEnviados);
        $stmt->fetch();
        $stmt->close();
    }
}

if ($mensajesEnviados >= 25) { // Límite generoso para experiencia pro
    echo json_encode([
        'response' => 'Has alcanzado el límite de consultas de esta sesión. Tómate un descanso o recarga la página para reiniciar tu conversación.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Obtener credenciales desde .env
$groqApiKey = kynap_env('GROQ_API_KEY', '');
$groqModel = kynap_env('GROQ_MODEL', 'llama-3.3-70b-versatile');
$timeout = (int)kynap_env('GROQ_TIMEOUT_SECONDS', 30);

// Recuperar biometría para contexto personalizado seguro
$bioContext = "Atleta de Kynap";
if ($dbAvailable) {
    $bStmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua FROM user_biometrics WHERE user_id = ? LIMIT 1");
    if ($bStmt) {
        $bStmt->bind_param("i", $userId);
        $bStmt->execute();
        $bRes = $bStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            $bioContext = "Peso: {$bRow['peso']}kg, Altura: {$bRow['altura']}cm, Objetivo: {$bRow['objetivo']}, Actividad: {$bRow['nivel_actividad']}, Agua diaria meta: {$bRow['agua']}L";
        }
        $bStmt->close();
    }
}

$systemPrompt = "Eres Kynap Coach AI, un entrenador personal y fisiólogo del ejercicio de élite para la plataforma Kynap. "
    . "Tu objetivo es brindar respuestas motivadoras, técnicas, seguras y concisas basadas en evidencia científica para el atleta. "
    . "Contexto del usuario: $bioContext. Responde en español con tono profesional, energético y claro (máximo 2 a 3 párrafos).";

$aiReply = "";

// Llamada a Groq si la clave está configurada y es válida
if (!empty($groqApiKey) && !str_starts_with($groqApiKey, 'gsk_demo') && $groqApiKey !== 'your_groq_api_key_here') {
    $groqUrl = "https://api.groq.com/openai/v1/chat/completions";
    $requestData = [
        "model"       => $groqModel,
        "temperature" => 0.7,
        "max_tokens"  => 600,
        "messages"    => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init($groqUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $groqApiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response !== false && $httpCode === 200) {
        $respData = json_decode($response, true);
        if (isset($respData['choices'][0]['message']['content'])) {
            $aiReply = trim($respData['choices'][0]['message']['content']);
        }
    } else {
        error_log("[GROQ COACH API ERROR]: Code $httpCode, Error: $curlError");
    }
}

// Fallback inteligente si la clave externa no está activa o falló la conexión
if (empty($aiReply)) {
    $lowerPrompt = mb_strtolower($prompt, 'UTF-8');
    if (str_contains($lowerPrompt, 'agua') || str_contains($lowerPrompt, 'hidrat')) {
        $aiReply = "¡Gran pregunta sobre hidratación! Para tu nivel de actividad y perfil metabólico, te recomiendo mantener una ingesta constante a lo largo del día. Asegúrate de consumir al menos 500 ml antes de entrenar y reponer electrolitos si tu sesión supera los 45 minutos.";
    } elseif (str_contains($lowerPrompt, 'rutina') || str_contains($lowerPrompt, 'ejercicio') || str_contains($lowerPrompt, 'entren')) {
        $aiReply = "Para maximizar tu progreso, enfócate en la sobrecarga progresiva y en la calidad biomecánica de cada repetición. Puedes pulsar el botón '✨ Generar Rutina con Kynap AI' en la pestaña de Rutinas para obtener una programación semanal completa adaptada a tu objetivo.";
    } elseif (str_contains($lowerPrompt, 'prote') || str_contains($lowerPrompt, 'comida') || str_contains($lowerPrompt, 'dieta')) {
        $aiReply = "La síntesis de proteína muscular se optimiza distribuyendo tu consumo de proteínas en 3 a 4 tomas diarias de 25-40 gramos. Recuerda registrar tus comidas en la sección de Nutrición & Macros para asegurar que cumplas tus metas diarias.";
    } else {
        $aiReply = "¡Excelente consulta, atleta! Para progresar de forma constante, mantén la consistencia en tus entrenamientos programados, cuida tu descanso nocturno (7-8 horas) y respeta los tiempos de descanso entre series (60-90 segundos para hipertrofia, 2-3 minutos para fuerza máxima). ¿En qué más te puedo orientar hoy?";
    }
}

// Incrementar contador de mensajes
if ($dbAvailable) {
    $stmt_update = $conn->prepare("UPDATE users SET mensajes_enviados = mensajes_enviados + 1 WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("i", $userId);
        $stmt_update->execute();
        $stmt_update->close();
    }
}

// Sanitizar respuesta antes de enviar
echo json_encode([
    'response' => htmlspecialchars($aiReply, ENT_NOQUOTES, 'UTF-8')
], JSON_UNESCAPED_UNICODE);
exit();
?>