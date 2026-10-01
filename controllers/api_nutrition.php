<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API DE NUTRICIÓN, MACROS E HIDRATACIÓN
 * ==============================================================================
 * Registra el balance energético diario, balance de macronutrientes (P/C/F) y
 * consumo de agua en tiempo real. Sincronizado para clientes Web y Móviles.
 *
 * Endpoints:
 * - GET  /controllers/api_nutrition.php                       -> Estado nutricional del día
 * - POST /controllers/api_nutrition.php?action=adjust_water   -> Ajuste rápido de agua (+/- ml)
 * - POST /controllers/api_nutrition.php?action=add_meal       -> Registro de comida con macros
 * - POST /controllers/api_nutrition.php?action=delete_meal    -> Eliminación de comida registrada
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/api_bootstrap.php';

$user = resolveAuthUser($conn);
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$today = date('Y-m-d');

// Asegurar tablas en la base de datos
if ($dbAvailable) {
    $conn->query("CREATE TABLE IF NOT EXISTS daily_nutrition_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        log_date DATE NOT NULL,
        calories_target INT DEFAULT 2200,
        water_ml INT DEFAULT 0,
        water_target_ml INT DEFAULT 2500,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_date (user_id, log_date),
        INDEX idx_user_date (user_id, log_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS logged_meals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nutrition_log_id INT NOT NULL,
        food_name VARCHAR(150) NOT NULL,
        category VARCHAR(30) DEFAULT 'Snack',
        calories INT NOT NULL,
        protein_g NUMERIC(5,1) DEFAULT 0,
        carbs_g NUMERIC(5,1) DEFAULT 0,
        fats_g NUMERIC(5,1) DEFAULT 0,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_log_meal (nutrition_log_id),
        CONSTRAINT fk_meals_log FOREIGN KEY (nutrition_log_id) REFERENCES daily_nutrition_logs (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// ==============================================================================
// 1. GET: Retorna estado de hidratación y comidas del día
// ==============================================================================
if ($method === 'GET') {
    $waterMl = 1750;
    $waterTargetMl = 2500;
    $caloriesTarget = 2200;
    $meals = [];

    if ($dbAvailable) {
        // Consultar meta de agua del usuario en tabla usuarios si existe
        $bioStmt = $conn->prepare("SELECT agua FROM usuarios WHERE user_id = ? LIMIT 1");
        if ($bioStmt) {
            $bioStmt->bind_param("i", $userId);
            $bioStmt->execute();
            $bRes = $bioStmt->get_result();
            if ($bRow = $bRes->fetch_assoc()) {
                if (!empty($bRow['agua']) && (int)$bRow['agua'] > 0) {
                    $waterTargetMl = (int)$bRow['agua'] * 1000;
                }
            }
            $bioStmt->close();
        }

        $res = $conn->query("SELECT id, water_ml, calories_target, water_target_ml FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $waterMl = (int)$row['water_ml'];
            $caloriesTarget = (int)($row['calories_target'] ?: $caloriesTarget);
            $waterTargetMl = (int)($row['water_target_ml'] ?: $waterTargetMl);
            $logId = (int)$row['id'];

            $mealsRes = $conn->query("SELECT id, food_name AS name, category AS type, calories, protein_g AS p, carbs_g AS c, fats_g AS f, logged_at FROM logged_meals WHERE nutrition_log_id = $logId ORDER BY id DESC");
            if ($mealsRes) {
                while ($m = $mealsRes->fetch_assoc()) {
                    $meals[] = [
                        'id' => (int)$m['id'],
                        'name' => $m['name'],
                        'type' => $m['type'],
                        'calories' => (int)$m['calories'],
                        'p' => (float)$m['p'],
                        'c' => (float)$m['c'],
                        'f' => (float)$m['f'],
                        'loggedAt' => $m['logged_at']
                    ];
                }
            }
        }
    }

    // Fallback de comidas por defecto si no hay registros previos
    if (empty($meals)) {
        $meals = [
            ['id' => 1, 'name' => 'Huevos revueltos con avena', 'type' => 'Desayuno', 'calories' => 450, 'p' => 32, 'c' => 45, 'f' => 14],
            ['id' => 2, 'name' => 'Pechuga a la plancha con arroz', 'type' => 'Almuerzo', 'calories' => 620, 'p' => 52, 'c' => 68, 'f' => 12],
            ['id' => 3, 'name' => 'Batido de proteína con plátano', 'type' => 'Snack', 'calories' => 280, 'p' => 26, 'c' => 35, 'f' => 4]
        ];
    }

    // Calcular totales consumidos
    $totalCalories = 0;
    $totalP = 0;
    $totalC = 0;
    $totalF = 0;
    foreach ($meals as $m) {
        $totalCalories += $m['calories'];
        $totalP += $m['p'];
        $totalC += $m['c'];
        $totalF += $m['f'];
    }

    apiSuccess([
        'date' => $today,
        'waterMl' => $waterMl,
        'waterTargetMl' => $waterTargetMl,
        'targets' => [
            'calories' => $caloriesTarget,
            'proteinG' => 150,
            'carbsG' => 230,
            'fatsG' => 65,
            'waterMl' => $waterTargetMl
        ],
        'consumed' => [
            'calories' => $totalCalories,
            'proteinG' => round($totalP, 1),
            'carbsG' => round($totalC, 1),
            'fatsG' => round($totalF, 1),
            'waterMl' => $waterMl
        ],
        'meals' => $meals,
        'dbConnected' => $dbAvailable
    ], 'Datos de nutrición obtenidos correctamente');
}

// ==============================================================================
// 2. POST: Acciones de ajuste de agua y registro de comidas
// ==============================================================================
if ($method === 'POST') {
    $input = getJsonInput();
    $action = $_GET['action'] ?? $input['action'] ?? '';

    // AJUSTAR CONSUMO DE AGUA (+/- ml)
    if ($action === 'adjust_water') {
        $delta = (int)($input['delta'] ?? 0);
        $newWater = 1750;

        if ($dbAvailable) {
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
            'waterMl' => $newWater,
            'delta' => $delta,
            'dbConnected' => $dbAvailable
        ], 'Consumo de agua actualizado');
    }

    // REGISTRAR NUEVA COMIDA
    if ($action === 'add_meal') {
        $name = trim($input['name'] ?? 'Comida rápida');
        $type = trim($input['type'] ?? 'Snack');
        $p = max(0, (float)($input['p'] ?? 0));
        $c = max(0, (float)($input['c'] ?? 0));
        $f = max(0, (float)($input['f'] ?? 0));

        // Cálculo Atwater 4-4-9 si calorías vienen en 0
        $cal = (int)($input['calories'] ?? 0);
        if ($cal <= 0) {
            $cal = (int)round(($p * 4) + ($c * 4) + ($f * 9));
        }

        $insertedId = null;

        if ($dbAvailable) {
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
                'id' => $insertedId ?: time(),
                'name' => $name,
                'type' => $type,
                'calories' => $cal,
                'p' => $p,
                'c' => $c,
                'f' => $f
            ],
            'dbConnected' => $dbAvailable
        ], 'Comida registrada con éxito');
    }

    // ELIMINAR COMIDA REGISTRADA
    if ($action === 'delete_meal') {
        $mealId = (int)($input['meal_id'] ?? 0);
        if ($dbAvailable && $mealId > 0) {
            $conn->query("DELETE FROM logged_meals WHERE id = $mealId");
        }
        apiSuccess(['deletedId' => $mealId], 'Comida eliminada');
    }
}

apiError('Acción no soportada o método no válido', 400);
