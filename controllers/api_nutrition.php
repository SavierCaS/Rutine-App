<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE NUTRICIÓN, HORARIOS DE COMIDAS & MACROS
 * ==============================================================================
 * Gestiona el plan alimenticio estructurado en bloques de horarios
 * (Desayuno, Media Mañana, Almuerzo, Merienda, Cena) con reinicio automático
 * a medianoche, filtro por día de la semana basado en reloj del servidor,
 * seguimiento de hidratación y recálculo de rachas activas.
 *
 * Endpoints:
 * - GET  /controllers/api_nutrition.php (?day=lunes)
 *        -> Plan del día, estado de comidas completadas hoy y macros
 * - POST /controllers/api_nutrition.php?action=toggle_scheduled_meal
 *        -> Alterna el check-in diario de un bloque de comida (Desayuno, etc.)
 * - POST /controllers/api_nutrition.php?action=adjust_water
 *        -> Ajuste rápido de agua (+/- ml)
 * - POST /controllers/api_nutrition.php?action=add_meal
 *        -> Registro libre de alimento
 * - POST /controllers/api_nutrition.php?action=delete_meal
 *        -> Elimina un alimento registrado libre
 *
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$today = date('Y-m-d');
$currentServerDayKey = getCurrentServerDayKey();
$currentServerDayName = getSpanishDayName($currentServerDayKey);

// ==============================================================================
// 1. GET: RETORNA PLAN ALIMENTICIO POR DÍA, HORARIOS, COMPLETADOS Y MACROS
// ==============================================================================
if ($method === 'GET') {
    $requestedDay = strtolower(trim($_GET['day'] ?? ''));
    $validDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    
    // Si no se especifica o es inválido, utilizar por defecto el día actual del servidor
    $selectedDayKey = in_array($requestedDay, $validDays) ? $requestedDay : $currentServerDayKey;
    $selectedDayName = getSpanishDayName($selectedDayKey);
    $isToday = ($selectedDayKey === $currentServerDayKey);

    $waterMl = 1750;
    $waterTargetMl = 2500;
    $caloriesTarget = 2200;
    $scheduledMeals = [];
    $completedMealKeys = [];
    $freeMeals = [];

    if ($dbAvailable && $userId > 0) {
        // 1. Meta de hidratación
        $bioStmt = $conn->prepare("SELECT agua FROM user_biometrics WHERE user_id = ? LIMIT 1");
        if ($bioStmt) {
            $bioStmt->bind_param("i", $userId);
            $bioStmt->execute();
            $bRes = $bioStmt->get_result();
            if ($bRow = $bRes->fetch_assoc()) {
                if (!empty($bRow['agua']) && (float)$bRow['agua'] > 0) {
                    $waterTargetMl = (int)round((float)$bRow['agua'] * 1000);
                }
            }
            $bioStmt->close();
        }

        // 2. Registro diario de nutrición (agua y calorías consumidas)
        $resLog = $conn->query("SELECT id, water_ml, calories_target, water_target_ml FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
        $logId = null;
        if ($resLog && $rowLog = $resLog->fetch_assoc()) {
            $waterMl = (int)$rowLog['water_ml'];
            $caloriesTarget = (int)($rowLog['calories_target'] ?: $caloriesTarget);
            $waterTargetMl = (int)($rowLog['water_target_ml'] ?: $waterTargetMl);
            $logId = (int)$rowLog['id'];
        }

        // 3. Comidas programadas para el día seleccionado (desde plantilla o personalizadas)
        $mealStmt = $conn->prepare("SELECT id, meal_key, meal_name, suggested_time, food_description, calories, protein_g, carbs_g, fats_g, order_index FROM meal_schedule_templates WHERE (user_id = ? OR user_id = 0) AND day_key = ? ORDER BY order_index ASC");
        if ($mealStmt) {
            $mealStmt->bind_param("is", $userId, $selectedDayKey);
            $mealStmt->execute();
            $mRes = $mealStmt->get_result();
            $rawSchedule = [];
            while ($mRow = $mRes->fetch_assoc()) {
                // Si el usuario tiene una personalizada para este meal_key, prevalece sobre user_id = 0
                $mKey = $mRow['meal_key'];
                if (!isset($rawSchedule[$mKey]) || $mRow['user_id'] == $userId) {
                    $rawSchedule[$mKey] = $mRow;
                }
            }
            $mealStmt->close();
            $scheduledMeals = array_values($rawSchedule);
            usort($scheduledMeals, fn($a, $b) => $a['order_index'] <=> $b['order_index']);
        }

        // 4. Comidas completadas para la fecha de hoy (REINICIO AUTOMÁTICO A MEDIANOCHE)
        // Dado que se filtra estrictamente por `meal_date = '$today'`, al cambiar la fecha
        // a medianoche la consulta devuelve cero completados para el nuevo día,
        // garantizando el reinicio automático sin perder los registros previos.
        $compStmt = $conn->prepare("SELECT meal_key, completed_at FROM daily_meal_completions WHERE user_id = ? AND meal_date = ? AND is_completed = 1");
        if ($compStmt) {
            $compStmt->bind_param("is", $userId, $today);
            $compStmt->execute();
            $cRes = $compStmt->get_result();
            while ($cRow = $cRes->fetch_assoc()) {
                $completedMealKeys[$cRow['meal_key']] = $cRow['completed_at'];
            }
            $compStmt->close();
        }

        // 5. Comidas libres adicionales registradas hoy
        if ($logId) {
            $freeRes = $conn->query("SELECT id, food_name AS name, category AS type, calories, protein_g AS p, carbs_g AS c, fats_g AS f, logged_at FROM logged_meals WHERE nutrition_log_id = $logId ORDER BY id DESC");
            if ($freeRes) {
                while ($fm = $freeRes->fetch_assoc()) {
                    $freeMeals[] = [
                        'id'       => (int)$fm['id'],
                        'name'     => $fm['name'],
                        'type'     => $fm['type'],
                        'calories' => (int)$fm['calories'],
                        'p'        => (float)$fm['p'],
                        'c'        => (float)$fm['c'],
                        'f'        => (float)$fm['f'],
                        'loggedAt' => $fm['logged_at']
                    ];
                }
            }
        }
    }

    // Fallback de horarios estructurados si la base de datos no tiene la plantilla
    if (empty($scheduledMeals)) {
        $scheduledMeals = [
            ['id' => 1, 'meal_key' => 'desayuno', 'meal_name' => 'Desayuno Energético', 'suggested_time' => '08:00', 'food_description' => 'Tortilla de claras y huevo con avena integral y frutos rojos', 'calories' => 430, 'protein_g' => 32, 'carbs_g' => 48, 'fats_g' => 12, 'order_index' => 1],
            ['id' => 2, 'meal_key' => 'media_manana', 'meal_name' => 'Media Mañana Proteica', 'suggested_time' => '11:00', 'food_description' => 'Yogur griego con nueces y semillas de chía', 'calories' => 220, 'protein_g' => 18, 'carbs_g' => 12, 'fats_g' => 10, 'order_index' => 2],
            ['id' => 3, 'meal_key' => 'almuerzo', 'meal_name' => 'Almuerzo Anabólico', 'suggested_time' => '14:00', 'food_description' => 'Pechuga de pollo a la plancha con arroz basmati y verduras al vapor', 'calories' => 640, 'protein_g' => 54, 'carbs_g' => 68, 'fats_g' => 14, 'order_index' => 3],
            ['id' => 4, 'meal_key' => 'merienda', 'meal_name' => 'Merienda Pre-Entreno', 'suggested_time' => '17:30', 'food_description' => 'Batido de proteína whey isolada con 1 plátano y canela', 'calories' => 310, 'protein_g' => 28, 'carbs_g' => 40, 'fats_g' => 4, 'order_index' => 4],
            ['id' => 5, 'meal_key' => 'cena', 'meal_name' => 'Cena Reparadora', 'suggested_time' => '20:30', 'food_description' => 'Salmón al horno con batata asada y espárragos trigueros', 'calories' => 550, 'protein_g' => 42, 'carbs_g' => 44, 'fats_g' => 18, 'order_index' => 5],
        ];
    }

    // Mapear comidas programadas con su estado de cumplimiento para hoy
    $formattedSchedule = [];
    $plannedCalories = 0;
    $plannedProtein = 0;
    $plannedCarbs = 0;
    $plannedFats = 0;

    $consumedCalories = 0;
    $consumedProtein = 0;
    $consumedCarbs = 0;
    $consumedFats = 0;
    $completedCount = 0;

    foreach ($scheduledMeals as $sm) {
        $mKey = $sm['meal_key'];
        $isDoneToday = isset($completedMealKeys[$mKey]);
        if ($isDoneToday) {
            $completedCount++;
            $consumedCalories += (int)$sm['calories'];
            $consumedProtein += (float)$sm['protein_g'];
            $consumedCarbs += (float)$sm['carbs_g'];
            $consumedFats += (float)$sm['fats_g'];
        }

        $plannedCalories += (int)$sm['calories'];
        $plannedProtein += (float)$sm['protein_g'];
        $plannedCarbs += (float)$sm['carbs_g'];
        $plannedFats += (float)$sm['fats_g'];

        $formattedSchedule[] = [
            'id'              => (int)$sm['id'],
            'mealKey'         => $sm['meal_key'],
            'name'            => $sm['meal_name'],
            'suggestedTime'   => $sm['suggested_time'],
            'description'     => $sm['food_description'],
            'calories'        => (int)$sm['calories'],
            'p'               => (float)$sm['protein_g'],
            'c'               => (float)$sm['carbs_g'],
            'f'               => (float)$sm['fats_g'],
            'isCompleted'     => $isDoneToday,
            'completedAt'     => $completedMealKeys[$mKey] ?? null
        ];
    }

    // Sumar comidas libres registradas
    foreach ($freeMeals as $fm) {
        $consumedCalories += $fm['calories'];
        $consumedProtein += $fm['p'];
        $consumedCarbs += $fm['c'];
        $consumedFats += $fm['f'];
    }

    $totalScheduled = count($formattedSchedule);
    $completionPct = $totalScheduled > 0 ? round(($completedCount / $totalScheduled) * 100) : 0;
    $streakData = calculateUserStreak($conn, $userId);

    apiSuccess([
        'currentServerDayKey'  => $currentServerDayKey,
        'currentServerDayName' => $currentServerDayName,
        'selectedDayKey'       => $selectedDayKey,
        'selectedDayName'      => $selectedDayName,
        'isToday'              => $isToday,
        'serverDate'           => $today,
        'schedule'             => $formattedSchedule,
        'freeMeals'            => $freeMeals,
        'waterMl'              => $waterMl,
        'waterTargetMl'        => $waterTargetMl,
        'summary'              => [
            'completedMeals'     => $completedCount,
            'totalMeals'         => $totalScheduled,
            'completionPct'      => $completionPct,
            'consumedCalories'   => $consumedCalories,
            'targetCalories'     => $caloriesTarget ?: $plannedCalories,
            'consumedProtein'    => round($consumedProtein, 1),
            'targetProtein'      => 150,
            'consumedCarbs'      => round($consumedCarbs, 1),
            'targetCarbs'        => 230,
            'consumedFats'       => round($consumedFats, 1),
            'targetFats'         => 65,
        ],
        'streak'               => $streakData,
        'dbConnected'          => $dbAvailable
    ], 'Plan de comidas y horarios obtenido correctamente');
}

// ==============================================================================
// 2. POST: ACCIONES DE COMIDAS PROGRAMADAS, AGUA Y ALIMENTOS LIBRES
// ==============================================================================
if ($method === 'POST') {
    $input = getJsonInput();
    $action = $_GET['action'] ?? $input['action'] ?? '';

    // ==========================================================================
    // 2.1 ALTERNAR CHECK-IN DE COMIDA PROGRAMADA (DESAYUNO, ALMUERZO, ETC.)
    // ==========================================================================
    if ($action === 'toggle_scheduled_meal') {
        $mealKey = strtolower(trim($input['meal_key'] ?? ''));
        if (empty($mealKey)) {
            apiError('El campo meal_key es obligatorio (ej: desayuno, almuerzo).', 422);
        }

        $newCompleted = true;

        if ($dbAvailable && $userId > 0) {
            $chkStmt = $conn->prepare("SELECT id, is_completed FROM daily_meal_completions WHERE user_id = ? AND meal_key = ? AND meal_date = ? LIMIT 1");
            if ($chkStmt) {
                $chkStmt->bind_param("iss", $userId, $mealKey, $today);
                $chkStmt->execute();
                $chkRes = $chkStmt->get_result();

                if ($cRow = $chkRes->fetch_assoc()) {
                    $newStatus = $cRow['is_completed'] ? 0 : 1;
                    $newCompleted = ($newStatus === 1);
                    $upd = $conn->prepare("UPDATE daily_meal_completions SET is_completed = ?, completed_at = NOW() WHERE id = ?");
                    $upd->bind_param("ii", $newStatus, $cRow['id']);
                    $upd->execute();
                    $upd->close();
                } else {
                    $ins = $conn->prepare("INSERT INTO daily_meal_completions (user_id, meal_key, meal_date, is_completed) VALUES (?, ?, ?, 1)");
                    $ins->bind_param("iss", $userId, $mealKey, $today);
                    $ins->execute();
                    $ins->close();
                    $newCompleted = true;
                }
                $chkStmt->close();
            }

            // Recalcular racha activa del atleta
            $streakData = calculateUserStreak($conn, $userId);

            // Obtener lista actualizada de comidas completadas hoy
            $allDone = [];
            $allDoneStmt = $conn->prepare("SELECT meal_key FROM daily_meal_completions WHERE user_id = ? AND meal_date = ? AND is_completed = 1");
            $allDoneStmt->bind_param("is", $userId, $today);
            $allDoneStmt->execute();
            $dRes = $allDoneStmt->get_result();
            while ($dr = $dRes->fetch_assoc()) {
                $allDone[] = $dr['meal_key'];
            }
            $allDoneStmt->close();
        } else {
            $streakData = calculateUserStreak(null, $userId);
            $allDone = [$mealKey];
        }

        apiSuccess([
            'mealKey'              => $mealKey,
            'isCompleted'          => $newCompleted,
            'completedKeys'        => $allDone,
            'serverDate'           => $today,
            'streak'               => $streakData,
            'dbConnected'          => $dbAvailable
        ], $newCompleted ? 'Comida marcada como consumida' : 'Comida desmarcada');
    }

    // ==========================================================================
    // 2.2 AJUSTAR CONSUMO DE AGUA (+/- ml)
    // ==========================================================================
    if ($action === 'adjust_water') {
        $delta = (int)($input['delta'] ?? 0);
        $newWater = 1750;

        if ($dbAvailable && $userId > 0) {
            $conn->query("INSERT INTO daily_nutrition_logs (user_id, log_date, water_ml) VALUES ($userId, '$today', GREATEST(0, $delta))
                          ON DUPLICATE KEY UPDATE water_ml = GREATEST(0, water_ml + $delta)");
            $res = $conn->query("SELECT water_ml FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $newWater = (int)$row['water_ml'];
            }
        } else {
            $newWater = max(0, 1750 + $delta);
        }

        apiSuccess([
            'waterMl'     => $newWater,
            'delta'       => $delta,
            'dbConnected' => $dbAvailable
        ], 'Consumo de agua actualizado');
    }

    // ==========================================================================
    // 2.3 REGISTRAR ALIMENTO LIBRE
    // ==========================================================================
    if ($action === 'add_meal') {
        $name = trim($input['name'] ?? 'Comida rápida');
        $type = trim($input['type'] ?? 'Snack');
        $p = max(0, (float)($input['p'] ?? 0));
        $c = max(0, (float)($input['c'] ?? 0));
        $f = max(0, (float)($input['f'] ?? 0));

        // Cálculo Atwater si calorías vienen en 0
        $cal = (int)($input['calories'] ?? 0);
        if ($cal <= 0) {
            $cal = (int)round(($p * 4) + ($c * 4) + ($f * 9));
        }

        $insertedId = null;

        if ($dbAvailable && $userId > 0) {
            $conn->query("INSERT INTO daily_nutrition_logs (user_id, log_date) VALUES ($userId, '$today') ON DUPLICATE KEY UPDATE id=id");
            $logRes = $conn->query("SELECT id FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
            if ($logRes && $lRow = $logRes->fetch_assoc()) {
                $logId = (int)$lRow['id'];
                $stmt = $conn->prepare("INSERT INTO logged_meals (nutrition_log_id, food_name, category, calories, protein_g, carbs_g, fats_g) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issiddd", $logId, $name, $type, $cal, $p, $c, $f);
                $stmt->execute();
                $insertedId = $conn->insert_id;
                $stmt->close();
            }
        }

        apiSuccess([
            'meal' => [
                'id'       => $insertedId ?: time(),
                'name'     => $name,
                'type'     => $type,
                'calories' => $cal,
                'p'        => $p,
                'c'        => $c,
                'f'        => $f
            ],
            'dbConnected' => $dbAvailable
        ], 'Alimento registrado con éxito');
    }

    // ==========================================================================
    // 2.4 ELIMINAR ALIMENTO LIBRE
    // ==========================================================================
    if ($action === 'delete_meal') {
        $mealId = (int)($input['meal_id'] ?? 0);
        if ($dbAvailable && $mealId > 0) {
            $conn->query("DELETE FROM logged_meals WHERE id = $mealId");
        }
        apiSuccess(['deletedId' => $mealId], 'Alimento eliminado');
    }
}

apiError('Acción no soportada o método no válido', 400);
