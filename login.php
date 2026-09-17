
<?php session_start(); ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kynap</title>
    <link rel="stylesheet" href="../public/style.css">
    <style>
     body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,rgb(252, 253, 253), #0056b3);
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    box-sizing: border-box;
    overflow-x: hidden;
}
.form-container {
    background-color: #0056b3;
    padding: 50px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 400px;
    color: white;
    text-align: center;
}
h2 {
    margin-bottom: 20px;
}
.form-style {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
label {
    text-align: left;
    font-weight: bold;
    margin-bottom: 5px;
}
input {
    padding: 10px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
}
button {
    background-color: #ffcc00;
    color: black;
    font-size: 18px;
    font-weight: bold;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}
button:hover {
    background-color: #e6b800;
}
.error-message {
    color: red;
    font-size: 14px;
    margin-top: 10px;
}
@media (max-width: 400px) {
    .form-container {
        padding: 30px 20px;
    }
    input, button {
        font-size: 14px;
        padding: 8px;
    }
}

    </style>
</head>
<body>
    <div class="form-container">
        <h2>Iniciar Sesión</h2>
        <form action="login_.php" method="POST" class="form-style">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar sesión</button>

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
