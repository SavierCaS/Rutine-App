<?php
session_start(); 

if (!isset($_SESSION['nombre'])) {
    header('Location: login.php'); 
    exit();
}

if (isset($_SESSION['nombre'])) {
    $user_name = $_SESSION['nombre']; 
    $user_name = ucwords(strtolower($user_name));
} else {
    $user_name = "Usuario";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kynap - Creando tu dieta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg,rgb(252, 253, 253), #0056b3);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            overflow: hidden;
        }

        .loading-container {
            text-align: center;
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 400px;
            opacity: 1;
            transition: opacity 1s ease-in-out;
        }

        .loading-text {
            font-size: 24px;
            color: #1d72b8;
            margin-bottom: 20px;
        }

        .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #ffb400;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .user-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .fade-out {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="loading-container" id="loadingContainer">
        <div class="loading-text">Cargando Panel de control...</div>
        <div class="loader"></div>
        <div class="user-name" id="userNamePlaceholder"><?php echo "Bienvenido " . $user_name; ?></div>
    </div>

    <script>
        setTimeout(() => {
            const loadingContainer = document.getElementById('loadingContainer');
            loadingContainer.classList.add('fade-out');
            
            setTimeout(() => {
                window.location.href = 'dashboard.php';  
            }, 1000); 
        }, 5000); 
    </script>
</body>
</html>
