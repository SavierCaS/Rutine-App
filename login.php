<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - LOGIN CONTROLLER & VIEW
 * ==============================================================================
 * Maneja el flujo de inicio de sesión seguro para la plataforma web y la
 * aplicación móvil. Utiliza sentencias preparadas contra inyección SQL,
 * verificación criptográfica con BCRYPT y emisión de tokens HMAC.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 2.6.0
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

// Si el usuario ya tiene sesión iniciada, redirigir al panel de control
if (isset($_SESSION['user_id']) && isset($_SESSION['nombre'])) {
    header('Location: dashboard.php');
    exit();
}

$errorMessage = '';
$showRegisterPrompt = false;
$successToken = null;
$redirectTarget = 'dashboard.php';

// Procesamiento de formulario de acceso tradicional
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');

    if (!$email || empty($password)) {
        $errorMessage = 'Por favor ingresa un correo electrónico válido y tu contraseña.';
    } elseif (!$dbAvailable) {
        $errorMessage = 'El servidor de base de datos no está disponible. Intenta de nuevo más tarde.';
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, email, password, imagen_user, chat_activado FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Establecer sesión segura
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['nombre'] = $row['nombre'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['imagen_user'] = $row['imagen_user'] ?? 'iconos/profile.svg';

                    // Generar Bearer Token para sincronización con app móvil y localStorage
                    $successToken = generateAuthToken((int)$row['id'], $row['email']);

                    // Redirección con guardado de token en cliente
                    echo "<!DOCTYPE html><html><body>";
                    echo "<script>";
                    echo "localStorage.setItem('kynap_auth_token', " . json_encode($successToken) . ");";
                    echo "window.location.href = " . json_encode($redirectTarget) . ";";
                    echo "</script>";
                    echo "</body></html>";
                    exit();
                } else {
                    $errorMessage = 'Contraseña incorrecta. Por favor verifica tus credenciales.';
                    $showRegisterPrompt = false;
                }
            } else {
                $errorMessage = 'No existe ninguna cuenta registrada con este correo electrónico. Debes crear una cuenta antes de acceder.';
                $showRegisterPrompt = true;
            }
            $stmt->close();
        } else {
            $errorMessage = 'Error interno del servidor. Por favor intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a0d14">
  <title>Iniciar Sesión - Kynap AI</title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">
  <script src="public/js/theme.js"></script>

  <style>
    .auth-page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background-color: var(--bg-primary);
      position: relative;
    }

    .theme-top-bar {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 10;
    }

    .auth-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 40px 32px;
      width: 100%;
      max-width: 420px;
      box-shadow: var(--shadow-card);
      animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .auth-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }

    .auth-brand .brand-logo {
      width: 48px;
      height: 48px;
      font-size: 26px;
    }

    .auth-brand h1 {
      font-family: var(--font-heading);
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .auth-brand p {
      font-size: 13px;
      color: var(--text-secondary);
      margin-top: -6px;
    }

    .auth-alert-error {
      background: rgba(255, 71, 87, 0.12);
      border: 1px solid rgba(255, 71, 87, 0.35);
      color: var(--accent-rose);
      padding: 14px 16px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin-bottom: 20px;
      line-height: 1.4;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .btn-register-cta-banner {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--accent-emerald);
      color: #000;
      font-weight: 700;
      font-size: 13px;
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      text-decoration: none;
      margin-top: 4px;
      transition: all var(--transition-fast);
    }
    .btn-register-cta-banner:hover {
      filter: brightness(1.1);
      transform: translateY(-1px);
    }

    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .password-wrapper input {
      width: 100%;
      padding-right: 42px;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: var(--text-secondary);
      font-size: 16px;
      cursor: pointer;
      padding: 4px;
    }

    .auth-footer-links {
      margin-top: 24px;
      text-align: center;
      font-size: 13px;
      color: var(--text-secondary);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .auth-footer-links a {
      color: var(--accent-emerald);
      text-decoration: none;
      font-weight: 600;
    }

    .auth-footer-links a:hover {
      text-decoration: underline;
    }

    .back-to-home {
      color: var(--text-muted) !important;
      font-size: 12px;
    }
  </style>
</head>
<body class="auth-page">

  <div class="theme-top-bar">
    <button type="button" class="btn-theme-toggle" onclick="toggleTheme()" aria-label="Cambiar tema">🌙</button>
  </div>

  <main class="auth-card" role="main">
    <div class="auth-brand">
      <div class="brand-logo">K</div>
      <h1>Kynap <span>AI</span></h1>
      <p>Ingresa a tu cuenta para continuar</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
      <div class="auth-alert-error" role="alert">
        <div style="display: flex; gap: 8px; align-items: flex-start;">
          <span>⚠</span>
          <div><?php echo htmlspecialchars($errorMessage); ?></div>
        </div>
        <?php if (!empty($showRegisterPrompt)): ?>
          <a href="registro.php?email=<?php echo urlencode($_POST['email'] ?? ''); ?>" class="btn-register-cta-banner">
            Crear Cuenta Nueva ➔
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div id="loginClientAlert" class="auth-alert-error" style="display: none;" role="alert"></div>

    <form id="loginForm" onsubmit="handleLogin(event)" method="POST" action="login.php" class="auth-form" novalidate>
      <div class="form-group" style="margin-bottom: 0;">
        <label for="emailInput">Correo Electrónico</label>
        <input 
          type="email" 
          id="emailInput" 
          name="email" 
          class="input-custom" 
          placeholder="tu_correo@ejemplo.com" 
          value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
          required 
          autocomplete="email"
        >
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <label for="passwordInput">Contraseña</label>
        </div>
        <div class="password-wrapper">
          <input 
            type="password" 
            id="passwordInput" 
            name="password" 
            class="input-custom" 
            placeholder="••••••••" 
            required 
            autocomplete="current-password"
          >
          <button type="button" class="password-toggle" onclick="togglePasswordVisibility()" aria-label="Mostrar u ocultar contraseña">
            👁
          </button>
        </div>
      </div>

      <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 12px; font-size: 15px; margin-top: 6px;">
        Iniciar Sesión ➔
      </button>
    </form>

    <div class="auth-footer-links">
      <p>¿Aún no tienes una cuenta? <a href="registro.php">Regístrate gratis</a></p>
      <a href="index.html" class="back-to-home">← Volver a la página principal</a>
    </div>
  </main>

  <script>
    function togglePasswordVisibility() {
      const input = document.getElementById('passwordInput');
      input.type = input.type === 'password' ? 'text' : 'password';
    }

    async function handleLogin(e) {
      if (e) e.preventDefault();
      const alertBox = document.getElementById('loginClientAlert');
      alertBox.style.display = 'none';
      alertBox.innerHTML = '';

      const email = document.getElementById('emailInput').value.trim();
      const password = document.getElementById('passwordInput').value;

      if (!email || !password) {
        alertBox.innerHTML = '<span>⚠</span><div>Por favor completa todos los campos.</div>';
        alertBox.style.display = 'flex';
        return;
      }

      try {
        const res = await fetch('controllers/api_auth.php?action=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        const data = await res.json();
        if (data.success && data.data?.token) {
          localStorage.setItem('kynap_auth_token', data.data.token);
          window.location.href = 'dashboard.php';
          return;
        } else {
          // Bloqueo estricto si no existe o clave errónea
          let promptHtml = '';
          if (data.data?.suggest_register || data.status === 404) {
            promptHtml = `<a href="registro.php?email=${encodeURIComponent(email)}" class="btn-register-cta-banner">Crear Cuenta Nueva ➔</a>`;
          }
          alertBox.innerHTML = `<div style="display:flex;gap:8px;align-items:flex-start;"><span>⚠</span><div>${data.message || 'Credenciales incorrectas o usuario no registrado.'}</div></div>${promptHtml}`;
          alertBox.style.display = 'flex';
        }
      } catch (err) {
        // En caso de fallo de red, enviar formulario tradicional PHP
        document.getElementById('loginForm').submit();
      }
    }
  </script>
</body>
</html>
