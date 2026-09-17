<?php
// controllers/api_workouts.php
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
    // Tablas de entrenamiento y series
    $conn->query("CREATE TABLE IF NOT EXISTS workout_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        workout_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'in_progress',
        total_volume_kg NUMERIC(10,2) DEFAULT 0,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS workout_sets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workout_log_id INT NOT NULL,
        exercise_id VARCHAR(50) NOT NULL,
        set_number INT NOT NULL,
        weight_kg NUMERIC(6,2) NOT NULL DEFAULT 0,
        reps INT NOT NULL DEFAULT 0,
        rpe NUMERIC(3,1) DEFAULT 8.0,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? 'current';

if ($action === 'current') {
    // Retorna la sesión activa o vacía
    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

if ($action === 'save_set') {
    $exerciseId = trim($input['exercise_id'] ?? 'ex-squats');
    $setNumber = (int)($input['set_number'] ?? 1);
    $weightKg = max(0, (float)($input['weight_kg'] ?? 0));
    $reps = max(0, (int)($input['reps'] ?? 0));
    $rpe = (float)($input['rpe'] ?? 8.0);
    $isCompleted = !empty($input['is_completed']) ? 1 : 0;

    $savedId = null;

    if ($dbAvailable) {
        $today = date('Y-m-d');
        // Obtener o crear workout_log activo
        $res = $conn->query("SELECT id FROM workout_logs WHERE user_id = $userId AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $workoutLogId = (int)$row['id'];
        } else {
            $conn->query("INSERT INTO workout_logs (user_id, workout_date, status) VALUES ($userId, '$today', 'in_progress')");
            $workoutLogId = $conn->insert_id;
        }

        // Insertar o actualizar serie
        $stmt = $conn->prepare("INSERT INTO workout_sets (workout_log_id, exercise_id, set_number, weight_kg, reps, rpe, is_completed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isididi", $workoutLogId, $exerciseId, $setNumber, $weightKg, $reps, $rpe, $isCompleted);
        $stmt->execute();
        $savedId = $conn->insert_id;
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'savedId' => $savedId,
        'exercise_id' => $exerciseId,
        'set_number' => $setNumber,
        'weight_kg' => $weightKg,
        'reps' => $reps,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

if ($action === 'complete_workout') {
    $totalVolume = max(0, (float)($input['total_volume_kg'] ?? 0));
    $notes = trim($input['notes'] ?? '');

    if ($dbAvailable) {
        $notesSafe = $conn->real_escape_string($notes);
        $conn->query("UPDATE workout_logs SET status = 'completed', total_volume_kg = $totalVolume, notes = '$notesSafe' WHERE user_id = $userId AND status = 'in_progress'");
    }

    echo json_encode([
        'success' => true,
        'status' => 'completed',
        'totalVolume' => $totalVolume,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
