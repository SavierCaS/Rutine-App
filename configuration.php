<?php
session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: login.php');
    exit();
}

include('kynap_db.php');

$user_id = $_SESSION['user_id'] ?? null;
$nombre = $_SESSION['nombre'] ?? '';
$email = $_SESSION['email'] ?? '';
$imagen_user = $_SESSION['imagen_user'] ?? '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT nombre, email, imagen_user FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $nombre = $row['nombre'];
        $email = $row['email'];
        $imagen_user = $row['imagen_user'];
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kynap - Configuración</title>
    <link rel="stylesheet" href="style-config.css" />
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="calendar-init.js"></script>
    <style>
        .config-container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: #333;
        }
        .config-container h2 {
            margin-bottom: 20px;
            font-weight: 700;
            color: #0072ff;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        label {
            font-weight: 600;
            margin-bottom: 6px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        input[type="password"] {
            letter-spacing: 0.1em;
        }
        .icon-preview {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 12px;
            border: 2px solid #0072ff;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .icon-grid label {
            cursor: pointer;
            display: block;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
        }
        .icon-grid input[type="radio"]:checked + img {
            border: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid input[type="radio"]:checked + img {
            outline: none;
        }

        .icon-grid label:has(input[type="radio"]:checked) {
            border: 3px solid #0072ff;
        }

        .icon-grid img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            display: block;
        }
        .icon-grid input[type="radio"]:checked + img {
            border: 3px solid #0072ff;
        }
        button {
            padding: 12px;
            border: none;
            background-color: #0072ff;
            color: white;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #005bb5;
        }
        .error-message, .success-message {
            text-align: center;
            font-weight: 600;
        }
        .error-message {
            color: #ff4d4d;
        }
        .success-message {
            color: #28a745;
        }
    </style>
</head>
<body>
<button class="menu-toggle" onclick="toggleSidebar()">☰</button>

<div class="sidebar">
    <h2 class="logo"><span class="kynap">Kynap</span> <span class="ai">AI</span></h2>
    <hr class="divider" />
    <nav>
        <ul>
            <li onclick="window.location.href='dashboard.php'"><img src="iconos/home.svg" class="icon" >Inicio</li>
            <hr class="divider" />
            <li class="active"><img src="iconos/settings.svg" class="icon"> Configuración</li>
            <li class="logout-item">
                <a href="logout.php" class="logout-link">
                    <img src="iconos/close.svg" class="icon" /> Cerrar sesión
                </a>
            </li>
        </ul>
    </nav>
</div>

<div class="main-content">
    <div class="config-container">
        <h2>Configuración de Perfil</h2>

        <?php
        if (isset($_SESSION['config_message'])) {
            $msg = $_SESSION['config_message'];
            $cls = $_SESSION['config_success'] ? 'success-message' : 'error-message';
            echo "<p class='{$cls}'>{$msg}</p>";
            unset($_SESSION['config_message'], $_SESSION['config_success']);
        }
        ?>

        <form action="update_profile.php" method="POST">
            <label for="imagen_user">Foto de perfil</label>
            <div class="icon-grid">
                <?php
                $iconos = [
                    'Girl.jpg', 'Boy.jpg', 'Hombre.jpg','Mujer.jpg', 'BlueMenAdmin.jpg',
                    'Men_Worked.jpg', 'WomenWorked.jpg', 'Old_Man.jpg', 'Old_Woman.jpg', 'MapacheAdm.jpg',
                    'Skull_Boy.jpg', 'Skull_Girl.jpg', 'King.jpg','Queen.jpg', 'Gygamen_Stone.jpg'
                ];
                foreach ($iconos as $icono) {
                    $path = "icon_users/{$icono}";
                    $checked = ($imagen_user === $path) ? 'checked' : '';
                    echo "<label>";
                    echo "<input type='radio' name='imagen_user' value='{$path}' {$checked} required>";
                    echo "<img src='{$path}' alt='Icono'>";
                    echo "</label>";
                }
                ?>
            </div>

            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

            <label for="password">Nueva contraseña <small>(dejar vacío para no cambiar)</small></label>
            <input type="password" id="password" name="password" placeholder="Nueva contraseña">

            <button type="submit">Guardar cambios</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('sidebar-open');
}
</script>

</body>
</html>

