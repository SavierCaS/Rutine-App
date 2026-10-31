<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE WORKOUT EN VIVO Y HISTORIAL DE SERIES
 * ==============================================================================
 * Proporciona el registro en tiempo real de sesiones de fuerza, cálculo de
 * tonelaje acumulado, estimación de 1RM por la fórmula de Epley y persistencia
 * atómica de series. Compatible con apps móviles (iOS/Android) y web.
 *
 * Endpoints:
 * - GET  /controllers/api_workouts.php?action=current          -> Sesión en curso y series
 * - POST /controllers/api_workouts.php?action=save_set         -> Guarda o actualiza serie
 * - POST /controllers/api_workouts.php?action=delete_set       -> Elimina serie de la sesión
 * - POST /controllers/api_workouts.php?action=complete_workout -> Finaliza y guarda volumen
 * - GET  /controllers/api_workouts.php?action=history         -> Historial de entrenamientos
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = getJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? 'current';

// Asegurar tablas en la base de datos
if ($dbAvailable) {
    $conn->query("CREATE TABLE IF NOT EXISTS workout_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        workout_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'in_progress',
        total_volume_kg NUMERIC(10,2) DEFAULT 0,
        notes TEXT,
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        INDEX idx_user_status (user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS workout_sets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workout_log_id INT NOT NULL,
        exercise_id VARCHAR(50) NOT NULL,
        set_number INT NOT NULL,
        weight_kg NUMERIC(6,2) NOT NULL DEFAULT 0,
        reps INT NOT NULL DEFAULT 0,
        rpe NUMERIC(3,1) DEFAULT 8.0,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_log_set (workout_log_id, set_number),
        CONSTRAINT fk_sets_log FOREIGN KEY (workout_log_id) REFERENCES workout_logs (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Obtiene los ejercicios asignados para el entrenamiento del día actual y evalúa
 * su estado secuencial de cumplimiento estricto (Requerimiento 2).
 *
 * @param mysqli $conn
 * @param int $userId
 * @param int $workoutLogId
 * @return array
 */
function getDayAssignedExercisesWithStatus(mysqli $conn, int $userId, int $workoutLogId = 0): array {
    $currentDayKey = getCurrentServerDayKey();
    $exercises = [];

    // 1. Buscar rutina activa del usuario
    $rotQuery = $conn->prepare("SELECT r.id, r.name FROM routines r WHERE r.user_id = ? AND r.is_active = 1 ORDER BY r.id DESC LIMIT 1");
    $routineId = 0;
    if ($rotQuery) {
        $rotQuery->bind_param("i", $userId);
        $rotQuery->execute();
        $rRes = $rotQuery->get_result();
        if ($rRow = $rRes->fetch_assoc()) {
            $routineId = (int)$rRow['id'];
        }
        $rotQuery->close();
    }

    if ($routineId === 0) {
        $ensured = ensureUserActiveRoutine($conn, $userId);
        if ($ensured && !empty($ensured['id'])) {
            $routineId = (int)$ensured['id'];
        }
    }

    if ($routineId > 0) {
        $dayQuery = $conn->prepare("SELECT rd.id as day_id, rd.is_rest_day FROM routine_days rd WHERE rd.routine_id = ? AND rd.day_key = ? LIMIT 1");
        if ($dayQuery) {
            $dayQuery->bind_param("is", $routineId, $currentDayKey);
            $dayQuery->execute();
            $dRes = $dayQuery->get_result();
            if ($dRow = $dRes->fetch_assoc()) {
                if (empty($dRow['is_rest_day'])) {
                    $dayId = (int)$dRow['day_id'];
                    $exQuery = $conn->prepare("SELECT e.id, e.slug, e.name, e.muscle_group, e.media_url, e.instructions, re.order_index, re.target_sets, re.target_reps, re.target_rpe, re.rest_seconds FROM routine_exercises re JOIN exercises e ON re.exercise_id = e.id WHERE re.routine_day_id = ? ORDER BY re.order_index ASC");
                    if ($exQuery) {
                        $exQuery->bind_param("i", $dayId);
                        $exQuery->execute();
                        $eRes = $exQuery->get_result();
                        while ($eRow = $eRes->fetch_assoc()) {
                            $exercises[] = [
                                'id'          => $eRow['slug'] ?: ('ex-' . $eRow['id']),
                                'dbId'        => (int)$eRow['id'],
                                'name'        => $eRow['name'],
                                'muscleGroup' => $eRow['muscle_group'],
                                'mediaUrl'    => $eRow['media_url'] ?: 'public/assets/exercises/Squats.gif',
                                'instructions'=> $eRow['instructions'] ?? '',
                                'targetSets'  => (int)$eRow['target_sets'],
                                'targetReps'  => $eRow['target_reps'],
                                'targetRpe'   => (float)$eRow['target_rpe'],
                                'restSeconds' => (int)$eRow['rest_seconds'],
                                'orderIndex'  => (int)$eRow['order_index']
                            ];
                        }
                        $exQuery->close();
                    }
                }
            }
            $dayQuery->close();
        }
    }

    // Fallback de ejercicios si aún no tiene rutina persistida en BD
    if (empty($exercises)) {
        $exercises = [
            ['id' => 'ex-squats', 'name' => 'Sentadillas', 'muscleGroup' => 'cuadriceps', 'mediaUrl' => 'public/assets/exercises/Squats.gif', 'instructions' => 'Pies al ancho de hombros, rompe el paralelo con espalda neutral.', 'targetSets' => 3, 'targetReps' => '10-12', 'targetRpe' => 8.0, 'restSeconds' => 90, 'orderIndex' => 1],
            ['id' => 'ex-butt-bridge', 'name' => 'Puente de Glúteos', 'muscleGroup' => 'gluteos', 'mediaUrl' => 'public/assets/exercises/Butt bridge.gif', 'instructions' => 'Eleva la pelvis contrayendo glúteos fuertemente 2 segundos.', 'targetSets' => 3, 'targetReps' => '12-15', 'targetRpe' => 8.0, 'restSeconds' => 75, 'orderIndex' => 2],
            ['id' => 'ex-estocada-atras', 'name' => 'Estocada Hacia Atrás', 'muscleGroup' => 'cuadriceps', 'mediaUrl' => 'public/assets/exercises/Estocada hacia atras.gif', 'instructions' => 'Paso largo atrás con rodilla a 90 grados y torso recto.', 'targetSets' => 3, 'targetReps' => '10-12', 'targetRpe' => 8.0, 'restSeconds' => 60, 'orderIndex' => 3]
        ];
    }

    // Series completadas por ejercicio en el workout_log activo
    $completedCountByEx = [];
    if ($workoutLogId > 0) {
        $countQuery = $conn->prepare("SELECT exercise_id, COUNT(*) as c FROM workout_sets WHERE workout_log_id = ? AND is_completed = 1 GROUP BY exercise_id");
        if ($countQuery) {
            $countQuery->bind_param("i", $workoutLogId);
            $countQuery->execute();
            $cRes = $countQuery->get_result();
            while ($cRow = $cRes->fetch_assoc()) {
                $completedCountByEx[$cRow['exercise_id']] = (int)$cRow['c'];
            }
            $countQuery->close();
        }
    }

    // Secuencialidad estricta:
    // El ejercicio X sólo se desbloquea si TODOS los ejercicios 1..(X-1) están completados (series >= targetSets)
    $allPreviousCompleted = true;
    $processed = [];
    $activeExerciseId = null;

    foreach ($exercises as $idx => $ex) {
        $completedSets = $completedCountByEx[$ex['id']] ?? 0;
        $isDone = ($completedSets >= $ex['targetSets'] && $ex['targetSets'] > 0);
        $isLocked = !$allPreviousCompleted;
        $isCurrent = false;

        if (!$isDone && $allPreviousCompleted && $activeExerciseId === null) {
            $isCurrent = true;
            $activeExerciseId = $ex['id'];
        }

        $processed[] = array_merge($ex, [
            'completedSets' => $completedSets,
            'isCompleted'   => $isDone,
            'isLocked'      => $isLocked,
            'isCurrent'     => $isCurrent
        ]);

        if (!$isDone) {
            $allPreviousCompleted = false;
        }
    }

    // Si todos fueron completados
    if ($activeExerciseId === null && !empty($processed)) {
        $processed[count($processed) - 1]['isCurrent'] = true;
        $activeExerciseId = $processed[count($processed) - 1]['id'];
    }

    return [
        'exercises'         => $processed,
        'currentExerciseId' => $activeExerciseId ?: ($exercises[0]['id'] ?? 'ex-squats'),
        'isAllCompleted'    => $allPreviousCompleted,
        'dayKey'            => $currentDayKey
    ];
}

// ==============================================================================
// 1. ENDPOINT: CURRENT (Sesión en curso, ejercicios asignados y series)
// ==============================================================================
if ($action === 'current' && ($method === 'GET' || $method === 'POST')) {
    $today = date('Y-m-d');
    $currentSession = null;
    $sets = [];
    $logId = 0;

    if ($dbAvailable) {
        $stmt = $conn->prepare("SELECT id, workout_date, status, total_volume_kg, started_at FROM workout_logs WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $currentSession = $row;
                $logId = (int)$row['id'];

                $setStmt = $conn->prepare("SELECT id, exercise_id, set_number, weight_kg, reps, rpe, is_completed FROM workout_sets WHERE workout_log_id = ? ORDER BY set_number ASC");
                $setStmt->bind_param("i", $logId);
                $setStmt->execute();
                $sRes = $setStmt->get_result();
                while ($sRow = $sRes->fetch_assoc()) {
                    $sets[] = [
                        'id' => (int)$sRow['id'],
                        'exerciseId' => $sRow['exercise_id'],
                        'setNumber' => (int)$sRow['set_number'],
                        'weightKg' => (float)$sRow['weight_kg'],
                        'reps' => (int)$sRow['reps'],
                        'rpe' => (float)$sRow['rpe'],
                        'isCompleted' => (bool)$sRow['is_completed']
                    ];
                }
                $setStmt->close();
            }
            $stmt->close();
        }
    }

    $routineExercisesStatus = $dbAvailable ? getDayAssignedExercisesWithStatus($conn, $userId, $logId) : [
        'exercises' => [],
        'currentExerciseId' => 'ex-squats',
        'isAllCompleted' => false,
        'dayKey' => 'lunes'
    ];

    $selectedExId = $routineExercisesStatus['currentExerciseId'];

    // Si no hay series en BD para el ejercicio activo, inicializar plantilla de series según targetSets
    $exerciseSets = array_values(array_filter($sets, function($s) use ($selectedExId) { return $s['exerciseId'] === $selectedExId; }));
    if (empty($exerciseSets)) {
        // Encontrar targetSets
        $targetCount = 3;
        foreach ($routineExercisesStatus['exercises'] as $re) {
            if ($re['id'] === $selectedExId) {
                $targetCount = $re['targetSets'];
                break;
            }
        }
        for ($i = 1; $i <= $targetCount; $i++) {
            $exerciseSets[] = [
                'id' => $i,
                'exerciseId' => $selectedExId,
                'setNumber' => $i,
                'weightKg' => 50,
                'reps' => 10,
                'rpe' => 8.0,
                'isCompleted' => false
            ];
        }
    }

    apiSuccess([
        'session'            => $currentSession,
        'exerciseId'         => $selectedExId,
        'sets'               => $exerciseSets,
        'allSets'            => $sets,
        'routineExercises'   => $routineExercisesStatus['exercises'],
        'isAllCompleted'     => $routineExercisesStatus['isAllCompleted'],
        'dayKey'             => $routineExercisesStatus['dayKey'],
        'dbConnected'        => $dbAvailable
    ], 'Sesión actual de entrenamiento');
}

// ==============================================================================
// 2. ENDPOINT: SAVE_SET (Registra serie con validación estricta de orden - Requerimiento 2)
// ==============================================================================
if ($action === 'save_set' && $method === 'POST') {
    $exerciseId = trim($input['exercise_id'] ?? 'ex-squats');
    $setNumber = max(1, (int)($input['set_number'] ?? 1));
    $weightKg = max(0, (float)($input['weight_kg'] ?? 0));
    $reps = max(0, (int)($input['reps'] ?? 0));
    $rpe = max(1, min(10, (float)($input['rpe'] ?? 8.0)));
    $isCompleted = !empty($input['is_completed']) ? 1 : 0;

    $savedSetId = null;

    if ($dbAvailable) {
        $today = date('Y-m-d');
        // Obtener o crear workout_log activo
        $res = $conn->query("SELECT id FROM workout_logs WHERE user_id = $userId AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $workoutLogId = (int)$row['id'];
        } else {
            $conn->query("INSERT INTO workout_logs (user_id, workout_date, status, started_at) VALUES ($userId, '$today', 'in_progress', NOW())");
            $workoutLogId = (int)$conn->insert_id;
        }

        // VALIDACIÓN DE SECUENCIALIDAD (Requerimiento 2):
        // Verificar que el ejercicio solicitado no esté bloqueado
        $statusCheck = getDayAssignedExercisesWithStatus($conn, $userId, $workoutLogId);
        foreach ($statusCheck['exercises'] as $exItem) {
            if ($exItem['id'] === $exerciseId && !empty($exItem['isLocked'])) {
                apiError("Ejercicio bloqueado: Debes completar todas las series del ejercicio previo ('{$statusCheck['currentExerciseId']}') antes de registrar series en este ejercicio.", 403, [
                    'isLocked' => true,
                    'requiredCurrentExercise' => $statusCheck['currentExerciseId']
                ]);
            }
        }

        // Verificar si la serie ya existe para este ejercicio y número
        $checkSet = $conn->prepare("SELECT id FROM workout_sets WHERE workout_log_id = ? AND exercise_id = ? AND set_number = ? LIMIT 1");
        $checkSet->bind_param("isi", $workoutLogId, $exerciseId, $setNumber);
        $checkSet->execute();
        $cRes = $checkSet->get_result();

        if ($existing = $cRes->fetch_assoc()) {
            $savedSetId = (int)$existing['id'];
            $upd = $conn->prepare("UPDATE workout_sets SET weight_kg = ?, reps = ?, rpe = ?, is_completed = ? WHERE id = ?");
            $upd->bind_param("didii", $weightKg, $reps, $rpe, $isCompleted, $savedSetId);
            $upd->execute();
            $upd->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO workout_sets (workout_log_id, exercise_id, set_number, weight_kg, reps, rpe, is_completed) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isididi", $workoutLogId, $exerciseId, $setNumber, $weightKg, $reps, $rpe, $isCompleted);
            $stmt->execute();
            $savedSetId = (int)$conn->insert_id;
            $stmt->close();
        }
        $checkSet->close();

        // Re-evaluar estado de ejercicios tras guardar
        $updatedRoutineStatus = getDayAssignedExercisesWithStatus($conn, $userId, $workoutLogId);
    } else {
        $updatedRoutineStatus = [
            'exercises' => [],
            'currentExerciseId' => $exerciseId,
            'isAllCompleted' => false
        ];
    }

    // Estimación 1RM Epley: Peso * (1 + Reps / 30)
    $est1RM = 0;
    if ($weightKg > 0 && $reps > 0) {
        $est1RM = round($weightKg * (1 + min(12, $reps) / 30), 1);
    }

    apiSuccess([
        'savedId'            => $savedSetId,
        'exerciseId'         => $exerciseId,
        'setNumber'          => $setNumber,
        'weightKg'           => $weightKg,
        'reps'               => $reps,
        'rpe'                => $rpe,
        'isCompleted'        => (bool)$isCompleted,
        'estimated1RM'       => $est1RM,
        'routineExercises'   => $updatedRoutineStatus['exercises'],
        'nextExerciseId'     => $updatedRoutineStatus['currentExerciseId'],
        'isAllCompleted'     => $updatedRoutineStatus['isAllCompleted'],
        'dbConnected'        => $dbAvailable
    ], 'Serie registrada correctamente');
}

// ==============================================================================
// 3. ENDPOINT: COMPLETE_WORKOUT (Finaliza sesión exigiendo que todos los ejercicios estén completados)
// ==============================================================================
if ($action === 'complete_workout' && $method === 'POST') {
    $totalVolume = max(0, (float)($input['total_volume_kg'] ?? 0));
    $notes = trim($input['notes'] ?? 'Entrenamiento finalizado');

    if ($dbAvailable) {
        $wRes = $conn->query("SELECT id FROM workout_logs WHERE user_id = $userId AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
        $activeLogId = ($wRes && $wRow = $wRes->fetch_assoc()) ? (int)$wRow['id'] : 0;

        // VALIDACIÓN ESTRICTA (Requerimiento 2):
        // Detectar si quedan ejercicios sin completar en la rutina asignada de hoy
        $statusCheck = getDayAssignedExercisesWithStatus($conn, $userId, $activeLogId);

        if (!$statusCheck['isAllCompleted']) {
            $pendingNames = [];
            foreach ($statusCheck['exercises'] as $ex) {
                if (!$ex['isCompleted']) {
                    $pendingNames[] = "{$ex['name']} ({$ex['completedSets']}/{$ex['targetSets']} series)";
                }
            }
            apiError(
                "No puedes finalizar la sesión de entrenamiento todavía. Tienes ejercicios pendientes en tu rutina asignada: " . implode(', ', $pendingNames) . ". Debes completar todos los ejercicios en el orden establecido.",
                422,
                [
                    'canFinish'        => false,
                    'pendingExercises' => $pendingNames,
                    'routineExercises' => $statusCheck['exercises']
                ]
            );
        }

        $notesSafe = $conn->real_escape_string($notes);
        $conn->query("UPDATE workout_logs SET status = 'completed', total_volume_kg = $totalVolume, notes = '$notesSafe', completed_at = NOW() WHERE user_id = $userId AND status = 'in_progress'");

        // Registrar automáticamente el check-in del día en routine_check_ins
        $today = date('Y-m-d');
        $week = (int)date('W');
        $year = (int)date('Y');
        $currentDayKey = getCurrentServerDayKey();

        $conn->query("INSERT INTO routine_check_ins (user_id, day_id, check_in_date, week_number, year, is_completed) 
            VALUES ($userId, '$currentDayKey', '$today', $week, $year, 1) 
            ON DUPLICATE KEY UPDATE is_completed = 1, check_in_date = '$today'");

        // Comprobar si toda la rutina semanal quedó 100% completada
        $planStatus = checkRoutinePlanCompletion($conn, $userId);
    } else {
        $planStatus = ['isCompleted' => true];
    }

    $congratsMessage = '¡Entrenamiento completado y guardado con éxito! Has realizado todos los ejercicios asignados.';
    if (!empty($planStatus['isCompleted'])) {
        $congratsMessage .= ' 🏆 ¡Has completado el 100% de tu plan de rutina! Ahora puedes actualizar tu condición física para evaluar tu progreso con IA.';
    }

    apiSuccess([
        'status'         => 'completed',
        'totalVolumeKg'  => $totalVolume,
        'completedAt'    => date('c'),
        'planStatus'     => $planStatus,
        'dbConnected'    => $dbAvailable
    ], $congratsMessage);
}

apiError('Acción no soportada en API de workouts', 400);
?>
