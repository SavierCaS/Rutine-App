<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - CONFIGURACIÓN DE CUENTA Y PERFIL
 * ==============================================================================
 * Permite al usuario actualizar su nombre, correo, contraseña, avatar y
 * métricas biométricas (peso, altura, edad, objetivo, actividad, agua) con
 * validaciones seguras y sincronización inmediata con la base de datos.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 2.5.0
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$statusMessage = '';
$isSuccess = false;

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $imagenUser = trim($_POST['imagen_user'] ?? 'iconos/profile.svg');
    $password = (string)($_POST['password'] ?? '');

    $peso = max(30, min(300, (float)($_POST['peso'] ?? 75)));
    $altura = max(100, min(250, (float)($_POST['altura'] ?? 175)));
    $edad = max(12, min(100, (int)($_POST['edad'] ?? 25)));
    $objetivo = trim($_POST['objetivo'] ?? 'ganar_musculo');
    $nivelActividad = trim($_POST['nivel_actividad'] ?? 'moderado');
    $agua = max(1, min(15, (int)($_POST['agua'] ?? 3)));

    if (empty($nombre)) {
        $statusMessage = 'El nombre no puede estar vacío.';
    } elseif (!$email) {
        $statusMessage = 'El correo electrónico no es válido.';
    } elseif (!$dbAvailable) {
        $statusMessage = 'Servidor de base de datos no disponible.';
    } else {
        // Verificar que el correo no esté ocupado por otra cuenta
        $checkStmt = $conn->prepare("SELECT id FROM accounts WHERE email = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $statusMessage = 'Este correo electrónico ya está en uso por otra cuenta.';
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // 1. Actualizar tabla accounts
            if (!empty($password) && strlen($password) >= 6) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $email, $imagenUser, $hashed, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nombre, $email, $imagenUser, $userId);
            }

            if ($stmt->execute()) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                $_SESSION['imagen_user'] = $imagenUser;
                $stmt->close();

                // 2. Actualizar tabla usuarios (biometría)
                $bioStmt = $conn->prepare("INSERT INTO usuarios (user_id, peso, altura, edad, objetivo, nivel_actividad, agua) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)
                                           ON DUPLICATE KEY UPDATE 
                                           peso = VALUES(peso), altura = VALUES(altura), edad = VALUES(edad), 
                                           objetivo = VALUES(objetivo), nivel_actividad = VALUES(nivel_actividad), 
                                           agua = VALUES(agua)");
                if ($bioStmt) {
                    $bioStmt->bind_param("iddiisi", $userId, $peso, $altura, $edad, $objetivo, $nivelActividad, $agua);
                    $bioStmt->execute();
                    $bioStmt->close();
                }

                $statusMessage = '¡Perfil y biometría actualizados con éxito!';
                $isSuccess = true;
            } else {
                $statusMessage = 'Error al actualizar: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// Cargar datos actuales del usuario
$nombre = $_SESSION['nombre'] ?? '';
$email = $_SESSION['email'] ?? '';
$imagenUser = $_SESSION['imagen_user'] ?? 'iconos/profile.svg';

$peso = 75;
$altura = 175;
$edad = 25;
$objetivo = 'ganar_musculo';
$nivelActividad = 'moderado';
$agua = 3;

if ($dbAvailable) {
    $stmt = $conn->prepare("SELECT nombre, email, imagen_user FROM accounts WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $nombre = $row['nombre'];
            $email = $row['email'];
            if (!empty($row['imagen_user'])) $imagenUser = $row['imagen_user'];
        }
        $stmt->close();
    }

    $bioStmt = $conn->prepare("SELECT peso, altura, edad, objetivo, nivel_actividad, agua FROM usuarios WHERE user_id = ? LIMIT 1");
    if ($bioStmt) {
        $bioStmt->bind_param("i", $userId);
        $bioStmt->execute();
        $bRes = $bioStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            $peso = (float)($bRow['peso'] ?: $peso);
            $altura = (float)($bRow['altura'] ?: $altura);
            $edad = (int)($bRow['edad'] ?: $edad);
            $objetivo = $bRow['objetivo'] ?: $objetivo;
            $nivelActividad = $bRow['nivel_actividad'] ?: $nivelActividad;
            $agua = (int)($bRow['agua'] ?: $agua);
        }
        $bioStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a0d14">
  <title>Configuración de Cuenta - Kynap AI</title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">

  <style>
    .config-page {
      min-height: 100vh;
      background-color: var(--bg-primary);
      padding: 32px 20px 80px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .config-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      width: 100%;
      max-width: 680px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
      animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .config-top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border-subtle);
    }

    .avatar-preview-wrapper {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
      padding: 16px;
      background: rgba(255, 255, 255, 0.03);
      border-radius: var(--radius-md);
      border: 1px solid var(--border-subtle);
    }

    .avatar-preview-img {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--accent-emerald);
      box-shadow: 0 0 16px var(--accent-emerald-glow);
    }

    .section-title-divider {
      font-family: var(--font-heading);
      font-size: 16px;
      color: var(--accent-cyan);
      margin: 20px 0 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid var(--border-subtle);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .grid-2-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    @media (max-width: 580px) {
      .grid-2-col {
        grid-template-columns: 1fr;
      }
      .config-card {
        padding: 24px 18px;
      }
    }
  </style>
</head>
<body class="config-page">

  <main class="config-card">
    <div class="config-top-bar">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 24px;">⚙️</span>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 800;">Ajustes de Perfil</h1>
      </div>
      <a href="dashboard.php" onclick="handleDashboardReturn(event)" class="btn-checkin" style="width: auto; padding: 6px 14px; font-size: 13px; text-decoration: none; background: rgba(255, 255, 255, 0.06); color: var(--text-primary);">
        ← Volver al Panel
      </a>
    </div>

    <?php if (!empty($statusMessage)): ?>
      <div style="background: <?php echo $isSuccess ? 'rgba(0, 255, 136, 0.12)' : 'rgba(255, 71, 87, 0.12)'; ?>; border: 1px solid <?php echo $isSuccess ? 'rgba(0, 255, 136, 0.35)' : 'rgba(255, 71, 87, 0.35)'; ?>; color: <?php echo $isSuccess ? 'var(--accent-emerald)' : '#ff8a93'; ?>; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 13px; font-weight: 600;">
        <?php echo htmlspecialchars($statusMessage); ?>
      </div>
    <?php endif; ?>

    <form id="configForm" method="POST" action="configuration.php" onsubmit="handleConfigSubmit(event)" style="display: flex; flex-direction: column; gap: 16px;">
      
      <!-- AVATAR ACTUAL Y ACCESO A SELECTOR -->
      <div class="avatar-preview-wrapper">
        <img src="<?php echo htmlspecialchars($imagenUser); ?>" alt="Avatar" class="avatar-preview-img" onerror="this.src='iconos/profile.svg'">
        <div>
          <h3 style="font-size: 15px; font-weight: 700;"><?php echo htmlspecialchars($nombre); ?></h3>
          <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">Avatar de atleta seleccionado</p>
          <a href="icon_select_user.php" onclick="if(window.location.protocol === 'file:' || window.location.port === '5500'){event.preventDefault(); window.location.href='app.html';}" style="font-size: 12px; color: var(--accent-emerald); text-decoration: none; font-weight: 600;">
            Cambiar avatar ➔
          </a>
        </div>
        <input type="hidden" name="imagen_user" value="<?php echo htmlspecialchars($imagenUser); ?>">
      </div>

      <!-- SECCIÓN 1: DATOS DE LA CUENTA -->
      <div class="section-title-divider">
        <span>👤</span> Credenciales de Acceso
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="nombre">Nombre Completo</label>
          <input type="text" id="nombre" name="nombre" class="input-custom" value="<?php echo htmlspecialchars($nombre); ?>" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="email">Correo Electrónico</label>
          <input type="email" id="email" name="email" class="input-custom" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
      </div>

      <div class="form-group" style="margin: 0;">
        <label for="password">Nueva Contraseña (Dejar en blanco para mantener la actual)</label>
        <input type="password" id="password" name="password" class="input-custom" placeholder="••••••••">
      </div>

      <!-- SECCIÓN 2: BIOMETRÍA Y METAS -->
      <div class="section-title-divider">
        <span>📊</span> Biometría & Metas Físicas
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="peso">Peso Corporal (kg)</label>
          <input type="number" id="peso" name="peso" class="input-custom" value="<?php echo $peso; ?>" step="0.5" min="30" max="300" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="altura">Altura (cm)</label>
          <input type="number" id="altura" name="altura" class="input-custom" value="<?php echo $altura; ?>" step="0.5" min="100" max="250" required>
        </div>
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="edad">Edad (Años)</label>
          <input type="number" id="edad" name="edad" class="input-custom" value="<?php echo $edad; ?>" min="12" max="100" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="agua">Consumo de Agua (L/día)</label>
          <input type="number" id="agua" name="agua" class="input-custom" value="<?php echo $agua; ?>" min="1" max="10" required>
        </div>
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="objetivo">Objetivo Físico</label>
          <select id="objetivo" name="objetivo" class="input-custom" required>
            <option value="ganar_musculo" <?php echo $objetivo === 'ganar_musculo' ? 'selected' : ''; ?>>Ganar masa muscular</option>
            <option value="bajar_peso" <?php echo ($objetivo === 'bajar_peso' || $objetivo === 'perder_peso') ? 'selected' : ''; ?>>Perder grasa corporal</option>
            <option value="mantener_peso" <?php echo $objetivo === 'mantener_peso' ? 'selected' : ''; ?>>Mantenimiento & Salud</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="nivel_actividad">Nivel de Actividad</label>
          <select id="nivel_actividad" name="nivel_actividad" class="input-custom" required>
            <option value="sedentario" <?php echo $nivelActividad === 'sedentario' ? 'selected' : ''; ?>>Sedentario</option>
            <option value="ligero" <?php echo $nivelActividad === 'ligero' ? 'selected' : ''; ?>>Ligero</option>
            <option value="moderado" <?php echo $nivelActividad === 'moderado' ? 'selected' : ''; ?>>Moderado</option>
            <option value="activo" <?php echo $nivelActividad === 'activo' ? 'selected' : ''; ?>>Activo</option>
          </select>
        </div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 12px;">
        <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 13px; font-size: 15px;">
          Guardar Cambios
        </button>
        <button type="button" class="btn-checkin" onclick="handleLogout(event)" style="background: rgba(255, 71, 87, 0.15); color: var(--accent-rose); width: auto; padding: 13px 20px;">
          Cerrar Sesión
        </button>
      </div>
    </form>
  </main>

  <script>
    function handleDashboardReturn(e) {
      const isStatic = window.location.protocol === 'file:' || window.location.port === '5500';
      if (isStatic) {
        if (e) e.preventDefault();
        window.location.href = 'app.html';
      }
    }

    function handleConfigSubmit(e) {
      const isStatic = window.location.protocol === 'file:' || window.location.port === '5500';
      if (isStatic) {
        if (e) e.preventDefault();
        window.location.href = 'app.html';
      }
    }

    function handleLogout(e) {
      if (e) e.preventDefault();
      localStorage.removeItem('kynap_auth_token');
      const isStatic = window.location.protocol === 'file:' || window.location.port === '5500';
      if (isStatic) {
        window.location.href = 'index.html';
      } else {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>
