<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE PERFIL, BIOMETRÍA & EVOLUCIÓN FÍSICA
 * ==============================================================================
 * Gestiona el perfil del atleta, cálculos energéticos metabólicos (Mifflin-St Jeor),
 * historial de evolución física y control estricto de periodicidad:
 *
 * REGLAS DE NEGOCIO (REQUERIMIENTO 4):
 * 1. Periodicidad semanal: Los registros de progreso físico se restringen a 1 vez
 *    cada 7 días para evitar fluctuaciones y errores continuos.
 * 2. Margen de corrección de 24 horas: Si el usuario cometió una equivocación al
 *    digitar un dato (ej. confundió peso o altura), cuenta con una ventana de 24 horas
 *    posteriores al registro para editarlo sin esperar los 7 días.
 * 3. Todas las validaciones de tiempo se realizan en el backend con el reloj de MySQL/PHP.
 *
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Calcula las metas de macronutrientes basadas en fórmulas validadas.
 *
 * @param float $weightKg
 * @param float $heightCm
 * @param int $age
 * @param string $gender
 * @param string $activity
 * @param string $goal
 * @return array
 */
function calculateNutritionTargets(
    float $weightKg,
    float $heightCm,
    int $age,
    string $gender = 'masculino',
    string $activity = 'moderado',
    string $goal = 'ganar_musculo'
): array {
    if ($weightKg <= 0 || $heightCm <= 0) {
        return [
            'calories' => 2200,
            'proteinG' => 150,
            'carbsG'   => 230,
            'fatsG'    => 65,
            'waterMl'  => 2500
        ];
    }

    $age = $age > 0 ? $age : 25;

    // Fórmula Mifflin-St Jeor para BMR
    if (strtolower($gender) === 'femenino') {
        $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
    } else {
        $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
    }

    $activityFactors = [
        'sedentario' => 1.2,
        'ligero'     => 1.375,
        'moderado'   => 1.55,
        'activo'     => 1.725,
        'intenso'    => 1.9
    ];
    $factor = $activityFactors[$activity] ?? 1.55;
    $tdee = $bmr * $factor;

    switch ($goal) {
        case 'bajar_peso':
        case 'perder_peso':
        case 'perder_grasa':
            $targetCalories = round($tdee - 450);
            $proteinMultiplier = 2.2;
            $fatsPercentage = 0.25;
            break;
        case 'ganar_musculo':
            $targetCalories = round($tdee + 350);
            $proteinMultiplier = 2.0;
            $fatsPercentage = 0.25;
            break;
        case 'mantener':
        case 'mantener_peso':
        default:
            $targetCalories = round($tdee);
            $proteinMultiplier = 1.8;
            $fatsPercentage = 0.28;
            break;
    }

    $targetCalories = max(1300, (int)$targetCalories);
    $proteinG = round($weightKg * $proteinMultiplier);
    $fatsG = round(($targetCalories * $fatsPercentage) / 9);
    $caloriesFromProteinAndFats = ($proteinG * 4) + ($fatsG * 9);
    $carbsCalories = max(0, $targetCalories - $caloriesFromProteinAndFats);
    $carbsG = round($carbsCalories / 4);

    $waterTargetMl = max(2000, min(4500, (int)round($weightKg * 35)));

    return [
        'calories' => (int)$targetCalories,
        'proteinG' => (int)$proteinG,
        'carbsG'   => (int)$carbsG,
        'fatsG'    => (int)$fatsG,
        'waterMl'  => (int)$waterTargetMl
    ];
}



// ==============================================================================
// 1. GET: RETORNA PERFIL, BIOMETRÍA ACTUAL, POLÍTICA DE ACTUALIZACIÓN E HISTORIAL
// ==============================================================================
if ($method === 'GET') {
    $biometrics = [
        'peso'            => 75.0,
        'altura'          => 175.0,
        'edad'            => 25,
        'sexo'            => 'masculino',
        'objetivo'        => 'ganar_musculo',
        'nivel_actividad' => 'moderado',
        'agua'            => 3.0,
        'condiciones'     => '',
        'alergias'        => '',
        'grasa_corporal'  => null,
        'cintura_cm'      => null,
        'pecho_cm'        => null,
        'brazo_cm'        => null
    ];

    if ($dbAvailable && $userId > 0) {
        $stmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias FROM user_biometrics WHERE user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $biometrics = array_merge($biometrics, array_filter($row, fn($v) => !is_null($v)));
            }
            $stmt->close();
        }
    }

    $targets = calculateNutritionTargets(
        (float)$biometrics['peso'],
        (float)$biometrics['altura'],
        (int)$biometrics['edad'],
        (string)$biometrics['sexo'],
        (string)$biometrics['nivel_actividad'],
        (string)$biometrics['objetivo']
    );

    // Evaluar política de periodicidad (7 días / 24h corrección)
    $policy = evaluateBiometricsPolicy($conn, $userId);

    // Recuperar historial de evolución física
    $history = [];
    if ($dbAvailable && $userId > 0) {
        $hStmt = $conn->prepare("SELECT id, peso, altura, edad, grasa_corporal, cintura_cm, pecho_cm, brazo_cm, notas, created_at FROM user_biometrics_history WHERE user_id = ? ORDER BY created_at ASC");
        if ($hStmt) {
            $hStmt->bind_param("i", $userId);
            $hStmt->execute();
            $hRes = $hStmt->get_result();
            $prevPeso = null;

            while ($hRow = $hRes->fetch_assoc()) {
                $p = (float)$hRow['peso'];
                $altM = (float)$hRow['altura'] / 100.0;
                $bmi = ($altM > 0) ? round($p / ($altM * $altM), 1) : 0;
                $deltaPeso = ($prevPeso !== null) ? round($p - $prevPeso, 2) : 0;
                $prevPeso = $p;

                $history[] = [
                    'id'            => (int)$hRow['id'],
                    'date'          => substr($hRow['created_at'], 0, 10),
                    'timestamp'     => $hRow['created_at'],
                    'peso'          => $p,
                    'altura'        => (float)$hRow['altura'],
                    'edad'          => (int)$hRow['edad'],
                    'bmi'           => $bmi,
                    'deltaPeso'     => $deltaPeso,
                    'grasaCorporal' => $hRow['grasa_corporal'] !== null ? (float)$hRow['grasa_corporal'] : null,
                    'cinturaCm'     => $hRow['cintura_cm'] !== null ? (float)$hRow['cintura_cm'] : null,
                    'pechoCm'       => $hRow['pecho_cm'] !== null ? (float)$hRow['pecho_cm'] : null,
                    'brazoCm'       => $hRow['brazo_cm'] !== null ? (float)$hRow['brazo_cm'] : null,
                    'notas'         => $hRow['notas'] ?? ''
                ];
            }
            $hStmt->close();
        }
    }

    // Ordenar historial para mostrar el más reciente primero en la tabla
    $historyDescending = array_reverse($history);

    // Si aún no hay historial pero hay biometría actual, agregar el punto de partida
    if (empty($historyDescending) && !empty($biometrics['peso'])) {
        $altM = (float)$biometrics['altura'] / 100.0;
        $bmi = ($altM > 0) ? round((float)$biometrics['peso'] / ($altM * $altM), 1) : 0;
        $historyDescending[] = [
            'id'            => 0,
            'date'          => date('Y-m-d'),
            'timestamp'     => date('Y-m-d H:i:s'),
            'peso'          => (float)$biometrics['peso'],
            'altura'        => (float)$biometrics['altura'],
            'edad'          => (int)$biometrics['edad'],
            'bmi'           => $bmi,
            'deltaPeso'     => 0,
            'grasaCorporal' => null,
            'cinturaCm'     => null,
            'pechoCm'       => null,
            'brazoCm'       => null,
            'notas'         => 'Registro inicial del atleta'
        ];
    }

    $streakData = calculateUserStreak($conn, $userId);

    apiSuccess([
        'user'        => $user,
        'biometrics'  => $biometrics,
        'targets'     => $targets,
        'policy'      => $policy,
        'history'     => $historyDescending,
        'streak'      => $streakData,
        'dbConnected' => $dbAvailable
    ], 'Perfil, biometría y evolución física obtenidos');
}

// ==============================================================================
// 2. POST: ACTUALIZACIÓN DE CONDICIÓN FÍSICA CON CONTROL DE PERIODICIDAD & 24H
// ==============================================================================
if ($method === 'POST') {
    $input = getJsonInput();

    // Sanitización y tipado de variables
    $peso = isset($input['peso']) ? max(30.0, min(300.0, (float)$input['peso'])) : null;
    $altura = isset($input['altura']) ? max(100.0, min(250.0, (float)$input['altura'])) : null;
    $edad = isset($input['edad']) ? max(12, min(100, (int)$input['edad'])) : 25;
    $sexo = in_array(strtolower($input['sexo'] ?? ''), ['masculino', 'femenino', 'otro']) ? strtolower($input['sexo']) : 'masculino';
    $objetivo = sanitizeString($input['objetivo'] ?? 'ganar_musculo');
    $nivelActividad = sanitizeString($input['nivel_actividad'] ?? 'moderado');
    $aguaLitros = isset($input['agua']) ? (int)$input['agua'] : 3;

    // Métricas complementarias de composición corporal
    $grasaCorporal = isset($input['grasa_corporal']) && is_numeric($input['grasa_corporal']) ? max(3.0, min(60.0, (float)$input['grasa_corporal'])) : null;
    $cinturaCm = isset($input['cintura_cm']) && is_numeric($input['cintura_cm']) ? max(40.0, min(200.0, (float)$input['cintura_cm'])) : null;
    $pechoCm = isset($input['pecho_cm']) && is_numeric($input['pecho_cm']) ? max(40.0, min(200.0, (float)$input['pecho_cm'])) : null;
    $brazoCm = isset($input['brazo_cm']) && is_numeric($input['brazo_cm']) ? max(15.0, min(80.0, (float)$input['brazo_cm'])) : null;
    $notas = sanitizeString($input['notas'] ?? '');

    if (!$peso || !$altura) {
        apiError('El peso (kg) y la altura (cm) son datos obligatorios para la condición física.', 422);
    }

    if ($dbAvailable && $userId > 0) {
        // ======================================================================
        // VALIDACIÓN ESTRICTA EN BACKEND: CONTROL DE PERIODICIDAD (REQUERIMIENTO 4)
        // ======================================================================
        $policy = evaluateBiometricsPolicy($conn, $userId);

        if (!$policy['canUpdate']) {
            apiError(
                $policy['message'],
                403,
                [
                    'isBlocked'        => true,
                    'daysRemaining'    => $policy['daysRemainingNextUpdate'],
                    'nextAllowedDate'  => $policy['nextAllowedDate'],
                    'lastRecordDate'   => $policy['lastRecordDate']
                ]
            );
        }

        // ======================================================================
        // APLICACIÓN SEGÚN MODO: 'correction' (UPDATE) O 'create' (INSERT)
        // ======================================================================
        if ($policy['mode'] === 'correction' && !empty($policy['lastRecordId'])) {
            // Rectificación de registro previo dentro de las 24 horas
            $recId = (int)$policy['lastRecordId'];
            $updHist = $conn->prepare("UPDATE user_biometrics_history SET peso = ?, altura = ?, edad = ?, sexo = ?, grasa_corporal = ?, cintura_cm = ?, pecho_cm = ?, brazo_cm = ?, notas = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            if ($updHist) {
                $updHist->bind_param("ddisddddsii", $peso, $altura, $edad, $sexo, $grasaCorporal, $cinturaCm, $pechoCm, $brazoCm, $notas, $recId, $userId);
                $updHist->execute();
                $updHist->close();
            }
            $actionNotice = 'Registro corregido exitosamente dentro del margen de 24 horas.';
        } else {
            // Nuevo registro semanal (después de 7 días o primer registro)
            $insHist = $conn->prepare("INSERT INTO user_biometrics_history (user_id, peso, altura, edad, sexo, grasa_corporal, cintura_cm, pecho_cm, brazo_cm, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insHist) {
                $insHist->bind_param("iddisdddds", $userId, $peso, $altura, $edad, $sexo, $grasaCorporal, $cinturaCm, $pechoCm, $brazoCm, $notas);
                $insHist->execute();
                $insHist->close();
            }
            $actionNotice = 'Nueva medición semanal registrada y añadida a tu evolución física.';
        }

        // Actualizar tabla principal user_biometrics
        $updBio = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE peso = VALUES(peso), altura = VALUES(altura), edad = VALUES(edad), sexo = VALUES(sexo), objetivo = VALUES(objetivo), nivel_actividad = VALUES(nivel_actividad), agua = VALUES(agua), updated_at = NOW()");
        if ($updBio) {
            $updBio->bind_param("iddisssi", $userId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros);
            $updBio->execute();
            $updBio->close();
        }

        // Sincronizar nombre y email si se enviaron
        $nuevoNombre = sanitizeString($input['nombre'] ?? '');
        $nuevoEmail = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!empty($nuevoNombre) || !empty($nuevoEmail)) {
            $updUser = $conn->prepare("UPDATE users SET nombre = IF(? != '', ?, nombre), email = IF(? != '', ?, email) WHERE id = ?");
            if ($updUser) {
                $updUser->bind_param("ssssi", $nuevoNombre, $nuevoNombre, $nuevoEmail, $nuevoEmail, $userId);
                $updUser->execute();
                $updUser->close();
            }
        }
    } else {
        $actionNotice = 'Biometría guardada localmente';
    }

    $updatedTargets = calculateNutritionTargets(
        $peso,
        $altura,
        $edad,
        $sexo,
        $nivelActividad,
        $objetivo
    );

    // Re-evaluar política tras el cambio
    $updatedPolicy = evaluateBiometricsPolicy($conn, $userId);
    $streakData = calculateUserStreak($conn, $userId);

    apiSuccess([
        'biometrics' => [
            'peso'            => $peso,
            'altura'          => $altura,
            'edad'            => $edad,
            'sexo'            => $sexo,
            'objetivo'        => $objetivo,
            'nivel_actividad' => $nivelActividad,
            'agua'            => $aguaLitros,
            'grasa_corporal'  => $grasaCorporal,
            'cintura_cm'      => $cinturaCm,
            'pecho_cm'        => $pechoCm,
            'brazo_cm'        => $brazoCm
        ],
        'targets'     => $updatedTargets,
        'policy'      => $updatedPolicy,
        'streak'      => $streakData,
        'dbConnected' => $dbAvailable
    ], $actionNotice);
}

apiError('Método HTTP no soportado', 405);
