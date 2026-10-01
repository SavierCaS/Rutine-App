<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE RUTINAS Y CHECK-INS SEMANALES
 * ==============================================================================
 * Gestiona el seguimiento de adherencia y tachado de días de entrenamiento.
 * Totalmente compatible con la estructura de contratos `src/core/types/fitness.ts`
 * tanto para la plataforma Web como para la App Móvil.
 *
 * Endpoints:
 * - GET  /controllers/api_routines.php           -> Obtiene rutina activa y check-ins de la semana
 * - POST /controllers/api_routines.php           -> Registra o alterna check-in de un día
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Asegurar tabla de check-ins en la base de datos
if ($dbAvailable) {
    $tableSql = "CREATE TABLE IF NOT EXISTS routine_check_ins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        day_id VARCHAR(50) NOT NULL,
        check_in_date DATE NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_day_date (user_id, day_id, check_in_date),
        INDEX idx_user_week_year (user_id, week_number, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($tableSql);
}

// ==============================================================================
// 1. GET: Retorna estado de la semana y días completados
// ==============================================================================
if ($method === 'GET') {
    $now = new DateTime();
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");
    $todayDay = strtolower($now->format("l")); // monday, tuesday...

    $completedDays = [];

    if ($dbAvailable) {
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
    } else {
        // Fallback en memoria si la BD está offline temporalmente
        $completedDays = ['lunes'];
    }

    // Plan estándar de 4 días de entrenamiento activo (Lunes, Martes, Jueves, Viernes)
    $totalPlannedDays = 4;
    $compliancePct = min(100, round((count($completedDays) / $totalPlannedDays) * 100));

    apiSuccess([
        'week' => $week,
        'year' => $year,
        'currentDay' => $todayDay,
        'completedDays' => array_values(array_unique($completedDays)),
        'totalPlannedDays' => $totalPlannedDays,
        'compliancePercentage' => $compliancePct,
        'routine' => [
            'id' => 'rot-1',
            'name' => 'Kynap Fuerza & Acondicionamiento (4 Días)',
            'days' => [
                ['id' => 'lunes', 'dayName' => 'Lunes', 'focus' => 'Pierna & Glúteos', 'isRest' => false, 'exercises' => ['Sentadillas (4x12)', 'Puente de Glúteos (3x15)', 'Estocada Hacia Atrás (3x10)']],
                ['id' => 'martes', 'dayName' => 'Martes', 'focus' => 'Core & Cardio', 'isRest' => false, 'exercises' => ['Burpees (4x12)', 'Crunch de Bicicleta (3x20)', 'Plancha (3x45s)']],
                ['id' => 'miercoles', 'dayName' => 'Miércoles', 'focus' => 'Descanso Activo', 'isRest' => true, 'exercises' => []],
                ['id' => 'jueves', 'dayName' => 'Jueves', 'focus' => 'Torso & Brazos', 'isRest' => false, 'exercises' => ['Flexiones Inclinadas (4x12)', 'Fondos en Silla (3x12)', 'Twist Ruso (3x20)']],
                ['id' => 'viernes', 'dayName' => 'Viernes', 'focus' => 'Full Body Funcional', 'isRest' => false, 'exercises' => ['Step en Silla (3x10)', 'Sentadillas (3x15)', 'Elevación de Rodillas (3x30s)']],
                ['id' => 'sabado', 'dayName' => 'Sábado', 'focus' => 'Recuperación', 'isRest' => true, 'exercises' => []],
                ['id' => 'domingo', 'dayName' => 'Domingo', 'focus' => 'Recuperación', 'isRest' => true, 'exercises' => []]
            ]
        ],
        'dbConnected' => $dbAvailable
    ], 'Rutina y check-ins obtenidos correctamente');
}

// ==============================================================================
// 2. POST: Alterna o registra el check-in (Tachado) del día
// ==============================================================================
if ($method === 'POST') {
    $input = getJsonInput();
    $dayId = strtolower(trim($input['day_id'] ?? ''));

    if (empty($dayId)) {
        apiError('El campo day_id es obligatorio (ej: lunes, martes).', 422);
    }

    $now = new DateTime();
    $today = $now->format("Y-m-d");
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");

    $isCompleted = true;

    if ($dbAvailable) {
        // Verificar si ya existe para alternar su estado
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

        // Obtener lista actualizada de días completados en la semana
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

    apiSuccess([
        'day_id' => $dayId,
        'isCompleted' => $isCompleted,
        'completedDays' => $updatedDays,
        'compliancePercentage' => $compliancePct,
        'date' => $today,
        'dbConnected' => $dbAvailable
    ], 'Check-in actualizado correctamente');
}

apiError('Método HTTP no soportado', 405);
