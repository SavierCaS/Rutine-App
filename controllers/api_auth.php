<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE AUTENTICACIÓN Y SESIÓN UNIFICADA
 * ==============================================================================
 * Proporciona autenticación híbrida sin estado (Stateless Bearer Token) para la
 * aplicación móvil (Flutter/React Native) y con estado (PHP Session) para la
 * aplicación web.
 *
 * Endpoints soportados:
 * - POST /controllers/api_auth.php?action=login     -> Inicia sesión y retorna Bearer Token
 * - POST /controllers/api_auth.php?action=register  -> Registra nueva cuenta
 * - GET  /controllers/api_auth.php?action=verify    -> Valida token o sesión activa
 * - POST /controllers/api_auth.php?action=logout    -> Cierra sesión
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$input = getJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? 'verify';

// ==============================================================================
// 1. ENDPOINT: VERIFY (Comprueba validez del token o de la sesión web)
// ==============================================================================
if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = resolveAuthUser($conn);
    
    // Estricta verificación: sólo si no es invitado y proviene de token o sesión legítima
    $isAuthenticated = (!$user['isGuest'] && $user['id'] > 0 && in_array($user['authSource'], ['bearer_token', 'php_session', 'custom_header']));

    if ($isAuthenticated) {
        // Consultar biometría asociada directamente desde la tabla maestra
        $biometrics = null;
        if ($dbAvailable) {
            $stmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo FROM usuarios WHERE user_id = ? LIMIT 1");
            if (!$stmt) {
                $stmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo FROM user_biometrics WHERE user_id = ? LIMIT 1");
            }
            if ($stmt) {
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $bRes = $stmt->get_result();
                if ($bRow = $bRes->fetch_assoc()) {
                    $biometrics = $bRow;
                }
                $stmt->close();
            }
        }

        apiSuccess([
            'authenticated' => true,
            'user' => $user,
            'biometrics' => $biometrics,
            'token' => generateAuthToken($user['id'], $user['email']),
            'dbConnected' => $dbAvailable
        ], 'Sesión activa y válida');
    }

    apiSuccess([
        'authenticated' => false,
        'user' => null,
        'message' => 'No autenticado. Por favor inicia sesión o crea una cuenta.'
    ]);
}

// ==============================================================================
// 2. ENDPOINT: LOGIN (Validación estricta de base de datos)
// ==============================================================================
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($input['password'] ?? '');

    if (!$email || empty($password)) {
        apiError('Debe proporcionar un correo electrónico válido y una contraseña.', 422);
    }

    if (!$dbAvailable) {
        apiError('El servidor de base de datos no está disponible. Intente más tarde.', 503);
    }

    // Consulta estricta a la tabla accounts (con fallback a users)
    $stmt = $conn->prepare("SELECT id, nombre, email, password, imagen_user, chat_activado FROM accounts WHERE email = ? LIMIT 1");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT id, nombre, email, password, imagen_user, chat_activado FROM users WHERE email = ? LIMIT 1");
    }
    if (!$stmt) {
        apiError('Error interno en la consulta de autenticación: ' . $conn->error, 500);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Si el usuario existe, validar hash criptográfico
        if (password_verify($password, $row['password'])) {
            $userId = (int)$row['id'];
            
            // Establecer sesión PHP para navegación web coherente
            $_SESSION['user_id'] = $userId;
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['imagen_user'] = $row['imagen_user'] ?? 'iconos/profile.svg';
            
            // Generar Bearer Token HMAC firmado para clientes móviles y PWA
            $token = generateAuthToken($userId, $row['email']);

            // Obtener biometría del atleta (desde usuarios o user_biometrics)
            $biometrics = null;
            $bioStmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo FROM usuarios WHERE user_id = ? LIMIT 1");
            if (!$bioStmt) {
                $bioStmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo FROM user_biometrics WHERE user_id = ? LIMIT 1");
            }
            if ($bioStmt) {
                $bioStmt->bind_param("i", $userId);
                $bioStmt->execute();
                $bRes = $bioStmt->get_result();
                if ($bRow = $bRes->fetch_assoc()) {
                    $biometrics = $bRow;
                }
                $bioStmt->close();
            }

            apiSuccess([
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'nombre' => $row['nombre'],
                    'email' => $row['email'],
                    'imagen_user' => $row['imagen_user'] ?? 'iconos/profile.svg',
                    'chat_activado' => (bool)$row['chat_activado']
                ],
                'biometrics' => $biometrics
            ], 'Inicio de sesión exitoso');
        } else {
            // Contraseña errónea pero correo existente
            apiError('Contraseña incorrecta. Por favor verifica tus credenciales.', 401, [
                'code' => 'INVALID_PASSWORD',
                'user_exists' => true,
                'suggest_register' => false
            ]);
        }
    } else {
        // Usuario NO EXISTE en la base de datos -> Denegación y obligación de registro
        apiError('No existe ninguna cuenta registrada con este correo. Debes crear una cuenta antes de acceder.', 404, [
            'code' => 'USER_NOT_FOUND',
            'user_exists' => false,
            'suggest_register' => true
        ]);
    }
    $stmt->close();
}

// ==============================================================================
// 3. ENDPOINT: REGISTER (Creación completa con biometría y cálculo de macros)
// ==============================================================================
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Datos personales y credenciales
    $nombre = trim($input['nombre'] ?? '');
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($input['password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? $password);

    if (empty($nombre) || !$email || strlen($password) < 6) {
        apiError('Datos inválidos. El nombre es obligatorio y la contraseña debe tener al menos 6 caracteres.', 422);
    }

    if ($password !== $confirmPassword) {
        apiError('Las contraseñas no coinciden. Por favor verifícalas.', 422);
    }

    if (!$dbAvailable) {
        apiError('Servicio de base de datos no disponible.', 503);
    }

    // 2. Parámetros biométricos iniciales (con valores saludables por defecto y compatibilidad formNutricion)
    $edad = max(12, min(100, (int)($input['edad'] ?? 25)));
    $peso = max(30.0, min(300.0, (float)($input['peso'] ?? 70.0)));
    $altura = max(100.0, min(250.0, (float)($input['altura'] ?? 170.0)));
    $sexo = in_array($input['sexo'] ?? '', ['masculino', 'femenino', 'otro']) ? $input['sexo'] : 'masculino';

    // Normalizar objetivo (admite tanto bajar_peso/perder_grasa como mantener/mantener_peso)
    $rawObj = trim($input['objetivo'] ?? 'ganar_musculo');
    if ($rawObj === 'bajar_peso') {
        $objetivo = 'perder_grasa';
    } elseif ($rawObj === 'mantener_peso') {
        $objetivo = 'mantener';
    } elseif (in_array($rawObj, ['ganar_musculo', 'perder_grasa', 'mantener'])) {
        $objetivo = $rawObj;
    } else {
        $objetivo = 'ganar_musculo';
    }

    // Normalizar nivel de actividad (admite sedentario, ligero, moderado, activo, muy_activo)
    $rawAct = trim($input['nivel_actividad'] ?? 'moderado');
    if (in_array($rawAct, ['sedentario', 'ligero', 'moderado', 'activo', 'muy_activo'])) {
        $nivelActividad = $rawAct;
    } else {
        $nivelActividad = 'moderado';
    }

    // Consumo de agua diario ingresado por el usuario o estimado por peso
    $rawAgua = isset($input['agua']) ? (float)$input['agua'] : 0;
    if ($rawAgua >= 1.0 && $rawAgua <= 15.0) {
        $aguaLitros = round($rawAgua, 1);
    } else {
        $aguaLitros = max(2.0, min(6.0, round($peso * 0.035, 1))); // 35 ml por kg de peso
    }

    $condiciones = isset($input['condiciones']) ? (is_array($input['condiciones']) ? implode(', ', $input['condiciones']) : trim($input['condiciones'])) : '';
    $alergias = trim($input['alergias'] ?? '');
    $preferencia = trim($input['preferencia'] ?? 'cualquierCosa');

    // 3. Verificar si el correo electrónico ya existe en la tabla accounts o users
    $checkStmt = $conn->prepare("SELECT id FROM accounts WHERE email = ? LIMIT 1");
    if (!$checkStmt) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    }
    if (!$checkStmt) {
        apiError('Error de base de datos al validar usuario: ' . $conn->error, 500);
    }
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        apiError('Este correo electrónico ya se encuentra registrado. Por favor inicia sesión.', 409, [
            'code' => 'EMAIL_EXISTS',
            'suggest_login' => true
        ]);
    }
    $checkStmt->close();

    // 4. Hashear contraseña con BCRYPT (cost 12 para máxima seguridad)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $defaultAvatar = 'iconos/profile.svg';
    $defaultChat = 1;

    // 5. Inserción en la tabla accounts (con fallback a users)
    $insertStmt = $conn->prepare("INSERT INTO accounts (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, ?, ?)");
    if (!$insertStmt) {
        $insertStmt = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, ?, ?)");
    }
    if (!$insertStmt) {
        apiError('Error al preparar inserción en base de datos: ' . $conn->error, 500);
    }

    $insertStmt->bind_param("ssssi", $nombre, $email, $hashedPassword, $defaultAvatar, $defaultChat);

    if ($insertStmt->execute()) {
        $newUserId = (int)$insertStmt->insert_id;
        $insertStmt->close();

        // 6. Inserción de biometría completa en user_biometrics y sincronización con tabla usuarios
        $bioStmt = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
        if ($bioStmt) {
            $bioStmt->bind_param("iddiissdsss", $newUserId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros, $condiciones, $alergias, $preferencia);
            $bioStmt->execute();
            $bioStmt->close();
        } else {
            // Fallback si la estructura aún no tiene columnas adicionales
            $bioStmtFallback = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
            if ($bioStmtFallback) {
                $bioStmtFallback->bind_param("iddiissd", $newUserId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros);
                $bioStmtFallback->execute();
                $bioStmtFallback->close();
            }
        }

        // Sincronización compatible con tabla legacy usuarios si está presente
        $uStmt = $conn->prepare("INSERT INTO usuarios (user_id, edad, peso, altura, objetivo, nivel_actividad, sexo, condiciones, alergias, preferencia, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE edad = VALUES(edad), peso = VALUES(peso), altura = VALUES(altura), objetivo = VALUES(objetivo), nivel_actividad = VALUES(nivel_actividad), sexo = VALUES(sexo), condiciones = VALUES(condiciones), alergias = VALUES(alergias), preferencia = VALUES(preferencia), agua = VALUES(agua)");
        if ($uStmt) {
            $aguaInt = (int)round($aguaLitros);
            $uStmt->bind_param("iidsssssssi", $newUserId, $edad, $peso, $altura, $objetivo, $nivelActividad, $sexo, $condiciones, $alergias, $preferencia, $aguaInt);
            $uStmt->execute();
            $uStmt->close();
        }

        // 7. Cálculo personalizado de metas nutricionales según objetivo y biometría
        $targetCalories = 2200;
        $proteinG = 150;
        $fatsG = 65;
        $carbsG = 230;

        if ($objetivo === 'ganar_musculo') {
            $targetCalories = (int)round($peso * 34); // Superávit moderado
            $proteinG = (int)round($peso * 2.0);
            $fatsG = (int)round($peso * 0.9);
        } elseif ($objetivo === 'perder_grasa') {
            $targetCalories = (int)round($peso * 25); // Déficit controlado
            $proteinG = (int)round($peso * 2.2); // Proteger masa magra
            $fatsG = (int)round($peso * 0.8);
        } else { // mantener
            $targetCalories = (int)round($peso * 30);
            $proteinG = (int)round($peso * 1.8);
            $fatsG = (int)round($peso * 0.9);
        }
        $carbsG = max(80, (int)round(($targetCalories - ($proteinG * 4 + $fatsG * 9)) / 4));
        $waterMl = (int)round($aguaLitros * 1000);

        $goalStmt = $conn->prepare("INSERT INTO nutrition_goals (user_id, daily_calories, protein_g, carbs_g, fats_g, water_ml, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
        if ($goalStmt) {
            $goalStmt->bind_param("iiiiii", $newUserId, $targetCalories, $proteinG, $carbsG, $fatsG, $waterMl);
            $goalStmt->execute();
            $goalStmt->close();
        }

        // 8. Autenticar inmediatamente (Sesión Web + Token Móvil)
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['imagen_user'] = $defaultAvatar;

        $token = generateAuthToken($newUserId, $email);

        apiSuccess([
            'token' => $token,
            'user' => [
                'id' => $newUserId,
                'nombre' => $nombre,
                'email' => $email,
                'imagen_user' => $defaultAvatar
            ],
            'biometrics' => [
                'peso' => $peso,
                'altura' => $altura,
                'edad' => $edad,
                'sexo' => $sexo,
                'objetivo' => $objetivo,
                'nivel_actividad' => $nivelActividad,
                'agua' => $aguaLitros,
                'condiciones' => $condiciones,
                'alergias' => $alergias,
                'preferencia' => $preferencia
            ],
            'goals' => [
                'calories' => $targetCalories,
                'protein' => $proteinG,
                'carbs' => $carbsG,
                'fats' => $fatsG,
                'water_ml' => $waterMl
            ]
        ], 'Cuenta registrada exitosamente', 201);
    } else {
        apiError('Error al crear la cuenta en la base de datos: ' . $insertStmt->error, 500);
    }
}

// ==============================================================================
// 4. ENDPOINT: LOGOUT (Limpieza de sesión web e invalidación)
// ==============================================================================
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    apiSuccess(null, 'Sesión finalizada con éxito');
}

apiError('Acción no soportada o método no válido', 405);
