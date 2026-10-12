<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE RUTINAS & GENERADOR IA CON GROQ (LLAMA 3.3)
 * ==============================================================================
 * Gestiona la persistencia de rutinas, check-ins de cumplimiento y la generación
 * de rutinas de alta precisión con IA respetando la separación estricta de capas:
 *
 * ARQUITECTURA DE SEGURIDAD (FASE 2):
 * 1. La IA (Groq / Llama) NUNCA interactúa con la base de datos directamente.
 * 2. El backend autentica la sesión, recupera los datos biométricos de MySQL y arma el contexto.
 * 3. El backend invoca el servicio Groq (groq_routine_service.py) mediante stdin aislado.
 * 4. El backend valida y sanitiza exhaustivamente la estructura y campos de la respuesta.
 * 5. El backend persiste la rutina sanitizada en la base de datos y la envía al cliente.
 *
 * Endpoints:
 * - GET  /controllers/api_routines.php                      -> Rutina activa y check-ins
 * - POST /controllers/api_routines.php (day_id)             -> Tachar / alternar check-in
 * - POST /controllers/api_routines.php?action=generate_ai   -> Generar nueva rutina con Llama 3.3
 *
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Verificar si se solicita generación con IA
$input = getJsonInput();
$action = sanitizeString($_GET['action'] ?? $input['action'] ?? '');

// ==============================================================================
// 1. GENERACIÓN SEGURA DE RUTINA CON GROQ (LLAMA 3.3) - AISLADA DE BD
// ==============================================================================
if ($action === 'generate_ai' && $method === 'POST') {
    // 1. Autenticación estricta
    if ($user['isGuest'] || $userId <= 0) {
        apiError('Debes iniciar sesión con una cuenta válida para generar una rutina con IA.', 401);
    }

    if (!$dbAvailable) {
        apiError('El servicio de base de datos no está disponible. Intenta más tarde.', 503);
    }

    // 2. Recuperar biometría del atleta desde la base de datos (Capa de Datos Segura)
    $bioData = [
        'nombre'          => $user['nombre'] ?: 'Atleta Kynap',
        'edad'            => 25,
        'peso'            => 75.0,
        'altura'          => 175.0,
        'sexo'            => 'masculino',
        'objetivo'        => 'ganar_musculo',
        'nivel_actividad' => 'moderado',
        'agua'            => 3.0,
        'condiciones'     => 'ninguna',
        'alergias'        => 'ninguna',
        'preferencia'     => 'gimnasio_completo'
    ];

    $bioStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? LIMIT 1");
    if ($bioStmt) {
        $bioStmt->bind_param("i", $userId);
        $bioStmt->execute();
        $bRes = $bioStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            $bioData['edad']            = (int)($bRow['edad'] ?: 25);
            $bioData['peso']            = (float)($bRow['peso'] ?: 75.0);
            $bioData['altura']          = (float)($bRow['altura'] ?: 175.0);
            $bioData['sexo']            = $bRow['sexo'] ?: 'masculino';
            $bioData['objetivo']        = $bRow['objetivo'] ?: 'ganar_musculo';
            $bioData['nivel_actividad'] = $bRow['nivel_actividad'] ?: 'moderado';
            $bioData['agua']            = (float)($bRow['agua'] ?: 3.0);
            $bioData['condiciones']     = $bRow['condiciones'] ?: 'ninguna';
            $bioData['alergias']        = $bRow['alergias'] ?: 'ninguna';
            $bioData['preferencia']     = $bRow['preferencia'] ?: 'gimnasio_completo';
        }
        $bioStmt->close();
    }

    // Parámetros opcionales enviados por el cliente
    $diasDeseados = max(3, min(6, (int)($input['dias_semana'] ?? 4)));
    $equipamiento = sanitizeString($input['equipamiento'] ?? $bioData['preferencia']);
    $objetivoFoco = sanitizeString($input['objetivo'] ?? $bioData['objetivo']);

    $bioData['dias_semana']  = $diasDeseados;
    $bioData['equipamiento'] = $equipamiento;
    $bioData['objetivo']     = $objetivoFoco;

    // 3. Invocar servicio seguro de Groq (Python) vía subproceso sin acceso a BD
    $pythonScript = dirname(__DIR__) . '/services/groq_routine_service.py';
    $payloadJson = json_encode($bioData, JSON_UNESCAPED_UNICODE);

    $pythonBin = kynap_env('PYTHON_BINARY');
    if (empty($pythonBin) || (!file_exists($pythonBin) && $pythonBin !== 'python')) {
        $candidates = [
            'C:\\Users\\pc\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
            'python',
            'py'
        ];
        foreach ($candidates as $cand) {
            if (file_exists($cand) || $cand === 'python') {
                $pythonBin = $cand;
                break;
            }
        }
    }
    if (empty($pythonBin)) $pythonBin = 'python';

    $descriptorSpec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];

    $cmd = '"' . $pythonBin . '" ' . escapeshellarg($pythonScript);
    $process = @proc_open($cmd, $descriptorSpec, $pipes, dirname(__DIR__));
    $aiRawOutput = "";
    $aiErrorOutput = "";

    if (is_resource($process)) {
        fwrite($pipes[0], $payloadJson);
        fclose($pipes[0]);

        $aiRawOutput = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $aiErrorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);
    } else {
        error_log("[GROQ ROUTINE SERVICE ERROR]: No se pudo iniciar el proceso de Python.");
    }

    $routineData = json_decode($aiRawOutput, true);

    // Fallback de resiliencia si el subproceso falla completamente
    if (!is_array($routineData) || !isset($routineData['days'])) {
        error_log("[GROQ SERVICE ERROR]: Salida inválida de Groq: " . $aiErrorOutput);
        apiError('No se pudo generar la rutina con la IA en este momento. Intenta de nuevo más tarde.', 500);
    }

    // 4. Validación y sanitización estricta de la estructura recibida de la IA
    $sanitizedRoutineName = sanitizeString($routineData['name'] ?? 'Rutina Kynap AI');
    $sanitizedDesc = sanitizeString($routineData['description'] ?? 'Planificación científica personalizada.');
    $rawDays = is_array($routineData['days'] ?? null) ? $routineData['days'] : [];

    if (empty($rawDays)) {
        apiError('La estructura de la rutina devuelta por la IA no es válida.', 500);
    }

    // 5. Persistencia segura en base de datos bajo transacción
    $conn->begin_transaction();
    try {
        // Desactivar rutinas previas del usuario
        $deactStmt = $conn->prepare("UPDATE routines SET is_active = 0 WHERE user_id = ?");
        $deactStmt->bind_param("i", $userId);
        $deactStmt->execute();
        $deactStmt->close();

        // Insertar cabecera de rutina
        $insRot = $conn->prepare("INSERT INTO routines (user_id, name, description, is_active) VALUES (?, ?, ?, 1)");
        $insRot->bind_param("iss", $userId, $sanitizedRoutineName, $sanitizedDesc);
        $insRot->execute();
        $newRoutineId = (int)$insRot->insert_id;
        $insRot->close();

        // Mapeo ordenado de días
        $dayOrderMap = [
            'lunes'     => 1,
            'martes'    => 2,
            'miercoles' => 3,
            'jueves'    => 4,
            'viernes'   => 5,
            'sabado'    => 6,
            'domingo'   => 7
        ];

        $insDay = $conn->prepare("INSERT INTO routine_days (routine_id, day_name, focus, day_key, day_order, is_rest_day) VALUES (?, ?, ?, ?, ?, ?)");
        $insEx = $conn->prepare("INSERT INTO routine_exercises (routine_day_id, exercise_id, order_index, target_sets, target_reps, target_rpe, rest_seconds) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $formattedDays = [];

        foreach ($rawDays as $idx => $dayItem) {
            $dayKey = strtolower(trim((string)($dayItem['id'] ?? '')));
            if (!isset($dayOrderMap[$dayKey])) continue;

            $dayName = sanitizeString($dayItem['dayName'] ?? ucfirst($dayKey));
            $focus = sanitizeString($dayItem['focus'] ?? ($dayItem['isRest'] ? 'Descanso' : 'Entrenamiento'));
            $isRest = !empty($dayItem['isRest']) ? 1 : 0;
            $orderIndex = $dayOrderMap[$dayKey];

            $insDay->bind_param("isssii", $newRoutineId, $dayName, $focus, $dayKey, $orderIndex, $isRest);
            $insDay->execute();
            $newDayId = (int)$insDay->insert_id;

            $exerciseStrings = [];

            if (!$isRest && !empty($dayItem['exercises']) && is_array($dayItem['exercises'])) {
                $exOrder = 1;
                foreach ($dayItem['exercises'] as $exItem) {
                    $exName = sanitizeString($exItem['name'] ?? 'Ejercicio');
                    $exMuscle = sanitizeString($exItem['muscle'] ?? 'full_body');
                    $exSets = max(1, min(10, (int)($exItem['sets'] ?? 3)));
                    $exReps = sanitizeString($exItem['reps'] ?? '10-12');
                    $exRpe = max(1.0, min(10.0, (float)($exItem['rpe'] ?? 8.0)));
                    $exRest = max(15, min(300, (int)($exItem['rest_seconds'] ?? 90)));

                    // Buscar o crear ejercicio en catálogo maestro
                    $exSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $exName), '-'));
                    if (empty($exSlug)) $exSlug = 'ejercicio-' . time() . '-' . rand(100, 999);

                    $findEx = $conn->prepare("SELECT id FROM exercises WHERE slug = ? OR name = ? LIMIT 1");
                    $findEx->bind_param("ss", $exSlug, $exName);
                    $findEx->execute();
                    $fRes = $findEx->get_result();
                    $dbExId = 0;
                    if ($fRow = $fRes->fetch_assoc()) {
                        $dbExId = (int)$fRow['id'];
                    }
                    $findEx->close();

                    if ($dbExId === 0) {
                        $addEx = $conn->prepare("INSERT INTO exercises (name, slug, muscle_group, equipment, is_custom, created_by_user_id) VALUES (?, ?, ?, 'corporal', 1, ?)");
                        $addEx->bind_param("sssi", $exName, $exSlug, $exMuscle, $userId);
                        $addEx->execute();
                        $dbExId = (int)$addEx->insert_id;
                        $addEx->close();
                    }

                    // Insertar en routine_exercises
                    if ($dbExId > 0) {
                        $insEx->bind_param("iiiisdi", $newDayId, $dbExId, $exOrder, $exSets, $exReps, $exRpe, $exRest);
                        $insEx->execute();
                    }

                    $exerciseStrings[] = "{$exName} ({$exSets}x{$exReps})";
                    $exOrder++;
                }
            }

            $formattedDays[] = [
                'id'        => $dayKey,
                'dayName'   => $dayName,
                'focus'     => $focus,
                'isRest'    => (bool)$isRest,
                'exercises' => $exerciseStrings
            ];
        }

        $insDay->close();
        $insEx->close();
        $conn->commit();

        apiSuccess([
            'routine' => [
                'id'          => 'rot-' . $newRoutineId,
                'name'        => $sanitizedRoutineName,
                'description' => $sanitizedDesc,
                'engine'      => $routineData['ai_engine'] ?? 'Groq Llama 3.3',
                'days'        => $formattedDays
            ]
        ], 'Rutina generada y guardada exitosamente con Kynap AI (Llama 3.3)', 201);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("[ROUTINE DB SAVE ERROR]: " . $e->getMessage());
        apiError('No se pudo guardar la rutina en la base de datos debido a un error interno.', 500);
    }
}

// ==============================================================================
// 2. GET: RETORNA RUTINA ACTIVA DEL ATLETA Y ESTADO DE LA SEMANA
// ==============================================================================
if ($method === 'GET') {
    $now = new DateTime();
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");
    $todayDay = strtolower($now->format("l"));

    $completedDays = [];

    if ($dbAvailable && $userId > 0) {
        $stmt = $conn->prepare("SELECT day_id FROM routine_check_ins WHERE user_id = ? AND week_number = ? AND year = ? AND is_completed = 1");
        if ($stmt) {
            $stmt->bind_param("iii", $userId, $week, $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $completedDays[] = $row['day_id'];
            }
            $stmt->close();
        }
    }

    // Buscar si el usuario tiene una rutina activa guardada en base de datos
    $activeRoutine = null;
    if ($dbAvailable && $userId > 0) {
        $rStmt = $conn->prepare("SELECT id, name, description FROM routines WHERE user_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
        if ($rStmt) {
            $rStmt->bind_param("i", $userId);
            $rStmt->execute();
            $rRes = $rStmt->get_result();
            if ($rRow = $rRes->fetch_assoc()) {
                $rId = (int)$rRow['id'];
                $daysQuery = $conn->prepare("SELECT id, day_name, focus, day_key, is_rest_day FROM routine_days WHERE routine_id = ? ORDER BY day_order ASC");
                if ($daysQuery) {
                    $daysQuery->bind_param("i", $rId);
                    $daysQuery->execute();
                    $dRes = $daysQuery->get_result();
                    $daysList = [];

                    while ($dRow = $dRes->fetch_assoc()) {
                        $dayPk = (int)$dRow['id'];
                        $isRest = (bool)$dRow['is_rest_day'];
                        $focusStr = !empty($dRow['focus']) ? $dRow['focus'] : ($isRest ? 'Descanso Activo' : 'Entrenamiento Programado');
                        $exList = [];

                        if (!$isRest) {
                            $exQuery = $conn->prepare("SELECT e.name, re.target_sets, re.target_reps FROM routine_exercises re JOIN exercises e ON re.exercise_id = e.id WHERE re.routine_day_id = ? ORDER BY re.order_index ASC");
                            if ($exQuery) {
                                $exQuery->bind_param("i", $dayPk);
                                $exQuery->execute();
                                $eRes = $exQuery->get_result();
                                while ($eRow = $eRes->fetch_assoc()) {
                                    $exList[] = "{$eRow['name']} ({$eRow['target_sets']}x{$eRow['target_reps']})";
                                }
                                $exQuery->close();
                            }
                        }

                        $daysList[] = [
                            'id'        => $dRow['day_key'],
                            'dayName'   => $dRow['day_name'],
                            'focus'     => $focusStr,
                            'isRest'    => $isRest,
                            'exercises' => $exList
                        ];
                    }
                    $daysQuery->close();

                    if (!empty($daysList)) {
                        $activeRoutine = [
                            'id'          => 'rot-' . $rId,
                            'name'        => htmlspecialchars($rRow['name'], ENT_QUOTES, 'UTF-8'),
                            'description' => htmlspecialchars($rRow['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                            'days'        => $daysList
                        ];
                    }
                }
            }
            $rStmt->close();
        }
    }

    // Plan por defecto verificado si aún no hay rutina guardada
    if (!$activeRoutine) {
        $activeRoutine = [
            'id'   => 'rot-default',
            'name' => 'Kynap Fuerza & Acondicionamiento (4 Días)',
            'days' => [
                ['id' => 'lunes', 'dayName' => 'Lunes', 'focus' => 'Pierna & Glúteos', 'isRest' => false, 'exercises' => ['Sentadillas (4x12)', 'Puente de Glúteos (3x15)', 'Estocada Hacia Atrás (3x10)']],
                ['id' => 'martes', 'dayName' => 'Martes', 'focus' => 'Core & Cardio', 'isRest' => false, 'exercises' => ['Burpees (4x12)', 'Crunch de Bicicleta (3x20)', 'Plancha Tradicional (3x45s)']],
                ['id' => 'miercoles', 'dayName' => 'Miércoles', 'focus' => 'Descanso Activo', 'isRest' => true, 'exercises' => []],
                ['id' => 'jueves', 'dayName' => 'Jueves', 'focus' => 'Torso & Brazos', 'isRest' => false, 'exercises' => ['Flexiones con Inclinación (4x12)', 'Fondos de Tríceps en Silla (3x12)', 'Twist Ruso (3x20)']],
                ['id' => 'viernes', 'dayName' => 'Viernes', 'focus' => 'Full Body Funcional', 'isRest' => false, 'exercises' => ['Step en Silla (3x10)', 'Sentadillas (3x15)', 'Elevación de Rodillas (3x30s)']],
                ['id' => 'sabado', 'dayName' => 'Sábado', 'focus' => 'Recuperación', 'isRest' => true, 'exercises' => []],
                ['id' => 'domingo', 'dayName' => 'Domingo', 'focus' => 'Recuperación', 'isRest' => true, 'exercises' => []]
            ]
        ];
    }

    $plannedDaysCount = count(array_filter($activeRoutine['days'], fn($d) => empty($d['isRest'])));
    $compliancePct = $plannedDaysCount > 0 ? min(100, round((count($completedDays) / $plannedDaysCount) * 100)) : 0;

    $currentServerDayKey = getCurrentServerDayKey();
    $currentServerDayName = getSpanishDayName($currentServerDayKey);
    $streakData = calculateUserStreak($conn, $userId);

    apiSuccess([
        'week'                 => $week,
        'year'                 => $year,
        'currentDay'           => $todayDay,
        'currentDayKey'        => $currentServerDayKey,
        'currentDayName'       => $currentServerDayName,
        'serverDate'           => $now->format('Y-m-d'),
        'serverTime'           => $now->format('H:i:s'),
        'completedDays'        => array_values(array_unique($completedDays)),
        'totalPlannedDays'     => $plannedDaysCount,
        'compliancePercentage' => $compliancePct,
        'routine'              => $activeRoutine,
        'streak'               => $streakData,
        'dbConnected'          => $dbAvailable
    ], 'Rutina y check-ins obtenidos correctamente');
}

// ==============================================================================
// 3. POST: REGISTRO O ALTERNANCIA DE CHECK-IN CON VALIDACIÓN ESTRICTA DE DÍA
// ==============================================================================
if ($method === 'POST') {
    $dayId = strtolower(trim($input['day_id'] ?? ''));

    if (empty($dayId)) {
        apiError('El campo day_id es obligatorio (ej: lunes, martes).', 422);
    }

    $now = new DateTime();
    $today = $now->format("Y-m-d");
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");
    $currentServerDayKey = getCurrentServerDayKey();
    $currentServerDayName = getSpanishDayName($currentServerDayKey);
    $attemptedDayName = getSpanishDayName($dayId);

    // ==========================================================================
    // RESTRICCIÓN DE NEGOCIO CRÍTICA (REQUERIMIENTO 2):
    // El atleta solo puede marcar o tachar el día correspondiente al día actual.
    // Los demás días permanecen estrictamente bloqueados en el backend.
    // ==========================================================================
    if ($dayId !== $currentServerDayKey) {
        apiError(
            "Acción no permitida: Hoy es {$currentServerDayName}. Por integridad de tu plan de entrenamiento, solo puedes registrar o tachar el entrenamiento del día presente. El día {$attemptedDayName} permanece en modo solo lectura.",
            403,
            [
                'isBlocked'            => true,
                'currentServerDayKey'  => $currentServerDayKey,
                'currentServerDayName' => $currentServerDayName,
                'attemptedDay'         => $dayId,
                'attemptedDayName'     => $attemptedDayName,
                'serverDate'           => $today
            ]
        );
    }

    $isCompleted = true;

    if ($dbAvailable && $userId > 0) {
        $checkStmt = $conn->prepare("SELECT id, is_completed FROM routine_check_ins WHERE user_id = ? AND day_id = ? AND week_number = ? AND year = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("isii", $userId, $dayId, $week, $year);
            $checkStmt->execute();
            $res = $checkStmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $newStatus = $row['is_completed'] ? 0 : 1;
                $isCompleted = ($newStatus === 1);
                $updateStmt = $conn->prepare("UPDATE routine_check_ins SET is_completed = ?, check_in_date = ? WHERE id = ?");
                $updateStmt->bind_param("isi", $newStatus, $today, $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                $insertStmt = $conn->prepare("INSERT INTO routine_check_ins (user_id, day_id, check_in_date, week_number, year, is_completed) VALUES (?, ?, ?, ?, ?, 1)");
                $insertStmt->bind_param("issii", $userId, $dayId, $today, $week, $year);
                $insertStmt->execute();
                $insertStmt->close();
                $isCompleted = true;
            }
            $checkStmt->close();
        }

        $allStmt = $conn->prepare("SELECT day_id FROM routine_check_ins WHERE user_id = ? AND week_number = ? AND year = ? AND is_completed = 1");
        $allStmt->bind_param("iii", $userId, $week, $year);
        $allStmt->execute();
        $updatedDays = [];
        $uRes = $allStmt->get_result();
        while ($uRow = $uRes->fetch_assoc()) {
            $updatedDays[] = $uRow['day_id'];
        }
        $allStmt->close();
    } else {
        $updatedDays = [$dayId];
    }

    $compliancePct = min(100, round((count($updatedDays) / 4) * 100));
    $streakData = calculateUserStreak($conn, $userId);

    apiSuccess([
        'day_id'               => $dayId,
        'dayName'              => $attemptedDayName,
        'isCompleted'          => $isCompleted,
        'completedDays'        => $updatedDays,
        'compliancePercentage' => $compliancePct,
        'date'                 => $today,
        'currentServerDayKey'  => $currentServerDayKey,
        'streak'               => $streakData,
        'dbConnected'          => $dbAvailable
    ], 'Check-in de entrenamiento actualizado correctamente');
}

apiError('Método HTTP no soportado', 405);
?>
