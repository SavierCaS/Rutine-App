<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - MASTER DASHBOARD & SAAS SUITE
 * ==============================================================================
 * Panel de control principal unificado con arquitectura SaaS moderna, diseño
 * dark-mode de alta gama, sincronización bidireccional con la app móvil y
 * soporte de navegación táctil mobile-first.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 2.5.0
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

// Verificación estricta de sesión de usuario
if (!isset($_SESSION['nombre']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$userName = htmlspecialchars(ucwords(strtolower($_SESSION['nombre'])));
$userEmail = $_SESSION['email'] ?? 'atleta@kynap.com';
$userAvatar = 'iconos/profile.svg';
$chatActivado = 1;

// Valores biométricos por defecto
$userPeso = 75;
$userAltura = 175;
$userEdad = 25;
$userObjetivo = 'ganar_musculo';
$userNivelActividad = 'moderado';
$userAgua = 3;

if ($dbAvailable) {
    // 1. Obtener imagen de perfil y estado de chat
    $stmtAccount = $conn->prepare("SELECT imagen_user, chat_activado FROM accounts WHERE id = ? LIMIT 1");
    if ($stmtAccount) {
        $stmtAccount->bind_param("i", $userId);
        $stmtAccount->execute();
        $stmtAccount->bind_result($imgUser, $chatAct);
        if ($stmtAccount->fetch()) {
            if (!empty($imgUser)) $userAvatar = $imgUser;
            if ($chatAct !== null) $chatActivado = (int)$chatAct;
        }
        $stmtAccount->close();
    }

    // 2. Obtener datos biométricos completos (incluyendo edad corregida)
    $stmtBio = $conn->prepare("SELECT peso, altura, edad, objetivo, nivel_actividad, agua FROM usuarios WHERE user_id = ? LIMIT 1");
    if ($stmtBio) {
        $stmtBio->bind_param("i", $userId);
        $stmtBio->execute();
        $resBio = $stmtBio->get_result();
        if ($rowBio = $resBio->fetch_assoc()) {
            $userPeso = (float)($rowBio['peso'] ?: $userPeso);
            $userAltura = (float)($rowBio['altura'] ?: $userAltura);
            $userEdad = (int)($rowBio['edad'] ?: $userEdad);
            $userObjetivo = $rowBio['objetivo'] ?: $userObjetivo;
            $userNivelActividad = $rowBio['nivel_actividad'] ?: $userNivelActividad;
            $userAgua = (int)($rowBio['agua'] ?: $userAgua);
        }
        $stmtBio->close();
    }
}

// Generar Bearer Token para comunicación con APIs y compatibilidad con app móvil
$userAuthToken = generateAuthToken($userId, $userEmail);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a0d14">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="description" content="Kynap - Plataforma Modular de Alto Rendimiento para Entrenamiento, Nutrición y Coaching con Inteligencia Artificial.">
  <title>Kynap AI - Panel de Control Fitness</title>

  <!-- Tipografías de Google Fonts: Outfit (Titulares) & Inter (Lectura y Datos) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de estilos del Design System Kynap -->
  <link rel="stylesheet" href="public/css/app.css">
  <script src="public/js/theme.js"></script>
</head>
<body>

  <!-- Contenedor de Notificaciones Toast -->
  <div id="toastContainer" class="toast-container" aria-live="polite"></div>

  <div class="app-container">
    
    <!-- ====================================================================
         BARRA LATERAL / SIDEBAR DE ESCRITORIO
         ==================================================================== -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo">K</div>
        <div class="brand-name">Kynap <span>AI</span></div>
      </div>

      <ul class="nav-links">
        <li>
          <button type="button" class="nav-btn active" onclick="switchTab('routines', this)" id="nav-routines">
            <span class="icon">📅</span>
            <span>Rutina Semanal</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="switchTab('workouts', this); switchWorkoutSubTab('series');" id="nav-workouts">
            <span class="icon">⚡</span>
            <span>Entrenar en Vivo</span>
          </button>
        </li>
        <li class="nav-sub-item">
          <button type="button" class="nav-btn nav-btn-sub" onclick="openBreathingModal()" id="nav-breathing" title="Mente Sana integrada en Entrenamiento en Vivo">
            <span class="icon">🧘</span>
            <span>Mente Sana (En Vivo)</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="switchTab('nutrition', this)" id="nav-nutrition">
            <span class="icon">🥗</span>
            <span>Nutrición & Macros</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="switchTab('exercises', this)" id="nav-exercises">
            <span class="icon">📚</span>
            <span>Librería de Ejercicios</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="toggleAICoach()" id="nav-aicoach">
            <span class="icon">🤖</span>
            <span>Coach AI</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="toggleTheme()" id="nav-theme">
            <span class="icon">🌓</span>
            <span>Modo Oscuro / Claro</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="window.location.href='configuration.php'" id="nav-config">
            <span class="icon">⚙️</span>
            <span>Configuración</span>
          </button>
        </li>
        <li>
          <button type="button" class="nav-btn" onclick="window.location.href='logout.php'" style="color: var(--accent-rose);">
            <span class="icon">🚪</span>
            <span>Cerrar Sesión</span>
          </button>
        </li>
      </ul>

      <!-- Perfil del usuario autenticado en la barra lateral -->
      <div class="user-snippet" onclick="openConfigModal(event)" style="cursor: pointer;" title="Configurar Perfil">
        <div class="user-avatar" id="sidebarAvatar"><?php 
          $initials = 'K';
          $parts = preg_split('/\s+/', trim($userName));
          if (count($parts) === 1 && !empty($parts[0])) {
              $initials = strtoupper(substr($parts[0], 0, 2));
          } elseif (count($parts) >= 2) {
              $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
          }
          echo htmlspecialchars($initials);
        ?></div>
        <div class="user-meta">
          <h4 id="sidebarUserName"><?php echo htmlspecialchars($userName); ?></h4>
          <p>Plan Pro Activo</p>
        </div>
      </div>
    </aside>

    <!-- ====================================================================
         ÁREA DE CONTENIDO PRINCIPAL
         ==================================================================== -->
    <main class="main-content">
      
      <!-- HEADER CON RESUMEN, RACHA Y ESTADO ONLINE -->
      <header class="header-bar">
        <div class="page-title">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
            <h1>Hola, <?php echo $userName; ?> 👋</h1>
            <div id="onlineStatusPill" class="status-pill online">
              <span class="status-dot"></span>
              <span>Sincronizado</span>
            </div>
          </div>
          <p>Semana actual: Bloque de Fuerza e Hipertrofia Funcional</p>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px;">
          <button type="button" class="btn-theme-toggle" onclick="toggleTheme()" aria-label="Cambiar tema">🌙</button>
          <div class="streak-badge" id="headerStreakBadge" onclick="openStreakModal()" title="Ver detalles de mi racha y calendario" style="cursor: pointer;">
            <span class="flame-animated">🔥</span>
            <span id="streakCounter">1 día activo</span>
            <span class="streak-medal-pill" id="streakMedalTag">🔥 Fuego</span>
          </div>
          <button type="button" class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 255, 136, 0.15); color: var(--accent-emerald);" onclick="openBiometricsModal()">
            📊 Evolución Física
          </button>
          <button type="button" class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.15); color: var(--accent-cyan);" onclick="openBreathingModal()">
            🧘 Respiración
          </button>
          <button type="button" class="btn-checkin" style="width: auto; padding: 8px 16px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700;" onclick="toggleAICoach()">
            🤖 Coach AI
          </button>
        </div>
      </header>

      <!-- ====================================================================
           TAB 1: CORE 1 - RUTINA SEMANAL & CHECK-IN
           ==================================================================== -->
      <section id="view-routines" class="tab-view active">
        <div class="routine-progress-card">
          <div class="progress-header">
            <div>
              <h3>Cumplimiento Semanal</h3>
              <p style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">4 entrenamientos programados para esta semana</p>
            </div>
            <span class="progress-pct" id="weeklyProgressPct">0%</span>
          </div>
          <div class="progress-track">
            <div class="progress-bar-fill" id="weeklyProgressBar" style="width: 0%;"></div>
          </div>
        </div>

        <!-- BANNER DE GENERACIÓN INTELIGENTE CON GROQ AI (LLAMA 3.3) -->
        <div class="ai-routine-generator-card" style="background: linear-gradient(135deg, rgba(0, 255, 136, 0.08), rgba(0, 217, 255, 0.08)); border: 1px solid rgba(0, 255, 136, 0.25); border-radius: var(--radius-md); padding: 18px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 20px;">🤖</span>
              <h4 style="font-family: var(--font-heading); font-size: 16px; margin: 0; font-weight: 700;">Diseñador de Rutinas Kynap AI</h4>
              <span class="day-badge" style="background: rgba(0, 217, 255, 0.15); color: var(--accent-cyan); font-size: 11px;">Llama 3.3 Versatile</span>
            </div>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 4px 0 0;">Genera una rutina de entrenamiento personalizada según tu biometría, objetivo y nivel físico.</p>
          </div>
          <button type="button" class="btn-checkin" id="btnOpenAiRoutineModal" onclick="openAiRoutineModal()" style="width: auto; padding: 10px 20px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; font-size: 14px; box-shadow: 0 4px 15px rgba(0,255,136,0.25);">
            ✨ Generar Nueva Rutina con IA
          </button>
        </div>

        <div class="days-grid" id="daysGrid">
          <!-- Renderizado dinámicamente por app.js con sincronización en tiempo real -->
        </div>
      </section>

      <!-- ====================================================================
           TAB 2: CORE 2 - WORKOUT EN VIVO & SETS (INCLUYE MENTE SANA & RESPIRACIÓN)
           ==================================================================== -->
      <section id="view-workouts" class="tab-view">

        <!-- SUB-NAVEGACIÓN INTERNA: SERIES vs MENTE SANA -->
        <div class="workout-subnav-bar" role="tablist" aria-label="Subsecciones de Entrenamiento en Vivo">
          <button type="button" class="workout-subnav-btn active" id="btnSubnavSeries" onclick="switchWorkoutSubTab('series')" role="tab" aria-selected="true">
            <span>⚡ Sesión de Series</span>
          </button>
          <button type="button" class="workout-subnav-btn" id="btnSubnavBreathing" onclick="switchWorkoutSubTab('breathing')" role="tab" aria-selected="false">
            <span>🧘 Mente Sana & Respiración</span>
          </button>
        </div>

        <!-- SUB-VISTA 1: REGISTRO DE SERIES EN VIVO -->
        <div id="workoutSubtabSeries" class="live-workout-container">
          
          <div class="workout-logger-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
              <h2 style="font-family: var(--font-heading);">Sesión de Entrenamiento Activa</h2>
              <span class="day-badge" style="background: rgba(0, 255, 136, 0.15); color: var(--accent-emerald);">En Progreso</span>
            </div>
            
            <div class="exercise-selector-bar">
              <select id="exerciseSelector" class="select-custom" onchange="AppState.activeWorkout.exerciseId = this.value; recalculateWorkoutMetrics();" aria-label="Seleccionar ejercicio actual">
                <!-- Inyectado dinámicamente -->
              </select>
              <button type="button" class="btn-checkin" style="width: auto; padding: 0 20px;" onclick="addSetRow()">+ Serie</button>
            </div>

            <div class="sets-table-wrapper">
              <table class="sets-table">
                <thead>
                  <tr>
                    <th>Serie</th>
                    <th>Peso (kg)</th>
                    <th>Reps</th>
                    <th>RPE</th>
                    <th>Estado</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="setsTableBody">
                  <!-- Filas de series interactivas -->
                </tbody>
              </table>
            </div>

            <button type="button" class="btn-checkin" style="background: linear-gradient(90deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; margin-top: 18px;" onclick="finishWorkoutSession(this)">
              Finalizar Sesión & Guardar Tonelaje
            </button>
          </div>

          <!-- SIDEBAR DE MÉTRICAS Y TEMPORIZADOR DE DESCANSO -->
          <div class="workout-stats-sidebar">
            <div class="stat-box">
              <h4>Volumen Total (Tonelaje)</h4>
              <div class="stat-number" id="statTotalVolume">0 kg</div>
              <p style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Sumatoria de Peso × Reps completadas</p>
            </div>

            <div class="stat-box">
              <h4>1RM Estimada (Fórmula de Epley)</h4>
              <div class="stat-number amber" id="statMax1RM">0 kg</div>
              <p style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Fuerza máxima calculada para el ejercicio</p>
            </div>

            <div class="rest-timer-box">
              <h4 style="color: var(--accent-purple); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Descanso Entre Series</h4>
              <div class="timer-digits" id="timerDigits">01:30</div>
              <div class="timer-controls">
                <button type="button" class="btn-checkin" style="background: var(--accent-purple); color: #fff; width: auto; padding: 6px 14px; font-size: 12px;" onclick="startRestTimer(90)">Iniciar 90s</button>
                <button type="button" class="btn-checkin" style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); width: auto; padding: 6px 12px; font-size: 12px;" onclick="addTimerSeconds(30)">+30s</button>
                <button type="button" class="btn-checkin" style="background: rgba(255,71,87,0.12); border: 1px solid rgba(255,71,87,0.25); color: var(--accent-rose); width: auto; padding: 6px 12px; font-size: 12px;" onclick="resetRestTimer()">Reset</button>
              </div>
            </div>

            <!-- ACCESO DIRECTO INTEGRADO A MENTE SANA -->
            <div class="stat-box" style="margin-top: 16px; border: 1px dashed rgba(0, 217, 255, 0.35); background: rgba(0, 217, 255, 0.04);">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <h4 style="color: var(--accent-cyan); margin: 0; font-size: 13px;">🧘 Mente Sana</h4>
                <span style="font-size: 10px; background: rgba(0, 217, 255, 0.15); color: var(--accent-cyan); padding: 2px 8px; border-radius: 10px; font-weight: 700;">PRE / POST</span>
              </div>
              <p style="font-size: 11px; color: var(--text-secondary); margin: 6px 0 10px 0;">Calma tu ritmo cardíaco o activa tu sistema nervioso con respiración guiada.</p>
              <button type="button" class="btn-checkin" style="background: rgba(0, 217, 255, 0.15); border: 1px solid rgba(0, 217, 255, 0.3); color: var(--accent-cyan); padding: 8px 14px; font-size: 12px; width: 100%;" onclick="openBreathingModal()">
                Abrir Respiración Guiada ➔
              </button>
            </div>
          </div>

        </div>

        <!-- SUB-VISTA 2: MENTE SANA & RESPIRACIÓN GUIADA (INTEGRADA NATIVAMENTE) -->
        <div id="workoutSubtabBreathing" style="display: none;">
          <div class="mente-sana-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
              <h2 style="font-family: var(--font-heading); font-size: 22px; margin: 0;">🧘 Mente Sana: Respiración Guiada</h2>
              <button type="button" class="btn-checkin" style="width: auto; padding: 6px 14px; font-size: 12px; background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text);" onclick="switchWorkoutSubTab('series')">
                ← Volver a Sesión de Series
              </button>
            </div>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 24px; max-width: 600px; margin-left: auto; margin-right: auto;">
              Optimiza tu enfoque deportivo pre-entreno o acelera tu recuperación neuromuscular sincronizando el ritmo de tu respiración.
            </p>

            <div class="breathing-sphere-container">
              <div class="breathing-sphere" onclick="toggleBreathingExercise()"
                title="Haz clic para iniciar o pausar la respiración">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                  <span class="breathing-text">Iniciar</span>
                  <span class="breathing-countdown" style="font-size: 13px; font-weight: 600; opacity: 0.9; margin-top: 2px;"></span>
                </div>
              </div>
            </div>

            <div class="breathing-timer-box" style="margin: 12px 0 16px 0; font-size: 13px; color: var(--accent-cyan); font-weight: 600; text-align: center;">
              ⏱️ Tiempo de Sesión: <span class="breathing-session-clock">00:00</span>
            </div>

            <div style="margin: 24px auto; max-width: 440px; text-align: left;">
              <label
                style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Patrón
                de Respiración:</label>
              <select class="input-custom breathing-preset-select" onchange="changeBreathingPreset(this.value)" style="width: 100%; margin-top: 6px;">
                <option value="4-4-4" selected>4-4-4 Box Breathing (Equilibrio Mental & Enfoque)</option>
                <option value="4-7-8">4-7-8 Respiración Profunda (Recuperación y Calma)</option>
                <option value="2-2">2-2 Respiración Energizante (Activación Pre-Entreno)</option>
                <option value="5-5">5-5 Coherencia Cardíaca (Estabilidad Cardiorrespiratoria)</option>
              </select>
            </div>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
              <button type="button" class="btn-breathing-toggle" data-no-throttle="true"
                style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); border: none; border-radius: var(--radius-sm); width: auto; padding: 12px 28px; font-weight: 700; cursor: pointer; transition: all 0.2s;"
                onclick="toggleBreathingExercise()">
                ▶ Comenzar Sesión
              </button>
              <button type="button" class="btn-breathing-reset" data-no-throttle="true"
                style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); border-radius: var(--radius-sm); width: auto; padding: 12px 20px; font-weight: 600; cursor: pointer;"
                onclick="resetBreathingExercise()">
                🔄 Reiniciar
              </button>
              <button type="button" class="btn-checkin"
                style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); width: auto; padding: 12px 20px;"
                onclick="switchWorkoutSubTab('series')">
                Volver a Series
              </button>
            </div>

            <div style="margin-top: 24px; padding: 14px 18px; border-radius: var(--radius-md); background: rgba(0, 255, 136, 0.05); border: 1px solid rgba(0, 255, 136, 0.15); text-align: left; font-size: 12px; color: var(--text-secondary);">
              <strong style="color: var(--accent-emerald);">💡 Recomendación de Entrenamiento:</strong>
              Usa el patrón <strong>2-2 Energizante</strong> antes de levantar cargas pesadas para activar el sistema nervioso, o 3-5 minutos de <strong>4-7-8</strong> al terminar para acelerar la recuperación y reducir el cortisol.
            </div>
          </div>
        </div>
      </section>

      <!-- ====================================================================
           TAB 3: CORE 3 - NUTRICIÓN, HORARIOS DE COMIDAS & MACROS
           ==================================================================== -->
      <section id="view-nutrition" class="tab-view">
        
        <!-- SELECTOR INTERACTIVO DE DÍA DE LA SEMANA (LUNES A DOMINGO CON DÍA ACTUAL DESTACADO) -->
        <div class="meal-day-tabs-wrapper">
          <div style="display: flex; align-items: center; gap: 8px; margin-right: 12px; white-space: nowrap;">
            <span style="font-size: 18px;">📅</span>
            <strong style="font-size: 13px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Plan Semanal:</strong>
          </div>
          <div class="meal-day-tabs" id="mealDayTabs">
            <!-- Inyectado dinámicamente con los 7 días y badge '⭐ HOY' -->
          </div>
        </div>

        <div class="meal-schedule-section">
          
          <!-- BANNER INFORMATIVO DE REINICIO DIARIO AUTOMÁTICO A MEDIANOCHE -->
          <div style="background: rgba(0, 217, 255, 0.08); border: 1px solid rgba(0, 217, 255, 0.22); border-radius: var(--radius-md); padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <span style="font-size: 20px;">🔄</span>
              <div>
                <strong style="color: var(--accent-cyan); font-size: 13px;">Reinicio Automático a Medianoche</strong>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0;">Cada medianoche el estado diario de comidas se renueva automáticamente. Todo tu historial permanece seguro en la base de datos.</p>
              </div>
            </div>
            <div id="mealCompletionCounterBadge" class="day-badge" style="background: rgba(0, 255, 136, 0.15); color: var(--accent-emerald); font-size: 12px;">
              0 / 5 Comidas Completadas (0%)
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px;">
            
            <!-- COLUMNA IZQUIERDA: BLOQUES DE COMIDAS ESTRUCTURADAS (DESAYUNO, ALMUERZO, CENA, ETC.) -->
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin: 0;" id="scheduledMealsHeading">Horarios & Alimentos Programados</h3>
                <span class="day-badge" id="selectedDayNameBadge" style="background: var(--pill-bg, rgba(255, 255, 255, 0.08)); border: 1px solid var(--border-subtle); color: var(--text-primary); font-size: 11px;">Hoy</span>
              </div>

              <!-- CONTENEDOR DE BLOQUES DE COMIDAS -->
              <div class="meal-blocks-container" id="mealScheduleContainer">
                <!-- Renderizado por app.js con Desayuno, Media Mañana, Almuerzo, Merienda, Cena -->
              </div>
            </div>

            <!-- COLUMNA DERECHA: BALANCE ENERGÉTICO, HIDRATACIÓN Y COMIDAS ADICIONALES -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
              
              <div class="macro-summary-card">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                  <h2 style="font-family: var(--font-heading); font-size: 18px;">Balance Energético</h2>
                  <button type="button" class="btn-checkin" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="openAddMealModal()">+ Añadir Extra</button>
                </div>

                <div style="margin-top: 14px;">
                  <span id="caloriesConsumed" style="font-family: var(--font-heading); font-size: 36px; font-weight: 800; color: var(--accent-emerald);">0 kcal</span>
                  <span id="caloriesTarget" style="color: var(--text-muted); font-size: 15px;">/ 2200 kcal</span>
                </div>

                <div class="macro-bars">
                  <div class="macro-bar-item">
                    <div class="macro-label-row">
                      <span style="color: var(--accent-cyan); font-weight: 600; font-size: 13px;">Proteínas</span>
                      <span id="valProtein" style="font-size: 13px;">0g / 150g</span>
                    </div>
                    <div class="macro-bar-track">
                      <div class="macro-bar-fill fill-protein" id="barProtein" style="width: 0%;"></div>
                    </div>
                  </div>

                  <div class="macro-bar-item">
                    <div class="macro-label-row">
                      <span style="color: var(--accent-amber); font-weight: 600; font-size: 13px;">Carbohidratos</span>
                      <span id="valCarbs" style="font-size: 13px;">0g / 230g</span>
                    </div>
                    <div class="macro-bar-track">
                      <div class="macro-bar-fill fill-carbs" id="barCarbs" style="width: 0%;"></div>
                    </div>
                  </div>

                  <div class="macro-bar-item">
                    <div class="macro-label-row">
                      <span style="color: var(--accent-rose); font-weight: 600; font-size: 13px;">Grasas</span>
                      <span id="valFats" style="font-size: 13px;">0g / 65g</span>
                    </div>
                    <div class="macro-bar-track">
                      <div class="macro-bar-fill fill-fats" id="barFats" style="width: 0%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="water-tracker-card">
                <h3 style="font-family: var(--font-heading); font-size: 18px;">Hidratación Diaria 💧</h3>
                <div class="water-display" id="waterDisplay">1750 ml</div>
                <p id="waterTarget" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;">Meta: 2500 ml</p>
                <div class="water-actions">
                  <button type="button" class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.2); color: var(--accent-cyan);" onclick="adjustWater(250)">+250 ml</button>
                  <button type="button" class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.2); color: var(--accent-cyan);" onclick="adjustWater(500)">+500 ml</button>
                  <button type="button" class="btn-checkin" style="width: auto; padding: 8px 12px; background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text);" onclick="adjustWater(-250)">-250 ml</button>
                </div>
              </div>

              <div class="macro-summary-card">
                <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 12px;">Alimentos Extra Registrados</h3>
                <ul id="recentMealsList" style="list-style: none;">
                  <!-- Lista de comidas adicionales -->
                </ul>
              </div>

            </div>

          </div>
        </div>
      </section>

      <!-- ====================================================================
           TAB 4: CORE 4 - LIBRERÍA DE EJERCICIOS CON DEMOSTRACIONES
           ==================================================================== -->
      <section id="view-exercises" class="tab-view">
        <div class="filter-bar">
          <input type="text" id="exerciseSearchInput" class="search-input" placeholder="Buscar ejercicio por nombre o músculo (ej. sentadillas, glúteo, abdomen)..." aria-label="Buscar ejercicio">
          
          <div class="chip-filters">
            <button type="button" class="chip active" onclick="filterExercisesByMuscle('all', this)">Todos</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('cuadriceps', this)">Cuádriceps</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('gluteos', this)">Glúteos</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('abdomen', this)">Abdomen</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('pecho', this)">Pecho</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('triceps', this)">Tríceps</button>
            <button type="button" class="chip" onclick="filterExercisesByMuscle('cardio', this)">Cardio</button>
          </div>
        </div>

        <div class="exercise-catalog-grid" id="exerciseCatalogGrid">
          <!-- Tarjetas con animaciones GIF -->
        </div>
      </section>

    </main>
  </div>

  <!-- ====================================================================
       BARRA DE NAVEGACIÓN INFERIOR PARA MÓVILES (MOBILE-FIRST)
       ==================================================================== -->
  <nav class="mobile-bottom-nav" aria-label="Navegación móvil">
    <button type="button" class="mobile-nav-item active" id="mobile-nav-routines" onclick="switchTab('routines')">
      <span class="icon">📅</span>
      <span>Rutina</span>
    </button>
    <button type="button" class="mobile-nav-item" id="mobile-nav-workouts" onclick="switchTab('workouts')">
      <span class="icon">⚡</span>
      <span>Entrenar</span>
    </button>
    <button type="button" class="mobile-nav-item" id="mobile-nav-nutrition" onclick="switchTab('nutrition')">
      <span class="icon">🥗</span>
      <span>Nutrición</span>
    </button>
    <button type="button" class="mobile-nav-item" id="mobile-nav-exercises" onclick="switchTab('exercises')">
      <span class="icon">📚</span>
      <span>Ejercicios</span>
    </button>
    <button type="button" class="mobile-nav-item" id="mobile-nav-coach" onclick="toggleAICoach()">
      <span class="icon">🤖</span>
      <span>Coach AI</span>
    </button>
  </nav>

  <!-- ====================================================================
       BOTÓN FLOTANTE Y DRAWER DE KYNAP AI COACH
       ==================================================================== -->
  <button type="button" class="floating-ai-btn" onclick="toggleAICoach()" aria-label="Abrir asistente inteligente Kynap AI">
    🤖
  </button>

  <aside class="chat-drawer" id="aiCoachDrawer" aria-label="Chat con Asistente Kynap AI">
    <div class="chat-drawer-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">🤖</span>
        <strong style="font-family: var(--font-heading); font-size: 15px;">Kynap Coach AI</strong>
      </div>
      <button type="button" class="btn-icon" onclick="toggleAICoach()" style="font-size: 16px;">✕</button>
    </div>
    <div class="chat-drawer-messages" id="aiCoachMessages">
      <div class="chat-bubble bot">
        ¡Hola <strong><?php echo $userName; ?></strong>! Soy tu coach personal Kynap AI. ¿Deseas recomendaciones sobre tu rutina de hoy, ajuste de macronutrientes o consejos de recuperación?
      </div>
    </div>
    <div class="chat-drawer-input">
      <input type="text" id="aiCoachInput" class="input-custom" placeholder="Escribe tu consulta fitness..." style="flex-grow: 1;">
      <button type="button" class="btn-checkin" style="width: auto; padding: 0 16px;" onclick="sendAICoachMessage()">Enviar</button>
    </div>
  </aside>

  <!-- ====================================================================
       MODAL 1: AÑADIR COMIDA CON MACROS
       ==================================================================== -->
  <div class="modal-backdrop" id="addMealModal" onclick="if(event.target === this) closeAddMealModal()">
    <div class="modal-sheet" style="padding: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px;">Registrar Alimento</h2>
        <button type="button" class="btn-icon" onclick="closeAddMealModal()">✕</button>
      </div>
      <form id="addMealForm" action="#" method="POST" onsubmit="submitAddMealForm(event)">
        <div class="form-group">
          <label for="mealNameInput">Nombre de la Comida / Plato</label>
          <input type="text" id="mealNameInput" class="input-custom" placeholder="Ej: Pollo con batata y ensalada" required>
        </div>
        <div class="form-group">
          <label for="mealTypeInput">Momento del Día</label>
          <select id="mealTypeInput" class="input-custom">
            <option value="Desayuno">Desayuno</option>
            <option value="Almuerzo" selected>Almuerzo</option>
            <option value="Cena">Cena</option>
            <option value="Snack">Snack / Post-Entreno</option>
          </select>
        </div>
        <div class="macro-inputs-grid">
          <div class="form-group">
            <label for="mealProteinInput" style="color: var(--accent-cyan);">Proteínas (g)</label>
            <input type="number" id="mealProteinInput" class="input-custom" placeholder="0" min="0" step="0.5" required>
          </div>
          <div class="form-group">
            <label for="mealCarbsInput" style="color: var(--accent-amber);">Carbos (g)</label>
            <input type="number" id="mealCarbsInput" class="input-custom" placeholder="0" min="0" step="0.5" required>
          </div>
          <div class="form-group">
            <label for="mealFatsInput" style="color: var(--accent-rose);">Grasas (g)</label>
            <input type="number" id="mealFatsInput" class="input-custom" placeholder="0" min="0" step="0.5" required>
          </div>
        </div>
        <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; margin-top: 10px;">
          Guardar Comida
        </button>
      </form>
    </div>
  </div>

  <!-- ====================================================================
       MODAL 2: DETALLE DEL EJERCICIO CON GIF DEMOSTRATIVO
       ==================================================================== -->
  <div class="modal-backdrop" id="exerciseModal" onclick="if(event.target === this) closeExerciseModal()">
    <div class="modal-sheet">
      <img src="" id="modalMedia" class="modal-media" alt="Demostración">
      <div class="modal-body">
        <span class="day-badge" id="modalCategory" style="background-color: rgba(0, 217, 255, 0.15); color: var(--accent-cyan);"></span>
        <h2 id="modalTitle" style="margin-top: 8px;"></h2>
        <p id="modalInstructionText" class="modal-instructions"></p>
        <button type="button" class="btn-checkin" onclick="closeExerciseModal()" style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); margin-top: 12px;">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================================
       MODAL 3: MENTE SANA & RESPIRACIÓN GUIADA
       ==================================================================== -->
  <div class="modal-backdrop" id="breathingModal" onclick="if(event.target === this) closeBreathingModal()">
    <div class="modal-sheet" style="padding: 24px; text-align: center; max-width: 520px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px; margin: 0;">🧘 Mente Sana: Respiración Guiada</h2>
        <button class="btn-icon" onclick="closeBreathingModal()" type="button" aria-label="Cerrar respiración">✕</button>
      </div>
      <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
        Optimiza tu enfoque deportivo pre-entreno o acelera tu recuperación neuromuscular sincronizando el ritmo de tu respiración.
      </p>

      <div class="breathing-sphere-container">
        <div class="breathing-sphere" onclick="toggleBreathingExercise()"
          title="Haz clic para iniciar o pausar la respiración">
          <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
            <span class="breathing-text">Iniciar</span>
            <span class="breathing-countdown" style="font-size: 13px; font-weight: 600; opacity: 0.9; margin-top: 2px;"></span>
          </div>
        </div>
      </div>

      <div class="breathing-timer-box" style="margin: 12px 0 16px 0; font-size: 13px; color: var(--accent-cyan); font-weight: 600; text-align: center;">
        ⏱️ Tiempo de Sesión: <span class="breathing-session-clock">00:00</span>
      </div>

      <div style="margin: 20px 0; text-align: left;">
        <label
          style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Patrón de
          Respiración:</label>
        <select class="input-custom breathing-preset-select" onchange="changeBreathingPreset(this.value)" style="width: 100%; margin-top: 6px;">
          <option value="4-4-4" selected>4-4-4 Box Breathing (Equilibrio Mental & Enfoque)</option>
          <option value="4-7-8">4-7-8 Respiración Profunda (Recuperación y Calma)</option>
          <option value="2-2">2-2 Respiración Energizante (Activación Pre-Entreno)</option>
          <option value="5-5">5-5 Coherencia Cardíaca (Estabilidad Cardiorrespiratoria)</option>
        </select>
      </div>

      <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <button type="button" class="btn-breathing-toggle" data-no-throttle="true"
          style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); border: none; border-radius: var(--radius-sm); width: auto; padding: 10px 24px; font-weight: 700; cursor: pointer; transition: all 0.2s;"
          onclick="toggleBreathingExercise()">
          ▶ Comenzar Sesión
        </button>
        <button type="button" class="btn-breathing-reset" data-no-throttle="true"
          style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); border-radius: var(--radius-sm); width: auto; padding: 10px 20px; font-weight: 600; cursor: pointer;"
          onclick="resetBreathingExercise()">
          🔄 Reiniciar
        </button>
        <button type="button" class="btn-checkin"
          style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-secondary); border-radius: var(--radius-sm); width: auto; padding: 10px 20px;"
          onclick="closeBreathingModal()">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL 5: GENERADOR DE RUTINAS CON KYNAP AI (LLAMA 3.3) -->
  <div class="modal-backdrop" id="aiRoutineModal" onclick="if(event.target === this) closeAiRoutineModal()">
    <div class="modal-sheet" style="padding: 24px; max-width: 500px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 22px;">🤖</span>
          <h2 style="font-family: var(--font-heading); font-size: 20px; margin: 0;">Diseñar Rutina con IA</h2>
        </div>
        <button class="btn-icon" onclick="closeAiRoutineModal()">✕</button>
      </div>
      <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 18px;">
        Kynap AI analiza tu perfil biométrico y programa una periodización óptima usando el modelo <strong>Llama 3.3 Versatile</strong>.
      </p>

      <form id="aiRoutineForm" onsubmit="handleGenerateAIRoutine(event)" style="display: flex; flex-direction: column; gap: 14px;">
        <div class="form-group" style="margin: 0;">
          <label for="aiRoutineObjetivo">Objetivo Fisiológico Principal</label>
          <select id="aiRoutineObjetivo" class="input-custom">
            <option value="ganar_musculo" selected>Hipertrofia & Ganancia Muscular</option>
            <option value="perder_grasa">Definición & Pérdida de Grasa</option>
            <option value="mantener">Fuerza Funcional & Mantenimiento</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="aiRoutineDias">Días de Entrenamiento por Semana</label>
          <select id="aiRoutineDias" class="input-custom">
            <option value="3">3 Días (Full Body / Frecuencia Óptima)</option>
            <option value="4" selected>4 Días (Torso / Pierna / Híbrido)</option>
            <option value="5">5 Días (Push / Pull / Legs + Especialización)</option>
            <option value="6">6 Días (Alto Rendimiento / Avanzado)</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="aiRoutineEquipamiento">Equipamiento Disponible</label>
          <select id="aiRoutineEquipamiento" class="input-custom">
            <option value="gimnasio_completo" selected>Gimnasio Completo (Barras, Mancuernas, Máquinas)</option>
            <option value="mancuernas">Mancuernas y Banco (En Casa)</option>
            <option value="corporal">Peso Corporal / Calistenia</option>
          </select>
        </div>

        <div id="aiRoutineStatusBox" style="display: none; background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.3); border-radius: var(--radius-sm); padding: 12px; font-size: 13px; color: var(--accent-cyan); text-align: center;">
          <span class="status-dot" style="display: inline-block; margin-right: 6px;"></span>
          <span id="aiRoutineStatusText">Conectando con Llama 3.3...</span>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 8px;">
          <button type="submit" id="btnSubmitAiRoutine" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; flex: 1;">
            ✨ Generar Rutina con Llama 3.3
          </button>
          <button type="button" class="btn-checkin" onclick="closeAiRoutineModal()" style="background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text); width: auto; padding: 10px 18px;">
            Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 4: CONFIGURACIÓN DE PERFIL & BIOMETRÍA -->
  <div class="modal-backdrop" id="configModal" onclick="if(event.target === this) closeConfigModal()">
    <div class="modal-sheet" style="padding: 24px; max-width: 480px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px;">⚙️ Configuración del Atleta</h2>
        <button class="btn-icon" onclick="closeConfigModal()">✕</button>
      </div>
      <form id="profileConfigForm" action="#" method="POST" onsubmit="handleProfileUpdate(event)" style="display: flex; flex-direction: column; gap: 14px;">
        <div class="form-group" style="margin: 0;">
          <label for="cfgNombre">Nombre Completo</label>
          <input type="text" id="cfgNombre" class="input-custom" required autocomplete="name">
        </div>
        <div class="form-group" style="margin: 0;">
          <label for="cfgEmail">Correo Electrónico</label>
          <input type="email" id="cfgEmail" class="input-custom" required autocomplete="email">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group" style="margin: 0;">
            <label for="cfgPeso">Peso Actual (kg)</label>
            <input type="number" id="cfgPeso" class="input-custom" step="0.5" min="30" max="300" required>
          </div>
          <div class="form-group" style="margin: 0;">
            <label for="cfgAltura">Altura (cm)</label>
            <input type="number" id="cfgAltura" class="input-custom" min="100" max="250" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group" style="margin: 0;">
            <label for="cfgEdad">Edad (Años)</label>
            <input type="number" id="cfgEdad" class="input-custom" min="12" max="100" required>
          </div>
          <div class="form-group" style="margin: 0;">
            <label for="cfgAgua">Meta de Agua (L)</label>
            <input type="number" id="cfgAgua" class="input-custom" step="0.5" min="1" max="10" required>
          </div>
        </div>
        <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; margin-top: 8px;">
          Guardar Configuración
        </button>
      </form>
    </div>
  </div>

  <!-- MODAL 6: SISTEMA DE RACHAS, MEDALLAS & CALENDARIO COMPACTO (REQUERIMIENTO 3) -->
  <div class="modal-backdrop" id="streakModal" onclick="if(event.target === this) closeStreakModal()">
    <div class="modal-sheet" style="padding: 26px; max-width: 480px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px; margin: 0;">🔥 Tu Racha de Entrenamiento</h2>
        <button class="btn-icon" onclick="closeStreakModal()">✕</button>
      </div>

      <div class="streak-hero-card">
        <span class="flame-animated" style="font-size: 48px;">🔥</span>
        <div class="streak-hero-number" id="streakModalDays">1</div>
        <div class="streak-hero-subtitle">Días Consecutivos Activo</div>
        <div style="margin-top: 10px;">
          <span class="streak-medal-pill" id="streakModalMedal" style="font-size: 13px; padding: 4px 12px;">🔥 Fuego Inicial</span>
        </div>
      </div>

      <div style="background: var(--bg-surface-elevated, var(--bg-card)); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-bottom: 6px;">
          <span style="color: var(--text-secondary);">Próxima Medalla:</span>
          <strong style="color: var(--accent-amber);" id="streakModalGoal">2 días para Bronce 🥉</strong>
        </div>
        <div class="progress-track" style="height: 8px;">
          <div class="progress-bar-fill" id="streakModalProgressBar" style="width: 33%; background: linear-gradient(90deg, #ff7b00, #ffb300);"></div>
        </div>
      </div>

      <div>
        <h4 style="font-family: var(--font-heading); font-size: 14px; margin-bottom: 6px;">Últimos 7 Días de Actividad</h4>
        <div class="streak-calendar-strip" id="streakCalendarStrip">
          <!-- Renderizado dinámicamente con los 7 días -->
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px;">
        <div style="background: var(--bg-surface-elevated, var(--bg-card)); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px; text-align: center;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Mejor Racha Histórica</div>
          <div style="font-size: 20px; font-weight: 800; color: var(--accent-cyan); margin-top: 2px;" id="streakModalLongest">1 día</div>
        </div>
        <div style="background: var(--bg-surface-elevated, var(--bg-card)); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px; text-align: center;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Total Días Activo</div>
          <div style="font-size: 20px; font-weight: 800; color: var(--accent-emerald); margin-top: 2px;" id="streakModalTotal">1 día</div>
        </div>
      </div>

      <button class="btn-checkin" style="margin-top: 18px; background: var(--btn-secondary-bg); border: 1px solid var(--btn-secondary-border); color: var(--btn-secondary-text);" onclick="closeStreakModal()">
        Cerrar
      </button>
    </div>
  </div>

  <!-- MODAL 7: CONDICIÓN FÍSICA & EVOLUCIÓN CON REGLA DE 7 DÍAS Y 24H (REQUERIMIENTO 4) -->
  <div class="modal-backdrop" id="biometricsModal" onclick="if(event.target === this) closeBiometricsModal()">
    <div class="modal-sheet" style="padding: 24px; max-width: 620px; max-height: 90vh; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 22px;">📊</span>
          <h2 style="font-family: var(--font-heading); font-size: 20px; margin: 0;">Evolución de Condición Física</h2>
        </div>
        <button class="btn-icon" onclick="closeBiometricsModal()">✕</button>
      </div>

      <!-- BANNER DINÁMICO DE POLÍTICA (MODO CORRECCIÓN 24H vs COOLDOWN 7 DÍAS) -->
      <div id="bioPolicyBanner" class="policy-banner available">
        <!-- Inyectado dinámicamente -->
      </div>

      <!-- PESTAÑAS DEL MODAL: FORMULARIO vs HISTORIAL -->
      <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px; margin-bottom: 16px;">
        <button type="button" class="chip active" id="btnTabBioForm" onclick="switchBioSubTab('form')">✏️ Actualizar Medidas</button>
        <button type="button" class="chip" id="btnTabBioHistory" onclick="switchBioSubTab('history')">📈 Historial de Progreso</button>
      </div>

      <!-- CONTENIDO 1: FORMULARIO DE ACTUALIZACIÓN / CORRECCIÓN -->
      <div id="bioTabContentForm">
        <form id="biometricsForm" onsubmit="handleBiometricsSubmit(event)" style="display: flex; flex-direction: column; gap: 14px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group" style="margin: 0;">
              <label for="bioInputPeso">Peso Corporal (kg) *</label>
              <input type="number" id="bioInputPeso" class="input-custom" step="0.1" min="30" max="300" required>
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="bioInputAltura">Altura (cm) *</label>
              <input type="number" id="bioInputAltura" class="input-custom" step="0.5" min="100" max="250" required>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group" style="margin: 0;">
              <label for="bioInputGrasa">% Grasa Corporal (Opcional)</label>
              <input type="number" id="bioInputGrasa" class="input-custom" step="0.1" min="3" max="60" placeholder="Ej: 14.5">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="bioInputCintura">Cintura (cm) (Opcional)</label>
              <input type="number" id="bioInputCintura" class="input-custom" step="0.5" min="40" max="200" placeholder="Ej: 82.0">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group" style="margin: 0;">
              <label for="bioInputPecho">Pecho (cm) (Opcional)</label>
              <input type="number" id="bioInputPecho" class="input-custom" step="0.5" min="40" max="200" placeholder="Ej: 102.0">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="bioInputBrazo">Brazo (cm) (Opcional)</label>
              <input type="number" id="bioInputBrazo" class="input-custom" step="0.5" min="15" max="80" placeholder="Ej: 37.5">
            </div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label for="bioInputNotas">Notas de la Medición (Opcional)</label>
            <input type="text" id="bioInputNotas" class="input-custom" placeholder="Ej: En ayunas, excelente energía semanal">
          </div>

          <button type="submit" id="btnSubmitBiometrics" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: var(--btn-cta-text, #ffffff); font-weight: 700; margin-top: 6px;">
            💾 Guardar Medición Semanal
          </button>
        </form>
      </div>

      <!-- CONTENIDO 2: TABLA DE HISTORIAL DE EVOLUCIÓN -->
      <div id="bioTabContentHistory" style="display: none;">
        <div style="overflow-x: auto;">
          <table class="biometrics-history-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Peso</th>
                <th>Variación</th>
                <th>IMC</th>
                <th>Cintura</th>
                <th>Notas</th>
              </tr>
            </thead>
            <tbody id="bioHistoryTableBody">
              <!-- Inyectado dinámicamente -->
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>



  <!-- Inyección de sesión autenticada para el cliente JavaScript -->
  <script>
    window.KYNAP_USER_SESSION = {
      id: <?php echo $userId; ?>,
      name: <?php echo json_encode($userName); ?>,
      email: <?php echo json_encode($userEmail); ?>,
      avatar: <?php echo json_encode($userAvatar); ?>,
      token: <?php echo json_encode($userAuthToken); ?>,
      biometrics: {
        peso: <?php echo $userPeso; ?>,
        altura: <?php echo $userAltura; ?>,
        edad: <?php echo $userEdad; ?>,
        objetivo: <?php echo json_encode($userObjetivo); ?>,
        nivel_actividad: <?php echo json_encode($userNivelActividad); ?>,
        agua: <?php echo $userAgua; ?>
      }
    };
  </script>

  <!-- Script principal de la aplicación -->
  <script src="public/js/app.js"></script>
</body>
</html>
