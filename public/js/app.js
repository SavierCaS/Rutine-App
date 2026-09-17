// ==========================================================================
// KYNAP FITNESS PLATFORM - FRONTEND APPLICATION SCRIPT CON PERSISTENCIA
// ==========================================================================

// 1. CATÁLOGO INICIAL DE EJERCICIOS CON ASSETS REALES
const INITIAL_EXERCISES = [
  {
    id: 'ex-squats',
    name: 'Sentadillas',
    muscleGroup: 'cuadriceps',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Squats.gif',
    instructions: 'Pies al ancho de hombros. Desciende flexionando cadera y rodillas hasta romper el paralelo.'
  },
  {
    id: 'ex-butt-bridge',
    name: 'Puente de Glúteos',
    muscleGroup: 'gluteos',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Butt bridge.gif',
    instructions: 'Eleva la pelvis contrayendo glúteos fuertemente en el punto más alto.'
  },
  {
    id: 'ex-crunch-abdominal',
    name: 'Crunch Abdominal',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Crunch Abdominales.gif',
    instructions: 'Flexión controlada de tronco expulsando el aire arriba sin forzar el cuello.'
  },
  {
    id: 'ex-crunch-bicicleta',
    name: 'Crunch de Bicicleta',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Crunch de bicicleta.gif',
    instructions: 'Alterna codo hacia la rodilla opuesta manteniendo la zona lumbar pegada al suelo.'
  },
  {
    id: 'ex-burpees',
    name: 'Burpees',
    muscleGroup: 'cardio',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Burpess.gif',
    instructions: 'Cuerpo al suelo, flexión de pecho y salto explosivo vertical con palmada.'
  },
  {
    id: 'ex-elevacion-rodillas',
    name: 'Elevación de Rodillas',
    muscleGroup: 'cardio',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Elevacion de rodillas.gif',
    instructions: 'Carrera estática elevando las rodillas a 90 grados con ritmo intenso.'
  },
  {
    id: 'ex-estocada-atras',
    name: 'Estocada Hacia Atrás',
    muscleGroup: 'cuadriceps',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Estocada hacia atras.gif',
    instructions: 'Paso hacia atrás descendiendo la rodilla posterior hasta rozar el suelo.'
  },
  {
    id: 'ex-flexiones-inclinadas',
    name: 'Flexiones con Inclinación',
    muscleGroup: 'pecho',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Flexiones con inclinación.gif',
    instructions: 'Manos apoyadas en superficie elevada manteniendo el core firme en todo el rango.'
  },
  {
    id: 'ex-step-silla',
    name: 'Step en Silla',
    muscleGroup: 'cuadriceps',
    equipment: 'silla',
    mediaUrl: 'public/assets/exercises/Step en silla.gif',
    instructions: 'Sube apoyando toda la planta del pie y empuja para extender la pierna por completo.'
  },
  {
    id: 'ex-plancha',
    name: 'Plancha Tradicional',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Tablon.jpg',
    instructions: 'Apoyo en antebrazos manteniendo el cuerpo recto y abdomen contraído.'
  },
  {
    id: 'ex-triceps-silla',
    name: 'Fondos de Tríceps en Silla',
    muscleGroup: 'triceps',
    equipment: 'silla',
    mediaUrl: 'public/assets/exercises/Triceps en silla.gif',
    instructions: 'Desciende flexionando los codos hacia atrás hasta 90 grados y empuja con tríceps.'
  },
  {
    id: 'ex-twist-ruso',
    name: 'Twist Ruso',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Twist Ruso.gif',
    instructions: 'Sentado a 45 grados rotando el torso controladamente a ambos lados.'
  }
];

// Helper para búsqueda insensible a acentos/tildes
function normalizeText(text) {
  return (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

// 2. ESTADO GLOBAL DE LA APLICACIÓN (Optimistic UI + Backend Sync)
const AppState = {
  activeTab: 'routines',
  activeRoutine: {
    id: 'rot-1',
    name: 'Kynap Fuerza & Acondicionamiento (4 Días)',
    days: [
      { id: 'lunes', dayName: 'Lunes', focus: 'Pierna & Glúteos', isRest: false, exercises: ['Sentadillas (4x12)', 'Puente de Glúteos (3x15)', 'Estocada Hacia Atrás (3x10)'] },
      { id: 'martes', dayName: 'Martes', focus: 'Core & Cardio', isRest: false, exercises: ['Burpees (4x12)', 'Crunch de Bicicleta (3x20)', 'Plancha (3x45s)'] },
      { id: 'miercoles', dayName: 'Miércoles', focus: 'Descanso Activo', isRest: true, exercises: [] },
      { id: 'jueves', dayName: 'Jueves', focus: 'Torso & Brazos', isRest: false, exercises: ['Flexiones Inclinadas (4x12)', 'Fondos en Silla (3x12)', 'Twist Ruso (3x20)'] },
      { id: 'viernes', dayName: 'Viernes', focus: 'Full Body Funcional', isRest: false, exercises: ['Step en Silla (3x10)', 'Sentadillas (3x15)', 'Elevación de Rodillas (3x30s)'] },
      { id: 'sabado', dayName: 'Sábado', focus: 'Recuperación', isRest: true, exercises: [] },
      { id: 'domingo', dayName: 'Domingo', focus: 'Recuperación', isRest: true, exercises: [] }
    ]
  },
  checkIns: new Set(['lunes']),
  activeWorkout: {
    exerciseId: 'ex-squats',
    sets: [
      { setNumber: 1, weightKg: 60, reps: 12, rpe: 7.5, isCompleted: true },
      { setNumber: 2, weightKg: 70, reps: 10, rpe: 8.0, isCompleted: true },
      { setNumber: 3, weightKg: 80, reps: 8, rpe: 8.5, isCompleted: false }
    ]
  },
  nutrition: {
    targets: { calories: 2200, proteinG: 150, carbsG: 230, fatsG: 65, waterMl: 2500 },
    consumed: { calories: 1540, proteinG: 110, carbsG: 160, fatsG: 48, waterMl: 1750 },
    meals: [
      { name: 'Huevos revueltos con avena', calories: 450, p: 32, c: 45, f: 14, type: 'Desayuno' },
      { name: 'Pechuga a la plancha con arroz', calories: 620, p: 52, c: 68, f: 12, type: 'Almuerzo' },
      { name: 'Batido de proteína con plátano', calories: 280, p: 26, c: 35, f: 4, type: 'Snack' }
    ]
  },
  timer: {
    secondsLeft: 90,
    intervalId: null,
    isRunning: false
  }
};

// ==========================================================================
// MÓDULO 1: RUTINAS Y CHECK-IN CON PERSISTENCIA
// ==========================================================================

function renderRoutines() {
  const container = document.getElementById('daysGrid');
  if (!container) return;

  const plannedDays = AppState.activeRoutine.days.filter(d => !d.isRest);
  const completedCount = AppState.activeRoutine.days.filter(d => AppState.checkIns.has(d.id)).length;
  const pct = Math.min(100, Math.round((completedCount / plannedDays.length) * 100));

  document.getElementById('weeklyProgressPct').textContent = `${pct}%`;
  document.getElementById('weeklyProgressBar').style.width = `${pct}%`;

  container.innerHTML = AppState.activeRoutine.days.map(day => {
    const isDone = AppState.checkIns.has(day.id);
    return `
      <div class="day-card ${isDone ? 'completed' : ''} ${day.isRest ? 'rest-day' : ''}" id="card-${day.id}">
        <div class="day-header">
          <span class="day-title">${day.dayName}</span>
          <span class="day-badge">${day.isRest ? 'Descanso' : isDone ? '✔ Hecho' : 'Pendiente'}</span>
        </div>
        <div class="day-subtitle">${day.focus}</div>
        <ul class="exercise-mini-list">
          ${day.isRest ? '<li class="exercise-mini-item">Recuperación muscular activa</li>' : ''}
          ${day.exercises.map(ex => `<li class="exercise-mini-item"><span>${ex}</span></li>`).join('')}
        </ul>
        ${!day.isRest ? `
          <button class="btn-checkin" onclick="toggleDayCheckIn('${day.id}')">
            ${isDone ? '✔ Completado (Desmarcar)' : 'Tachar Día'}
          </button>
        ` : ''}
      </div>
    `;
  }).join('');
}

window.toggleDayCheckIn = async function (dayId) {
  // 1. Actualización optimista instantánea
  if (AppState.checkIns.has(dayId)) {
    AppState.checkIns.delete(dayId);
  } else {
    AppState.checkIns.add(dayId);
    triggerCelebration();
  }
  renderRoutines();

  // 2. Sincronización asíncrona con el backend
  try {
    const res = await fetch('controllers/api_routines.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ day_id: dayId })
    });
    const data = await res.json();
    if (data.success && data.isCompleted !== undefined) {
      if (data.isCompleted) AppState.checkIns.add(dayId);
      else AppState.checkIns.delete(dayId);
      renderRoutines();
    }
  } catch (err) {
    console.warn('Persistencia local activa (backend offline o sin Apache):', err);
  }
};

function triggerCelebration() {
  const bar = document.getElementById('weeklyProgressBar');
  if (bar) {
    bar.style.filter = 'brightness(1.5)';
    setTimeout(() => bar.style.filter = '', 300);
  }
}

// ==========================================================================
// MÓDULO 2: WORKOUT EN VIVO & SETS CON PERSISTENCIA
// ==========================================================================

function renderWorkoutLogger() {
  const tableBody = document.getElementById('setsTableBody');
  const select = document.getElementById('exerciseSelector');
  if (!tableBody || !select) return;

  if (select.children.length === 0) {
    select.innerHTML = INITIAL_EXERCISES.map(ex => 
      `<option value="${ex.id}" ${ex.id === AppState.activeWorkout.exerciseId ? 'selected' : ''}>${ex.name} (${ex.muscleGroup})</option>`
    ).join('');
  }

  tableBody.innerHTML = AppState.activeWorkout.sets.map((set, idx) => `
    <tr>
      <td><strong>#${set.setNumber}</strong></td>
      <td>
        <input type="number" class="input-number-sm" value="${set.weightKg}" 
          onchange="updateSetData(${idx}, 'weightKg', this.value)" min="0" step="2.5"> kg
      </td>
      <td>
        <input type="number" class="input-number-sm" value="${set.reps}" 
          onchange="updateSetData(${idx}, 'reps', this.value)" min="0" max="100">
      </td>
      <td>
        <input type="number" class="input-number-sm" value="${set.rpe || 8}" 
          onchange="updateSetData(${idx}, 'rpe', this.value)" min="1" max="10" step="0.5">
      </td>
      <td>
        <button class="btn-check-set ${set.isCompleted ? 'checked' : ''}" onclick="toggleSetCheck(${idx})">
          ${set.isCompleted ? '✔' : ''}
        </button>
      </td>
      <td>
        <button class="btn-icon" onclick="deleteSetRow(${idx})" title="Eliminar serie">✕</button>
      </td>
    </tr>
  `).join('');

  recalculateWorkoutMetrics();
}

window.updateSetData = function (index, field, value) {
  const num = Math.max(0, Number(value) || 0);
  AppState.activeWorkout.sets[index][field] = num;
  recalculateWorkoutMetrics();
};

window.toggleSetCheck = async function (index) {
  const set = AppState.activeWorkout.sets[index];
  set.isCompleted = !set.isCompleted;

  if (set.isCompleted) {
    startRestTimer(90);
  }
  renderWorkoutLogger();

  // Persistir serie en backend
  try {
    await fetch('controllers/api_workouts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_set',
        exercise_id: AppState.activeWorkout.exerciseId,
        set_number: set.setNumber,
        weight_kg: set.weightKg,
        reps: set.reps,
        rpe: set.rpe,
        is_completed: set.isCompleted
      })
    });
  } catch (e) {
    console.warn('Serie guardada en memoria local.');
  }
};

window.addSetRow = function () {
  const current = AppState.activeWorkout.sets;
  const lastSet = current[current.length - 1] || { weightKg: 50, reps: 10, rpe: 8 };
  current.push({
    setNumber: current.length + 1,
    weightKg: lastSet.weightKg,
    reps: lastSet.reps,
    rpe: lastSet.rpe,
    isCompleted: false
  });
  renderWorkoutLogger();
};

window.deleteSetRow = function (index) {
  AppState.activeWorkout.sets.splice(index, 1);
  AppState.activeWorkout.sets.forEach((s, idx) => s.setNumber = idx + 1);
  renderWorkoutLogger();
};

window.finishWorkoutSession = async function () {
  let volume = 0;
  AppState.activeWorkout.sets.forEach(set => {
    if (set.isCompleted) volume += (set.weightKg * set.reps);
  });

  try {
    await fetch('controllers/api_workouts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'complete_workout',
        total_volume_kg: volume,
        notes: 'Entrenamiento completado en app'
      })
    });
  } catch (e) {
    console.warn('Finalización de sesión registrada localmente.');
  }

  alert(`¡Entrenamiento finalizado! Volumen total alcanzado: ${volume.toLocaleString()} kg.`);
};

function recalculateWorkoutMetrics() {
  let volume = 0;
  let max1RM = 0;

  AppState.activeWorkout.sets.forEach(set => {
    if (set.isCompleted) {
      volume += (set.weightKg * set.reps);
      if (set.weightKg > 0 && set.reps > 0) {
        const cappedReps = Math.min(12, set.reps);
        const est = Math.round((set.weightKg * (1 + cappedReps / 30)) * 10) / 10;
        if (est > max1RM) max1RM = est;
      }
    }
  });

  const volEl = document.getElementById('statTotalVolume');
  const rmEl = document.getElementById('statMax1RM');
  if (volEl) volEl.textContent = `${volume.toLocaleString()} kg`;
  if (rmEl) rmEl.textContent = `${max1RM} kg`;
}

// Temporizador flotante con Web Audio API
function startRestTimer(seconds) {
  clearInterval(AppState.timer.intervalId);
  AppState.timer.secondsLeft = seconds;
  AppState.timer.isRunning = true;
  updateTimerUI();

  AppState.timer.intervalId = setInterval(() => {
    AppState.timer.secondsLeft--;
    updateTimerUI();

    if (AppState.timer.secondsLeft <= 0) {
      clearInterval(AppState.timer.intervalId);
      AppState.timer.isRunning = false;
      playBeep();
    }
  }, 1000);
}

function updateTimerUI() {
  const min = String(Math.floor(AppState.timer.secondsLeft / 60)).padStart(2, '0');
  const sec = String(AppState.timer.secondsLeft % 60).padStart(2, '0');
  const el = document.getElementById('timerDigits');
  if (el) el.textContent = `${min}:${sec}`;
}

window.addTimerSeconds = function (sec) {
  AppState.timer.secondsLeft += sec;
  updateTimerUI();
};

window.resetRestTimer = function () {
  clearInterval(AppState.timer.intervalId);
  AppState.timer.isRunning = false;
  AppState.timer.secondsLeft = 90;
  updateTimerUI();
};

function playBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = 880;
    gain.gain.setValueAtTime(0.15, ctx.currentTime);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.3);
  } catch (e) {
    console.warn('AudioContext no disponible:', e);
  }
}

// ==========================================================================
// MÓDULO 3: NUTRICIÓN & MACROS CON PERSISTENCIA
// ==========================================================================

function renderNutrition() {
  const { targets, consumed, meals } = AppState.nutrition;

  document.getElementById('caloriesConsumed').textContent = `${consumed.calories} kcal`;
  document.getElementById('caloriesTarget').textContent = `/ ${targets.calories} kcal`;

  const pPct = Math.min(100, Math.round((consumed.proteinG / targets.proteinG) * 100));
  const cPct = Math.min(100, Math.round((consumed.carbsG / targets.carbsG) * 100));
  const fPct = Math.min(100, Math.round((consumed.fatsG / targets.fatsG) * 100));

  document.getElementById('barProtein').style.width = `${pPct}%`;
  document.getElementById('valProtein').textContent = `${consumed.proteinG}g / ${targets.proteinG}g`;

  document.getElementById('barCarbs').style.width = `${cPct}%`;
  document.getElementById('valCarbs').textContent = `${consumed.carbsG}g / ${targets.carbsG}g`;

  document.getElementById('barFats').style.width = `${fPct}%`;
  document.getElementById('valFats').textContent = `${consumed.fatsG}g / ${targets.fatsG}g`;

  document.getElementById('waterDisplay').textContent = `${consumed.waterMl} ml`;
  document.getElementById('waterTarget').textContent = `Meta: ${targets.waterMl} ml`;

  const mealList = document.getElementById('recentMealsList');
  if (mealList) {
    mealList.innerHTML = meals.map(m => `
      <li class="exercise-mini-item" style="margin-bottom: 8px;">
        <div>
          <strong>${m.name}</strong> <span style="color: var(--text-muted);">(${m.type})</span>
          <div style="font-size: 11px; color: var(--text-secondary);">P: ${m.p}g | C: ${m.c}g | G: ${m.f}g</div>
        </div>
        <span style="color: var(--accent-amber); font-weight: bold;">${m.calories} kcal</span>
      </li>
    `).join('');
  }
}

window.adjustWater = async function (delta) {
  AppState.nutrition.consumed.waterMl = Math.max(0, AppState.nutrition.consumed.waterMl + delta);
  renderNutrition();

  try {
    await fetch('controllers/api_nutrition.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'adjust_water', delta })
    });
  } catch (e) {
    console.warn('Agua sincronizada en modo local.');
  }
};

window.quickAddMeal = async function () {
  const name = prompt('Nombre del alimento (ej. Ensalada con atún):');
  if (!name) return;

  const p = Number(prompt('Gramos de Proteína:', '20')) || 0;
  const c = Number(prompt('Gramos de Carbohidratos:', '15')) || 0;
  const f = Number(prompt('Gramos de Grasas:', '8')) || 0;

  // Auto-cálculo 4-4-9
  const calories = Math.round(p * 4 + c * 4 + f * 9);

  AppState.nutrition.meals.unshift({ name, calories, p, c, f, type: 'Snack' });
  AppState.nutrition.consumed.calories += calories;
  AppState.nutrition.consumed.proteinG += p;
  AppState.nutrition.consumed.carbsG += c;
  AppState.nutrition.consumed.fatsG += f;

  renderNutrition();

  try {
    await fetch('controllers/api_nutrition.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add_meal', name, p, c, f, calories, type: 'Snack' })
    });
  } catch (e) {
    console.warn('Comida guardada localmente.');
  }
};

// ==========================================================================
// MÓDULO 4: LIBRERÍA DE EJERCICIOS
// ==========================================================================

let activeFilter = 'all';

function renderExerciseCatalog() {
  const container = document.getElementById('exerciseCatalogGrid');
  const searchInput = document.getElementById('exerciseSearchInput');
  if (!container) return;

  const query = normalizeText(searchInput ? searchInput.value : '');

  const filtered = INITIAL_EXERCISES.filter(ex => {
    const matchesFilter = activeFilter === 'all' || ex.muscleGroup === activeFilter;
    const matchesQuery = !query || 
      normalizeText(ex.name).includes(query) || 
      normalizeText(ex.muscleGroup).includes(query) ||
      normalizeText(ex.equipment).includes(query);
    return matchesFilter && matchesQuery;
  });

  container.innerHTML = filtered.map(ex => `
    <div class="exercise-card" onclick="openExerciseModal('${ex.id}')">
      <img src="${ex.mediaUrl}" alt="${ex.name}" class="exercise-media" loading="lazy">
      <div class="exercise-card-info">
        <h4 class="exercise-card-title">${ex.name}</h4>
        <span class="exercise-card-tag">${ex.muscleGroup} • ${ex.equipment}</span>
      </div>
    </div>
  `).join('');
}

window.filterExercisesByMuscle = function (muscle, btnElement) {
  activeFilter = muscle;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  if (btnElement) btnElement.classList.add('active');
  renderExerciseCatalog();
};

window.openExerciseModal = function (exId) {
  const ex = INITIAL_EXERCISES.find(e => e.id === exId);
  if (!ex) return;

  document.getElementById('modalMedia').src = ex.mediaUrl;
  document.getElementById('modalTitle').textContent = ex.name;
  document.getElementById('modalCategory').textContent = `${ex.muscleGroup.toUpperCase()} • ${ex.equipment.toUpperCase()}`;
  document.getElementById('modalInstructionText').textContent = ex.instructions;

  document.getElementById('exerciseModal').classList.add('open');
};

window.closeExerciseModal = function () {
  document.getElementById('exerciseModal').classList.remove('open');
};

// ==========================================================================
// CONTROL DE TABS & SINCRONIZACIÓN INICIAL
// ==========================================================================

window.switchTab = function (tabId, navBtn) {
  AppState.activeTab = tabId;

  document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
  if (navBtn) navBtn.classList.add('active');

  document.querySelectorAll('.tab-view').forEach(view => view.classList.remove('active'));
  const targetView = document.getElementById(`view-${tabId}`);
  if (targetView) targetView.classList.add('active');

  if (tabId === 'routines') renderRoutines();
  if (tabId === 'workouts') renderWorkoutLogger();
  if (tabId === 'nutrition') renderNutrition();
  if (tabId === 'exercises') renderExerciseCatalog();
};

// Sincronización inicial desde la Base de Datos al abrir
async function syncInitialData() {
  try {
    // 1. Sincronizar check-ins de la semana
    const routRes = await fetch('controllers/api_routines.php');
    const routData = await routRes.json();
    if (routData.success && Array.isArray(routData.completedDays)) {
      AppState.checkIns = new Set(routData.completedDays);
      renderRoutines();
    }
  } catch (e) {
    console.warn('Routines API offline, usando estado en memoria.');
  }

  try {
    // 2. Sincronizar nutrición y agua
    const nutRes = await fetch('controllers/api_nutrition.php');
    const nutData = await nutRes.json();
    if (nutData.success) {
      if (nutData.waterMl !== undefined) AppState.nutrition.consumed.waterMl = nutData.waterMl;
      if (Array.isArray(nutData.meals) && nutData.meals.length > 0) {
        AppState.nutrition.meals = nutData.meals;
      }
      renderNutrition();
    }
  } catch (e) {
    console.warn('Nutrition API offline, usando estado en memoria.');
  }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
  renderRoutines();
  renderWorkoutLogger();
  renderNutrition();
  renderExerciseCatalog();
  updateTimerUI();

  const search = document.getElementById('exerciseSearchInput');
  if (search) {
    search.addEventListener('input', renderExerciseCatalog);
  }

  // Sincronizar con backend si está disponible
  syncInitialData();
});
