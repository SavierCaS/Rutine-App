<?php
// controllers/api_nutrition.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$host = "localhost";
$usuario = "root";
$password = "123456789";
$dbname = "kynap_db";

$conn = @new mysqli($host, $usuario, $password, $dbname);
$dbAvailable = !$conn->connect_error;

$userId = $_SESSION['user_id'] ?? 1;

if ($dbAvailable) {
    // Tablas de nutrición diaria y comidas
    $conn->query("CREATE TABLE IF NOT EXISTS daily_nutrition_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        log_date DATE NOT NULL,
        calories_target INT DEFAULT 2200,
        water_ml INT DEFAULT 0,
        water_target_ml INT DEFAULT 2500,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_date (user_id, log_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS logged_meals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nutrition_log_id INT NOT NULL,
        food_name VARCHAR(150) NOT NULL,
        category VARCHAR(30) DEFAULT 'Snack',
        calories INT NOT NULL,
        protein_g NUMERIC(5,1) DEFAULT 0,
        carbs_g NUMERIC(5,1) DEFAULT 0,
        fats_g NUMERIC(5,1) DEFAULT 0,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$method = $_SERVER['REQUEST_METHOD'];
$today = date('Y-m-d');

if ($method === 'GET') {
    $water = 1750;
    $meals = [];

    if ($dbAvailable) {
        $res = $conn->query("SELECT id, water_ml FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $water = (int)$row['water_ml'];
            $logId = (int)$row['id'];
            $mealsRes = $conn->query("SELECT food_name AS name, category AS type, calories, protein_g AS p, carbs_g AS c, fats_g AS f FROM logged_meals WHERE nutrition_log_id = $logId ORDER BY id DESC");
            while ($m = $mealsRes->fetch_assoc()) {
                $meals[] = $m;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'date' => $today,
        'waterMl' => $water,
        'meals' => $meals,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'adjust_water') {
        $delta = (int)($input['delta'] ?? 0);
        $newWater = 0;

        if ($dbAvailable) {
            $conn->query("INSERT INTO daily_nutrition_logs (user_id, log_date, water_ml) VALUES ($userId, '$today', GREATEST(0, $delta))
                          ON DUPLICATE KEY UPDATE water_ml = GREATEST(0, water_ml + $delta)");
            $res = $conn->query("SELECT water_ml FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today'");
            if ($res && $row = $res->fetch_assoc()) {
                $newWater = (int)$row['water_ml'];
            }
        }

        echo json_encode([
            'success' => true,
            'waterMl' => $newWater,
            'dbConnected' => $dbAvailable
        ]);
        exit;
    }

    if ($action === 'add_meal') {
        $name = trim($input['name'] ?? 'Comida');
        $type = trim($input['type'] ?? 'Snack');
        $p = max(0, (float)($input['p'] ?? 0));
        $c = max(0, (float)($input['c'] ?? 0));
        $f = max(0, (float)($input['f'] ?? 0));
        // Auto-cálculo 4-4-9 si calorías vienen en 0
        $cal = (int)($input['calories'] ?? 0);
        if ($cal <= 0) {
            $cal = (int)round($p * 4 + $c * 4 + $f * 9);
        }

        if ($dbAvailable) {
            $conn->query("INSERT INTO daily_nutrition_logs (user_id, log_date) VALUES ($userId, '$today') ON DUPLICATE KEY UPDATE id=id");
            $logRes = $conn->query("SELECT id FROM daily_nutrition_logs WHERE user_id = $userId AND log_date = '$today' LIMIT 1");
            $logId = (int)$logRes->fetch_assoc()['id'];

            $nameSafe = $conn->real_escape_string($name);
            $typeSafe = $conn->real_escape_string($type);
            $conn->query("INSERT INTO logged_meals (nutrition_log_id, food_name, category, calories, protein_g, carbs_g, fats_g) 
                          VALUES ($logId, '$nameSafe', '$typeSafe', $cal, $p, $c, $f)");
        }

        echo json_encode([
            'success' => true,
            'meal' => [
                'name' => $name,
                'type' => $type,
                'calories' => $cal,
                'p' => $p,
                'c' => $c,
                'f' => $f
            ],
            'dbConnected' => $dbAvailable
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
