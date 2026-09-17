<?php
session_start();
include("kynap_db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $edad = $_POST['edad'];
    $peso = $_POST['peso'];
    $altura = $_POST['altura'];
    $objetivo = $_POST['objetivo'];
    $nivel_actividad = $_POST['nivel_actividad'];
    $sexo = $_POST['sexo'];
    $condiciones = isset($_POST['condiciones']) ? implode(", ", $_POST['condiciones']) : ''; 
    $alergias = $_POST['alergias'];
    $preferencia = $_POST['preferencia'];
    $agua = $_POST['agua'];

    if (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];

        $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();

            $sql = "INSERT INTO usuarios (user_id, edad, peso, altura, objetivo, nivel_actividad, sexo, condiciones, alergias, preferencia, agua) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $conn->prepare($sql);
            $stmt_insert->bind_param("iissssssssi", $user_id, $edad, $peso, $altura, $objetivo, $nivel_actividad, $sexo, $condiciones, $alergias, $preferencia, $agua);

            if ($stmt_insert->execute()) {
                header("Location: icon_select_user.php");
                exit();
            } else {
                echo "Error al guardar los datos: " . $stmt_insert->error;
            }

            $stmt_insert->close();
        } else {
            echo "No se encontró el usuario con el correo proporcionado.";
        }

        $stmt->close();
    } else {
        echo "No se encontró el correo en la sesión. Por favor, vuelve a iniciar sesión.";
        header('Location: login.php'); 
        exit();
    }

    $conn->close();
}
?>
