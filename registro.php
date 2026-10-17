<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - REGISTRO CONTROLLER & VIEW (ONBOARDING MULTIPASOS)
 * ==============================================================================
 * Maneja la creación de nuevas cuentas de usuario de manera segura.
 * Flujo de 3 pasos: 1. Cuenta, 2. Biometría, 3. Metas y estilo de vida.
 * Valida formatos, previene duplicados, genera BCRYPT y emite tokens seguros.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 3.0.0
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

// Si el usuario ya tiene sesión iniciada, redirigir al panel de control
if (isset($_SESSION['user_id']) && isset($_SESSION['nombre'])) {
    header('Location: app.html');
    exit();
}

$errorMessage = '';
$redirectTarget = 'app.html';
$successToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Datos personales y credenciales
    $nombre = sanitizeString($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // 2. Parámetros biométricos
    $edad = max(12, min(100, (int)($_POST['edad'] ?? 25)));
    $peso = max(30.0, min(300.0, (float)($_POST['peso'] ?? 75.0)));
    $altura = max(100.0, min(250.0, (float)($_POST['altura'] ?? 175.0)));
    $sexo = in_array($_POST['sexo'] ?? '', ['masculino', 'femenino', 'otro']) ? $_POST['sexo'] : 'masculino';
    $objetivo = in_array($_POST['objetivo'] ?? '', ['ganar_musculo', 'perder_grasa', 'mantener', 'recomposicion']) ? $_POST['objetivo'] : 'ganar_musculo';
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
                $stmt = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssi", $nombre, $email, $hashedPassword, $defaultAvatar, $defaultChat);

                    if ($stmt->execute()) {
                        $newUserId = (int)$stmt->insert_id;
                        $stmt->close();

                        // 4. Inserción de perfil biométrico en user_biometrics
                        $bioStmt = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua) VALUES (?, ?, ?, ?, ?, ?, ?, 3.0) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
                        if ($bioStmt) {
                            $bioStmt->bind_param("iddiiss", $newUserId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad);
                            $bioStmt->execute();
                            $bioStmt->close();
                        }

                        // 5. Cálculo y guardado de metas nutricionales
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
                        } elseif ($objetivo === 'recomposicion') {
                            $targetCalories = (int)round($peso * 29);
                            $proteinG = (int)round($peso * 2.2);
                            $fatsG = (int)round($peso * 0.85);
                        } else {
                            $targetCalories = (int)round($peso * 30);
                            $proteinG = (int)round($peso * 1.8);
                            $fatsG = (int)round($peso * 0.9);
                        }
                        $carbsG = max(80, (int)round(($targetCalories - ($proteinG * 4 + $fatsG * 9)) / 4));
                        $waterMl = max(2000, (int)round($peso * 35));

                        $goalStmt = $conn->prepare("INSERT INTO nutrition_goals (user_id, daily_calories, protein_g, carbs_g, fats_g, water_ml, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
                        if ($goalStmt) {
                            $goalStmt->bind_param("iiiiii", $newUserId, $targetCalories, $proteinG, $carbsG, $fatsG, $waterMl);
                            $goalStmt->execute();
                            $goalStmt->close();
                        }

                        // Regeneración segura de sesión
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_regenerate_id(true);
                        }
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['nombre'] = $nombre;
                        $_SESSION['email'] = $email;
                        $_SESSION['imagen_user'] = $defaultAvatar;

                        $successToken = generateAuthToken($newUserId, $email);

                        echo "<!DOCTYPE html><html><body>";
                        echo "<script>";
                        echo "localStorage.setItem('kynap_auth_token', " . json_encode($successToken) . ");";
                        echo "window.location.href = 'app.html';";
                        echo "</script>";
                        echo "</body></html>";
                        exit();
                    } else {
                        error_log("[REGISTRO ERROR EXECUTE]: " . $stmt->error);
                        $errorMessage = 'Ocurrió un error al crear la cuenta. Por favor intenta más tarde.';
                        $stmt->close();
                    }
                } else {
                    error_log("[REGISTRO ERROR PREPARE]: " . $conn->error);
                    $errorMessage = 'Error interno al procesar el registro. Por favor intenta más tarde.';
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
  <title>Crear Cuenta - Kynap AI Fitness</title>

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
      padding: 36px 20px;
      background-color: var(--bg-primary);
      position: relative;
      overflow-x: hidden;
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
      max-width: 560px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
      animation: modalPop 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      position: relative;
      z-index: 2;
    }

    .auth-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
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

    .auth-brand h1 span {
      background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .auth-brand p {
      font-size: 13px;
      color: var(--text-secondary);
      margin-top: -4px;
      text-align: center;
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
      font-weight: 600;
    }

    .auth-footer-links {
      margin-top: 24px;
      text-align: center;
      font-size: 13px;
      color: var(--text-secondary);
      display: flex;
      flex-direction: column;
      gap: 8px;
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

    .ambient-glow {
      position: absolute;
      width: 450px;
      height: 450px;
      border-radius: 50%;
      filter: blur(140px);
      pointer-events: none;
      opacity: 0.15;
      z-index: 1;
    }

    @media (max-width: 580px) {
      .auth-card {
        padding: 26px 20px;
      }
      .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
      }
      .step-label {
        font-size: 9px;
      }
      .step-bubble {
        width: 30px;
        height: 30px;
        font-size: 11px;
      }
    }
  </style>
</head>
<body class="auth-page">

  <div class="ambient-glow" style="top: -100px; left: 10%; background: var(--accent-emerald);"></div>
  <div class="ambient-glow" style="bottom: -100px; right: 10%; background: var(--accent-cyan);"></div>

  <div class="theme-top-bar">
    <button type="button" class="btn-theme-toggle" onclick="toggleTheme()" aria-label="Cambiar tema">🌙</button>
  </div>

  <!-- Contenedor de Notificaciones Toast -->
  <div id="toastContainer" class="toast-container" aria-live="polite"></div>

  <main class="auth-card" id="registerCard" role="main">
    <div class="auth-brand">
      <div class="brand-logo">K</div>
      <h1>Crear Cuenta en <span>Kynap</span></h1>
      <p>Configura tu perfil para una periodización y nutrición personalizada</p>
    </div>

    <!-- BANNER CONTEXTUAL DE ERROR -->
    <div id="registerAlertBox" class="auth-alert-banner" style="<?php echo !empty($errorMessage) ? 'display: flex;' : 'display: none;'; ?>" role="alert">
      <?php echo htmlspecialchars($errorMessage); ?>
    </div>

    <!-- MULTISTEP WIZARD PROGRESS BAR -->
    <div class="multistep-bar" id="stepBar">
      <div class="step-node active" id="stepNode1" onclick="tryNavigateToStep(1)">
        <div class="step-bubble">1</div>
        <span class="step-label">Cuenta</span>
      </div>
      <div class="step-connector" id="stepConnector1"></div>
      <div class="step-node" id="stepNode2" onclick="tryNavigateToStep(2)">
        <div class="step-bubble">2</div>
        <span class="step-label">Biometría</span>
      </div>
      <div class="step-connector" id="stepConnector2"></div>
      <div class="step-node" id="stepNode3" onclick="tryNavigateToStep(3)">
        <div class="step-bubble">3</div>
        <span class="step-label">Metas</span>
      </div>
    </div>

    <form id="completeRegisterForm" action="registro.php" method="POST" onsubmit="handleRegisterSubmit(event)" novalidate>
      
      <!-- ==================================================================
           PASO 1: CUENTA & CREDENCIALES
           ================================================================== -->
      <div class="wizard-step-pane active" id="wizardPane1">
        <div class="form-group" style="margin: 0;">
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
          <div class="field-error-msg" id="err-nombreInput" style="display: none;"></div>
        </div>

        <div class="form-group" style="margin: 0;">
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
          <div class="field-error-msg" id="err-emailInput" style="display: none;"></div>
        </div>

        <div class="form-grid-2">
          <div class="form-group" style="margin: 0;">
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
            <div class="field-error-msg" id="err-passwordInput" style="display: none;"></div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="confirmPasswordInput">Confirmar Contraseña *</label>
            <input 
              type="password" 
              id="confirmPasswordInput" 
              name="confirm_password" 
              class="input-custom" 
              placeholder="Repite contraseña" 
              required 
              autocomplete="new-password"
              oninput="checkPasswordMatch()"
            >
            <div id="matchIndicator" class="password-match-indicator"></div>
            <div class="field-error-msg" id="err-confirmPasswordInput" style="display: none;"></div>
          </div>
        </div>

        <div class="wizard-actions" style="justify-content: flex-end;">
          <button type="button" class="btn-checkin" style="width: auto; padding: 12px 28px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700;" onclick="nextStep(1)">
            Continuar a Biometría ➔
          </button>
        </div>
      </div>

      <!-- ==================================================================
           PASO 2: PERFIL BIOMÉTRICO
           ================================================================== -->
      <div class="wizard-step-pane" id="wizardPane2">
        <div class="form-grid-2">
          <div class="form-group" style="margin: 0;">
            <label for="edadInput">Edad (Años) *</label>
            <input 
              type="number" 
              id="edadInput" 
              name="edad" 
              class="input-custom" 
              value="<?php echo htmlspecialchars((string)($_POST['edad'] ?? 25)); ?>" 
              min="12" 
              max="100" 
              required
            >
            <div class="field-error-msg" id="err-edadInput" style="display: none;"></div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="sexoInput">Sexo Biológico *</label>
            <select id="sexoInput" name="sexo" class="input-custom" required>
              <option value="masculino" <?php echo (($_POST['sexo'] ?? '') === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
              <option value="femenino" <?php echo (($_POST['sexo'] ?? '') === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
              <option value="otro" <?php echo (($_POST['sexo'] ?? '') === 'otro') ? 'selected' : ''; ?>>Otro</option>
            </select>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group" style="margin: 0;">
            <label for="pesoInput">Peso Actual (kg) *</label>
            <input 
              type="number" 
              id="pesoInput" 
              name="peso" 
              class="input-custom" 
              value="<?php echo htmlspecialchars((string)($_POST['peso'] ?? 75.0)); ?>" 
              step="0.5" 
              min="30" 
              max="300" 
              required
              oninput="recalcBmiPreview()"
            >
            <div class="field-error-msg" id="err-pesoInput" style="display: none;"></div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="alturaInput">Altura (cm) *</label>
            <input 
              type="number" 
              id="alturaInput" 
              name="altura" 
              class="input-custom" 
              value="<?php echo htmlspecialchars((string)($_POST['altura'] ?? 175)); ?>" 
              min="100" 
              max="250" 
              required
              oninput="recalcBmiPreview()"
            >
            <div class="field-error-msg" id="err-alturaInput" style="display: none;"></div>
          </div>
        </div>

        <!-- WIDGET EN TIEMPO REAL DE IMC -->
        <div class="bmi-live-preview">
          <div>
            <strong style="color: var(--accent-cyan); font-size: 12px; text-transform: uppercase;">Índice de Masa Corporal (IMC):</strong>
            <div style="font-size: 14px; margin-top: 2px;"><span id="bmiValPreview">24.5</span> kg/m² • <span id="bmiCatPreview" style="color: var(--accent-emerald); font-weight: 700;">Peso Saludable</span></div>
          </div>
          <span style="font-size: 22px;">⚖️</span>
        </div>

        <div class="wizard-actions">
          <button type="button" class="btn-checkin" style="width: auto; padding: 12px 20px; background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text);" onclick="prevStep(2)">
            ← Volver
          </button>
          <button type="button" class="btn-checkin" style="width: auto; padding: 12px 28px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700;" onclick="nextStep(2)">
            Continuar a Metas ➔
          </button>
        </div>
      </div>

      <!-- ==================================================================
           PASO 3: METAS & ESTILO DE VIDA
           ================================================================== -->
      <div class="wizard-step-pane" id="wizardPane3">
        <div class="form-group" style="margin: 0;">
          <label for="objetivoInput">Objetivo Principal de Entrenamiento *</label>
          <select id="objetivoInput" name="objetivo" class="input-custom" required>
            <option value="ganar_musculo" <?php echo (($_POST['objetivo'] ?? '') === 'ganar_musculo') ? 'selected' : ''; ?>>🔥 Hipertrofia & Ganancia Muscular</option>
            <option value="perder_grasa" <?php echo (($_POST['objetivo'] ?? '') === 'perder_grasa') ? 'selected' : ''; ?>>⚡ Definición & Pérdida de Grasa</option>
            <option value="mantener" <?php echo (($_POST['objetivo'] ?? '') === 'mantener') ? 'selected' : ''; ?>>🛡️ Mantenimiento & Fuerza Funcional</option>
            <option value="recomposicion" <?php echo (($_POST['objetivo'] ?? '') === 'recomposicion') ? 'selected' : ''; ?>>✨ Recomposición Corporal (Músculo + Definición)</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="actividadInput">Nivel de Actividad Diaria *</label>
          <select id="actividadInput" name="nivel_actividad" class="input-custom" required>
            <option value="sedentario" <?php echo (($_POST['nivel_actividad'] ?? '') === 'sedentario') ? 'selected' : ''; ?>>Sedentario (Trabajo de escritorio / Poco ejercicio)</option>
            <option value="ligero" <?php echo (($_POST['nivel_actividad'] ?? '') === 'ligero') ? 'selected' : ''; ?>>Ligero (1-2 días de actividad ligera por semana)</option>
            <option value="moderado" <?php echo (($_POST['nivel_actividad'] ?? 'moderado') === 'moderado') ? 'selected' : ''; ?>>Moderado (3-5 días de entrenamiento habitual)</option>
            <option value="muy_activo" <?php echo (($_POST['nivel_actividad'] ?? '') === 'muy_activo') ? 'selected' : ''; ?>>Muy Activo (6-7 días de entrenamiento intenso)</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="diasEntrenamientoInput">Días Disponibles para Entrenar / Semana *</label>
          <select id="diasEntrenamientoInput" name="dias_entrenamiento" class="input-custom" required>
            <option value="3">3 Días (Frecuencia Óptima Full Body)</option>
            <option value="4" selected>4 Días (Torso / Pierna / Híbrido)</option>
            <option value="5">5 Días (Push / Pull / Legs + Especialización)</option>
            <option value="6">6 Días (Alto Rendimiento Deportivo)</option>
          </select>
        </div>

        <div class="wizard-actions">
          <button type="button" class="btn-checkin" style="width: auto; padding: 12px 20px; background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text);" onclick="prevStep(3)">
            ← Volver
          </button>
          <button type="submit" id="btnSubmitRegister" class="btn-checkin" style="width: auto; padding: 12px 30px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 800; font-size: 15px; box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);">
            🚀 Completar Registro & Entrar
          </button>
        </div>
      </div>

    </form>

    <div class="auth-footer-links">
      <p>¿Ya tienes una cuenta registrada? <a href="login.php">Inicia sesión aquí</a></p>
      <a href="index.html" class="back-to-home">← Volver a la página principal</a>
    </div>
  </main>

  <script>
    let currentStep = 1;

    // Toast flotante integrado
    function showToast(type = 'success', title = '', desc = '', duration = 3500) {
      let container = document.getElementById('toastContainer');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      const icons = { success: '✔', error: '✕', warning: '⚠', info: 'ℹ' };
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      toast.innerHTML = `
        <div class="toast-icon">${icons[type] || '✔'}</div>
        <div class="toast-content">
          <div class="toast-title">${title}</div>
          ${desc ? `<div class="toast-desc">${desc}</div>` : ''}
        </div>
        <div class="toast-progress" style="animation-duration: ${duration}ms;"></div>
      `;
      container.appendChild(toast);
      setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 260);
      }, duration);
    }

    function setFieldError(fieldId, errorMsg) {
      const field = document.getElementById(fieldId);
      const errBox = document.getElementById('err-' + fieldId);
      if (field) {
        field.classList.add('input-error');
        field.addEventListener('input', () => clearFieldError(fieldId), { once: true });
      }
      if (errBox) {
        errBox.textContent = '✕ ' + errorMsg;
        errBox.style.display = 'flex';
      }
    }

    function clearFieldError(fieldId) {
      const field = document.getElementById(fieldId);
      const errBox = document.getElementById('err-' + fieldId);
      if (field) field.classList.remove('input-error');
      if (errBox) {
        errBox.textContent = '';
        errBox.style.display = 'none';
      }
    }

    function clearAllErrors() {
      document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
      document.querySelectorAll('.field-error-msg').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
      });
      const alertBox = document.getElementById('registerAlertBox');
      if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.textContent = '';
      }
    }

    function triggerShake() {
      const card = document.getElementById('registerCard');
      if (card) {
        card.classList.remove('shake');
        void card.offsetWidth;
        card.classList.add('shake');
        setTimeout(() => card.classList.remove('shake'), 450);
      }
    }

    function validateStep(step) {
      clearAllErrors();
      let isValid = true;
      let firstInvalidId = null;

      if (step === 1) {
        const nombre = document.getElementById('nombreInput')?.value.trim();
        const email = document.getElementById('emailInput')?.value.trim();
        const password = document.getElementById('passwordInput')?.value;
        const confirm = document.getElementById('confirmPasswordInput')?.value;

        if (!nombre || nombre.length < 2) {
          setFieldError('nombreInput', 'Por favor ingresa tu nombre completo.');
          if (!firstInvalidId) firstInvalidId = 'nombreInput';
          isValid = false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
          setFieldError('emailInput', 'Ingresa un correo electrónico con formato válido.');
          if (!firstInvalidId) firstInvalidId = 'emailInput';
          isValid = false;
        }

        if (!password || password.length < 6) {
          setFieldError('passwordInput', 'La contraseña debe tener al menos 6 caracteres.');
          if (!firstInvalidId) firstInvalidId = 'passwordInput';
          isValid = false;
        }

        if (password !== confirm) {
          setFieldError('confirmPasswordInput', 'Las contraseñas no coinciden.');
          if (!firstInvalidId) firstInvalidId = 'confirmPasswordInput';
          isValid = false;
        }
      }

      if (step === 2) {
        const edad = parseInt(document.getElementById('edadInput')?.value, 10);
        const peso = parseFloat(document.getElementById('pesoInput')?.value);
        const altura = parseFloat(document.getElementById('alturaInput')?.value);

        if (isNaN(edad) || edad < 12 || edad > 100) {
          setFieldError('edadInput', 'Edad debe ser entre 12 y 100 años.');
          if (!firstInvalidId) firstInvalidId = 'edadInput';
          isValid = false;
        }
        if (isNaN(peso) || peso < 30 || peso > 300) {
          setFieldError('pesoInput', 'El peso debe ser entre 30 y 300 kg.');
          if (!firstInvalidId) firstInvalidId = 'pesoInput';
          isValid = false;
        }
        if (isNaN(altura) || altura < 100 || altura > 250) {
          setFieldError('alturaInput', 'La altura debe ser entre 100 y 250 cm.');
          if (!firstInvalidId) firstInvalidId = 'alturaInput';
          isValid = false;
        }
      }

      if (!isValid) {
        triggerShake();
        const alertBox = document.getElementById('registerAlertBox');
        if (alertBox) {
          alertBox.textContent = 'Por favor corrige los campos destacados antes de continuar.';
          alertBox.style.display = 'flex';
        }
        showToast('error', 'Campos Requeridos', 'Revisa la información en rojo para continuar.');
        if (firstInvalidId) {
          const el = document.getElementById(firstInvalidId);
          if (el) el.focus();
        }
      }

      return isValid;
    }

    function goToStep(step) {
      currentStep = step;

      document.querySelectorAll('.wizard-step-pane').forEach((pane, idx) => {
        pane.classList.toggle('active', (idx + 1) === step);
      });

      for (let i = 1; i <= 3; i++) {
        const node = document.getElementById(`stepNode${i}`);
        if (node) {
          node.classList.remove('active', 'completed');
          if (i === step) node.classList.add('active');
          else if (i < step) node.classList.add('completed');
        }
        if (i < 3) {
          const conn = document.getElementById(`stepConnector${i}`);
          if (conn) conn.classList.toggle('completed', i < step);
        }
      }

      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function nextStep(current) {
      if (!validateStep(current)) return;
      goToStep(current + 1);
    }

    function prevStep(current) {
      clearAllErrors();
      goToStep(current - 1);
    }

    function tryNavigateToStep(target) {
      if (target <= currentStep) {
        goToStep(target);
      } else {
        if (validateStep(currentStep)) {
          goToStep(target);
        }
      }
    }

    function checkPasswordMatch() {
      const p1 = document.getElementById('passwordInput')?.value || '';
      const p2 = document.getElementById('confirmPasswordInput')?.value || '';
      const ind = document.getElementById('matchIndicator');
      if (!ind) return;

      if (!p2) {
        ind.style.display = 'none';
        return;
      }
      ind.style.display = 'block';
      if (p1 === p2) {
        ind.textContent = '✓ Las contraseñas coinciden';
        ind.style.color = 'var(--accent-emerald)';
      } else {
        ind.textContent = '✕ Las contraseñas no coinciden';
        ind.style.color = 'var(--accent-rose)';
      }
    }

    function recalcBmiPreview() {
      const p = parseFloat(document.getElementById('pesoInput')?.value) || 0;
      const a = parseFloat(document.getElementById('alturaInput')?.value) || 0;
      const vEl = document.getElementById('bmiValPreview');
      const cEl = document.getElementById('bmiCatPreview');
      if (!vEl || !cEl) return;

      if (p > 0 && a > 0) {
        const altM = a / 100.0;
        const bmi = (p / (altM * altM)).toFixed(1);
        vEl.textContent = bmi;

        if (bmi < 18.5) {
          cEl.textContent = 'Bajo Peso';
          cEl.style.color = 'var(--accent-amber)';
        } else if (bmi < 25) {
          cEl.textContent = 'Peso Saludable';
          cEl.style.color = 'var(--accent-emerald)';
        } else if (bmi < 30) {
          cEl.textContent = 'Sobrepeso';
          cEl.style.color = 'var(--accent-amber)';
        } else {
          cEl.textContent = 'Obesidad';
          cEl.style.color = 'var(--accent-rose)';
        }
      }
    }

    async function handleRegisterSubmit(e) {
      if (!validateStep(1) || !validateStep(2)) {
        if (e) e.preventDefault();
        return;
      }

      // Si es envío por AJAX:
      if (e) {
        e.preventDefault();
        const alertBox = document.getElementById('registerAlertBox');
        alertBox.style.display = 'none';

        const nombre = document.getElementById('nombreInput').value.trim();
        const email = document.getElementById('emailInput').value.trim();
        const password = document.getElementById('passwordInput').value;
        const confirm_password = document.getElementById('confirmPasswordInput').value;

        const edad = parseInt(document.getElementById('edadInput').value, 10);
        const peso = parseFloat(document.getElementById('pesoInput').value);
        const altura = parseFloat(document.getElementById('alturaInput').value);
        const sexo = document.getElementById('sexoInput').value;
        const objetivo = document.getElementById('objetivoInput').value;
        const nivel_actividad = document.getElementById('actividadInput').value;
        const dias_entrenamiento = parseInt(document.getElementById('diasEntrenamientoInput').value, 10);

        const submitBtn = document.getElementById('btnSubmitRegister');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⚡ Registrando Atleta...';

        try {
          const res = await fetch('controllers/api_auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              nombre,
              email,
              password,
              confirm_password,
              edad,
              peso,
              altura,
              sexo,
              objetivo,
              nivel_actividad,
              dias_entrenamiento
            })
          });

          const data = await res.json();

          if (data.success && data.data?.token) {
            localStorage.setItem('kynap_auth_token', data.data.token);
            showToast('success', '¡Bienvenido a Kynap!', 'Tu cuenta ha sido creada exitosamente. Redirigiendo...');
            setTimeout(() => {
              window.location.href = 'app.html';
            }, 1000);
          } else {
            triggerShake();
            const msg = data.message || 'Error al procesar el registro.';
            alertBox.textContent = msg;
            alertBox.style.display = 'flex';

            if (data.errors?.code === 'EMAIL_EXISTS' || msg.toLowerCase().includes('correo')) {
              setFieldError('emailInput', msg);
              goToStep(1);
            }

            showToast('error', 'Registro Rechazado', msg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
          }
        } catch (err) {
          // Si falla AJAX, enviar por formulario tradicional POST
          document.getElementById('completeRegisterForm').submit();
        }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      recalcBmiPreview();
      <?php if (!empty($errorMessage)): ?>
        showToast('error', 'Validación del Servidor', <?php echo json_encode($errorMessage); ?>);
        triggerShake();
      <?php endif; ?>
    });
  </script>
</body>
</html>
