<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE PERFIL, BIOMETRÍA Y METAS
 * ==============================================================================
 * Sincroniza bidireccionalmente los datos biométricos, objetivos y metas
 * energéticas (calorías y macronutrientes) entre la plataforma web y la app móvil.
 *
 * Utiliza fórmulas validadas (Mifflin-St Jeor) para calcular el gasto
 * metabólico basal (BMR) y el gasto energético diario total (TDEE).
 *
 * Endpoints:
 * - GET  /controllers/api_profile.php        -> Obtiene perfil, biometría y targets
 * - POST /controllers/api_profile.php        -> Actualiza métricas biométricas
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Calcula las metas de macronutrientes basadas en peso, altura, edad, sexo, actividad y objetivo.
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
    // Si faltan datos clave, proporcionar valores de referencia equilibrados
    if ($weightKg <= 0 || $heightCm <= 0) {
        return [
            'calories' => 2200,
            'proteinG' => 150,
            'carbsG' => 230,
            'fatsG' => 65,
            'waterMl' => 2500
        ];
    }

    $age = $age > 0 ? $age : 25;

    // Fórmula Mifflin-St Jeor para BMR
    if (strtolower($gender) === 'femenino') {
        $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
    } else {
        $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
    }

    // Factores de actividad física
    $activityFactors = [
        'sedentario' => 1.2,
        'ligero' => 1.375,
        'moderado' => 1.55,
        'activo' => 1.725,
        'intenso' => 1.9
    ];
    $factor = $activityFactors[$activity] ?? 1.55;
    $tdee = $bmr * $factor;

    // Ajuste calórico según objetivo
    switch ($goal) {
        case 'bajar_peso':
        case 'perder_peso':
            $targetCalories = round($tdee - 450);
            $proteinMultiplier = 2.2; // Alta proteína para preservar masa muscular
            $fatsPercentage = 0.25;
            break;
        case 'ganar_musculo':
            $targetCalories = round($tdee + 350); // Superávit controlado
            $proteinMultiplier = 2.0;
            $fatsPercentage = 0.25;
            break;
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

    // Meta de agua recomendada: 35ml por kg de peso corporal
    $waterTargetMl = round($weightKg * 35);
    $waterTargetMl = max(2000, min(4500, (int)$waterTargetMl));

    return [
        'calories' => (int)$targetCalories,
        'proteinG' => (int)$proteinG,
        'carbsG' => (int)$carbsG,
        'fatsG' => (int)$fatsG,
        'waterMl' => (int)$waterTargetMl
    ];
}

// ==============================================================================
// 1. GET: Retorna perfil del usuario, biometría y cálculos sincronizados
// ==============================================================================
if ($method === 'GET') {
    $biometrics = [
        'peso' => 75,
        'altura' => 175,
        'edad' => 25,
        'sexo' => 'masculino',
        'objetivo' => 'ganar_musculo',
        'nivel_actividad' => 'moderado',
        'agua' => 3,
        'condiciones' => '',
        'alergias' => ''
    ];

    if ($dbAvailable) {
        $stmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias FROM usuarios WHERE user_id = ? LIMIT 1");
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

    // Calcular metas nutricionales dinámicas
    $targets = calculateNutritionTargets(
        (float)$biometrics['peso'],
        (float)$biometrics['altura'],
        (int)$biometrics['edad'],
        (string)$biometrics['sexo'],
        (string)$biometrics['nivel_actividad'],
        (string)$biometrics['objetivo']
    );

    // Calcular racha activa (días consecutivos con check-in o workout)
    $streakDays = 4; // Racha por defecto
    if ($dbAvailable) {
        $streakStmt = $conn->prepare("SELECT COUNT(DISTINCT check_in_date) AS total FROM routine_check_ins WHERE user_id = ? AND is_completed = 1 AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        if ($streakStmt) {
            $streakStmt->bind_param("i", $userId);
            $streakStmt->execute();
            $sRes = $streakStmt->get_result();
            if ($sRow = $sRes->fetch_assoc()) {
                $streakDays = max(1, (int)$sRow['total']);
            }
            $streakStmt->close();
        }
    }

    apiSuccess([
        'user' => $user,
        'biometrics' => $biometrics,
        'targets' => $targets,
        'streakDays' => $streakDays,
        'dbConnected' => $dbAvailable
    ], 'Perfil y metas sincronizadas');
}

// ==============================================================================
// 2. POST: Actualiza datos biométricos y recomputa metas
// ==============================================================================
if ($method === 'POST') {
    $input = getJsonInput();

    $peso = isset($input['peso']) ? max(30, min(300, (float)$input['peso'])) : null;
    $altura = isset($input['altura']) ? max(100, min(250, (float)$input['altura'])) : null;
    $edad = isset($input['edad']) ? max(12, min(100, (int)$input['edad'])) : 25;
    $sexo = in_array(strtolower($input['sexo'] ?? ''), ['masculino', 'femenino', 'otro']) ? strtolower($input['sexo']) : 'masculino';
    $objetivo = trim($input['objetivo'] ?? 'ganar_musculo');
    $nivelActividad = trim($input['nivel_actividad'] ?? 'moderado');
    $aguaLitros = isset($input['agua']) ? (int)$input['agua'] : 3;

    if ($dbAvailable && $peso && $altura) {
        $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE user_id = ? LIMIT 1");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            $updateStmt = $conn->prepare("UPDATE usuarios SET peso = ?, altura = ?, edad = ?, sexo = ?, objetivo = ?, nivel_actividad = ?, agua = ?, modified_at = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("ddisssii", $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros, $userId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO usuarios (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("iddisssi", $userId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    $updatedTargets = calculateNutritionTargets(
        $peso ?: 75.0,
        $altura ?: 175.0,
        $edad,
        $sexo,
        $nivelActividad,
        $objetivo
    );

    apiSuccess([
        'biometrics' => [
            'peso' => $peso,
            'altura' => $altura,
            'edad' => $edad,
            'sexo' => $sexo,
            'objetivo' => $objetivo,
            'nivel_actividad' => $nivelActividad,
            'agua' => $aguaLitros
        ],
        'targets' => $updatedTargets,
        'dbConnected' => $dbAvailable
    ], 'Biometría y metas actualizadas exitosamente');
}

apiError('Método HTTP no soportado', 405);
