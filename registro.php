<?php session_start(); ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Kynap</title>
    <link rel="stylesheet" href="../public/style.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg,rgb(252, 253, 253), #0056b3);
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .form-container {
        background-color: #0056b3;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 95%;
        max-width: 400px;
        color: white;
        text-align: center;
        box-sizing: border-box;
    }
    h2 {
        margin-bottom: 25px;
        font-weight: 700;
        font-size: 1.8rem;
    }
    .form-style {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    input {
        padding: 12px 15px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        outline-offset: 2px;
        outline-color: #0072ff;
    }
    input:focus {
        outline-style: solid;
    }
    button {
        background-color: #ffcc00;
        color: black;
        font-size: 18px;
        font-weight: bold;
        padding: 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    button:hover {
        background-color: #e6b800;
    }
    .error-message {
        color: #ff4d4d;
        font-size: 14px;
        margin-top: 10px;
        font-weight: 600;
    }
    @media (max-width: 400px) {
        .form-container {
            padding: 30px 15px;
        }
        h2 {
            font-size: 1.5rem;
        }
        input {
            font-size: 14px;
            padding: 10px 12px;
        }
        button {
            font-size: 16px;
            padding: 10px;
        }
    }
</style>

</head>
<body>
    <div class="form-container">
        <h2>Registro</h2>
        <form action="registro_cuenta.php" method="POST" class="form-style">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Registrarse</button>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div id="errorMessage" class="error-message">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
