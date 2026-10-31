<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - CONFIGURACIÓN DE CUENTA, PERFIL Y ENCUESTA MÉDICA
 * ==============================================================================
 * Gestiona la visualización y actualización de la cuenta, perfil biométrico y
 * encuesta médica del atleta con RESTRICCIÓN ESTRICTA DE RUTINA (REQUERIMIENTO 3):
 *
 * 1. Los datos personales y biométricos se encuentran bloqueados hasta que el
 *    usuario complete el 100% de los entrenamientos de su rutina semanal asignada.
 * 2. Una vez completada la rutina, el formulario se desbloquea para que el atleta
 *    actualice sus medidas y condición actual.
 * 3. El sistema compara los nuevos datos con el registro anterior:
 *    - Si hay mejora: identifica y explica el progreso alcanzado.
 *    - Si no hay mejora: analiza las áreas de atención (calorías, RPE, hidratación)
 *      y genera recomendaciones personalizadas con IA.
 *
 * @version 3.0.0
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
$evaluationResult = null;

// Asegurar que exista rutina activa para el usuario
if ($dbAvailable) {
    ensureUserActiveRoutine($conn, $userId);
}

// Evaluar política de actualización de información basada en cumplimiento de rutina
$policy = evaluateBiometricsPolicy($conn, $userId);
$routineStatus = $policy['routineStatus'] ?? checkRoutinePlanCompletion($conn, $userId);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeString($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $imagenUser = sanitizeString($_POST['imagen_user'] ?? 'iconos/profile.svg');
    $password = (string)($_POST['password'] ?? '');

    // Parámetros biométricos y encuesta
    $peso = max(30.0, min(300.0, (float)($_POST['peso'] ?? 75.0)));
    $altura = max(100.0, min(250.0, (float)($_POST['altura'] ?? 175.0)));
    $edad = max(12, min(100, (int)($_POST['edad'] ?? 25)));
    $sexo = in_array($_POST['sexo'] ?? '', ['masculino', 'femenino', 'otro']) ? $_POST['sexo'] : 'masculino';
    $objetivo = sanitizeString($_POST['objetivo'] ?? 'ganar_musculo');
    $nivelActividad = sanitizeString($_POST['nivel_actividad'] ?? 'moderado');
    $condiciones = sanitizeString($_POST['condiciones'] ?? 'ninguna');
    $alergias = sanitizeString($_POST['alergias'] ?? 'ninguna');
    $preferencia = sanitizeString($_POST['preferencia'] ?? 'omnivora');
    $agua = max(1.0, min(10.0, (float)($_POST['agua'] ?? 3.0)));

    $grasa = !empty($_POST['grasa_corporal']) ? (float)$_POST['grasa_corporal'] : null;
    $cintura = !empty($_POST['cintura_cm']) ? (float)$_POST['cintura_cm'] : null;
    $pecho = !empty($_POST['pecho_cm']) ? (float)$_POST['pecho_cm'] : null;
    $brazo = !empty($_POST['brazo_cm']) ? (float)$_POST['brazo_cm'] : null;
    $notas = sanitizeString($_POST['notas'] ?? '');

    if (empty($nombre)) {
        $statusMessage = 'El nombre no puede estar vacío.';
    } elseif (!$email) {
        $statusMessage = 'El correo electrónico no es válido.';
    } elseif (!$dbAvailable) {
        $statusMessage = 'Servidor de base de datos no disponible.';
    } else {
        // Verificar que el correo no esté ocupado por otra cuenta
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $statusMessage = 'Este correo electrónico ya está en uso por otra cuenta.';
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // 1. Actualizar credenciales básicas
            if (!empty($password) && strlen($password) >= 6) {
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $conn->prepare("UPDATE users SET nombre = ?, email = ?, imagen_user = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $email, $imagenUser, $hashed, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nombre = ?, email = ?, imagen_user = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nombre, $email, $imagenUser, $userId);
            }

            if ($stmt->execute()) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                $_SESSION['imagen_user'] = $imagenUser;
                $stmt->close();

                // 2. REGLA DE NEGOCIO: Validar si la biometría está habilitada para cambios
                if (!$policy['canUpdate']) {
                    $statusMessage = 'Tus credenciales fueron guardadas. Sin embargo, tus datos personales y biometría permanecen protegidos hasta que completes todos los días de entrenamiento de tu rutina asignada (' . $routineStatus['completedCount'] . '/' . $routineStatus['totalDays'] . ' días completados).';
                    $isSuccess = true;
                } else {
                    // Recuperar datos previos para comparación y evaluación de progreso
                    $prevBio = null;
                    $prevBio = null;
                    $pStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo FROM user_biometrics WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                    if ($pStmt) {
                        $pStmt->bind_param("i", $userId);
                        $pStmt->execute();
                        $pRes = $pStmt->get_result();
                        $prevBio = $pRes->fetch_assoc();
                        $pStmt->close();
                    }
                    if (!$prevBio) {
                        $pStmt = $conn->prepare("SELECT peso, altura, edad, sexo FROM user_biometrics_history WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                        if ($pStmt) {
                            $pStmt->bind_param("i", $userId);
                            $pStmt->execute();
                            $pRes = $pStmt->get_result();
                            $prevBio = $pRes->fetch_assoc();
                            $pStmt->close();
                        }
                    }

                    // Actualizar o insertar en user_biometrics
                    $checkBio = $conn->prepare("SELECT id FROM user_biometrics WHERE user_id = ? LIMIT 1");
                    $bioExists = false;
                    if ($checkBio) {
                        $checkBio->bind_param("i", $userId);
                        $checkBio->execute();
                        $checkBio->store_result();
                        $bioExists = ($checkBio->num_rows > 0);
                        $checkBio->close();
                    }

                    if ($bioExists) {
                        $bioStmt = $conn->prepare("UPDATE user_biometrics SET peso = ?, altura = ?, edad = ?, sexo = ?, objetivo = ?, nivel_actividad = ?, agua = ?, condiciones = ?, alergias = ?, preferencia = ?, modified_at = NOW() WHERE user_id = ?");
                        if ($bioStmt) {
                            $bioStmt->bind_param("ddisssdsssi", $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $agua, $condiciones, $alergias, $preferencia, $userId);
                            $bioStmt->execute();
                            $bioStmt->close();
                        }
                    } else {
                        $bioStmt = $conn->prepare("INSERT INTO user_biometrics (user_id, peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($bioStmt) {
                            $bioStmt->bind_param("iddisssdsss", $userId, $peso, $altura, $edad, $sexo, $objetivo, $nivelActividad, $agua, $condiciones, $alergias, $preferencia);
                            $bioStmt->execute();
                            $bioStmt->close();
                        }
                    }

                    // Registrar en historial user_biometrics_history
                    if (($policy['mode'] ?? '') === 'correction' && !empty($policy['lastRecordId'])) {
                        $recId = (int)$policy['lastRecordId'];
                        $hStmt = $conn->prepare("UPDATE user_biometrics_history SET peso = ?, altura = ?, edad = ?, sexo = ?, grasa_corporal = ?, cintura_cm = ?, pecho_cm = ?, brazo_cm = ?, notas = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                        if ($hStmt) {
                            $hStmt->bind_param("ddisddddsii", $peso, $altura, $edad, $sexo, $grasa, $cintura, $pecho, $brazo, $notas, $recId, $userId);
                            $hStmt->execute();
                            $hStmt->close();
                        }
                    } else {
                        $hStmt = $conn->prepare("INSERT INTO user_biometrics_history (user_id, peso, altura, edad, sexo, grasa_corporal, cintura_cm, pecho_cm, brazo_cm, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($hStmt) {
                            $hStmt->bind_param("iddisdddds", $userId, $peso, $altura, $edad, $sexo, $grasa, $cintura, $pecho, $brazo, $notas);
                            $hStmt->execute();
                            $hStmt->close();
                        }
                    }

                    // Actualizar metas nutricionales según nuevo peso y objetivo
                    $macroTargets = calculateNutritionTargets([
                        'peso'            => $peso,
                        'altura'          => $altura,
                        'edad'            => $edad,
                        'sexo'            => $sexo,
                        'objetivo'        => $objetivo,
                        'nivel_actividad' => $nivelActividad
                    ]);
                    saveNutritionGoals($conn, $userId, $macroTargets);

                    // EVALUACIÓN DE PROGRESO CON INTELIGENCIA ARTIFICIAL (REQUERIMIENTO 3)
                    $userContext = [
                        'nombre'   => $nombre,
                        'objetivo' => $objetivo,
                        'sexo'     => $sexo,
                        'edad'     => $edad
                    ];
                    $newBioData = [
                        'peso'           => $peso,
                        'altura'         => $altura,
                        'grasa_corporal' => $grasa,
                        'cintura_cm'     => $cintura,
                        'pecho_cm'       => $pecho,
                        'brazo_cm'       => $brazo
                    ];

                    $evaluation = evaluateUserProgress($prevBio ?: $newBioData, $newBioData, $userContext);
                    $evaluationResult = $evaluation;

                    // Si completó su rutina, guardar recomendación en BD y renovar el ciclo
                    if ($policy['mode'] === 'routine_completed' || $routineStatus['isCompleted']) {
                        $recText = $evaluation['narrative'] . "\n\n" . implode("\n", array_map(function($r) { return "• " . $r; }, $evaluation['recommendations']));
                        $recStmt = $conn->prepare("INSERT INTO recomendaciones (user_id, mensaje, tipo, evaluacion_datos) VALUES (?, ?, 'progreso_semanal', ?)");
                        if ($recStmt) {
                            $evalJson = json_encode($evaluation, JSON_UNESCAPED_UNICODE);
                            $recStmt->bind_param("iss", $userId, $recText, $evalJson);
                            $recStmt->execute();
                            $recStmt->close();
                        }

                        // Renovar ciclo de check-ins avanzando a una nueva semana de registro
                        $advanceStmt = $conn->prepare("UPDATE routine_check_ins SET week_number = week_number - 1 WHERE user_id = ? AND week_number = ?");
                        if ($advanceStmt) {
                            $nowDt = new DateTime();
                            $wk = (int)$nowDt->format("W");
                            $advanceStmt->bind_param("ii", $userId, $wk);
                            $advanceStmt->execute();
                            $advanceStmt->close();
                        }
                    }

                    $statusMessage = '¡Información personal y métricas corporales actualizadas con éxito!';
                    $isSuccess = true;

                    // Reevaluar política para reflejar el nuevo estado
                    $policy = evaluateBiometricsPolicy($conn, $userId);
                    $routineStatus = $policy['routineStatus'];
                }
            } else {
                $statusMessage = 'Error al actualizar: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// Cargar datos actuales del usuario desde users y user_biometrics
$nombre = $_SESSION['nombre'] ?? '';
$email = $_SESSION['email'] ?? '';
$imagenUser = $_SESSION['imagen_user'] ?? 'iconos/profile.svg';

$peso = 75.0;
$altura = 175.0;
$edad = 25;
$sexo = 'masculino';
$objetivo = 'ganar_musculo';
$nivelActividad = 'moderado';
$agua = 3.0;
$condiciones = 'ninguna';
$alergias = 'ninguna';
$preferencia = 'omnivora';
$grasa = null;
$cintura = null;
$pecho = null;
$brazo = null;
$notas = '';

if ($dbAvailable) {
    $stmt = $conn->prepare("SELECT nombre, email, imagen_user FROM users WHERE id = ? LIMIT 1");
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

    $bioStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia FROM user_biometrics WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if (!$bioStmt) {
        $bioStmt = $conn->prepare("SELECT peso, altura, edad, sexo, objetivo, nivel_actividad, agua, condiciones, alergias, preferencia FROM usuarios WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    }
    if ($bioStmt) {
        $bioStmt->bind_param("i", $userId);
        $bioStmt->execute();
        $bRes = $bioStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            $peso = (float)($bRow['peso'] ?: $peso);
            $altura = (float)($bRow['altura'] ?: $altura);
            $edad = (int)($bRow['edad'] ?: $edad);
            $sexo = $bRow['sexo'] ?: $sexo;
            $objetivo = $bRow['objetivo'] ?: $objetivo;
            $nivelActividad = $bRow['nivel_actividad'] ?: $nivelActividad;
            $agua = (float)($bRow['agua'] ?: $agua);
            $condiciones = $bRow['condiciones'] ?: $condiciones;
            $alergias = $bRow['alergias'] ?: $alergias;
            $preferencia = $bRow['preferencia'] ?: $preferencia;
        }
        $bioStmt->close();
    }

    // Consultar medidas corporales más recientes desde user_biometrics_history
    $hBioStmt = $conn->prepare("SELECT grasa_corporal, cintura_cm, pecho_cm, brazo_cm, notas FROM user_biometrics_history WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if ($hBioStmt) {
        $hBioStmt->bind_param("i", $userId);
        $hBioStmt->execute();
        $hRes = $hBioStmt->get_result();
        if ($hRow = $hRes->fetch_assoc()) {
            $grasa = $hRow['grasa_corporal'];
            $cintura = $hRow['cintura_cm'];
            $pecho = $hRow['pecho_cm'];
            $brazo = $hRow['brazo_cm'];
            $notas = $hRow['notas'] ?: '';
        }
        $hBioStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a0d14">
  <title>Ajustes de Perfil & Encuesta - Kynap AI</title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">
  <script src="public/js/theme.js"></script>

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
      max-width: 740px;
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
      margin: 22px 0 12px;
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

    .grid-3-col {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 14px;
    }

    .eval-card {
      background: linear-gradient(135deg, rgba(0, 255, 136, 0.08), rgba(0, 217, 255, 0.08));
      border: 1px solid rgba(0, 255, 136, 0.3);
      border-radius: var(--radius-md);
      padding: 20px;
      margin-bottom: 24px;
    }

    .eval-card.needs-attention {
      background: linear-gradient(135deg, rgba(255, 184, 0, 0.08), rgba(255, 71, 87, 0.08));
      border-color: rgba(255, 184, 0, 0.35);
    }

    .disabled-biometrics-overlay {
      position: relative;
    }

    .disabled-biometrics-overlay input:disabled,
    .disabled-biometrics-overlay select:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      background: rgba(0, 0, 0, 0.25);
    }

    @media (max-width: 600px) {
      .grid-2-col, .grid-3-col {
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
        <span style="font-size: 26px;">⚙️</span>
        <div>
          <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; margin: 0;">Ajustes de Perfil & Encuesta</h1>
          <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0;">Gestión de cuenta y parámetros fisiológicos para la IA</p>
        </div>
      </div>
      <a href="dashboard.php" class="btn-checkin" style="width: auto; padding: 6px 14px; font-size: 13px; text-decoration: none; background: rgba(255, 255, 255, 0.06); color: var(--text-primary);">
        ← Volver al Panel
      </a>
    </div>

    <!-- BANNER DE RESTRICCIÓN O DESBLOQUEO POR RUTINA (REQUERIMIENTO 3) -->
    <?php if (!$policy['canUpdate']): ?>
      <div class="policy-banner locked" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
          <span style="font-size: 26px;">🔒</span>
          <div>
            <strong style="color: var(--accent-rose); font-size: 14px;">Modificación de Biometría & Encuesta Protegida</strong>
            <p style="font-size: 12px; margin: 4px 0 6px; color: var(--text-secondary); line-height: 1.5;">
              Para garantizar la consistencia fisiológica y permitir que la IA evalúe tu progreso real, tus datos personales solo pueden actualizarse <strong>después de completar tu rutina asignada</strong>.
            </p>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 6px;">
              <span class="day-badge" style="background: rgba(255, 71, 87, 0.15); color: var(--accent-rose);">
                Progreso: <?php echo $routineStatus['completedCount']; ?> / <?php echo $routineStatus['totalDays']; ?> días completados (<?php echo $routineStatus['progressPct']; ?>%)
              </span>
              <span style="font-size: 12px; color: var(--text-muted);">
                Pendientes: <?php echo implode(', ', $routineStatus['pendingDays']); ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="policy-banner available" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 26px;">🎉</span>
          <div>
            <strong style="color: var(--accent-emerald); font-size: 14px;">¡Actualización Desbloqueada!</strong>
            <p style="font-size: 12px; margin: 3px 0 0; color: var(--text-secondary);">
              <?php echo htmlspecialchars($policy['message']); ?>
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- INFORME DE EVALUACIÓN DE PROGRESO DE LA IA (SI HUBO ACTUALIZACIÓN) -->
    <?php if (!empty($evaluationResult)): ?>
      <div class="eval-card <?php echo !empty($evaluationResult['needsAttention']) ? 'needs-attention' : ''; ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <h3 style="font-family: var(--font-heading); font-size: 16px; margin: 0; color: <?php echo !empty($evaluationResult['needsAttention']) ? 'var(--accent-amber)' : 'var(--accent-emerald)'; ?>;">
            <?php echo htmlspecialchars($evaluationResult['statusTitle'] ?? 'Evaluación de Progreso Kynap AI'); ?>
          </h3>
          <span class="day-badge" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-emerald);">
            Score: <?php echo $evaluationResult['score'] ?? 80; ?>/100
          </span>
        </div>
        <p style="font-size: 13px; line-height: 1.5; color: var(--text-primary); margin-bottom: 12px;">
          <?php echo nl2br(htmlspecialchars($evaluationResult['narrative'])); ?>
        </p>

        <?php if (!empty($evaluationResult['metricsComparison'])): ?>
          <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
            <?php foreach ($evaluationResult['metricsComparison'] as $k => $m): ?>
              <span class="delta-tag <?php echo $m['diff'] > 0 ? 'up' : ($m['diff'] < 0 ? 'down' : 'neutral'); ?>">
                <?php echo ucfirst($k); ?>: <?php echo $m['previous']; ?> ➔ <?php echo $m['current']; ?> (<?php echo ($m['diff'] > 0 ? '+' : '') . $m['diff']; ?>)
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($evaluationResult['recommendations'])): ?>
          <div style="border-top: 1px solid var(--border-subtle); padding-top: 10px;">
            <strong style="font-size: 12px; color: var(--accent-cyan); text-transform: uppercase;">Directrices del Entrenador AI:</strong>
            <ul style="margin: 6px 0 0 18px; font-size: 13px; color: var(--text-secondary); line-height: 1.5;">
              <?php foreach ($evaluationResult['recommendations'] as $rec): ?>
                <li><?php echo htmlspecialchars($rec); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($statusMessage)): ?>
      <div style="background: <?php echo $isSuccess ? 'rgba(0, 255, 136, 0.12)' : 'rgba(255, 71, 87, 0.12)'; ?>; border: 1px solid <?php echo $isSuccess ? 'rgba(0, 255, 136, 0.35)' : 'rgba(255, 71, 87, 0.35)'; ?>; color: <?php echo $isSuccess ? 'var(--accent-emerald)' : '#ff8a93'; ?>; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 13px; font-weight: 600;">
        <?php echo htmlspecialchars($statusMessage); ?>
      </div>
    <?php endif; ?>

    <form id="configForm" method="POST" action="configuration.php" style="display: flex; flex-direction: column; gap: 16px;">
      
      <!-- AVATAR ACTUAL -->
      <div class="avatar-preview-wrapper">
        <img src="<?php echo htmlspecialchars($imagenUser); ?>" alt="Avatar" class="avatar-preview-img" onerror="this.src='iconos/profile.svg'">
        <div>
          <h3 style="font-size: 15px; font-weight: 700;"><?php echo htmlspecialchars($nombre); ?></h3>
          <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">Avatar de atleta Kynap</p>
          <a href="icon_select_user.php" style="font-size: 12px; color: var(--accent-emerald); text-decoration: none; font-weight: 600;">
            Cambiar avatar ➔
          </a>
        </div>
        <input type="hidden" name="imagen_user" value="<?php echo htmlspecialchars($imagenUser); ?>">
      </div>

      <!-- SECCIÓN 1: DATOS DE LA CUENTA (SIEMPRE DISPONIBLES) -->
      <div class="section-title-divider">
        <span>👤</span> Credenciales de Acceso
      </div>

      <div class="grid-2-col">
        <div class="form-group" style="margin: 0;">
          <label for="nombre">Nombre Completo *</label>
          <input type="text" id="nombre" name="nombre" class="input-custom" value="<?php echo htmlspecialchars($nombre); ?>" required>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="email">Correo Electrónico *</label>
          <input type="email" id="email" name="email" class="input-custom" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
      </div>

      <div class="form-group" style="margin: 0;">
        <label for="password">Nueva Contraseña (Dejar en blanco para mantener la actual)</label>
        <input type="password" id="password" name="password" class="input-custom" placeholder="••••••••">
      </div>

      <!-- SECCIÓN 2: BIOMETRÍA Y ENCUESTA (SUJETA A RESTRICCIÓN DE RUTINA) -->
      <div class="section-title-divider">
        <span>📊</span> Biometría & Datos Físicos <?php if (!$policy['canUpdate']) echo '<span style="font-size: 12px; color: var(--accent-rose); margin-left: 6px;">(🔒 Bloqueado por rutina)</span>'; ?>
      </div>

      <div class="disabled-biometrics-overlay">
        <div class="grid-2-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="peso">Peso Corporal (kg) *</label>
            <input type="number" id="peso" name="peso" class="input-custom" value="<?php echo $peso; ?>" step="0.1" min="30" max="300" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?> required>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="altura">Altura (cm) *</label>
            <input type="number" id="altura" name="altura" class="input-custom" value="<?php echo $altura; ?>" step="0.5" min="100" max="250" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?> required>
          </div>
        </div>

        <div class="grid-2-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="edad">Edad (Años) *</label>
            <input type="number" id="edad" name="edad" class="input-custom" value="<?php echo $edad; ?>" min="12" max="100" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?> required>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="sexo">Sexo Biológico *</label>
            <select id="sexo" name="sexo" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?> required>
              <option value="masculino" <?php echo $sexo === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
              <option value="femenino" <?php echo $sexo === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
              <option value="otro" <?php echo $sexo === 'otro' ? 'selected' : ''; ?>>Otro</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="sexo" value="<?php echo htmlspecialchars($sexo); ?>"><?php endif; ?>
          </div>
        </div>

        <div class="grid-2-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="objetivo">Objetivo Físico *</label>
            <select id="objetivo" name="objetivo" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?> required>
              <option value="ganar_musculo" <?php echo $objetivo === 'ganar_musculo' ? 'selected' : ''; ?>>🔥 Hipertrofia & Ganancia Muscular</option>
              <option value="perder_grasa" <?php echo $objetivo === 'perder_grasa' ? 'selected' : ''; ?>>⚡ Definición & Pérdida de Grasa</option>
              <option value="recomposicion" <?php echo $objetivo === 'recomposicion' ? 'selected' : ''; ?>>✨ Recomposición Muscular</option>
              <option value="mantener" <?php echo $objetivo === 'mantener' ? 'selected' : ''; ?>>🛡️ Mantenimiento & Salud</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="objetivo" value="<?php echo htmlspecialchars($objetivo); ?>"><?php endif; ?>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="nivel_actividad">Nivel de Actividad *</label>
            <select id="nivel_actividad" name="nivel_actividad" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?> required>
              <option value="sedentario" <?php echo $nivelActividad === 'sedentario' ? 'selected' : ''; ?>>Sedentario (Poco ejercicio)</option>
              <option value="ligero" <?php echo $nivelActividad === 'ligero' ? 'selected' : ''; ?>>Ligero (1-2 días/semana)</option>
              <option value="moderado" <?php echo $nivelActividad === 'moderado' ? 'selected' : ''; ?>>Moderado (3-5 días/semana)</option>
              <option value="muy_activo" <?php echo $nivelActividad === 'muy_activo' ? 'selected' : ''; ?>>Muy Activo (6-7 días intensos)</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="nivel_actividad" value="<?php echo htmlspecialchars($nivelActividad); ?>"><?php endif; ?>
          </div>
        </div>

        <!-- SECCIÓN 3: ENCUESTA MÉDICA Y ALIMENTARIA -->
        <div class="section-title-divider">
          <span>🥗</span> Encuesta de Salud, Alergias & Dieta
        </div>

        <div class="grid-2-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="condiciones">Condiciones / Limitaciones Físicas</label>
            <select id="condiciones" name="condiciones" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?>>
              <option value="ninguna" <?php echo $condiciones === 'ninguna' ? 'selected' : ''; ?>>Ninguna / Saludable</option>
              <option value="lesion_rodilla" <?php echo $condiciones === 'lesion_rodilla' ? 'selected' : ''; ?>>Lesión / Dolor de Rodilla</option>
              <option value="dolor_lumbar" <?php echo $condiciones === 'dolor_lumbar' ? 'selected' : ''; ?>>Dolor Lumbar / Espalda</option>
              <option value="hipertension" <?php echo $condiciones === 'hipertension' ? 'selected' : ''; ?>>Hipertensión / Cardiovascular</option>
              <option value="diabetes" <?php echo $condiciones === 'diabetes' ? 'selected' : ''; ?>>Diabetes / Resistencia Insulina</option>
              <option value="asma" <?php echo $condiciones === 'asma' ? 'selected' : ''; ?>>Asma / Respiratorio</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="condiciones" value="<?php echo htmlspecialchars($condiciones); ?>"><?php endif; ?>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="alergias">Alergias o Intolerancias</label>
            <select id="alergias" name="alergias" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?>>
              <option value="ninguna" <?php echo $alergias === 'ninguna' ? 'selected' : ''; ?>>Ninguna</option>
              <option value="lactosa" <?php echo $alergias === 'lactosa' ? 'selected' : ''; ?>>Intolerancia a la Lactosa</option>
              <option value="gluten" <?php echo $alergias === 'gluten' ? 'selected' : ''; ?>>Gluten / Celíaco</option>
              <option value="mani" <?php echo $alergias === 'mani' ? 'selected' : ''; ?>>Maní / Frutos Secos</option>
              <option value="mariscos" <?php echo $alergias === 'mariscos' ? 'selected' : ''; ?>>Pescados y Mariscos</option>
              <option value="huevo" <?php echo $alergias === 'huevo' ? 'selected' : ''; ?>>Alergia al Huevo</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="alergias" value="<?php echo htmlspecialchars($alergias); ?>"><?php endif; ?>
          </div>
        </div>

        <div class="grid-2-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="preferencia">Preferencia Dietética</label>
            <select id="preferencia" name="preferencia" class="input-custom" <?php echo !$policy['canUpdate'] ? 'disabled' : ''; ?>>
              <option value="omnivora" <?php echo $preferencia === 'omnivora' ? 'selected' : ''; ?>>🥩 Omnívora / Balanceada</option>
              <option value="vegetariano" <?php echo $preferencia === 'vegetariano' ? 'selected' : ''; ?>>🥗 Vegetariana</option>
              <option value="vegano" <?php echo $preferencia === 'vegano' ? 'selected' : ''; ?>>🌱 Vegana</option>
              <option value="keto" <?php echo $preferencia === 'keto' ? 'selected' : ''; ?>>🥑 Cetogénica / Baja en Carbohidratos</option>
            </select>
            <?php if (!$policy['canUpdate']): ?><input type="hidden" name="preferencia" value="<?php echo htmlspecialchars($preferencia); ?>"><?php endif; ?>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="agua">Consumo de Agua (L/día) *</label>
            <input type="number" id="agua" name="agua" class="input-custom" value="<?php echo $agua; ?>" step="0.5" min="1.0" max="10.0" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?> required>
          </div>
        </div>

        <!-- MEDIDAS CORPORALES OPCIONALES -->
        <div class="grid-3-col" style="margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label for="cintura_cm">Cintura (cm)</label>
            <input type="number" id="cintura_cm" name="cintura_cm" class="input-custom" value="<?php echo $cintura ?? ''; ?>" step="0.5" min="40" max="200" placeholder="Ej: 82.0" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?>>
          </div>
          <div class="form-group" style="margin: 0;">
            <label for="pecho_cm">Pecho (cm)</label>
            <input type="number" id="pecho_cm" name="pecho_cm" class="input-custom" value="<?php echo $pecho ?? ''; ?>" step="0.5" min="40" max="200" placeholder="Ej: 102.0" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?>>
          </div>
          <div class="form-group" style="margin: 0;">
            <label for="brazo_cm">Brazo (cm)</label>
            <input type="number" id="brazo_cm" name="brazo_cm" class="input-custom" value="<?php echo $brazo ?? ''; ?>" step="0.5" min="15" max="80" placeholder="Ej: 37.0" <?php echo !$policy['canUpdate'] ? 'readonly style="opacity: 0.6;"' : ''; ?>>
          </div>
        </div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 14px;">
        <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; padding: 13px; font-size: 15px;">
          <?php echo $policy['canUpdate'] ? '💾 Guardar Datos & Evaluar Progreso' : '💾 Guardar Credenciales'; ?>
        </button>
        <button type="button" class="btn-checkin" onclick="handleLogout(event)" style="background: rgba(255, 71, 87, 0.15); color: var(--accent-rose); width: auto; padding: 13px 20px;">
          Cerrar Sesión
        </button>
      </div>
    </form>
  </main>

  <script>
    function handleLogout(e) {
      if (e) e.preventDefault();
      localStorage.removeItem('kynap_auth_token');
      window.location.href = 'logout.php';
    }
  </script>
</body>
</html>
