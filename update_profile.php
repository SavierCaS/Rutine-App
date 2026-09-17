<?php
session_start();
include('kynap_db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $imagen_user = $_POST['imagen_user'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['config_message'] = "El correo electrónico ya está en uso por otro usuario.";
        $_SESSION['config_success'] = false;
        header('Location: config.php');
        exit();
    }
    $stmt->close();

    if (!empty($password)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $email, $imagen_user, $password_hashed, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $email, $imagen_user, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['imagen_user'] = $imagen_user;
        $_SESSION['config_message'] = "Perfil actualizado con éxito.";
        $_SESSION['config_success'] = true;
    } else {
        $_SESSION['config_message'] = "Error al actualizar perfil: " . $stmt->error;
        $_SESSION['config_success'] = false;
    }

    $stmt->close();
    $conn->close();

    header('Location: configuration.php');
    exit();
} else {
    header('Location: configuration.php');
    exit();
}
?>
