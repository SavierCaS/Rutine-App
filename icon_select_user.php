<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - SELECCIÓN DE AVATAR (icon_select_user.php)
 * ==============================================================================
 * Paso 3 del onboarding: permite al usuario seleccionar un avatar para su perfil
 * y culmina el flujo de registro redirigiendo directamente a dashboard.php.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $imagenUser = trim($_POST['imagen_user'] ?? 'iconos/profile.svg');

    if ($dbAvailable) {
        $stmt = $conn->prepare("UPDATE accounts SET imagen_user = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $imagenUser, $userId);
            if ($stmt->execute()) {
                $_SESSION['imagen_user'] = $imagenUser;
                $stmt->close();
                header("Location: dashboard.php");
                exit();
            } else {
                $errorMessage = "Error al guardar el avatar: " . $stmt->error;
                $stmt->close();
            }
        }
    } else {
        $_SESSION['imagen_user'] = $imagenUser;
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a0d14">
  <title>Paso 3: Elige tu Avatar - Kynap AI</title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">

  <style>
    .avatar-page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      background-color: var(--bg-primary);
    }

    .avatar-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      width: 100%;
      max-width: 640px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
      animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .step-pill {
      display: inline-block;
      padding: 4px 12px;
      border-radius: var(--radius-full);
      background: rgba(0, 255, 136, 0.15);
      color: var(--accent-emerald);
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .avatar-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
      gap: 16px;
      margin: 24px 0;
    }

    .avatar-option {
      cursor: pointer;
      display: block;
      position: relative;
    }

    .avatar-option input[type="radio"] {
      position: absolute;
      opacity: 0;
    }

    .avatar-img {
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: var(--radius-md);
      object-fit: cover;
      border: 2px solid var(--border-subtle);
      transition: var(--transition-normal);
      display: block;
    }

    .avatar-option input[type="radio"]:checked + .avatar-img {
      border-color: var(--accent-emerald);
      transform: scale(1.08);
      box-shadow: 0 0 18px var(--accent-emerald-glow);
    }

    .avatar-option:hover .avatar-img {
      border-color: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }
  </style>
</head>
<body class="avatar-page">

  <main class="avatar-card">
    <div style="text-align: center;">
      <span class="step-pill">PASO 3 DE 3</span>
      <h1 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800;">Elige tu Avatar de Atleta</h1>
      <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
        Personaliza tu perfil para la comunidad y panel de control.
      </p>
    </div>

    <?php if (!empty($errorMessage)): ?>
      <div style="background: rgba(255, 71, 87, 0.12); border: 1px solid rgba(255, 71, 87, 0.35); color: #ff8a93; padding: 12px; border-radius: var(--radius-sm); margin: 16px 0; font-size: 13px;">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <form id="avatarForm" method="POST" action="icon_select_user.php" onsubmit="handleAvatarSubmit(event)">
      <div class="avatar-grid">
        <?php
        $iconos = [
            'Boy.jpg', 'Girl.jpg', 'Hombre.jpg', 'Mujer.jpg', 'BlueMenAdmin.jpg',
            'Men_Worked.jpg', 'WomenWorked.jpg', 'Old_Man.jpg', 'Old_Woman.jpg', 'MapacheAdm.jpg',
            'Skull_Boy.jpg', 'Skull_Girl.jpg', 'King.jpg', 'Queen.jpg', 'Gygamen_Stone.jpg'
        ];

        $currentIcon = $_SESSION['imagen_user'] ?? 'icon_users/Boy.jpg';

        foreach ($iconos as $idx => $icono) {
            $path = "icon_users/{$icono}";
            $isChecked = ($currentIcon === $path || ($idx === 0 && !isset($_SESSION['imagen_user']))) ? 'checked' : '';
            echo "<label class='avatar-option'>";
            echo   "<input type='radio' name='imagen_user' value='{$path}' {$isChecked} required>";
            echo   "<img src='{$path}' alt='Avatar {$icono}' class='avatar-img' onerror=\"this.src='iconos/profile.svg'\">";
            echo "</label>";
        }
        ?>
      </div>

      <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 13px; font-size: 15px; margin-top: 8px;">
        Completar Registro & Ir al Panel 🚀
      </button>
    </form>
  </main>

  <script>
    function handleAvatarSubmit(e) {
      const isStatic = window.location.protocol === 'file:' || window.location.port === '5500';
      if (isStatic) {
        if (e) e.preventDefault();
        window.location.href = 'app.html';
      }
    }
  </script>
</body>
</html>
