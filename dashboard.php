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
          <button class="nav-btn active" onclick="switchTab('routines', this)" id="nav-routines">
            <span class="icon">📅</span>
            <span>Rutina Semanal</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="switchTab('workouts', this)" id="nav-workouts">
            <span class="icon">⚡</span>
            <span>Entrenar en Vivo</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="switchTab('nutrition', this)" id="nav-nutrition">
            <span class="icon">🥗</span>
            <span>Nutrición & Macros</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="switchTab('exercises', this)" id="nav-exercises">
            <span class="icon">📚</span>
            <span>Librería de Ejercicios</span>
          </button>
        </li>
        <li style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
          <button class="nav-btn" onclick="openBreathingModal()" id="nav-breathing">
            <span class="icon">🧘</span>
            <span>Mente Sana</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="toggleAICoach()" id="nav-aicoach">
            <span class="icon">🤖</span>
            <span>Coach AI</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="toggleTheme()" id="nav-theme">
            <span class="icon">🌓</span>
            <span>Modo Oscuro / Claro</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="window.location.href='configuration.php'" id="nav-config">
            <span class="icon">⚙️</span>
            <span>Configuración</span>
          </button>
        </li>
        <li>
          <button class="nav-btn" onclick="window.location.href='logout.php'" style="color: var(--accent-rose);">
            <span class="icon">🚪</span>
            <span>Cerrar Sesión</span>
          </button>
        </li>
      </ul>

      <!-- Perfil del usuario autenticado en la barra lateral -->
      <div class="user-snippet" onclick="window.location.href='configuration.php'" style="cursor: pointer;" title="Ir a configuración de cuenta">
        <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar" class="user-avatar" onerror="this.src='iconos/profile.svg'" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-emerald);">
        <div class="user-meta">
          <h4><?php echo $userName; ?></h4>
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
          <div class="streak-badge">
            <span>🔥</span> <span id="streakCounter">4 días activos</span>
          </div>
          <button class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.15); color: var(--accent-cyan);" onclick="openBreathingModal()">
            🧘 Respiración
          </button>
          <button class="btn-checkin" style="width: auto; padding: 8px 16px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700;" onclick="toggleAICoach()">
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

        <div class="days-grid" id="daysGrid">
          <!-- Renderizado dinámicamente por app.js con sincronización en tiempo real -->
        </div>
      </section>

      <!-- ====================================================================
           TAB 2: CORE 2 - WORKOUT EN VIVO & SETS (LOGGING DE FUERZA)
           ==================================================================== -->
      <section id="view-workouts" class="tab-view">
        <div class="live-workout-container">
          
          <div class="workout-logger-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
              <h2 style="font-family: var(--font-heading);">Sesión de Entrenamiento Activa</h2>
              <span class="day-badge" style="background: rgba(0, 255, 136, 0.15); color: var(--accent-emerald);">En Progreso</span>
            </div>
            
            <div class="exercise-selector-bar">
              <select id="exerciseSelector" class="select-custom" onchange="AppState.activeWorkout.exerciseId = this.value; recalculateWorkoutMetrics();" aria-label="Seleccionar ejercicio actual">
                <!-- Inyectado dinámicamente -->
              </select>
              <button class="btn-checkin" style="width: auto; padding: 0 20px;" onclick="addSetRow()">+ Serie</button>
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

            <button class="btn-checkin" style="background: linear-gradient(90deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; margin-top: 18px;" onclick="finishWorkoutSession()">
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
                <button class="btn-checkin" style="background: var(--accent-purple); color: #fff; width: auto; padding: 6px 14px; font-size: 12px;" onclick="startRestTimer(90)">Iniciar 90s</button>
                <button class="btn-checkin" style="background: rgba(255,255,255,0.08); color: #fff; width: auto; padding: 6px 12px; font-size: 12px;" onclick="addTimerSeconds(30)">+30s</button>
                <button class="btn-checkin" style="background: rgba(255,71,87,0.15); color: var(--accent-rose); width: auto; padding: 6px 12px; font-size: 12px;" onclick="resetRestTimer()">Reset</button>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ====================================================================
           TAB 3: CORE 3 - NUTRICIÓN, MACROS E HIDRATACIÓN
           ==================================================================== -->
      <section id="view-nutrition" class="tab-view">
        <div class="nutrition-grid">
          
          <!-- RESUMEN DE MACRONUTRIENTES Y CALORÍAS -->
          <div class="macro-summary-card">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
              <h2 style="font-family: var(--font-heading);">Balance Energético</h2>
              <button class="btn-checkin" style="width: auto; padding: 8px 16px; font-size: 13px;" onclick="openAddMealModal()">+ Añadir Comida</button>
            </div>

            <div style="margin-top: 16px;">
              <span id="caloriesConsumed" style="font-family: var(--font-heading); font-size: 38px; font-weight: 800; color: var(--accent-emerald);">0 kcal</span>
              <span id="caloriesTarget" style="color: var(--text-muted); font-size: 16px;">/ 2200 kcal</span>
            </div>

            <div class="macro-bars">
              <div class="macro-bar-item">
                <div class="macro-label-row">
                  <span style="color: var(--accent-cyan); font-weight: 600;">Proteínas (4 kcal/g)</span>
                  <span id="valProtein">0g / 150g</span>
                </div>
                <div class="macro-bar-track">
                  <div class="macro-bar-fill fill-protein" id="barProtein" style="width: 0%;"></div>
                </div>
              </div>

              <div class="macro-bar-item">
                <div class="macro-label-row">
                  <span style="color: var(--accent-amber); font-weight: 600;">Carbohidratos (4 kcal/g)</span>
                  <span id="valCarbs">0g / 230g</span>
                </div>
                <div class="macro-bar-track">
                  <div class="macro-bar-fill fill-carbs" id="barCarbs" style="width: 0%;"></div>
                </div>
              </div>

              <div class="macro-bar-item">
                <div class="macro-label-row">
                  <span style="color: var(--accent-rose); font-weight: 600;">Grasas (9 kcal/g)</span>
                  <span id="valFats">0g / 65g</span>
                </div>
                <div class="macro-bar-track">
                  <div class="macro-bar-fill fill-fats" id="barFats" style="width: 0%;"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- HIDRATACIÓN Y LISTA DE COMIDAS -->
          <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="water-tracker-card">
              <h3 style="font-family: var(--font-heading); font-size: 18px;">Hidratación Diaria 💧</h3>
              <div class="water-display" id="waterDisplay">1750 ml</div>
              <p id="waterTarget" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;">Meta recomendada: 2500 ml</p>
              <div class="water-actions">
                <button class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.2); color: var(--accent-cyan);" onclick="adjustWater(250)">+250 ml</button>
                <button class="btn-checkin" style="width: auto; padding: 8px 16px; background: rgba(0, 217, 255, 0.2); color: var(--accent-cyan);" onclick="adjustWater(500)">+500 ml</button>
                <button class="btn-checkin" style="width: auto; padding: 8px 12px; background: rgba(255, 255, 255, 0.06); color: var(--text-secondary);" onclick="adjustWater(-250)">-250 ml</button>
              </div>
            </div>

            <div class="macro-summary-card">
              <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 12px;">Comidas Registradas Hoy</h3>
              <ul id="recentMealsList" style="list-style: none;">
                <!-- Lista de comidas -->
              </ul>
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
            <button class="chip active" onclick="filterExercisesByMuscle('all', this)">Todos</button>
            <button class="chip" onclick="filterExercisesByMuscle('cuadriceps', this)">Cuádriceps</button>
            <button class="chip" onclick="filterExercisesByMuscle('gluteos', this)">Glúteos</button>
            <button class="chip" onclick="filterExercisesByMuscle('abdomen', this)">Abdomen</button>
            <button class="chip" onclick="filterExercisesByMuscle('pecho', this)">Pecho</button>
            <button class="chip" onclick="filterExercisesByMuscle('triceps', this)">Tríceps</button>
            <button class="chip" onclick="filterExercisesByMuscle('cardio', this)">Cardio</button>
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
    <button class="mobile-nav-item active" id="mobile-nav-routines" onclick="switchTab('routines')">
      <span class="icon">📅</span>
      <span>Rutina</span>
    </button>
    <button class="mobile-nav-item" id="mobile-nav-workouts" onclick="switchTab('workouts')">
      <span class="icon">⚡</span>
      <span>Entrenar</span>
    </button>
    <button class="mobile-nav-item" id="mobile-nav-nutrition" onclick="switchTab('nutrition')">
      <span class="icon">🥗</span>
      <span>Nutrición</span>
    </button>
    <button class="mobile-nav-item" id="mobile-nav-exercises" onclick="switchTab('exercises')">
      <span class="icon">📚</span>
      <span>Ejercicios</span>
    </button>
    <button class="mobile-nav-item" id="mobile-nav-coach" onclick="toggleAICoach()">
      <span class="icon">🤖</span>
      <span>Coach AI</span>
    </button>
  </nav>

  <!-- ====================================================================
       BOTÓN FLOTANTE Y DRAWER DE KYNAP AI COACH
       ==================================================================== -->
  <button class="floating-ai-btn" onclick="toggleAICoach()" aria-label="Abrir asistente inteligente Kynap AI">
    🤖
  </button>

  <aside class="chat-drawer" id="aiCoachDrawer" aria-label="Chat con Asistente Kynap AI">
    <div class="chat-drawer-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">🤖</span>
        <strong style="font-family: var(--font-heading); font-size: 15px;">Kynap Coach AI</strong>
      </div>
      <button class="btn-icon" onclick="toggleAICoach()" style="font-size: 16px;">✕</button>
    </div>
    <div class="chat-drawer-messages" id="aiCoachMessages">
      <div class="chat-bubble bot">
        ¡Hola <strong><?php echo $userName; ?></strong>! Soy tu coach personal Kynap AI. ¿Deseas recomendaciones sobre tu rutina de hoy, ajuste de macronutrientes o consejos de recuperación?
      </div>
    </div>
    <div class="chat-drawer-input">
      <input type="text" id="aiCoachInput" class="input-custom" placeholder="Escribe tu consulta fitness..." style="flex-grow: 1;">
      <button class="btn-checkin" style="width: auto; padding: 0 16px;" onclick="sendAICoachMessage()">Enviar</button>
    </div>
  </aside>

  <!-- ====================================================================
       MODAL 1: AÑADIR COMIDA CON MACROS
       ==================================================================== -->
  <div class="modal-backdrop" id="addMealModal" onclick="if(event.target === this) closeAddMealModal()">
    <div class="modal-sheet" style="padding: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px;">Registrar Alimento</h2>
        <button class="btn-icon" onclick="closeAddMealModal()">✕</button>
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
        <button type="submit" class="btn-checkin" style="background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); color: #000; font-weight: 700; margin-top: 10px;">
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
        <button class="btn-checkin" onclick="closeExerciseModal()" style="background-color: var(--border-subtle); color: var(--text-primary); margin-top: 12px;">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================================
       MODAL 3: MENTE SANA & RESPIRACIÓN GUIADA
       ==================================================================== -->
  <div class="modal-backdrop" id="breathingModal" onclick="if(event.target === this) closeBreathingModal()">
    <div class="modal-sheet" style="padding: 24px; text-align: center;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px;">🧘 Mente Sana: Respiración Guiada</h2>
        <button class="btn-icon" onclick="closeBreathingModal()">✕</button>
      </div>
      <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
        Reduce el estrés pre o post-entreno sincronizando el ritmo de tu respiración.
      </p>

      <div class="breathing-sphere-container">
        <div class="breathing-sphere" id="breathingSphere" onclick="toggleBreathingExercise()">
          <span class="breathing-text" id="breathingSphereText">Iniciar</span>
        </div>
      </div>

      <div style="margin: 20px 0;">
        <label for="breathingPresetSelect" style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Patrón de Respiración:</label>
        <select id="breathingPresetSelect" class="input-custom" style="width: 100%; margin-top: 6px;">
          <option value="4-4-4" selected>4-4-4 Box Breathing (Equilibrio Mental)</option>
          <option value="4-7-8">4-7-8 Respiración Profunda (Sueño y Calma)</option>
          <option value="2-2">2-2 Respiración Energizante (Pre-Entreno)</option>
          <option value="5-5">5-5 Coherencia Cardíaca (Relajación)</option>
        </select>
      </div>

      <div style="display: flex; gap: 10px; justify-content: center;">
        <button class="btn-checkin" id="breathingToggleBtn" style="background: var(--accent-cyan); color: #000; width: auto; padding: 10px 24px; font-weight: 700;" onclick="toggleBreathingExercise()">
          Comenzar
        </button>
        <button class="btn-checkin" style="background: var(--border-subtle); color: var(--text-primary); width: auto; padding: 10px 20px;" onclick="closeBreathingModal()">
          Cerrar
        </button>
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
