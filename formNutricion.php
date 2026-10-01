<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - ONBOARDING BIOMÉTRICO (formNutricion.php)
 * ==============================================================================
 * Paso 2 del registro: captura biometría inicial del atleta (edad, peso, altura,
 * objetivo, nivel de actividad y consumo de agua) para calcular automáticamente
 * el gasto calórico basal (BMR) y metas de macronutrientes.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edad = max(12, min(100, (int)($_POST['edad'] ?? 25)));
    $peso = max(30, min(300, (float)($_POST['peso'] ?? 70)));
    $altura = max(100, min(250, (float)($_POST['altura'] ?? 170)));
    $objetivo = trim($_POST['objetivo'] ?? 'ganar_musculo');
    $nivelActividad = trim($_POST['nivel_actividad'] ?? 'moderado');
    $sexo = trim($_POST['sexo'] ?? 'masculino');
    $condiciones = isset($_POST['condiciones']) && is_array($_POST['condiciones']) ? implode(', ', $_POST['condiciones']) : '';
    $alergias = trim($_POST['alergias'] ?? '');
    $preferencia = trim($_POST['preferencia'] ?? 'cualquierCosa');
    $agua = max(1, min(15, (int)($_POST['agua'] ?? 3)));

    if ($dbAvailable) {
        // Upsert en tabla usuarios
        $sql = "INSERT INTO usuarios (user_id, edad, peso, altura, objetivo, nivel_actividad, sexo, condiciones, alergias, preferencia, agua) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                edad = VALUES(edad), peso = VALUES(peso), altura = VALUES(altura), 
                objetivo = VALUES(objetivo), nivel_actividad = VALUES(nivel_actividad), 
                sexo = VALUES(sexo), condiciones = VALUES(condiciones), 
                alergias = VALUES(alergias), preferencia = VALUES(preferencia), 
                agua = VALUES(agua)";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iidsssssssi", $userId, $edad, $peso, $altura, $objetivo, $nivelActividad, $sexo, $condiciones, $alergias, $preferencia, $agua);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: icon_select_user.php");
                exit();
            } else {
                $errorMessage = "Error al guardar información biométrica: " . $stmt->error;
                $stmt->close();
            }
        }
    } else {
        // Modo offline: continuar al selector de avatar
        header("Location: icon_select_user.php");
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
  <title>Paso 2: Datos Biométricos - Kynap AI</title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">

  <style>
    .onboarding-page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      background-color: var(--bg-primary);
    }

    .onboarding-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      width: 100%;
      max-width: 580px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
      animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .step-pill {
      display: inline-block;
      padding: 4px 12px;
      border-radius: var(--radius-full);
      background: rgba(0, 217, 255, 0.15);
      color: var(--accent-cyan);
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .grid-2-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    @media (max-width: 540px) {
      .grid-2-col {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="onboarding-page">

  <main class="onboarding-card">
    <div style="text-align: center; margin-bottom: 24px;">
      <span class="step-pill">PASO 2 DE 3</span>
      <h1 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800;">Configura tu Perfil Biométrico</h1>
      <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
        Calcularemos automáticamente tus calorías y macronutrientes diarios.
      </p>
    </div>

    <?php if (!empty($errorMessage)): ?>
      <div style="background: rgba(255, 71, 87, 0.12); border: 1px solid rgba(255, 71, 87, 0.35); color: #ff8a93; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 13px;">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <form id="formNutricion" method="POST" action="formNutricion.php" onsubmit="handleNutricionSubmit(event)" style="display: flex; flex-direction: column; gap: 16px;">
      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="edad">Edad (Años)</label>
          <input type="number" id="edad" name="edad" class="input-custom" value="25" min="12" max="100" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="sexo">Sexo Biológico</label>
          <select id="sexo" name="sexo" class="input-custom" required>
            <option value="masculino" selected>Masculino</option>
            <option value="femenino">Femenino</option>
            <option value="otro">Otro</option>
          </select>
        </div>
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="peso">Peso Actual (kg)</label>
          <input type="number" id="peso" name="peso" class="input-custom" value="75" step="0.5" min="30" max="300" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="altura">Altura (cm)</label>
          <input type="number" id="altura" name="altura" class="input-custom" value="175" step="0.5" min="100" max="250" required>
        </div>
      </div>

      <div class="form-group" style="margin: 0;">
        <label for="objetivo">Objetivo Principal</label>
        <select id="objetivo" name="objetivo" class="input-custom" required>
          <option value="ganar_musculo" selected>Ganar masa muscular (Hipertrofia)</option>
          <option value="bajar_peso">Perder grasa corporal (Definición)</option>
          <option value="mantener_peso">Mantener peso y recomposición</option>
        </select>
      </div>

      <div class="form-group" style="margin: 0;">
        <label for="nivel_actividad">Nivel de Actividad Física</label>
        <select id="nivel_actividad" name="nivel_actividad" class="input-custom" required>
          <option value="sedentario">Sedentario (Poco o ningún ejercicio)</option>
          <option value="ligero">Ligero (1-3 días de entrenamiento / semana)</option>
          <option value="moderado" selected>Moderado (3-5 días de entrenamiento / semana)</option>
          <option value="activo">Activo (6-7 días de entrenamiento intenso)</option>
        </select>
      </div>

      <div class="form-group" style="margin: 0;">
        <label for="agua">Consumo Estimado de Agua al Día (Litros)</label>
        <input type="number" id="agua" name="agua" class="input-custom" value="3" min="1" max="10" required>
      </div>

      <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 13px; font-size: 15px; margin-top: 8px;">
        Guardar & Elegir Avatar ➔
      </button>
    </form>
  </main>

  <script>
    function handleNutricionSubmit(e) {
      const isStatic = window.location.protocol === 'file:' || window.location.port === '5500';
      if (isStatic) {
        if (e) e.preventDefault();
        window.location.href = 'icon_select_user.php';
      }
    }
  </script>
</body>
</html>
