<?php
session_start();
include("kynap_db.php");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id'] ?? 0);

if ($user_id > 0 && $_SESSION['user_id'] == $user_id) {
    $stmt = $conn->prepare("UPDATE accounts SET chat_activado = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
}
?>
