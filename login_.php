
<?php
session_start();
include("kynap_db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $email = mysqli_real_escape_string($conn, $email);

    $sql = "SELECT * FROM accounts WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['user_id'] = $row['id'];  


            header("Location: loading.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Contraseña incorrecta.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Usuario no encontrado.";
        header("Location: login.php");
        exit();
    }

    $conn->close();
}
?>
