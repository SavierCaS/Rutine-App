<?php
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $password]);

    header("Location: ../views/formNutricion.php");
    exit();
}
?>
