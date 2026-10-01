<?php
session_start();
include("kynap_db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Este correo electrónico ya está registrado. Por favor, intenta con otro correo.";
        header("Location: registro.php");
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO accounts (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $password);

        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;

            $user_id = $conn->insert_id; 

            $_SESSION['user_id'] = $user_id;

            header("Location: formNutricion.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error al registrar la cuenta: " . $stmt->error;
            header("Location: registro.php");
            exit();
        }
    }

    $stmt->close();
    $conn->close();
}
?>
