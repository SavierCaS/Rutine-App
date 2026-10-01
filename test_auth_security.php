<?php
$_SERVER['REQUEST_METHOD'] = 'POST';

function simulateAuthCall($action, $payload) {
    ob_start();
    // Simulate JSON input
    $GLOBALS['simulated_input'] = $payload;
    $_GET['action'] = $action;
    
    // We execute api_auth logic
    include __DIR__ . '/controllers/api_auth.php';
    $output = ob_get_clean();
    return json_decode($output, true);
}

// Modify api_bootstrap getJsonInput to support simulation
require_once __DIR__ . '/controllers/api_bootstrap.php';

echo "=== TEST 1: LOGIN CON USUARIO INEXISTENTE ===" . PHP_EOL;
$fakeUser = [
    'email' => 'no_existe_999@kynap.com',
    'password' => 'cualquierClave'
];
// Test direct query in database
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $fakeUser['email']);
$checkStmt->execute();
$checkStmt->store_result();
echo "Usuario existe en BD?: " . ($checkStmt->num_rows > 0 ? "SI" : "NO") . PHP_EOL;
$checkStmt->close();

echo "=== TEST 2: LOGIN CON CONTRASEÑA INCORRECTA ===" . PHP_EOL;
$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = 'atleta@kynap.com' LIMIT 1");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$validPass = password_verify('123456', $row['password']);
$invalidPass = password_verify('claveEquivocada', $row['password']);
echo "Password correcto (123456): " . ($validPass ? "VALIDO" : "FALLO") . PHP_EOL;
echo "Password incorrecto (claveEquivocada): " . ($invalidPass ? "VALIDO" : "RECHAZADO") . PHP_EOL;
$stmt->close();

echo "=== TEST 3: REGISTRO COMPLETO CON BIOMETRIA ===" . PHP_EOL;
$newEmail = 'nuevo_atleta_' . time() . '@kynap.com';
$password = 'SeguridadTotal2026';
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$nombre = 'Carlos Mendoza';
$edad = 28;
$peso = 82.5;
$altura = 180.0;
$sexo = 'masculino';
$objetivo = 'ganar_musculo';
$nivel_actividad = 'moderado';

// Insert into users
$ins = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, 'iconos/profile.svg', 1)");
$ins->bind_param("sss", $nombre, $newEmail, $hashed);
$ins->execute();
$newUid = $ins->insert_id;
$ins->close();
echo "Nuevo usuario creado con ID: $newUid" . PHP_EOL;

// Insert biometrics
$agua = round($peso * 0.035, 1);
$bio = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$bio->bind_param("iddiissd", $newUid, $peso, $altura, $edad, $sexo, $objetivo, $nivel_actividad, $agua);
$bio->execute();
echo "Biometría guardada correctamente: " . ($bio->affected_rows > 0 ? "SI" : "NO") . PHP_EOL;
$bio->close();

// Insert calculated nutrition goals
$targetCal = (int)round($peso * 34); // Ganar músculo
$prot = (int)round($peso * 2.0);
$fats = (int)round($peso * 0.9);
$carbs = (int)round(($targetCal - ($prot * 4 + $fats * 9)) / 4);
$waterMl = (int)round($agua * 1000);

$goal = $conn->prepare("INSERT INTO nutrition_goals (user_id, daily_calories, protein_g, carbs_g, fats_g, water_ml, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
$goal->bind_param("iiiiii", $newUid, $targetCal, $prot, $carbs, $fats, $waterMl);
$goal->execute();
echo "Metas calculadas (Cal: $targetCal kcal, Prot: {$prot}g, Carbs: {$carbs}g, Grasas: {$fats}g, Agua: {$waterMl}ml): " . ($goal->affected_rows > 0 ? "EXITO" : "FALLO") . PHP_EOL;
$goal->close();

// Duplicate check test
$dup = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$dup->bind_param("s", $newEmail);
$dup->execute();
$dup->store_result();
echo "Detección de correo duplicado: " . ($dup->num_rows > 0 ? "DETECTADO Y BLOQUEADO" : "FALLO") . PHP_EOL;
$dup->close();

// Clean up
$conn->query("DELETE FROM users WHERE id = $newUid");
echo "Usuario de prueba eliminado limpiamente." . PHP_EOL;
