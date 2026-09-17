<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../kynap_db.php';

// Obtener o inferir ID de usuario desde la sesión
$userId = $_SESSION['user_id'] ?? null;
if (!$userId && isset($_SESSION['email'])) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $res = $conn->query("SELECT id FROM accounts WHERE email = '$email' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $userId = $row['id'];
        $_SESSION['user_id'] = $userId;
    }
}

// Si no hay sesión activa en testing, usar ID 1 como fallback seguro
if (!$userId) {
    $userId = 1;
}

// Asegurar que la tabla de progreso exista
$tableSql = "CREATE TABLE IF NOT EXISTS progreso_rutinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dia VARCHAR(50) NOT NULL,
    completado TINYINT(1) DEFAULT 1,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_dia (user_id, dia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($tableSql);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $dia = strtolower(trim($input['day_id'] ?? ''));

    if (empty($dia)) {
        echo json_encode(['success' => false, 'message' => 'Día no especificado']);
        exit;
    }

    $diaSafe = mysqli_real_escape_string($conn, $dia);
    $saveSql = "INSERT INTO progreso_rutinas (user_id, dia, completado) 
                VALUES ($userId, '$diaSafe', 1) 
                ON DUPLICATE KEY UPDATE completado = 1, fecha_actualizacion = NOW()";

    if ($conn->query($saveSql)) {
        // Calcular porcentaje sobre 7 días estándar
        $countRes = $conn->query("SELECT COUNT(*) AS total FROM progreso_rutinas WHERE user_id = $userId AND completado = 1");
        $completedCount = $countRes ? (int)$countRes->fetch_assoc()['total'] : 1;
        $progressPercentage = min(100, round(($completedCount / 7) * 100));

        echo json_encode([
            'success' => true,
            'completedDay' => $dia,
            'completedCount' => $completedCount,
            'progressPercentage' => $progressPercentage
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($method === 'GET') {
    $query = $conn->query("SELECT dia FROM progreso_rutinas WHERE user_id = $userId AND completado = 1");
    $days = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $days[] = strtolower($row['dia']);
        }
    }

    $completedCount = count($days);
    $progressPercentage = min(100, round(($completedCount / 7) * 100));

    echo json_encode([
        'success' => true,
        'completedDays' => $days,
        'progressPercentage' => $progressPercentage
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no soportado']);
