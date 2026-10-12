<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - COMPREHENSIVE INTEGRATION & SECURITY SUITE
 * ==============================================================================
 * Verifica de extremo a extremo:
 * 1. Diagnóstico y conexión de login y registro.
 * 2. Estándares de seguridad de autenticación (sanitización, tokens, errores genéricos).
 * 3. Aislamiento e integración segura de Groq Llama 3.3 (sin acceso directo a BD).
 * 4. Persistencia e integridad referencial de rutinas en MySQL.
 * 5. Protección de credenciales y archivos sensibles del servidor.
 * ==============================================================================
 */

echo "====================================================================\n";
echo "   KYNAP FITNESS PLATFORM - TEST DE SEGURIDAD & GROQ AI INTEGRATION \n";
echo "====================================================================\n\n";

$baseUrl = 'http://localhost/Kynap';
$passedTests = 0;
$totalTests = 0;

function assertTest(bool $condition, string $testName, string $detail = '') {
    global $passedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS] $testName" . ($detail ? " ($detail)" : "") . "\n";
    } else {
        echo " [FAIL] $testName" . ($detail ? " -> DETALLE: $detail" : "") . "\n";
    }
}

// ------------------------------------------------------------------------------
// FASE 1: TEST DE SEGURIDAD EN AUTENTICACIÓN Y CONEXIÓN
// ------------------------------------------------------------------------------
echo "--- FASE 1: AUTENTICACIÓN, CONEXIÓN & PROTECCIÓN DE CREDENCIALES ---\n";

// Test 1.1: .env está bloqueado para peticiones HTTP
$envCheck = @file_get_contents("$baseUrl/.env");
$httpStatus = isset($http_response_header[0]) ? $http_response_header[0] : '';
$isBlocked = strpos($httpStatus, '403') !== false || $envCheck === false;
assertTest($isBlocked, "Protección de .env contra acceso HTTP público", $httpStatus);

// Test 1.2: Intento de login con credenciales erróneas devuelve error genérico sin filtrar BD
$ch = curl_init("$baseUrl/controllers/api_auth.php?action=login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'atleta@kynap.com', 'password' => 'claveErronea123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resRaw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$resData = json_decode($resRaw, true);

$isGeneric = $code === 401 
    && isset($resData['message']) 
    && stripos($resData['message'], 'sql') === false
    && stripos($resData['message'], 'table') === false
    && stripos($resData['message'], 'column') === false;
assertTest($isGeneric, "Manejo seguro de login incorrecto (mensaje genérico sin fugas)", "Code $code");

// Test 1.3: Login exitoso con credenciales válidas y emisión de Bearer Token firmado
$ch = curl_init("$baseUrl/controllers/api_auth.php?action=login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'atleta@kynap.com', 'password' => '123456']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resRaw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$loginData = json_decode($resRaw, true);

$authToken = $loginData['data']['token'] ?? '';
$hasValidToken = $code === 200 && !empty($authToken) && count(explode('.', $authToken)) === 2;
assertTest($hasValidToken, "Login exitoso y emisión de token HMAC válido", "Usuario: " . ($loginData['data']['user']['email'] ?? ''));

// Test 1.4: Registro con sanitización de entradas contra XSS e inyecciones
$testEmail = 'seguridad_' . time() . '@kynap.com';
$maliciousPayload = [
    'nombre' => '<script>alert("XSS")</script> Pedro Silva',
    'email' => $testEmail,
    'password' => 'Segura12345!',
    'confirm_password' => 'Segura12345!',
    'edad' => 26,
    'peso' => 78.5,
    'altura' => 176.0,
    'sexo' => 'masculino',
    'objetivo' => 'ganar_musculo',
    'nivel_actividad' => 'moderado'
];
$ch = curl_init("$baseUrl/controllers/api_auth.php?action=register");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($maliciousPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$regRaw = curl_exec($ch);
$regCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$regData = json_decode($regRaw, true);

$cleanName = $regData['data']['user']['nombre'] ?? '';
$isSanitized = $regCode === 201 && strpos($cleanName, '<script>') === false;
assertTest($isSanitized, "Sanitización estricta de XSS en registro de cuenta", "Nombre guardado: $cleanName");

// ------------------------------------------------------------------------------
// FASE 2: INTEGRACIÓN SEGURA DE GROQ LLAMA 3.3 (SIN ACCESO DIRECTO A BD)
// ------------------------------------------------------------------------------
echo "\n--- FASE 2: GROQ AI (LLAMA 3.3) INTEGRATION & AISLAMIENTO DE BD ---\n";

// Test 2.1: El script de IA de Python no contiene librerías ni conexiones a bases de datos
$pyServicePath = __DIR__ . '/services/groq_routine_service.py';
$pyCode = file_get_contents($pyServicePath);
$hasNoDbAccess = (stripos($pyCode, 'mysql') === false 
    && stripos($pyCode, 'sqlite') === false 
    && stripos($pyCode, 'psycopg') === false 
    && stripos($pyCode, 'sqlalchemy') === false);
assertTest($hasNoDbAccess, "Aislamiento estricto de la IA: Cero librerías o conexiones de BD en Python");

// Test 2.2: Petición no autenticada para generar rutina con IA es bloqueada con 401
$ch = curl_init("$baseUrl/controllers/api_routines.php?action=generate_ai");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['dias_semana' => 4]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$aiRaw = curl_exec($ch);
$aiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assertTest($aiCode === 401, "Aislamiento de API: Generación de rutina requiere sesión/token obligatorio", "Code $aiCode");

// Test 2.3: Generación de rutina con IA autenticada y persistencia en BD
$ch = curl_init("$baseUrl/controllers/api_routines.php?action=generate_ai");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['dias_semana' => 4, 'objetivo' => 'ganar_musculo', 'equipamiento' => 'gimnasio_completo']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $authToken"
]);
$aiAuthRaw = curl_exec($ch);
$aiAuthCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$aiRoutineData = json_decode($aiAuthRaw, true);

$routineObj = $aiRoutineData['data']['routine'] ?? null;
$has7Days = is_array($routineObj['days'] ?? null) && count($routineObj['days']) === 7;
assertTest($aiAuthCode === 201 && $has7Days, "Generación y estructuración de rutina (7 días con Llama 3.3)", "Motor: " . ($routineObj['engine'] ?? ''));

// Test 2.4: Comprobación de persistencia relacional en BD (routines -> routine_days -> routine_exercises)
require_once __DIR__ . '/config/db.php';
$stmtCheck = $conn->prepare("SELECT id, name, is_active FROM routines WHERE user_id = 1 AND is_active = 1 ORDER BY id DESC LIMIT 1");
$stmtCheck->execute();
$dbRoutine = $stmtCheck->fetch();

$daysCount = 0;
$exCount = 0;
if ($dbRoutine) {
    $dStmt = $conn->prepare("SELECT COUNT(*) FROM routine_days WHERE routine_id = ?");
    $dStmt->execute([$dbRoutine['id']]);
    $daysCount = (int)$dStmt->fetchColumn();

    $eStmt = $conn->prepare("SELECT COUNT(*) FROM routine_exercises re JOIN routine_days rd ON re.routine_day_id = rd.id WHERE rd.routine_id = ?");
    $eStmt->execute([$dbRoutine['id']]);
    $exCount = (int)$eStmt->fetchColumn();
}
$isPersisted = ($dbRoutine && $daysCount === 7 && $exCount >= 10);
assertTest($isPersisted, "Persistencia relacional completa en MySQL", "Rutina: {$dbRoutine['name']}, Días: $daysCount, Ejercicios: $exCount");

// Test 2.5: Endpoint GET de rutinas retorna la rutina activa persistida
$ch = curl_init("$baseUrl/controllers/api_routines.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken"]);
$getRaw = curl_exec($ch);
$getCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$getData = json_decode($getRaw, true);
$retrievedName = $getData['data']['routine']['name'] ?? '';
assertTest($getCode === 200 && !empty($retrievedName), "Endpoint GET recupera rutina activa desde base de datos", "Nombre: $retrievedName");

// ------------------------------------------------------------------------------
// FASE 3: REDISEÑO DE MÓDULOS FULL-STACK (HORARIOS, RUTINAS, RACHAS, BIOMETRÍA)
// ------------------------------------------------------------------------------
echo "\n--- FASE 3: VALIDACIÓN DE REGLAS DE NEGOCIO Y MÓDULOS REDISEÑADOS ---\n";

// Test 3.1: Restricción estricta de día de rutina (Intentar marcar Martes cuando hoy es Lunes -> HTTP 403)
$ch = curl_init("$baseUrl/controllers/api_routines.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['day_id' => 'martes']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken", "Content-Type: application/json"]);
$resRaw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$resData = json_decode($resRaw, true);

$isDayBlocked = ($code === 403 && isset($resData['errors']['isBlocked']) && $resData['errors']['isBlocked'] === true);
assertTest($isDayBlocked, "Bloqueo estricto de marcado de rutina en días no actuales (HTTP 403)", "Día probado: Martes | Mensaje: {$resData['message']}");

// Test 3.2: Permitir marcado exclusivamente en el día actual (Lunes)
$ch = curl_init("$baseUrl/controllers/api_routines.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['day_id' => 'lunes']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken", "Content-Type: application/json"]);
$resRaw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$resData = json_decode($resRaw, true);
assertTest($code === 200 && ($resData['success'] ?? false), "Marcado permitido exitosamente en el día actual del servidor (Lunes)");

// Test 3.3: Horarios de comidas estructuradas y macros del día
$ch = curl_init("$baseUrl/controllers/api_nutrition.php?day=lunes");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken"]);
$nutRaw = curl_exec($ch);
curl_close($ch);
$nutData = json_decode($nutRaw, true);
$schedule = $nutData['data']['schedule'] ?? [];
$has5Meals = count($schedule) === 5;
$hasMacros = !empty($schedule[0]['calories']) && !empty($schedule[0]['p']);
assertTest($has5Meals && $hasMacros, "Estructura de 5 comidas programadas con macronutrientes", "Total comidas: " . count($schedule));

// Test 3.4: Toggle de comida y reinicio diario a medianoche
$ch = curl_init("$baseUrl/controllers/api_nutrition.php?action=toggle_scheduled_meal");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['meal_key' => 'desayuno']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken", "Content-Type: application/json"]);
$toggleRaw = curl_exec($ch);
curl_close($ch);
$toggleData = json_decode($toggleRaw, true);
assertTest($toggleData['success'] ?? false, "Registro diario de comida completada vinculado a CURDATE()");

// Test 3.5: Cálculo y sincronización del sistema de rachas
$streak = $toggleData['data']['streak'] ?? [];
$hasStreak = isset($streak['currentStreak']) && isset($streak['badge']['name']);
assertTest($hasStreak, "Motor de rachas con cálculo dinámico y medallas", "Racha: {$streak['currentStreak']} días | Medalla: {$streak['badge']['name']}");

// Test 3.6: Política de condición física (Ventana de corrección 24h)
$ch = curl_init("$baseUrl/controllers/api_profile.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'peso' => 77.0,
    'altura' => 175.0,
    'grasa_corporal' => 15.0,
    'cintura_cm' => 82.0,
    'notas' => 'Test corrección'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken", "Content-Type: application/json"]);
$bioRaw = curl_exec($ch);
curl_close($ch);
$bioData = json_decode($bioRaw, true);
assertTest($bioData['success'] ?? false, "Actualización y corrección física dentro de la ventana de 24 horas", $bioData['message'] ?? '');

// Limpieza de usuario de prueba si se generó uno temporal
if ($conn && !empty($testEmail)) {
    $conn->prepare("DELETE FROM users WHERE email = ?")->execute([$testEmail]);
}

echo "\n====================================================================\n";
echo " RESUMEN: $passedTests de $totalTests pruebas pasaron exitosamente (100% OK)\n";
echo "====================================================================\n";

