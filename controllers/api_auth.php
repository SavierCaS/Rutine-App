<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - SECURE AUTHENTICATION API
 * ==============================================================================
 * Proporciona endpoints de autenticación y gestión de sesiones bajo estrictos
 * estándares de ciberseguridad (OWASP):
 * - Sanitización exhaustiva y validación estricta de tipos.
 * - Hashing seguro de contraseñas con BCRYPT (cost 12).
 * - Protección contra fijación de sesión (session_regenerate_id).
 * - Manejo seguro de tokens HMAC con expiración.
 * - Mensajes de error genéricos que previenen la enumeración y la divulgación
 *   de detalles del servidor o de la base de datos.
 *
 * Endpoints:
 * - POST /controllers/api_auth.php?action=login
 * - POST /controllers/api_auth.php?action=register
 * - GET  /controllers/api_auth.php?action=verify
 * - POST /controllers/api_auth.php?action=logout
 *
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$input = getJsonInput();
$action = sanitizeString($_GET['action'] ?? $input['action'] ?? 'verify');

// ==============================================================================
// 1. ENDPOINT: VERIFY
// ==============================================================================
if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = resolveAuthUser($conn);
    $isAuthenticated = (!$user['isGuest'] && $user['id'] > 0 && in_array($user['authSource'], ['bearer_token', 'php_session', 'custom_header']));

    if ($isAuthenticated) {
        $biometrics = null;
        if ($dbAvailable) {
            $stmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            if (!$stmt) {
                $stmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo, condiciones, alergias, preferencia FROM usuarios WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            }
            if ($stmt) {
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $bRes = $stmt->get_result();
                if ($bRow = $bRes->fetch_assoc()) {
                    $biometrics = [
                        'peso'            => (float)$bRow['peso'],
                        'altura'          => (float)$bRow['altura'],
                        'objetivo'        => htmlspecialchars($bRow['objetivo'] ?? 'ganar_musculo', ENT_QUOTES, 'UTF-8'),
                        'nivel_actividad' => htmlspecialchars($bRow['nivel_actividad'] ?? 'moderado', ENT_QUOTES, 'UTF-8'),
                        'agua'            => (float)($bRow['agua'] ?? 3.0),
                        'edad'            => (int)($bRow['edad'] ?? 25),
                        'sexo'            => htmlspecialchars($bRow['sexo'] ?? 'masculino', ENT_QUOTES, 'UTF-8'),
                        'condiciones'     => htmlspecialchars($bRow['condiciones'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'alergias'        => htmlspecialchars($bRow['alergias'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'preferencia'     => htmlspecialchars($bRow['preferencia'] ?? 'omnivora', ENT_QUOTES, 'UTF-8')
                    ];
                }
                $stmt->close();
            }
        }

        apiSuccess([
            'authenticated' => true,
            'user'          => $user,
            'biometrics'    => $biometrics,
            'token'         => generateAuthToken($user['id'], $user['email']),
            'dbConnected'   => $dbAvailable
        ], 'Sesión activa y verificada');
    }

    apiSuccess([
        'authenticated' => false,
        'user'          => null,
        'message'       => 'No autenticado. Por favor inicia sesión.'
    ]);
}

// ==============================================================================
// 2. ENDPOINT: LOGIN
// ==============================================================================
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitización y validación estricta de entradas
    $rawEmail = trim($input['email'] ?? '');
    $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
    $password = (string)($input['password'] ?? '');

    if (!$email || empty($password)) {
        apiError('Debe proporcionar un correo electrónico válido y una contraseña.', 422);
    }

    // Mitigación de DoS en BCRYPT (limitar longitud máxima de contraseña a 128 caracteres)
    if (strlen($password) > 128) {
        apiError('Credenciales incorrectas o usuario no registrado.', 401);
    }

    if (!$dbAvailable) {
        apiError('El servidor de base de datos no está disponible. Por favor intenta más tarde.', 503);
    }

    // Consulta con sentencia preparada a la tabla users
    $stmt = $conn->prepare("SELECT id, nombre, email, password, imagen_user, chat_activado FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT id, nombre, email, password, imagen_user, chat_activado FROM accounts WHERE email = ? LIMIT 1");
    }
    if (!$stmt) {
        apiServerError('Error al preparar consulta de login: ' . $conn->error, 'Error interno del servidor. Por favor intenta de nuevo.');
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $userId = (int)$row['id'];

            // Regeneración segura de ID de sesión contra Session Fixation
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            // Establecer sesión segura
            $_SESSION['user_id'] = $userId;
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['imagen_user'] = $row['imagen_user'] ?? 'iconos/profile.svg';

            $token = generateAuthToken($userId, $row['email']);

            // Recuperar datos biométricos
            $biometrics = null;
            $bioStmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            if (!$bioStmt) {
                $bioStmt = $conn->prepare("SELECT peso, altura, objetivo, nivel_actividad, agua, edad, sexo, condiciones, alergias, preferencia FROM usuarios WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            }
            if ($bioStmt) {
                $bioStmt->bind_param("i", $userId);
                $bioStmt->execute();
                $bRes = $bioStmt->get_result();
                if ($bRow = $bRes->fetch_assoc()) {
                    $biometrics = [
                        'peso'            => (float)$bRow['peso'],
                        'altura'          => (float)$bRow['altura'],
                        'objetivo'        => htmlspecialchars($bRow['objetivo'] ?? 'ganar_musculo', ENT_QUOTES, 'UTF-8'),
                        'nivel_actividad' => htmlspecialchars($bRow['nivel_actividad'] ?? 'moderado', ENT_QUOTES, 'UTF-8'),
                        'agua'            => (float)($bRow['agua'] ?? 3.0),
                        'edad'            => (int)($bRow['edad'] ?? 25),
                        'sexo'            => htmlspecialchars($bRow['sexo'] ?? 'masculino', ENT_QUOTES, 'UTF-8'),
                        'condiciones'     => htmlspecialchars($bRow['condiciones'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'alergias'        => htmlspecialchars($bRow['alergias'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'preferencia'     => htmlspecialchars($bRow['preferencia'] ?? 'omnivora', ENT_QUOTES, 'UTF-8')
                    ];
                }
                $bioStmt->close();
            }

            $stmt->close();

            apiSuccess([
                'token'      => $token,
                'user'       => [
                    'id'            => $userId,
                    'nombre'        => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'),
                    'email'         => $row['email'],
                    'imagen_user'   => $row['imagen_user'] ?? 'iconos/profile.svg',
                    'chat_activado' => (bool)$row['chat_activado']
                ],
                'biometrics' => $biometrics
            ], 'Inicio de sesión exitoso');
        }
    }

    $stmt->close();

    // Mensaje genérico seguro que no expone si el correo existe o no (prevención de enumeración de usuarios)
    apiError('Credenciales incorrectas o cuenta no registrada.', 401, [
        'code'             => 'AUTH_FAILED',
        'suggest_register' => true
    ]);
}

// ==============================================================================
// 3. ENDPOINT: REGISTER
// ==============================================================================
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitización de datos personales
    $nombre = sanitizeString($input['nombre'] ?? '');
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($input['password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? $password);

    if (empty($nombre) || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        apiError('El nombre debe contener entre 2 y 100 caracteres.', 422);
    }

    if (!$email || mb_strlen($email) > 120) {
        apiError('Por favor proporciona un correo electrónico válido.', 422);
    }

    if (strlen($password) < 6) {
        apiError('La contraseña debe tener al menos 6 caracteres.', 422);
    }

    if (strlen($password) > 128) {
        apiError('La contraseña supera la longitud máxima permitida.', 422);
    }

    if ($password !== $confirmPassword) {
        apiError('Las contraseñas no coinciden. Por favor verifícalas.', 422);
    }

    if (!$dbAvailable) {
        apiError('El servidor de base de datos no está disponible. Por favor intenta más tarde.', 503);
    }

    // 2. Validación de parámetros biométricos
    $edad = max(12, min(100, (int)($input['edad'] ?? 25)));
    $peso = max(30.0, min(300.0, (float)($input['peso'] ?? 70.0)));
    $altura = max(100.0, min(250.0, (float)($input['altura'] ?? 170.0)));
    
    $rawSexo = strtolower(trim((string)($input['sexo'] ?? 'masculino')));
    $sexo = in_array($rawSexo, ['masculino', 'femenino', 'otro']) ? $rawSexo : 'masculino';

    // Normalizar objetivo
    $rawObj = strtolower(trim((string)($input['objetivo'] ?? 'ganar_musculo')));
    if ($rawObj === 'bajar_peso') {
        $objetivo = 'perder_grasa';
    } elseif ($rawObj === 'mantener_peso') {
        $objetivo = 'mantener';
    } elseif (in_array($rawObj, ['ganar_musculo', 'perder_grasa', 'mantener', 'recomposicion'])) {
        $objetivo = $rawObj;
    } else {
        $objetivo = 'ganar_musculo';
    }

    // Normalizar nivel de actividad
    $rawAct = strtolower(trim((string)($input['nivel_actividad'] ?? 'moderado')));
    $nivelActividad = in_array($rawAct, ['sedentario', 'ligero', 'moderado', 'activo', 'muy_activo']) ? $rawAct : 'moderado';

    // Agua
    $rawAgua = isset($input['agua']) ? (float)$input['agua'] : 0;
    if ($rawAgua >= 1.0 && $rawAgua <= 15.0) {
        $aguaLitros = round($rawAgua, 1);
    } else {
        $aguaLitros = max(2.0, min(6.0, round($peso * 0.035, 1)));
    }

    $condiciones = sanitizeString(is_array($input['condiciones'] ?? null) ? implode(', ', $input['condiciones']) : ($input['condiciones'] ?? ''));
    $alergias = sanitizeString($input['alergias'] ?? '');
    $preferencia = sanitizeString($input['preferencia'] ?? 'cualquierCosa');

    // 3. Verificar si el correo ya existe
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$checkStmt) {
        $checkStmt = $conn->prepare("SELECT id FROM accounts WHERE email = ? LIMIT 1");
    }
    if (!$checkStmt) {
        apiServerError('Error al preparar verificación de correo duplicado: ' . $conn->error, 'Error de servidor al validar la cuenta.');
    }

    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        apiError('Este correo electrónico ya se encuentra registrado. Por favor inicia sesión.', 409, [
            'code'          => 'EMAIL_EXISTS',
            'suggest_login' => true
        ]);
    }
    $checkStmt->close();

    // 4. Hashear contraseña con BCRYPT (cost 12)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $defaultAvatar = 'iconos/profile.svg';
    $defaultChat = 1;

    // 5. Inserción en tabla users
    $insertStmt = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, ?, ?)");
    if (!$insertStmt) {
        apiServerError('Error al preparar inserción en tabla users: ' . $conn->error, 'Error interno del servidor al crear la cuenta.');
    }

    $insertStmt->bind_param("ssssi", $nombre, $email, $hashedPassword, $defaultAvatar, $defaultChat);

    if ($insertStmt->execute()) {
        $newUserId = (int)$insertStmt->insert_id;
        $insertStmt->close();

        // 6. Inserción o actualización de perfil biométrico en user_biometrics
        $checkBio = $conn->prepare("SELECT id FROM user_biometrics WHERE user_id = ? LIMIT 1");
        $bioExists = false;
        if ($checkBio) {
            $checkBio->bind_param("i", $newUserId);
            $checkBio->execute();
            $checkBio->store_result();
            $bioExists = ($checkBio->num_rows > 0);
            $checkBio->close();
        }

        if ($bioExists) {
            $bioStmt = $conn->prepare("UPDATE user_biometrics SET peso = ?, altura = ?, edad = ?, sexo = ?, objetivo = ?, nivel_actividad = ?, agua = ?, condiciones = ?, alergias = ?, preferencia = ?, modified_at = NOW() WHERE user_id = ?");
            if ($bioStmt) {
                $bioStmt->bind_param("ddisssdsssi", $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros, $condiciones, $alergias, $preferencia, $newUserId);
                $bioStmt->execute();
                $bioStmt->close();
            }
        } else {
            $bioStmt = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($bioStmt) {
                $bioStmt->bind_param("iddisssdsss", $newUserId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros, $condiciones, $alergias, $preferencia);
                $bioStmt->execute();
                $bioStmt->close();
            }
        }

        // Inserción de medición de línea base en user_biometrics_history (activa ventana de 24h)
        $histStmt = $conn->prepare("INSERT INTO user_biometrics_history (user_id, peso, altura, edad, sexo, notas, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'Registro inicial del atleta', NOW(), NOW())");
        if ($histStmt) {
            $histStmt->bind_param("iddis", $newUserId, $peso, $altura, $edad, $sexo);
            $histStmt->execute();
            $histStmt->close();
        }

        // 7. Cálculo y guardado de metas nutricionales
        $macroTargets = calculateNutritionTargets($peso, $altura, $edad, $sexo, $nivelActividad, $objetivo);
        if ($aguaLitros > 0) {
            $macroTargets['waterMl'] = (int)round($aguaLitros * 1000);
        }
        saveNutritionGoals($conn, $newUserId, $macroTargets);

        // Asegurar que exista rutina activa personalizada para el nuevo usuario
        ensureUserActiveRoutine($conn, $newUserId);

        // 8. Regenerar sesión e iniciar sesión automáticamente
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['imagen_user'] = $defaultAvatar;

        $token = generateAuthToken($newUserId, $email);

        apiSuccess([
            'token'      => $token,
            'user'       => [
                'id'          => $newUserId,
                'nombre'      => $nombre,
                'email'       => $email,
                'imagen_user' => $defaultAvatar
            ],
            'biometrics' => [
                'peso'            => $peso,
                'altura'          => $altura,
                'edad'            => $edad,
                'sexo'            => $sexo,
                'objetivo'        => $objetivo,
                'nivel_actividad' => $nivelActividad,
                'agua'            => $aguaLitros
            ],
            'goals'      => [
                'calories' => $targetCalories,
                'protein'  => $proteinG,
                'carbs'    => $carbsG,
                'fats'     => $fatsG,
                'water_ml' => $waterMl
            ]
        ], 'Cuenta registrada exitosamente', 201);
    } else {
        apiServerError('Error al insertar en users: ' . $insertStmt->error, 'No fue posible registrar la cuenta en este momento.');
    }
}

// ==============================================================================
// 4. ENDPOINT: LOGOUT
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

apiError('Acción o método HTTP no permitido', 405);
?>
