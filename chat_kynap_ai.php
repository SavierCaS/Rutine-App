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

// Recuperar biometría y encuesta completa para contexto personalizado seguro
$bioContext = "Atleta de Kynap";
$userBioData = [
    'peso' => 75.0,
    'altura' => 175.0,
    'edad' => 25,
    'sexo' => 'masculino',
    'objetivo' => 'ganar_musculo',
    'nivel_actividad' => 'moderado',
    'agua' => 3.0,
    'condiciones' => 'ninguna',
    'alergias' => 'ninguna',
    'preferencia' => 'omnivora'
];

if ($dbAvailable) {
    $bStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? LIMIT 1");
    if ($bStmt) {
        $bStmt->bind_param("i", $userId);
        $bStmt->execute();
        $bRes = $bStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            foreach ($bRow as $k => $v) {
                if ($v !== null && $v !== '') {
                    $userBioData[$k] = $v;
                }
            }
            $bioContext = "Edad: {$userBioData['edad']} años, Sexo: {$userBioData['sexo']}, Peso: {$userBioData['peso']}kg, Altura: {$userBioData['altura']}cm, Objetivo: {$userBioData['objetivo']}, Actividad: {$userBioData['nivel_actividad']}, Agua meta: {$userBioData['agua']}L/día, Limitaciones físicas/salud: {$userBioData['condiciones']}, Alergias: {$userBioData['alergias']}, Preferencia alimentaria: {$userBioData['preferencia']}";
        }
        $bStmt->close();
    }
}

$systemPrompt = "Eres Kynap Coach AI, un entrenador personal y nutricionista deportivo de élite para la plataforma Kynap. "
    . "Tu objetivo es brindar respuestas motivadoras, técnicas, seguras y concisas basadas en evidencia científica para el atleta. "
    . "ATENCIÓN CRÍTICA: Debes basar todas tus respuestas y consejos en el perfil real del usuario: $bioContext. "
    . "Si tiene condiciones de salud o lesiones (ej: dolor lumbar, rodilla), adapta los ejercicios. Si tiene alergias o preferencias dietéticas, respétalas estrictamente. "
    . "Responde en español con tono profesional, energético y claro (máximo 2 a 3 párrafos).";

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

// Fallback inteligente hiper-personalizado con los datos reales del usuario
if (empty($aiReply)) {
    $lowerPrompt = mb_strtolower($prompt, 'UTF-8');
    $cond = $userBioData['condiciones'];
    $alerg = $userBioData['alergias'];
    $pref = $userBioData['preferencia'];
    $obj = $userBioData['objetivo'];
    $aguaMeta = $userBioData['agua'];

    if (str_contains($lowerPrompt, 'agua') || str_contains($lowerPrompt, 'hidrat')) {
        $aiReply = "Para tu peso ({$userBioData['peso']} kg) y nivel de actividad ({$userBioData['nivel_actividad']}), tu meta diaria personalizada es de {$aguaMeta} L de agua al día. Te recomiendo distribuir la ingesta en tomas de 300-500 ml a lo largo del día y consumir 500 ml adicionales durante tu sesión de entrenamiento.";
    } elseif (str_contains($lowerPrompt, 'rutina') || str_contains($lowerPrompt, 'ejercicio') || str_contains($lowerPrompt, 'entren')) {
        $condAdvice = ($cond !== 'ninguna') ? " Tomando en cuenta tu condición física ('{$cond}'), tus ejercicios han sido optimizados para proteger tus articulaciones y evitar sobrecargas lesivas." : "";
        $aiReply = "Tu plan de entrenamiento está enfocado en tu objetivo principal de '" . str_replace('_', ' ', $obj) . "'.{$condAdvice} Recuerda seguir el orden estricto de los ejercicios en 'Entrenar en Vivo' y completar todas las series antes de avanzar para asegurar el estímulo neuromuscular óptimo.";
    } elseif (str_contains($lowerPrompt, 'prote') || str_contains($lowerPrompt, 'comida') || str_contains($lowerPrompt, 'dieta') || str_contains($lowerPrompt, 'nutri')) {
        $dietNote = "Adaptado a tu preferencia '{$pref}'" . ($alerg !== 'ninguna' ? " y excluyendo estrictamente '{$alerg}'" : "") . ".";
        $aiReply = "Para tu objetivo de '" . str_replace('_', ' ', $obj) . "', tus macros diarios han sido calculados con base en tu peso corporal de {$userBioData['peso']} kg. {$dietNote} Distribuye tu ingesta en las 5 comidas programadas en tu pestaña de Nutrición para optimizar la síntesis proteica y tus niveles de energía.";
    } else {
        $aiReply = "¡Hola! Como tu Coach Kynap AI, estoy monitoreando tu evolución para tu objetivo de " . str_replace('_', ' ', $obj) . ". Recuerda que la consistencia en tu rutina semanal y la actualización de tus medidas al completar cada bloque son fundamentales para calibrar tus progresos.";
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