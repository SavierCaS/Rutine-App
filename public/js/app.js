/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - FRONTEND APPLICATION CORE & MOBILE BRIDGE
 * ==============================================================================
 * Arquitectura modular de alto rendimiento (SaaS Digital Fitness) con:
 * - Sincronización bidireccional cliente-servidor (RESTful API + Bearer Token).
 * - UI Optimista con cola de persistencia local (LocalStorage Fallback).
 * - Sistema de Notificaciones Toast no bloqueante.
 * - Soporte Mobile-First (Bottom Navigation Bar, Drawer táctil y Safe Areas).
 * - Core 1: Rutinas y Check-in semanal.
 * - Core 2: Workout en vivo, sets, RPE, 1RM Epley y Temporizador con Web Audio.
 * - Core 3: Nutrición, balance energético 4-4-9 e hidratación en mililitros.
 * - Core 4: Librería de ejercicios con demostraciones en GIF y búsqueda difusa.
 * - Core 5: Asistente IA (Kynap AI Coach) integrado.
 * - Core 6: Mente Sana (Respiración guiada 4-4-4, 4-7-8, 2-2, 5-5).
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 2.5.0
 * ==============================================================================
 */

// ==============================================================================
// 1. CATÁLOGO BASE DE EJERCICIOS CON ASSETS REALES
// ==============================================================================
const INITIAL_EXERCISES = [
  {
    id: 'ex-squats',
    name: 'Sentadillas',
    muscleGroup: 'cuadriceps',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Squats.gif',
    instructions: 'Pies al ancho de hombros. Desciende flexionando cadera y rodillas hasta romper el paralelo manteniendo la espalda neutral.'
  },
  {
    id: 'ex-butt-bridge',
    name: 'Puente de Glúteos',
    muscleGroup: 'gluteos',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Butt bridge.gif',
    instructions: 'Túmbate boca arriba con rodillas flexionadas. Eleva la pelvis contrayendo glúteos fuertemente 2 segundos en el punto más alto.'
  },
  {
    id: 'ex-crunch-abdominal',
    name: 'Crunch Abdominal',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Crunch Abdominales.gif',
    instructions: 'Flexión controlada de tronco expulsando el aire arriba sin forzar el cuello. Mantén la zona lumbar firme.'
  },
  {
    id: 'ex-crunch-bicicleta',
    name: 'Crunch de Bicicleta',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Crunch de bicicleta.gif',
    instructions: 'Alterna codo hacia la rodilla opuesta manteniendo la zona lumbar pegada al suelo y el abdomen en tensión continua.'
  },
  {
    id: 'ex-burpees',
    name: 'Burpees',
    muscleGroup: 'cardio',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Burpess.gif',
    instructions: 'Cuerpo al suelo con flexión completa de pecho y salto explosivo vertical con palmada sobre la cabeza.'
  },
  {
    id: 'ex-elevacion-rodillas',
    name: 'Elevación de Rodillas',
    muscleGroup: 'cardio',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Elevacion de rodillas.gif',
    instructions: 'Carrera estática elevando las rodillas a 90 grados con ritmo intenso y amortiguación en metatarsos.'
  },
  {
    id: 'ex-estocada-atras',
    name: 'Estocada Hacia Atrás',
    muscleGroup: 'cuadriceps',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Estocada hacia atras.gif',
    instructions: 'Paso largo hacia atrás descendiendo la rodilla posterior hasta rozar el suelo, torso recto y empuje con el talón delantero.'
  },
  {
    id: 'ex-flexiones-inclinadas',
    name: 'Flexiones con Inclinación',
    muscleGroup: 'pecho',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Flexiones con inclinación.gif',
    instructions: 'Manos apoyadas en superficie elevada manteniendo una línea recta tobillo-cadera-hombro en todo el rango de movimiento.'
  },
  {
    id: 'ex-step-silla',
    name: 'Step en Silla',
    muscleGroup: 'cuadriceps',
    equipment: 'silla',
    mediaUrl: 'public/assets/exercises/Step en silla.gif',
    instructions: 'Sube apoyando toda la planta del pie en la silla y empuja para extender la pierna por completo sin impulso excesivo.'
  },
  {
    id: 'ex-plancha',
    name: 'Plancha Tradicional',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Tablon.jpg',
    instructions: 'Apoyo en antebrazos y puntas de pie. Contrae abdomen, glúteos y cuádriceps sin arquear la zona lumbar.'
  },
  {
    id: 'ex-triceps-silla',
    name: 'Fondos de Tríceps en Silla',
    muscleGroup: 'triceps',
    equipment: 'silla',
    mediaUrl: 'public/assets/exercises/Triceps en silla.gif',
    instructions: 'Desciende flexionando los codos hacia atrás hasta 90 grados manteniendo la espalda pegada a la silla y empuja fuerte.'
  },
  {
    id: 'ex-twist-ruso',
    name: 'Twist Ruso',
    muscleGroup: 'abdomen',
    equipment: 'corporal',
    mediaUrl: 'public/assets/exercises/Twist Ruso.gif',
    instructions: 'Sentado a 45 grados con talones elevados, rota el torso controladamente a ambos lados tocando el suelo.'
  }
];

/**
 * Normaliza cadenas eliminando tildes y caracteres diacríticos para búsqueda difusa.
 * @param {string} text 
 * @returns {string}
 */
function normalizeText(text) {
  return (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

// ==============================================================================
// 2. ESTADO GLOBAL REACTIVO (AppState)
// ==============================================================================
const AppState = {
  activeTab: 'routines',
  isOnline: navigator.onLine,
  user: {
    id: 1,
    name: 'Usuario Kynap',
    email: 'atleta@kynap.com',
    avatar: 'iconos/profile.svg',
    plan: 'Plan Pro Activo',
    streakDays: 4,
    token: null
  },
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
      { id: 1, setNumber: 1, weightKg: 60, reps: 12, rpe: 7.5, isCompleted: true },
      { id: 2, setNumber: 2, weightKg: 70, reps: 10, rpe: 8.0, isCompleted: true },
      { id: 3, setNumber: 3, weightKg: 80, reps: 8, rpe: 8.5, isCompleted: false }
    ]
  },
  nutrition: {
    targets: { calories: 2200, proteinG: 150, carbsG: 230, fatsG: 65, waterMl: 2500 },
    consumed: { calories: 1540, proteinG: 110, carbsG: 160, fatsG: 48, waterMl: 1750 },
    meals: [
      { id: 1, name: 'Huevos revueltos con avena', calories: 450, p: 32, c: 45, f: 14, type: 'Desayuno' },
      { id: 2, name: 'Pechuga a la plancha con arroz', calories: 620, p: 52, c: 68, f: 12, type: 'Almuerzo' },
      { id: 3, name: 'Batido de proteína con plátano', calories: 280, p: 26, c: 35, f: 4, type: 'Snack' }
    ]
  },
  timer: {
    secondsLeft: 90,
    intervalId: null,
    isRunning: false
  },
  breathing: {
    type: '4-4-4',
    isActive: false,
    intervalId: null
  }
};

// ==============================================================================
// 3. CLIENTE API ROBUSTO & BRIDGE MÓVIL (ApiClient)
// ==============================================================================
class ApiClient {
  /**
   * Obtiene las cabeceras de autorización unificadas para Web y Móvil.
   * @returns {HeadersInit}
   */
  static getHeaders() {
    const token = AppState.user.token || localStorage.getItem('kynap_auth_token');
    const headers = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    if (AppState.user.id) {
      headers['X-User-Id'] = String(AppState.user.id);
    }
    return headers;
  }

  /**
   * Ejecuta una petición HTTP con manejo automático de errores y fallback offline.
   * @param {string} endpoint 
   * @param {RequestInit} options 
   * @returns {Promise<any>}
   */
  static async request(endpoint, options = {}) {
    try {
      const response = await fetch(endpoint, {
        ...options,
        headers: {
          ...ApiClient.getHeaders(),
          ...(options.headers || {})
        }
      });

      const data = await response.json();
      if (!response.ok && !data.success) {
        throw new Error(data.message || `Error HTTP ${response.status}`);
      }
      return data;
    } catch (err) {
      console.warn(`[Kynap API] Petición fallida a ${endpoint}:`, err.message);
      ApiClient.handleOfflineFallback(endpoint, options);
      throw err;
    }
  }

  /**
   * Encola o procesa acciones en modo offline cuando la red o el servidor fallan.
   */
  static handleOfflineFallback(endpoint, options) {
    if (options.method === 'POST') {
      try {
        const queue = JSON.parse(localStorage.getItem('kynap_offline_sync') || '[]');
        queue.push({ endpoint, options, timestamp: Date.now() });
        localStorage.setItem('kynap_offline_sync', JSON.stringify(queue));
        updateOnlineStatusPill(false);
      } catch (e) {
        // LocalStorage no disponible
      }
    }
  }
}

// ==============================================================================
// 4. SISTEMA DE TOAST NOTIFICATIONS (Reemplazo accesible de alert())
// ==============================================================================
/**
 * Despliega una notificación flotante moderna y no intrusiva.
 * @param {'success'|'error'|'warning'|'info'} type 
 * @param {string} title 
 * @param {string} desc 
 * @param {number} duration 
 */
function showToast(type = 'success', title = '', desc = '', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  }

  const icons = {
    success: '✔',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  };

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
    setTimeout(() => {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 260);
  }, duration);
}

// Actualiza indicador de estado online/offline
function updateOnlineStatusPill(isOnline) {
  const pill = document.getElementById('onlineStatusPill');
  if (pill) {
    pill.className = `status-pill ${isOnline ? 'online' : 'offline'}`;
    pill.innerHTML = `
      <span class="status-dot"></span>
      <span>${isOnline ? 'Sincronizado' : 'Modo Offline'}</span>
    `;
  }
}

window.addEventListener('online', () => {
  AppState.isOnline = true;
  updateOnlineStatusPill(true);
  showToast('info', 'Conexión Restablecida', 'Sincronizando cambios locales con el servidor...');
  syncInitialData();
});

window.addEventListener('offline', () => {
  AppState.isOnline = false;
  updateOnlineStatusPill(false);
  showToast('warning', 'Modo Offline Activo', 'Los cambios se guardan localmente.');
});

// ==============================================================================
// 5. MÓDULO 1: RUTINAS Y CHECK-IN SEMANAL
// ==============================================================================
/**
 * Renderiza la grilla de días y la barra de progreso semanal.
 */
function renderRoutines() {
  const container = document.getElementById('daysGrid');
  if (!container) return;

  const plannedDays = AppState.activeRoutine.days.filter(d => !d.isRest);
  const completedCount = AppState.activeRoutine.days.filter(d => AppState.checkIns.has(d.id)).length;
  const pct = Math.min(100, Math.round((completedCount / plannedDays.length) * 100));

  const pctEl = document.getElementById('weeklyProgressPct');
  const barEl = document.getElementById('weeklyProgressBar');
  if (pctEl) pctEl.textContent = `${pct}%`;
  if (barEl) barEl.style.width = `${pct}%`;

  container.innerHTML = AppState.activeRoutine.days.map(day => {
    const isDone = AppState.checkIns.has(day.id);
    return `
      <div class="day-card ${isDone ? 'completed' : ''} ${day.isRest ? 'rest-day' : ''}" id="card-${day.id}">
        <div class="day-header">
          <span class="day-title">${day.dayName}</span>
          <span class="day-badge">${day.isRest ? 'Descanso' : isDone ? '✔ Completado' : 'Pendiente'}</span>
        </div>
        <div class="day-subtitle">${day.focus}</div>
        <ul class="exercise-mini-list">
          ${day.isRest ? '<li class="exercise-mini-item">Recuperación muscular activa & descanso</li>' : ''}
          ${day.exercises.map(ex => `<li class="exercise-mini-item"><span>${ex}</span></li>`).join('')}
        </ul>
        ${!day.isRest ? `
          <button class="btn-checkin" onclick="toggleDayCheckIn('${day.id}')" aria-label="Marcar ${day.dayName}">
            ${isDone ? '✔ Completado (Desmarcar)' : 'Tachar Día'}
          </button>
        ` : ''}
      </div>
    `;
  }).join('');
}

/**
 * Alterna el estado de cumplimiento de un día con actualización optimista y persistencia en backend.
 * @param {string} dayId 
 */
window.toggleDayCheckIn = async function (dayId) {
  const isCurrentlyDone = AppState.checkIns.has(dayId);

  // 1. Actualización optimista inmediata
  if (isCurrentlyDone) {
    AppState.checkIns.delete(dayId);
    showToast('info', 'Día Desmarcado', `${dayId.toUpperCase()} marcado como pendiente.`);
  } else {
    AppState.checkIns.add(dayId);
    triggerCelebration();
    showToast('success', '¡Día Completado!', `Gran trabajo completando el entrenamiento de ${dayId.toUpperCase()}.`);
  }
  renderRoutines();

  // 2. Sincronización asíncrona con el backend
  try {
    const res = await ApiClient.request('controllers/api_routines.php', {
      method: 'POST',
      body: JSON.stringify({ day_id: dayId })
    });

    if (res && res.success) {
      if (res.data?.completedDays) {
        AppState.checkIns = new Set(res.data.completedDays);
        renderRoutines();
      }
    }
  } catch (err) {
    // La actualización optimista se mantiene activa localmente
  }
};

function triggerCelebration() {
  const bar = document.getElementById('weeklyProgressBar');
  if (bar) {
    bar.style.filter = 'brightness(1.6) drop-shadow(0 0 10px var(--accent-emerald))';
    setTimeout(() => {
      bar.style.filter = '';
    }, 450);
  }
}

// ==============================================================================
// 6. MÓDULO 2: WORKOUT EN VIVO & SETS (LOGGING DE FUERZA)
// ==============================================================================
/**
 * Renderiza el selector de ejercicio y la tabla interactiva de series.
 */
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
          onchange="updateSetData(${idx}, 'weightKg', this.value)" min="0" step="2.5" aria-label="Peso en kg"> kg
      </td>
      <td>
        <input type="number" class="input-number-sm" value="${set.reps}" 
          onchange="updateSetData(${idx}, 'reps', this.value)" min="0" max="100" aria-label="Repeticiones">
      </td>
      <td>
        <input type="number" class="input-number-sm" value="${set.rpe || 8}" 
          onchange="updateSetData(${idx}, 'rpe', this.value)" min="1" max="10" step="0.5" aria-label="RPE">
      </td>
      <td>
        <button class="btn-check-set ${set.isCompleted ? 'checked' : ''}" onclick="toggleSetCheck(${idx})" aria-label="Marcar serie ${set.setNumber}">
          ${set.isCompleted ? '✔' : ''}
        </button>
      </td>
      <td>
        <button class="btn-icon" onclick="deleteSetRow(${idx})" title="Eliminar serie" aria-label="Eliminar serie">✕</button>
      </td>
    </tr>
  `).join('');

  recalculateWorkoutMetrics();
}

/**
 * Actualiza los campos numéricos de una serie.
 */
window.updateSetData = function (index, field, value) {
  const num = Math.max(0, Number(value) || 0);
  if (AppState.activeWorkout.sets[index]) {
    AppState.activeWorkout.sets[index][field] = num;
    recalculateWorkoutMetrics();
  }
};

/**
 * Alterna el estado de una serie y dispara el temporizador de descanso.
 */
window.toggleSetCheck = async function (index) {
  const set = AppState.activeWorkout.sets[index];
  if (!set) return;

  set.isCompleted = !set.isCompleted;

  if (set.isCompleted) {
    startRestTimer(90);
    showToast('success', `Serie #${set.setNumber} Registrada`, `${set.weightKg} kg x ${set.reps} reps (RPE ${set.rpe || 8}).`);
  }
  renderWorkoutLogger();

  // Persistir serie en backend
  try {
    await ApiClient.request('controllers/api_workouts.php?action=save_set', {
      method: 'POST',
      body: JSON.stringify({
        exercise_id: AppState.activeWorkout.exerciseId,
        set_number: set.setNumber,
        weight_kg: set.weightKg,
        reps: set.reps,
        rpe: set.rpe,
        is_completed: set.isCompleted
      })
    });
  } catch (e) {
    // Persistencia local tolerante
  }
};

/**
 * Agrega una nueva serie heredando el peso y reps de la última serie registrada.
 */
window.addSetRow = function () {
  const current = AppState.activeWorkout.sets;
  const lastSet = current[current.length - 1] || { weightKg: 50, reps: 10, rpe: 8 };
  current.push({
    id: Date.now(),
    setNumber: current.length + 1,
    weightKg: lastSet.weightKg,
    reps: lastSet.reps,
    rpe: lastSet.rpe,
    isCompleted: false
  });
  renderWorkoutLogger();
  showToast('info', 'Serie Añadida', `Serie #${current.length} lista.`);
};

/**
 * Elimina una serie y recalcula números ordinales.
 */
window.deleteSetRow = function (index) {
  AppState.activeWorkout.sets.splice(index, 1);
  AppState.activeWorkout.sets.forEach((s, idx) => s.setNumber = idx + 1);
  renderWorkoutLogger();
};

/**
 * Finaliza la sesión de entrenamiento y almacena el tonelaje acumulado.
 */
window.finishWorkoutSession = async function () {
  let volume = 0;
  AppState.activeWorkout.sets.forEach(set => {
    if (set.isCompleted) volume += (set.weightKg * set.reps);
  });

  try {
    await ApiClient.request('controllers/api_workouts.php?action=complete_workout', {
      method: 'POST',
      body: JSON.stringify({
        total_volume_kg: volume,
        notes: 'Entrenamiento completado'
      })
    });
  } catch (e) {
    // Guardado local
  }

  showToast('success', '¡Entrenamiento Finalizado!', `Tonelaje total acumulado: ${volume.toLocaleString()} kg.`);
};

/**
 * Recalcula en tiempo real el tonelaje y la 1RM estimada (Fórmula de Epley).
 */
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

// Temporizador flotante con Web Audio API sintetizado
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
      showToast('info', '¡Descanso Finalizado!', 'Es momento de iniciar tu siguiente serie.');
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
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      const ctx = new AudioContextClass();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.35);
    }
  } catch (e) {
    // AudioContext silencioso
  }
}

// ==============================================================================
// 7. MÓDULO 3: NUTRICIÓN, MACROS E HIDRATACIÓN
// ==============================================================================
/**
 * Renderiza el balance calórico, barras de macronutrientes y comidas del día.
 */
function renderNutrition() {
  const { targets, consumed, meals } = AppState.nutrition;

  const calConsEl = document.getElementById('caloriesConsumed');
  const calTargEl = document.getElementById('caloriesTarget');
  if (calConsEl) calConsEl.textContent = `${consumed.calories} kcal`;
  if (calTargEl) calTargEl.textContent = `/ ${targets.calories} kcal`;

  const pPct = Math.min(100, Math.round((consumed.proteinG / targets.proteinG) * 100));
  const cPct = Math.min(100, Math.round((consumed.carbsG / targets.carbsG) * 100));
  const fPct = Math.min(100, Math.round((consumed.fatsG / targets.fatsG) * 100));

  const barP = document.getElementById('barProtein');
  const valP = document.getElementById('valProtein');
  if (barP) barP.style.width = `${pPct}%`;
  if (valP) valP.textContent = `${consumed.proteinG}g / ${targets.proteinG}g`;

  const barC = document.getElementById('barCarbs');
  const valC = document.getElementById('valCarbs');
  if (barC) barC.style.width = `${cPct}%`;
  if (valC) valC.textContent = `${consumed.carbsG}g / ${targets.carbsG}g`;

  const barF = document.getElementById('barFats');
  const valF = document.getElementById('valFats');
  if (barF) barF.style.width = `${fPct}%`;
  if (valF) valF.textContent = `${consumed.fatsG}g / ${targets.fatsG}g`;

  const waterDis = document.getElementById('waterDisplay');
  const waterTar = document.getElementById('waterTarget');
  if (waterDis) waterDis.textContent = `${consumed.waterMl} ml`;
  if (waterTar) waterTar.textContent = `Meta: ${targets.waterMl} ml`;

  const list = document.getElementById('recentMealsList');
  if (list) {
    if (meals.length === 0) {
      list.innerHTML = `<li style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 12px;">No hay comidas registradas hoy.</li>`;
    } else {
      list.innerHTML = meals.map(m => `
        <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <div style="font-weight: 600; font-size: 14px;">${m.name}</div>
            <div style="font-size: 12px; color: var(--text-secondary);">${m.type || 'Comida'} • P: ${m.p}g | C: ${m.c}g | G: ${m.f}g</div>
          </div>
          <span style="font-weight: 700; color: var(--accent-emerald); font-size: 14px;">+${m.calories} kcal</span>
        </li>
      `).join('');
    }
  }
}

/**
 * Ajusta la cantidad de agua consumida en mililitros.
 * @param {number} deltaMl 
 */
window.adjustWater = async function (deltaMl) {
  const current = AppState.nutrition.consumed.waterMl;
  AppState.nutrition.consumed.waterMl = Math.max(0, current + deltaMl);
  renderNutrition();

  showToast('info', 'Hidratación Actualizada', `${deltaMl > 0 ? '+' : ''}${deltaMl} ml registrados.`);

  try {
    await ApiClient.request('controllers/api_nutrition.php?action=adjust_water', {
      method: 'POST',
      body: JSON.stringify({ delta: deltaMl })
    });
  } catch (e) {
    // Persistencia local
  }
};

/**
 * Abre el modal para añadir una comida personalizada.
 */
window.openAddMealModal = function () {
  const modal = document.getElementById('addMealModal');
  if (modal) modal.classList.add('open');
};

window.closeAddMealModal = function () {
  const modal = document.getElementById('addMealModal');
  if (modal) modal.classList.remove('open');
};

/**
 * Registra una comida con cálculo automático de calorías.
 */
window.submitAddMealForm = async function (e) {
  if (e) e.preventDefault();
  const nameInput = document.getElementById('mealNameInput');
  const typeInput = document.getElementById('mealTypeInput');
  const pInput = document.getElementById('mealProteinInput');
  const cInput = document.getElementById('mealCarbsInput');
  const fInput = document.getElementById('mealFatsInput');

  const name = nameInput ? nameInput.value.trim() : 'Comida rápida';
  const type = typeInput ? typeInput.value : 'Snack';
  const p = Math.max(0, Number(pInput?.value) || 0);
  const c = Math.max(0, Number(cInput?.value) || 0);
  const f = Math.max(0, Number(fInput?.value) || 0);
  const cal = Math.round((p * 4) + (c * 4) + (f * 9));

  if (!name) {
    showToast('warning', 'Campo Requerido', 'Por favor ingresa el nombre de la comida.');
    return;
  }

  const newMeal = { id: Date.now(), name, type, p, c, f, calories: cal };
  AppState.nutrition.meals.unshift(newMeal);
  AppState.nutrition.consumed.calories += cal;
  AppState.nutrition.consumed.proteinG += p;
  AppState.nutrition.consumed.carbsG += c;
  AppState.nutrition.consumed.fatsG += f;

  renderNutrition();
  closeAddMealModal();
  showToast('success', 'Comida Registrada', `${name} (+${cal} kcal).`);

  if (nameInput) nameInput.value = '';
  if (pInput) pInput.value = '';
  if (cInput) cInput.value = '';
  if (fInput) fInput.value = '';

  try {
    await ApiClient.request('controllers/api_nutrition.php?action=add_meal', {
      method: 'POST',
      body: JSON.stringify({ name, type, p, c, f, calories: cal })
    });
  } catch (err) {
    // Guardado local
  }
};

window.quickAddMeal = function () {
  openAddMealModal();
};

// ==============================================================================
// 8. MÓDULO 4: LIBRERÍA DE EJERCICIOS
// ==============================================================================
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

  if (filtered.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
        <p style="font-size: 16px;">No se encontraron ejercicios coincidentes.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(ex => `
    <div class="exercise-card" onclick="openExerciseModal('${ex.id}')" tabindex="0" role="button" aria-label="Ver detalles de ${ex.name}">
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

  const mediaEl = document.getElementById('modalMedia');
  const titleEl = document.getElementById('modalTitle');
  const catEl = document.getElementById('modalCategory');
  const descEl = document.getElementById('modalInstructionText');
  const modalEl = document.getElementById('exerciseModal');

  if (mediaEl) mediaEl.src = ex.mediaUrl;
  if (titleEl) titleEl.textContent = ex.name;
  if (catEl) catEl.textContent = `${ex.muscleGroup.toUpperCase()} • ${ex.equipment.toUpperCase()}`;
  if (descEl) descEl.textContent = ex.instructions;

  if (modalEl) modalEl.classList.add('open');
};

window.closeExerciseModal = function () {
  const modal = document.getElementById('exerciseModal');
  if (modal) modal.classList.remove('open');
};

// ==============================================================================
// 9. MÓDULO 5: ASISTENTE IA (KYNAP AI COACH DRAWER)
// ==============================================================================
window.toggleAICoach = function () {
  const drawer = document.getElementById('aiCoachDrawer');
  if (drawer) {
    drawer.classList.toggle('open');
    if (drawer.classList.contains('open')) {
      const input = document.getElementById('aiCoachInput');
      if (input) input.focus();
    }
  }
};

window.sendAICoachMessage = async function () {
  const input = document.getElementById('aiCoachInput');
  const box = document.getElementById('aiCoachMessages');
  if (!input || !box) return;

  const text = input.value.trim();
  if (!text) return;

  // Insertar mensaje del usuario
  const userMsg = document.createElement('div');
  userMsg.className = 'chat-bubble user';
  userMsg.textContent = text;
  box.appendChild(userMsg);
  input.value = '';
  box.scrollTop = box.scrollHeight;

  // Indicador de escritura del bot
  const botMsg = document.createElement('div');
  botMsg.className = 'chat-bubble bot';
  botMsg.innerHTML = '<em>Pensando respuesta óptima...</em>';
  box.appendChild(botMsg);
  box.scrollTop = box.scrollHeight;

  try {
    const res = await fetch('chat_kynap_ai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ message: text, user_id: AppState.user.id })
    });
    const reply = await res.text();
    botMsg.innerHTML = reply.replace(/\n/g, '<br>');
  } catch (e) {
    botMsg.innerHTML = `Entendido atleta. Te sugiero mantener una hidratación de ${AppState.nutrition.targets.waterMl} ml y priorizar tu descanso nocturno para optimizar la hipertrofia.`;
  }
  box.scrollTop = box.scrollHeight;
};

// ==============================================================================
// 10. MÓDULO 6: MENTE SANA & RESPIRACIÓN GUIADA
// ==============================================================================
const BREATHING_PRESETS = {
  '4-4-4': { inhale: 4, hold: 4, exhale: 4, rest: 4, label: 'Box Breathing (Equilibrio)' },
  '4-7-8': { inhale: 4, hold: 7, exhale: 8, rest: 0, label: '4-7-8 (Sueño Profundo)' },
  '2-2': { inhale: 2, hold: 0, exhale: 2, rest: 0, label: '2-2 (Energizante Rápido)' },
  '5-5': { inhale: 5, hold: 0, exhale: 5, rest: 0, label: '5-5 (Coherencia Cardíaca)' }
};

window.openBreathingModal = function () {
  const modal = document.getElementById('breathingModal');
  if (modal) modal.classList.add('open');
};

window.closeBreathingModal = function () {
  const modal = document.getElementById('breathingModal');
  if (modal) {
    modal.classList.remove('open');
    stopBreathingExercise();
  }
};

window.toggleBreathingExercise = function () {
  if (AppState.breathing.isActive) {
    stopBreathingExercise();
  } else {
    startBreathingExercise();
  }
};

function startBreathingExercise() {
  AppState.breathing.isActive = true;
  const sphere = document.getElementById('breathingSphere');
  const textEl = document.getElementById('breathingSphereText');
  const btn = document.getElementById('breathingToggleBtn');
  const select = document.getElementById('breathingPresetSelect');
  const type = select ? select.value : '4-4-4';
  const config = BREATHING_PRESETS[type] || BREATHING_PRESETS['4-4-4'];

  if (btn) btn.textContent = 'Pausar Sesión';

  function runCycle() {
    if (!AppState.breathing.isActive) return;

    // 1. Inhalar
    if (textEl) textEl.textContent = 'Inhala...';
    if (sphere) {
      sphere.style.transition = `transform ${config.inhale}s cubic-bezier(0.4, 0, 0.2, 1)`;
      sphere.style.transform = 'scale(2.2)';
    }

    setTimeout(() => {
      if (!AppState.breathing.isActive) return;

      // 2. Mantener (si aplica)
      if (config.hold > 0) {
        if (textEl) textEl.textContent = 'Mantén...';
        setTimeout(exhaleStep, config.hold * 1000);
      } else {
        exhaleStep();
      }
    }, config.inhale * 1000);

    function exhaleStep() {
      if (!AppState.breathing.isActive) return;
      if (textEl) textEl.textContent = 'Exhala...';
      if (sphere) {
        sphere.style.transition = `transform ${config.exhale}s cubic-bezier(0.4, 0, 0.2, 1)`;
        sphere.style.transform = 'scale(1)';
      }

      setTimeout(() => {
        if (!AppState.breathing.isActive) return;
        if (config.rest > 0) {
          if (textEl) textEl.textContent = 'Pausa...';
          setTimeout(runCycle, config.rest * 1000);
        } else {
          runCycle();
        }
      }, config.exhale * 1000);
    }
  }

  runCycle();
}

function stopBreathingExercise() {
  AppState.breathing.isActive = false;
  const sphere = document.getElementById('breathingSphere');
  const textEl = document.getElementById('breathingSphereText');
  const btn = document.getElementById('breathingToggleBtn');

  if (sphere) {
    sphere.style.transition = 'transform 0.5s ease';
    sphere.style.transform = 'scale(1)';
  }
  if (textEl) textEl.textContent = 'Iniciar';
  if (btn) btn.textContent = 'Comenzar';
}

// ==============================================================================
// 11. NAVEGACIÓN Y CONTROLADOR DE TABS (DESKTOP + MOBILE)
// ==============================================================================
/**
 * Cambia la pestaña activa sincronizando sidebar de escritorio y barra móvil inferior.
 * @param {string} tabId 
 * @param {HTMLElement|null} navBtn 
 */
window.switchTab = function (tabId, navBtn) {
  AppState.activeTab = tabId;

  // Actualizar botones de escritorio
  document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
  const desktopBtn = document.getElementById(`nav-${tabId}`) || navBtn;
  if (desktopBtn && desktopBtn.classList.contains('nav-btn')) desktopBtn.classList.add('active');

  // Actualizar botones de navegación móvil inferior
  document.querySelectorAll('.mobile-nav-item').forEach(item => item.classList.remove('active'));
  const mobileBtn = document.getElementById(`mobile-nav-${tabId}`);
  if (mobileBtn) mobileBtn.classList.add('active');

  // Alternar vista visible
  document.querySelectorAll('.tab-view').forEach(view => view.classList.remove('active'));
  const targetView = document.getElementById(`view-${tabId}`);
  if (targetView) targetView.classList.add('active');

  // Renderizar contenido de la pestaña
  if (tabId === 'routines') renderRoutines();
  if (tabId === 'workouts') renderWorkoutLogger();
  if (tabId === 'nutrition') renderNutrition();
  if (tabId === 'exercises') renderExerciseCatalog();

  // Scroll suave al tope en móvil
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ==============================================================================
// 12. HIDRATACIÓN INICIAL Y SINCRONIZACIÓN CON BACKEND
// ==============================================================================
async function syncInitialData() {
  // 1. Hidratar usuario desde sesión PHP inyectada si existe
  if (window.KYNAP_USER_SESSION) {
    const s = window.KYNAP_USER_SESSION;
    if (s.id) AppState.user.id = s.id;
    if (s.name) AppState.user.name = s.name;
    if (s.email) AppState.user.email = s.email;
    if (s.avatar) AppState.user.avatar = s.avatar;
    if (s.token) {
      AppState.user.token = s.token;
      localStorage.setItem('kynap_auth_token', s.token);
    }
  }

  // 2. Sincronizar perfil y metas nutricionales (controllers/api_profile.php)
  try {
    const profRes = await ApiClient.request('controllers/api_profile.php');
    if (profRes && profRes.success && profRes.data) {
      const d = profRes.data;
      if (d.user) {
        AppState.user.name = d.user.nombre || AppState.user.name;
        AppState.user.email = d.user.email || AppState.user.email;
        if (d.user.imagen_user) AppState.user.avatar = d.user.imagen_user;
      }
      if (d.targets) {
        AppState.nutrition.targets = {
          calories: d.targets.calories || 2200,
          proteinG: d.targets.proteinG || 150,
          carbsG: d.targets.carbsG || 230,
          fatsG: d.targets.fatsG || 65,
          waterMl: d.targets.waterMl || 2500
        };
      }
      if (d.streakDays) {
        AppState.user.streakDays = d.streakDays;
        const streakEl = document.getElementById('streakCounter');
        if (streakEl) streakEl.textContent = `${d.streakDays} días activos`;
      }
      renderNutrition();
    }
  } catch (e) {
    // Continuar con valores por defecto
  }

  // 3. Sincronizar check-ins de la semana (controllers/api_routines.php)
  try {
    const routRes = await ApiClient.request('controllers/api_routines.php');
    if (routRes && routRes.success && routRes.data?.completedDays) {
      AppState.checkIns = new Set(routRes.data.completedDays);
      renderRoutines();
    }
  } catch (e) {
    // Continuar en memoria
  }

  // 4. Sincronizar nutrición del día (controllers/api_nutrition.php)
  try {
    const nutRes = await ApiClient.request('controllers/api_nutrition.php');
    if (nutRes && nutRes.success && nutRes.data) {
      const d = nutRes.data;
      if (d.waterMl !== undefined) AppState.nutrition.consumed.waterMl = d.waterMl;
      if (Array.isArray(d.meals) && d.meals.length > 0) {
        AppState.nutrition.meals = d.meals;
        if (d.consumed) {
          AppState.nutrition.consumed.calories = d.consumed.calories;
          AppState.nutrition.consumed.proteinG = d.consumed.proteinG;
          AppState.nutrition.consumed.carbsG = d.consumed.carbsG;
          AppState.nutrition.consumed.fatsG = d.consumed.fatsG;
        }
      }
      renderNutrition();
    }
  } catch (e) {
    // Continuar en memoria
  }

  // 5. Sincronizar sesión activa de workout (controllers/api_workouts.php)
  try {
    const workRes = await ApiClient.request('controllers/api_workouts.php?action=current');
    if (workRes && workRes.success && workRes.data?.sets && workRes.data.sets.length > 0) {
      AppState.activeWorkout.sets = workRes.data.sets;
      if (workRes.data.exerciseId) AppState.activeWorkout.exerciseId = workRes.data.exerciseId;
      renderWorkoutLogger();
    }
  } catch (e) {
    // Continuar en memoria
  }
}

// ==============================================================================
// 13. CONFIGURACIÓN DEL PERFIL Y CIERRE DE SESIÓN SEGURO
// ==============================================================================
window.openConfigModal = function (e) {
  if (e) e.preventDefault();
  const modal = document.getElementById('configModal');
  if (!modal) return;

  const n = document.getElementById('cfgNombre');
  const em = document.getElementById('cfgEmail');
  const p = document.getElementById('cfgPeso');
  const al = document.getElementById('cfgAltura');
  const ed = document.getElementById('cfgEdad');
  const ag = document.getElementById('cfgAgua');

  if (n) n.value = AppState.user.name || 'Savier CaS';
  if (em) em.value = AppState.user.email || 'atleta@kynap.com';
  if (p) p.value = AppState.user.peso || 75;
  if (al) al.value = AppState.user.altura || 175;
  if (ed) ed.value = AppState.user.edad || 25;
  if (ag) ag.value = (AppState.nutrition.targets.waterMl / 1000) || 2.5;

  modal.classList.add('open');
};

window.closeConfigModal = function (e) {
  if (e) e.preventDefault();
  const modal = document.getElementById('configModal');
  if (modal) modal.classList.remove('open');
};

window.handleProfileUpdate = async function (e) {
  if (e) e.preventDefault();
  const n = document.getElementById('cfgNombre')?.value.trim();
  const em = document.getElementById('cfgEmail')?.value.trim();
  const p = parseFloat(document.getElementById('cfgPeso')?.value) || 75;
  const al = parseFloat(document.getElementById('cfgAltura')?.value) || 175;
  const ed = parseInt(document.getElementById('cfgEdad')?.value, 10) || 25;
  const ag = parseFloat(document.getElementById('cfgAgua')?.value) || 2.5;

  if (n) AppState.user.name = n;
  if (em) AppState.user.email = em;
  AppState.user.peso = p;
  AppState.user.altura = al;
  AppState.user.edad = ed;
  AppState.nutrition.targets.waterMl = Math.round(ag * 1000);

  // Mifflin-St Jeor: Recálculo inmediato de macros
  const bmr = (10 * p) + (6.25 * al) - (5 * ed) + 5;
  const tdee = Math.round(bmr * 1.55);
  AppState.nutrition.targets.calories = tdee;
  AppState.nutrition.targets.proteinG = Math.round(p * 2);
  AppState.nutrition.targets.fatsG = Math.round(p * 0.9);
  const remainingCal = tdee - (AppState.nutrition.targets.proteinG * 4) - (AppState.nutrition.targets.fatsG * 9);
  AppState.nutrition.targets.carbsG = Math.max(50, Math.round(remainingCal / 4));

  const sidebarName = document.getElementById('sidebarUserName');
  if (sidebarName) sidebarName.textContent = n;
  renderNutrition();
  closeConfigModal();

  showToast('success', 'Perfil Actualizado', 'Metas nutricionales recalculadas con éxito.');

  try {
    await ApiClient.request('controllers/api_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        nombre: n,
        email: em,
        peso: p,
        altura: al,
        edad: ed,
        agua: ag
      })
    });
  } catch (err) {
    // Sincronización local
  }
};

window.handleLogout = function (e) {
  if (e) e.preventDefault();
  localStorage.removeItem('kynap_auth_token');
  try {
    fetch('controllers/api_auth.php?action=logout', { method: 'POST' }).catch(() => { });
  } catch (err) { }
  window.location.href = 'index.html?auth=login';
};

// ==============================================================================
// 14. INICIALIZACIÓN DE LA APLICACIÓN AL CARGAR DOM
// ==============================================================================
document.addEventListener('DOMContentLoaded', () => {
  renderRoutines();
  renderWorkoutLogger();
  renderNutrition();
  renderExerciseCatalog();
  updateTimerUI();

  // Búsqueda en catálogo de ejercicios
  const search = document.getElementById('exerciseSearchInput');
  if (search) {
    search.addEventListener('input', renderExerciseCatalog);
  }

  // Formulario modal de añadir comida
  const addMealForm = document.getElementById('addMealForm');
  if (addMealForm) {
    addMealForm.addEventListener('submit', (e) => {
      e.preventDefault();
      submitAddMealForm(e);
    });
  }

  // Formulario de configuración de perfil
  const profileConfigForm = document.getElementById('profileConfigForm');
  if (profileConfigForm) {
    profileConfigForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleProfileUpdate(e);
    });
  }

  // Enter en input de AI coach
  const aiInput = document.getElementById('aiCoachInput');
  if (aiInput) {
    aiInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') sendAICoachMessage();
    });
  }

  // Iniciar sincronización remota
  syncInitialData();
});
