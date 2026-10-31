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

    // Recuperar perfil biométrico completo del usuario para nutrición personalizada (Requerimiento 1)
    $bioData = [
        'peso'            => 75.0,
        'altura'          => 175.0,
        'edad'            => 25,
        'sexo'            => 'masculino',
        'objetivo'        => 'ganar_musculo',
        'nivel_actividad' => 'moderado',
        'agua'            => 3.0,
        'condiciones'     => '',
        'alergias'        => '',
        'preferencia'     => 'cualquierCosa'
    ];

    if ($dbAvailable && $userId > 0) {
        $bioStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? LIMIT 1");
        if ($bioStmt) {
            $bioStmt->bind_param("i", $userId);
            $bioStmt->execute();
            $bRes = $bioStmt->get_result();
            if ($bRow = $bRes->fetch_assoc()) {
                $bioData = array_merge($bioData, array_filter($bRow, function($v) { return !is_null($v); }));
                if (!empty($bRow['agua']) && (float)$bRow['agua'] > 0) {
                    $waterTargetMl = (int)round((float)$bRow['agua'] * 1000);
                }
            }
            $bioStmt->close();
        }

        // Calcular metas energéticas exactas para el atleta
        $calculatedTargets = calculateNutritionTargets(
            (float)$bioData['peso'],
            (float)$bioData['altura'],
            (int)$bioData['edad'],
            (string)$bioData['sexo'],
            (string)$bioData['nivel_actividad'],
            (string)$bioData['objetivo']
        );
        $caloriesTarget = $calculatedTargets['calories'];
        $waterTargetMl = $calculatedTargets['waterMl'];

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
        $mealStmt = $conn->prepare("SELECT id, meal_key, meal_name, suggested_time, food_description, calories, protein_g, carbs_g, fats_g, order_index FROM meal_schedule_templates WHERE user_id = ? AND day_key = ? ORDER BY order_index ASC");
        if ($mealStmt) {
            $mealStmt->bind_param("is", $userId, $selectedDayKey);
            $mealStmt->execute();
            $mRes = $mealStmt->get_result();
            while ($mRow = $mRes->fetch_assoc()) {
                $scheduledMeals[] = $mRow;
            }
            $mealStmt->close();
        }

        // Si no existen comidas personalizadas en BD para este usuario y día, generarlas dinámicamente según su encuesta
        if (empty($scheduledMeals)) {
            $scheduledMeals = generatePersonalizedMealsForUser($conn, $userId, $bioData, $selectedDayKey, $calculatedTargets);
        }

        // 4. Comidas completadas para la fecha de hoy (REINICIO AUTOMÁTICO A MEDIANOCHE)
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

    // Fallback de resiliencia si la conexión a base de datos estuviera inactiva
    if (empty($scheduledMeals)) {
        $scheduledMeals = getOfflineFallbackMeals($bioData);
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

/**
 * Genera comidas científicamente estructuradas y 100% personalizadas según la encuesta biométrica
 * del usuario (preferencias alimenticias, alergias, condiciones médicas y objetivo fitness).
 * REQUERIMIENTO 1.
 *
 * @param mysqli $conn
 * @param int $userId
 * @param array $bioData
 * @param string $dayKey
 * @param array $targets
 * @return array
 */
function generatePersonalizedMealsForUser(mysqli $conn, int $userId, array $bioData, string $dayKey, array $targets): array {
    $pref = strtolower(trim($bioData['preferencia'] ?? 'cualquierCosa'));
    $alergias = strtolower(trim($bioData['alergias'] ?? ''));
    $condiciones = strtolower(trim($bioData['condiciones'] ?? ''));
    $objetivo = strtolower(trim($bioData['objetivo'] ?? 'ganar_musculo'));

    $totalCal = max(1300, (int)($targets['calories'] ?? 2200));
    $totalP   = max(60, (int)($targets['proteinG'] ?? 150));
    $totalC   = max(50, (int)($targets['carbsG'] ?? 230));
    $totalF   = max(30, (int)($targets['fatsG'] ?? 65));

    // Verificación de alergias
    $hasNutAllergy = str_contains($alergias, 'maní') || str_contains($alergias, 'mani') || str_contains($alergias, 'nuez') || str_contains($alergias, 'nueces') || str_contains($alergias, 'cacahuate');
    $hasDairyAllergy = str_contains($alergias, 'lact') || str_contains($alergias, 'leche') || str_contains($alergias, 'queso');
    $hasGlutenAllergy = str_contains($alergias, 'gluten') || str_contains($alergias, 'trigo');
    $hasEggAllergy = str_contains($alergias, 'huevo');
    $hasFishAllergy = str_contains($alergias, 'pescado') || str_contains($alergias, 'marisco');

    // Verificación de condiciones
    $isDiabetic = str_contains($condiciones, 'diabet');
    $isHypertensive = str_contains($condiciones, 'hiperten');

    $nutNote = $hasNutAllergy ? "semillas de calabaza y chía (libre de frutos secos)" : "nueces y almendras";
    $dairyNote = $hasDairyAllergy ? "bebida vegetal enriquecida y proteína vegetal" : "yogur griego natural";
    $grainNote = $hasGlutenAllergy ? "avena certificada sin gluten y arroz integral" : "avena integral y arroz basmati";

    // 1. DESAYUNO (25% calorías y macros)
    $dCal = (int)round($totalCal * 0.25);
    $dP   = (int)round($totalP * 0.25);
    $dC   = (int)round($totalC * 0.30);
    $dF   = (int)round($totalF * 0.20);

    if ($pref === 'vegano') {
        $dDesc = "Tazón de porridge de avena con bebida de soja/almendras, proteína vegetal de guisante aislada, arándanos frescos y {$nutNote}.";
    } elseif ($pref === 'vegetariano') {
        $eggPart = $hasEggAllergy ? "tofu revuelto con cúrcuma" : "tortilla de 2 huevos de campo con espinacas baby";
        $dDesc = "{$eggPart}, acompañado de copos de {$grainNote} con frutos rojos y {$nutNote}.";
    } elseif ($pref === 'keto') {
        $eggPart = $hasEggAllergy ? "tofu a la plancha con aguacate" : "huevos revueltos con aguacate";
        $dDesc = "{$eggPart}, espinacas salteadas en aceite de oliva virgen extra y {$nutNote}.";
    } else {
        $eggPart = $hasEggAllergy ? "tofu salteado con pavo magro" : "tortilla de claras y huevo entero";
        $dDesc = "{$eggPart} con {$grainNote}, rodajas de manzana verde y {$nutNote}.";
    }

    // 2. MEDIA MAÑANA (15% calorías y macros)
    $mCal = (int)round($totalCal * 0.15);
    $mP   = (int)round($totalP * 0.15);
    $mC   = (int)round($totalC * 0.15);
    $mF   = (int)round($totalF * 0.20);

    if ($pref === 'vegano') {
        $mDesc = "Tostada de pan germinado con hummus casero de garbanzos, semillas de chía y rodajas de tomate.";
    } elseif ($pref === 'keto') {
        $mDesc = "Pudín de chía con leche de coco sin azúcar, semillas de girasol y frutos rojos bajos en azúcar.";
    } else {
        $mDesc = "{$dairyNote} desnatado sin azúcar con semillas de chía, canela de Ceilán y frutos del bosque.";
    }

    // 3. ALMUERZO (32% calorías y macros)
    $lCal = (int)round($totalCal * 0.32);
    $lP   = (int)round($totalP * 0.32);
    $lC   = (int)round($totalC * 0.32);
    $lF   = (int)round($totalF * 0.30);

    if ($pref === 'vegano') {
        $lDesc = "Salteado de tofu firme marinado y tempeh con quinoa real, brócoli al vapor, zanahorias y aceite de oliva virgen extra.";
    } elseif ($pref === 'vegetariano') {
        $lDesc = "Lentejas estofadas con verduras variadas, quinoa, queso fresco magro (o levadura nutricional) y ensalada de hojas verdes.";
    } elseif ($pref === 'keto') {
        $proteinOption = $hasFishAllergy ? "pechuga de pollo de corral" : "salmón noruego a la plancha";
        $lDesc = "{$proteinOption} con aguacate entero, espárragos trigueros y ensalada verde bañada en aceite de oliva virgen extra.";
    } else {
        $proteinOption = $hasFishAllergy ? "pechuga de pollo de corral o pavo magro" : "filete de salmón o pechuga de pollo";
        $carbOption = $isDiabetic ? "arroz integral con abundante ensalada de legumbres de bajo índice glucémico" : "arroz basmati aromático o batata al horno";
        $lDesc = "{$proteinOption} a la plancha con {$carbOption}, brócoli al vapor y aderezo de aceite de oliva.";
    }

    // 4. MERIENDA PRE/POST-ENTRENO (10% calorías y macros)
    $snCal = (int)round($totalCal * 0.10);
    $snP   = (int)round($totalP * 0.12);
    $snC   = (int)round($totalC * 0.10);
    $snF   = (int)round($totalF * 0.10);

    $proteinPowder = ($pref === 'vegano' || $hasDairyAllergy) ? "proteína aislada de guisante y arroz" : "aislado de proteína whey";
    $snDesc = "Batido de {$proteinPowder} con 1 plátano maduro, canela en polvo y agua mineral fresca.";

    // 5. CENA REPARADORA (18% calorías y macros)
    $cCal = max(200, $totalCal - ($dCal + $mCal + $lCal + $snCal));
    $cP   = max(15, $totalP - ($dP + $mP + $lP + $snP));
    $cC   = max(10, $totalC - ($dC + $mC + $lC + $snC));
    $cF   = max(8, $totalF - ($dF + $mF + $lF + $snF));

    if ($pref === 'vegano') {
        $cDesc = "Curry suave de garbanzos con espinacas y cúrcuma, acompañado de arroz integral y semillas de calabaza.";
    } elseif ($pref === 'vegetariano') {
        $cDesc = "Revuelto de setas shiitake y espárragos con queso de cabra magro, batata asada y ensalada de canónigos.";
    } elseif ($pref === 'keto') {
        $cDesc = "Lubina o pechuga a las hierbas provenzales con puré de coliflor y aguacate macerado.";
    } else {
        $fishOrChicken = $hasFishAllergy ? "pechuga de pavo al romero" : "filete de merluza fresca o salmón al horno";
        $cDesc = "{$fishOrChicken} con batata asada, espárragos a la plancha y ensalada verde depurativa.";
    }

    if ($isDiabetic) {
        $dDesc .= " (Control glucémico: fibra soluble integrada).";
        $lDesc .= " (Bajo impacto insulínico y absorción lenta).";
    }

    if ($isHypertensive) {
        $lDesc .= " (Cocción con hierbas aromáticas sin sal añadida).";
        $cDesc .= " (Rico en potasio y magnesio para regulación de tensión).";
    }

    $meals = [
        ['meal_key' => 'desayuno', 'meal_name' => 'Desayuno Energético', 'suggested_time' => '08:00', 'food_description' => $dDesc, 'calories' => $dCal, 'protein_g' => $dP, 'carbs_g' => $dC, 'fats_g' => $dF, 'order_index' => 1],
        ['meal_key' => 'media_manana', 'meal_name' => 'Media Mañana Proteica', 'suggested_time' => '11:00', 'food_description' => $mDesc, 'calories' => $mCal, 'protein_g' => $mP, 'carbs_g' => $mC, 'fats_g' => $mF, 'order_index' => 2],
        ['meal_key' => 'almuerzo', 'meal_name' => 'Almuerzo Principal', 'suggested_time' => '14:00', 'food_description' => $lDesc, 'calories' => $lCal, 'protein_g' => $lP, 'carbs_g' => $lC, 'fats_g' => $lF, 'order_index' => 3],
        ['meal_key' => 'merienda', 'meal_name' => 'Merienda Nutritiva', 'suggested_time' => '17:30', 'food_description' => $snDesc, 'calories' => $snCal, 'protein_g' => $snP, 'carbs_g' => $snC, 'fats_g' => $snF, 'order_index' => 4],
        ['meal_key' => 'cena', 'meal_name' => 'Cena Reparadora', 'suggested_time' => '20:30', 'food_description' => $cDesc, 'calories' => $cCal, 'protein_g' => $cP, 'carbs_g' => $cC, 'fats_g' => $cF, 'order_index' => 5]
    ];

    // Persistir en meal_schedule_templates para este usuario
    $ins = $conn->prepare("INSERT INTO meal_schedule_templates (user_id, day_key, meal_key, meal_name, suggested_time, food_description, calories, protein_g, carbs_g, fats_g, order_index) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE food_description = VALUES(food_description), calories = VALUES(calories), protein_g = VALUES(protein_g), carbs_g = VALUES(carbs_g), fats_g = VALUES(fats_g)");
    if ($ins) {
        foreach ($meals as $m) {
            $ins->bind_param("isssssiiiii", $userId, $dayKey, $m['meal_key'], $m['meal_name'], $m['suggested_time'], $m['food_description'], $m['calories'], $m['protein_g'], $m['carbs_g'], $m['fats_g'], $m['order_index']);
            $ins->execute();
        }
        $ins->close();
    }

    return $meals;
}

function getOfflineFallbackMeals(array $bioData): array {
    return [
        ['id' => 1, 'meal_key' => 'desayuno', 'meal_name' => 'Desayuno Personalizado', 'suggested_time' => '08:00', 'food_description' => 'Avena integral con proteína, frutos rojos y semillas.', 'calories' => 450, 'protein_g' => 32, 'carbs_g' => 50, 'fats_g' => 12, 'order_index' => 1],
        ['id' => 2, 'meal_key' => 'media_manana', 'meal_name' => 'Media Mañana Saludable', 'suggested_time' => '11:00', 'food_description' => 'Yogur natural con semillas de chía y fruta fresca.', 'calories' => 220, 'protein_g' => 18, 'carbs_g' => 14, 'fats_g' => 8, 'order_index' => 2],
        ['id' => 3, 'meal_key' => 'almuerzo', 'meal_name' => 'Almuerzo Equilibrado', 'suggested_time' => '14:00', 'food_description' => 'Proteína magra con arroz basmati y verduras al vapor con aceite de oliva.', 'calories' => 650, 'protein_g' => 52, 'carbs_g' => 70, 'fats_g' => 15, 'order_index' => 3],
        ['id' => 4, 'meal_key' => 'merienda', 'meal_name' => 'Merienda Energética', 'suggested_time' => '17:30', 'food_description' => 'Batido proteico con plátano y canela.', 'calories' => 300, 'protein_g' => 26, 'carbs_g' => 38, 'fats_g' => 4, 'order_index' => 4],
        ['id' => 5, 'meal_key' => 'cena', 'meal_name' => 'Cena Ligera & Proteica', 'suggested_time' => '20:30', 'food_description' => 'Pescado blanco o tofu a la plancha con batata asada y ensalada verde.', 'calories' => 520, 'protein_g' => 42, 'carbs_g' => 40, 'fats_g' => 16, 'order_index' => 5],
    ];
}

apiError('Acción no soportada o método no válido', 400);
?>
