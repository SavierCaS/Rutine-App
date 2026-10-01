<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - REGISTRO CONTROLLER & VIEW
 * ==============================================================================
 * Maneja la creación de nuevas cuentas de usuario de manera segura.
 * Valida formatos, previene cuentas duplicadas mediante sentencias preparadas,
 * genera hash BCRYPT y calcula metas de macronutrientes personalizadas.
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
$redirectTarget = 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Datos personales y credenciales
    $nombre = trim($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // 2. Parámetros biométricos
    $edad = max(12, min(100, (int)($_POST['edad'] ?? 25)));
    $peso = max(30.0, min(300.0, (float)($_POST['peso'] ?? 70.0)));
    $altura = max(100.0, min(250.0, (float)($_POST['altura'] ?? 170.0)));
    $sexo = in_array($_POST['sexo'] ?? '', ['masculino', 'femenino', 'otro']) ? $_POST['sexo'] : 'masculino';
    $objetivo = in_array($_POST['objetivo'] ?? '', ['ganar_musculo', 'perder_grasa', 'mantener']) ? $_POST['objetivo'] : 'ganar_musculo';
    $nivelActividad = in_array($_POST['nivel_actividad'] ?? '', ['sedentario', 'ligero', 'moderado', 'muy_activo']) ? $_POST['nivel_actividad'] : 'moderado';

    if (empty($nombre)) {
        $errorMessage = 'Por favor ingresa tu nombre completo.';
    } elseif (!$email) {
        $errorMessage = 'Por favor ingresa un correo electrónico válido.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'La contraseña debe contener al menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Las contraseñas no coinciden. Por favor verifícalas.';
    } elseif (!$dbAvailable) {
        $errorMessage = 'Servidor de base de datos no disponible. Por favor intenta más tarde.';
    } else {
        // 1. Verificar si el correo ya está registrado en la tabla users
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $errorMessage = 'Este correo electrónico ya se encuentra registrado. Por favor inicia sesión.';
                $checkStmt->close();
            } else {
                $checkStmt->close();

                // 2. Hashear contraseña de forma segura con BCRYPT (coste 12)
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $defaultAvatar = 'iconos/profile.svg';
                $defaultChat = 1;

                // 3. Inserción con sentencia preparada contra inyección SQL
                $insertStmt = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, ?, ?)");
                
                if ($insertStmt) {
                    $insertStmt->bind_param("ssssi", $nombre, $email, $hashedPassword, $defaultAvatar, $defaultChat);
                    if ($insertStmt->execute()) {
                        $newUserId = (int)$insertStmt->insert_id;
                        $insertStmt->close();

                        // 4. Inserción de perfil biométrico en user_biometrics
                        $aguaLitros = max(2.0, min(6.0, round($peso * 0.035, 1)));
                        $bioStmt = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
                        if ($bioStmt) {
                            $bioStmt->bind_param("iddiissd", $newUserId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $aguaLitros);
                            $bioStmt->execute();
                            $bioStmt->close();
                        }

                        // 5. Cálculo y guardado de metas nutricionales personalizadas
                        $targetCalories = 2200;
                        $proteinG = 150;
                        $fatsG = 65;
                        if ($objetivo === 'ganar_musculo') {
                            $targetCalories = (int)round($peso * 34);
                            $proteinG = (int)round($peso * 2.0);
                            $fatsG = (int)round($peso * 0.9);
                        } elseif ($objetivo === 'perder_grasa') {
                            $targetCalories = (int)round($peso * 25);
                            $proteinG = (int)round($peso * 2.2);
                            $fatsG = (int)round($peso * 0.8);
                        } else {
                            $targetCalories = (int)round($peso * 30);
                            $proteinG = (int)round($peso * 1.8);
                            $fatsG = (int)round($peso * 0.9);
                        }
                        $carbsG = max(80, (int)round(($targetCalories - ($proteinG * 4 + $fatsG * 9)) / 4));
                        $waterMl = (int)round($aguaLitros * 1000);

                        $goalStmt = $conn->prepare("INSERT INTO nutrition_goals (user_id, daily_calories, protein_g, carbs_g, fats_g, water_ml, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
                        if ($goalStmt) {
                            $goalStmt->bind_param("iiiiii", $newUserId, $targetCalories, $proteinG, $carbsG, $fatsG, $waterMl);
                            $goalStmt->execute();
                            $goalStmt->close();
                        }

                        // 6. Establecer sesión PHP activa para el ecosistema Web
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['nombre'] = $nombre;
                        $_SESSION['email'] = $email;
                        $_SESSION['imagen_user'] = $defaultAvatar;

                        // 7. Generar Bearer Token HMAC para sincronización con app móvil / PWA
                        $token = generateAuthToken($newUserId, $email);

                        // 8. Respuesta y redirección inmediata guardando token en cliente
                        echo "<!DOCTYPE html><html><body>";
                        echo "<script>";
                        echo "localStorage.setItem('kynap_auth_token', " . json_encode($token) . ");";
                        echo "window.location.href = " . json_encode($redirectTarget) . ";";
                        echo "</script>";
                        echo "</body></html>";
                        exit();
                    } else {
                        $errorMessage = 'Error al registrar la cuenta en la base de datos: ' . $insertStmt->error;
                        $insertStmt->close();
                    }
                } else {
                    $errorMessage = 'Error interno en la preparación de la consulta: ' . $conn->error;
                }
            }
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
  <title>Crear Cuenta - Kynap AI</title>

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
      padding: 32px 20px;
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
      padding: 36px 32px;
      width: 100%;
      max-width: 540px;
      box-shadow: var(--shadow-card);
      animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .auth-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
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

    .section-title-divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 18px 0 12px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--accent-cyan);
    }
    .section-title-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border-subtle);
    }

    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .form-grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 10px;
    }

    .auth-alert-error {
      background: rgba(255, 71, 87, 0.12);
      border: 1px solid rgba(255, 71, 87, 0.35);
      color: var(--accent-rose);
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      line-height: 1.4;
      margin-bottom: 16px;
    }

    .password-hint {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    .password-match-indicator {
      font-size: 11px;
      margin-top: 4px;
      display: none;
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
      cursor: pointer;
    }

    .auth-footer-links a:hover {
      text-decoration: underline;
    }

    .back-to-home {
      color: var(--text-muted) !important;
      font-size: 12px;
    }

    @media (max-width: 580px) {
      .auth-card {
        padding: 24px 20px;
      }
      .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
      }
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
      <h1>Crear Cuenta en <span>Kynap</span></h1>
      <p>Comienza tu transformación física y nutricional con IA</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
      <div class="auth-alert-error" role="alert">
        <span>⚠</span>
        <div><?php echo htmlspecialchars($errorMessage); ?></div>
      </div>
    <?php endif; ?>

    <form id="registroForm" action="registro.php" method="POST" class="auth-form" novalidate>
      
      <!-- SECCIÓN 1: CREDENCIALES DE ACCESO -->
      <div class="section-title-divider">
        <span>1. Datos de Acceso</span>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="nombreInput">Nombre Completo *</label>
        <input 
          type="text" 
          id="nombreInput" 
          name="nombre" 
          class="input-custom" 
          placeholder="Ej: Savier Casiani" 
          value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
          required 
          autocomplete="name"
        >
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="emailInput">Correo Electrónico *</label>
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

      <div class="form-grid-2">
        <div class="form-group" style="margin-bottom: 0;">
          <label for="passwordInput">Contraseña *</label>
          <input 
            type="password" 
            id="passwordInput" 
            name="password" 
            class="input-custom" 
            placeholder="Mín. 6 caracteres" 
            required 
            autocomplete="new-password"
            oninput="checkPasswordMatch()"
          >
          <div class="password-hint">Mínimo 6 caracteres</div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="confirmPasswordInput">Confirmar Contraseña *</label>
          <input 
            type="password" 
            id="confirmPasswordInput" 
            name="confirm_password" 
            class="input-custom" 
            placeholder="Repite la contraseña" 
            required 
            autocomplete="new-password"
            oninput="checkPasswordMatch()"
          >
          <div id="matchIndicator" class="password-match-indicator"></div>
        </div>
      </div>

      <!-- SECCIÓN 2: DATOS BIOMÉTRICOS & OBJETIVOS FITNESS -->
      <div class="section-title-divider">
        <span>2. Perfil Físico & Nutricional</span>
      </div>

      <div class="form-grid-3">
        <div class="form-group" style="margin-bottom: 0;">
          <label for="edadInput">Edad (Años)</label>
          <input 
            type="number" 
            id="edadInput" 
            name="edad" 
            class="input-custom" 
            value="<?php echo htmlspecialchars($_POST['edad'] ?? '25'); ?>" 
            min="12" 
            max="100" 
            required
          >
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="pesoInput">Peso (kg)</label>
          <input 
            type="number" 
            id="pesoInput" 
            name="peso" 
            class="input-custom" 
            value="<?php echo htmlspecialchars($_POST['peso'] ?? '70.0'); ?>" 
            step="0.5" 
            min="30" 
            max="300" 
            required
          >
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="alturaInput">Altura (cm)</label>
          <input 
            type="number" 
            id="alturaInput" 
            name="altura" 
            class="input-custom" 
            value="<?php echo htmlspecialchars($_POST['altura'] ?? '175'); ?>" 
            min="100" 
            max="250" 
            required
          >
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group" style="margin-bottom: 0;">
          <label for="sexoInput">Sexo Biológico</label>
          <select id="sexoInput" name="sexo" class="input-custom" required>
            <option value="masculino" <?php echo (($_POST['sexo'] ?? '') === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
            <option value="femenino" <?php echo (($_POST['sexo'] ?? '') === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
            <option value="otro" <?php echo (($_POST['sexo'] ?? '') === 'otro') ? 'selected' : ''; ?>>Otro</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="objetivoInput">Objetivo Principal</label>
          <select id="objetivoInput" name="objetivo" class="input-custom" required>
            <option value="ganar_musculo" <?php echo (($_POST['objetivo'] ?? '') === 'ganar_musculo') ? 'selected' : ''; ?>>Ganar Músculo (Hipertrofia)</option>
            <option value="perder_grasa" <?php echo (($_POST['objetivo'] ?? '') === 'perder_grasa') ? 'selected' : ''; ?>>Perder Grasa (Definición)</option>
            <option value="mantener" <?php echo (($_POST['objetivo'] ?? '') === 'mantener') ? 'selected' : ''; ?>>Mantener y Salud General</option>
          </select>
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="actividadInput">Nivel de Actividad Diaria</label>
        <select id="actividadInput" name="nivel_actividad" class="input-custom" required>
          <option value="sedentario" <?php echo (($_POST['nivel_actividad'] ?? '') === 'sedentario') ? 'selected' : ''; ?>>Sedentario (Poco o ningún ejercicio)</option>
          <option value="ligero" <?php echo (($_POST['nivel_actividad'] ?? '') === 'ligero') ? 'selected' : ''; ?>>Ligero (1-3 días de ejercicio por semana)</option>
          <option value="moderado" <?php echo (($_POST['nivel_actividad'] ?? 'moderado') === 'moderado') ? 'selected' : ''; ?>>Moderado (3-5 días de ejercicio por semana)</option>
          <option value="muy_activo" <?php echo (($_POST['nivel_actividad'] ?? '') === 'muy_activo') ? 'selected' : ''; ?>>Muy Activo (6-7 días de entrenamiento intenso)</option>
        </select>
      </div>

      <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 13px; font-size: 15px; margin-top: 10px;">
        Crear Cuenta & Entrar al Panel ➔
      </button>
    </form>

    <div class="auth-footer-links">
      <p>¿Ya tienes una cuenta registrada? <a href="login.php">Inicia sesión</a></p>
      <a href="index.html" class="back-to-home">← Volver a la página principal</a>
    </div>
  </main>

  <script>
    function checkPasswordMatch() {
      const p1 = document.getElementById('passwordInput').value;
      const p2 = document.getElementById('confirmPasswordInput').value;
      const indicator = document.getElementById('matchIndicator');

      if (!p2) {
        indicator.style.display = 'none';
        return;
      }

      indicator.style.display = 'block';
      if (p1 === p2) {
        indicator.textContent = '✓ Las contraseñas coinciden';
        indicator.style.color = 'var(--accent-emerald)';
      } else {
        indicator.textContent = '✕ Las contraseñas no coinciden';
        indicator.style.color = 'var(--accent-rose)';
      }
    }
  </script>
</body>
</html>
