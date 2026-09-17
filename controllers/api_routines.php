<?php
// controllers/api_routines.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Conexión tolerante a fallos
$host = "localhost";
$usuario = "root";
$password = "123456789";
$dbname = "kynap_db";

$conn = @new mysqli($host, $usuario, $password, $dbname);
$dbAvailable = !$conn->connect_error;

$userId = $_SESSION['user_id'] ?? 1;

if ($dbAvailable) {
    // Asegurar tabla de check-ins de rutina
    $tableSql = "CREATE TABLE IF NOT EXISTS routine_check_ins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        day_id VARCHAR(50) NOT NULL,
        check_in_date DATE NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_day_date (user_id, day_id, check_in_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($tableSql);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener check-ins de la semana actual
if ($method === 'GET') {
    $now = new DateTime();
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");

    $completedDays = [];

    if ($dbAvailable) {
        $stmt = $conn->prepare("SELECT day_id FROM routine_check_ins WHERE user_id = ? AND week_number = ? AND year = ? AND is_completed = 1");
        $stmt->bind_param("iii", $userId, $week, $year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $completedDays[] = $row['day_id'];
        }
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'week' => $week,
        'year' => $year,
        'completedDays' => $completedDays,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

// POST: Alternar o registrar check-in (Tachado de día)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $dayId = trim($input['day_id'] ?? '');

    if (empty($dayId)) {
        echo json_encode(['success' => false, 'message' => 'day_id requerido']);
        exit;
    }

    $now = new DateTime();
    $today = $now->format("Y-m-d");
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");

    $isCompleted = true;

    if ($dbAvailable) {
        // Verificar si ya existe para alternar
        $checkStmt = $conn->prepare("SELECT is_completed FROM routine_check_ins WHERE user_id = ? AND day_id = ? AND check_in_date = ?");
        $checkStmt->bind_param("iss", $userId, $dayId, $today);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        if ($row = $res->fetch_assoc()) {
            // Alternar estado
            $newStatus = $row['is_completed'] ? 0 : 1;
            $isCompleted = ($newStatus === 1);
            $updateStmt = $conn->prepare("UPDATE routine_check_ins SET is_completed = ? WHERE user_id = ? AND day_id = ? AND check_in_date = ?");
            $updateStmt->bind_param("iiss", $newStatus, $userId, $dayId, $today);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Insertar nuevo check-in
            $insertStmt = $conn->prepare("INSERT INTO routine_check_ins (user_id, day_id, check_in_date, week_number, year, is_completed) VALUES (?, ?, ?, ?, ?, 1)");
            $insertStmt->bind_param("issii", $userId, $dayId, $today, $week, $year);
            $insertStmt->execute();
            $insertStmt->close();
            $isCompleted = true;
        }
        $checkStmt->close();
    }

    echo json_encode([
        'success' => true,
        'day_id' => $dayId,
        'isCompleted' => $isCompleted,
        'date' => $today,
        'dbConnected' => $dbAvailable
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no soportado']);
