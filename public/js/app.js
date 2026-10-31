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
  currentServerDayKey: 'lunes',
  currentServerDayName: 'Lunes',
  serverDate: new Date().toISOString().split('T')[0],
  user: {
    id: 1,
    name: 'Usuario Kynap',
    email: 'atleta@kynap.com',
    avatar: 'iconos/profile.svg',
    plan: 'Plan Pro Activo',
    streakDays: 1,
    token: null
  },
  streak: {
    currentStreak: 1,
    longestStreak: 1,
    hasActivityToday: false,
    badge: { name: 'Fuego Inicial', icon: '🔥', level: 1, nextGoal: '2 días para Bronce 🥉' },
    compactWeek: [],
    totalActiveDays: 1
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
    selectedDayKey: 'lunes',
    selectedDayName: 'Lunes',
    isToday: true,
    schedule: [],
    freeMeals: [],
    targets: { calories: 2200, proteinG: 150, carbsG: 230, fatsG: 65, waterMl: 2500 },
    consumed: { calories: 0, proteinG: 0, carbsG: 0, fatsG: 0, waterMl: 1750 },
    meals: [],
    summary: { completedMeals: 0, totalMeals: 5, completionPct: 0 }
  },
  biometrics: {
    current: { peso: 75, altura: 175, edad: 25, sexo: 'masculino', objetivo: 'ganar_musculo', nivel_actividad: 'moderado', agua: 3 },
    policy: { canUpdate: true, mode: 'create', message: '', hoursRemainingCorrection: null, daysRemainingNextUpdate: 0, nextAllowedDate: null },
    history: []
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
  },
  workoutSubtab: 'series',
  pendingCheckIns: new Set(),
  pendingMeals: new Set()
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
// Registro para deduplicación estricta de alertas (evita spam de notificaciones)
const _recentToastTimes = new Map();

/**
 * Despliega una notificación flotante moderna y no intrusiva con control anti-spam de alerta única.
 * @param {'success'|'error'|'warning'|'info'} type 
 * @param {string} title 
 * @param {string} desc 
 * @param {number} duration 
 */
function showToast(type = 'success', title = '', desc = '', duration = 3500) {
  const key = `${type}:${title}`;
  const now = Date.now();
  // Si ya se disparó esta alerta en los últimos 2.5 segundos, ignorar para erradicar el spam
  if (_recentToastTimes.has(key) && (now - _recentToastTimes.get(key)) < 2500) {
    return;
  }
  _recentToastTimes.set(key, now);

  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  } else {
    // Si ya existe un toast similar visible, reemplazarlo para mantener una ALERTA ÚNICA
    const existingToasts = container.querySelectorAll('.toast');
    existingToasts.forEach(t => {
      if (t.innerText.includes(title)) {
        t.remove();
      }
    });
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
 * Renderiza la grilla de días con validación estricta del día actual y candados en días no permitidos.
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
    const isCurrentDay = (day.id === AppState.currentServerDayKey);

    let cardClasses = ['day-card'];
    if (isDone) cardClasses.push('completed');
    if (day.isRest) cardClasses.push('rest-day');
    if (isCurrentDay) cardClasses.push('is-today');
    else if (!day.isRest) cardClasses.push('locked-day');

    let badgeText = day.isRest ? 'Descanso' : (isDone ? '✔ Completado' : 'Pendiente');
    let badgeClass = 'day-badge';
    if (isCurrentDay && !day.isRest) {
      badgeClass += ' badge-today';
      badgeText = isDone ? '⭐ HOY (✔ Hecho)' : '⭐ HOY (ACTIVO)';
    } else if (!isCurrentDay && !day.isRest) {
      badgeText = isDone ? '✔ Completado' : '🔒 Solo Lectura';
    }

    return `
      <div class="${cardClasses.join(' ')}" id="card-${day.id}">
        <div class="day-header">
          <span class="day-title">${day.dayName}</span>
          <span class="${badgeClass}">${badgeText}</span>
        </div>
        <div class="day-subtitle">${day.focus}</div>
        <ul class="exercise-mini-list">
          ${day.isRest ? '<li class="exercise-mini-item">Recuperación muscular activa & descanso</li>' : ''}
          ${day.exercises.map(ex => `<li class="exercise-mini-item"><span>${ex}</span></li>`).join('')}
        </ul>
        ${!day.isRest ? (
          isCurrentDay ? `
            <button type="button" class="btn-checkin" onclick="toggleDayCheckIn('${day.id}', this)" aria-label="Marcar ${day.dayName}">
              ${isDone ? '✔ Completado (Desmarcar)' : 'Tachar Día Actual'}
            </button>
          ` : `
            <button type="button" class="btn-checkin btn-locked" onclick="handleLockedDayClick('${day.dayName}')" title="Solo puedes registrar el progreso del día actual (${AppState.currentServerDayName})">
              ${isDone ? '✔ Completado (Historial)' : '🔒 Bloqueado'}
            </button>
          `
        ) : ''}
      </div>
    `;
  }).join('');
}

/**
 * Informa sutilmente si el usuario intenta interactuar con un día diferente al actual.
 * @param {string} dayName 
 */
window.handleLockedDayClick = function (dayName) {
  // Acción no permitida: el estado visual y el tooltip comunican que está bloqueado
};

/**
 * Alterna el estado de cumplimiento de un día de entrenamiento sin generar ventanas emergentes.
 * Cambia limpiamente el estado y sincroniza en segundo plano.
 * @param {string} dayId 
 * @param {HTMLElement|null} btnElement
 */
window.toggleDayCheckIn = async function (dayId, btnElement) {
  if (dayId !== AppState.currentServerDayKey) return;

  if (!AppState.pendingCheckIns) AppState.pendingCheckIns = new Set();
  if (AppState.pendingCheckIns.has(dayId)) return;
  AppState.pendingCheckIns.add(dayId);

  const isCurrentlyDone = AppState.checkIns.has(dayId);

  // 1. Actualización limpia de estado en el cliente (sin alertas intrusivas)
  if (isCurrentlyDone) {
    AppState.checkIns.delete(dayId);
  } else {
    AppState.checkIns.add(dayId);
    triggerCelebration();
  }
  renderRoutines();

  // 2. Sincronización transparente con el backend
  try {
    const res = await ApiClient.request('controllers/api_routines.php', {
      method: 'POST',
      body: JSON.stringify({ day_id: dayId })
    });

    if (res && res.success) {
      if (res.data?.completedDays) {
        AppState.checkIns = new Set(res.data.completedDays);
      }
      if (res.data?.streak) {
        AppState.streak = res.data.streak;
        renderStreakComponent();
      }
      renderRoutines();
    } else if (res && !res.success) {
      // Revertir estado si el servidor no pudo procesar
      if (isCurrentlyDone) AppState.checkIns.add(dayId);
      else AppState.checkIns.delete(dayId);
      renderRoutines();
    }
  } catch (err) {
    if (isCurrentlyDone) AppState.checkIns.add(dayId);
    else AppState.checkIns.delete(dayId);
    renderRoutines();
  } finally {
    AppState.pendingCheckIns.delete(dayId);
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
        <button type="button" class="btn-check-set ${set.isCompleted ? 'checked' : ''}" onclick="toggleSetCheck(${idx})" aria-label="Marcar serie ${set.setNumber}">
          ${set.isCompleted ? '✔' : ''}
        </button>
      </td>
      <td>
        <button type="button" class="btn-icon" onclick="deleteSetRow(${idx})" title="Eliminar serie" aria-label="Eliminar serie">✕</button>
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
 * Alterna el estado de una serie y dispara el temporizador de descanso sin ventanas emergentes.
 */
const _pendingSets = new Set();
window.toggleSetCheck = async function (index) {
  if (_pendingSets.has(index)) return;
  _pendingSets.add(index);

  const set = AppState.activeWorkout.sets[index];
  if (!set) {
    _pendingSets.delete(index);
    return;
  }

  set.isCompleted = !set.isCompleted;

  if (set.isCompleted) {
    startRestTimer(90);
  }
  renderWorkoutLogger();

  // Persistir serie en backend de forma silenciosa
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
  } finally {
    _pendingSets.delete(index);
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
 * Deshabilita temporalmente el botón durante 2 segundos para evitar registros duplicados.
 */
let _isFinishingWorkout = false;
window.finishWorkoutSession = async function (btnElement) {
  if (_isFinishingWorkout) return;
  _isFinishingWorkout = true;

  const finishBtn = btnElement || document.querySelector('button[onclick*="finishWorkoutSession"]');
  if (finishBtn) {
    finishBtn.disabled = true;
    finishBtn.classList.add('btn-throttled');
    finishBtn.textContent = '⏳ Finalizando y Guardando...';
  }

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
  } finally {
    showToast('success', '¡Entrenamiento Finalizado!', `Tonelaje total acumulado: ${volume.toLocaleString()} kg.`);
    setTimeout(() => {
      _isFinishingWorkout = false;
      if (finishBtn) {
        finishBtn.disabled = false;
        finishBtn.classList.remove('btn-throttled');
        finishBtn.textContent = 'Finalizar Sesión & Guardar Tonelaje';
      }
    }, 2500);
  }
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
// 7. MÓDULO 3: HORARIOS DE COMIDAS, NUTRICIÓN & REINICIO DIARIO (REQUERIMIENTO 1)
// ==============================================================================
const MEAL_DAYS = [
  { key: 'lunes', name: 'Lunes', short: 'L' },
  { key: 'martes', name: 'Martes', short: 'M' },
  { key: 'miercoles', name: 'Miércoles', short: 'X' },
  { key: 'jueves', name: 'Jueves', short: 'J' },
  { key: 'viernes', name: 'Viernes', short: 'V' },
  { key: 'sabado', name: 'Sábado', short: 'S' },
  { key: 'domingo', name: 'Domingo', short: 'D' }
];

/**
 * Carga el plan de comidas para el día seleccionado desde el backend.
 * @param {string} [dayKey]
 */
async function loadNutritionData(dayKey) {
  const targetDay = dayKey || AppState.nutrition.selectedDayKey || AppState.currentServerDayKey || 'lunes';
  try {
    const res = await ApiClient.request(`controllers/api_nutrition.php?day=${encodeURIComponent(targetDay)}`);
    if (res && res.success && res.data) {
      const d = res.data;
      if (d.currentServerDayKey) AppState.currentServerDayKey = d.currentServerDayKey;
      if (d.currentServerDayName) AppState.currentServerDayName = d.currentServerDayName;
      AppState.nutrition.selectedDayKey = d.selectedDayKey;
      AppState.nutrition.selectedDayName = d.selectedDayName;
      AppState.nutrition.isToday = !!d.isToday;
      AppState.nutrition.schedule = Array.isArray(d.schedule) ? d.schedule : [];
      AppState.nutrition.freeMeals = Array.isArray(d.freeMeals) ? d.freeMeals : [];
      AppState.nutrition.consumed.waterMl = d.waterMl ?? 1750;
      AppState.nutrition.targets.waterMl = d.waterTargetMl ?? 2500;
      AppState.nutrition.summary = d.summary || {};

      if (d.summary) {
        AppState.nutrition.consumed.calories = d.summary.consumedCalories || 0;
        AppState.nutrition.consumed.proteinG = d.summary.consumedProtein || 0;
        AppState.nutrition.consumed.carbsG = d.summary.consumedCarbs || 0;
        AppState.nutrition.consumed.fatsG = d.summary.consumedFats || 0;
        if (d.summary.targetCalories) AppState.nutrition.targets.calories = d.summary.targetCalories;
      }

      if (d.streak) {
        AppState.streak = d.streak;
        renderStreakComponent();
      }

      renderNutritionSchedule();
      renderNutritionSummary();
    }
  } catch (err) {
    // Si falla la red, renderizar con datos en memoria
    renderNutritionSchedule();
    renderNutritionSummary();
  }
}

/**
 * Cambia el día activo en la vista de plan de comidas.
 * @param {string} dayKey
 */
window.switchMealDay = function (dayKey) {
  AppState.nutrition.selectedDayKey = dayKey;
  loadNutritionData(dayKey);
};

/**
 * Renderiza el selector de días de la semana y los bloques estructurados de comidas.
 */
function renderNutritionSchedule() {
  const tabsContainer = document.getElementById('mealDayTabs');
  const blocksContainer = document.getElementById('mealScheduleContainer');
  const counterBadge = document.getElementById('mealCompletionCounterBadge');
  const heading = document.getElementById('scheduledMealsHeading');
  const dayBadge = document.getElementById('selectedDayNameBadge');

  if (heading) {
    heading.textContent = `Horarios & Alimentos (${AppState.nutrition.selectedDayName})`;
  }
  if (dayBadge) {
    dayBadge.textContent = AppState.nutrition.isToday ? '⭐ Hoy' : AppState.nutrition.selectedDayName;
    dayBadge.style.color = AppState.nutrition.isToday ? 'var(--accent-emerald)' : 'var(--text-secondary)';
  }

  // 1. Renderizar selector de días de la semana
  if (tabsContainer) {
    tabsContainer.innerHTML = MEAL_DAYS.map(day => {
      const isSelected = (day.key === AppState.nutrition.selectedDayKey);
      const isServerToday = (day.key === AppState.currentServerDayKey);
      return `
        <button type="button" class="meal-day-pill ${isSelected ? 'active' : ''}" onclick="switchMealDay('${day.key}')" aria-label="Ver plan de ${day.name}">
          <span class="day-letter">${day.short} • ${day.name.slice(0, 3)}</span>
          ${isServerToday ? '<span class="today-indicator">Hoy</span>' : ''}
        </button>
      `;
    }).join('');
  }

  // 2. Renderizar contador de comidas completadas
  if (counterBadge) {
    const sum = AppState.nutrition.summary;
    const done = sum.completedMeals ?? AppState.nutrition.schedule.filter(m => m.isCompleted).length;
    const total = sum.totalMeals ?? AppState.nutrition.schedule.length;
    const pct = sum.completionPct ?? (total > 0 ? Math.round((done / total) * 100) : 0);
    counterBadge.textContent = `${done} / ${total} Comidas Completadas (${pct}%)`;
    counterBadge.style.background = done === total && total > 0 ? 'rgba(0, 255, 136, 0.25)' : 'rgba(0, 255, 136, 0.12)';
  }

  // 3. Renderizar bloques de comidas estructuradas
  if (blocksContainer) {
    if (AppState.nutrition.schedule.length === 0) {
      blocksContainer.innerHTML = `
        <div style="background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); padding: 24px; text-align: center; color: var(--text-secondary);">
          No hay comidas programadas para este día.
        </div>
      `;
      return;
    }

    blocksContainer.innerHTML = AppState.nutrition.schedule.map(meal => {
      const isDone = !!meal.isCompleted;
      const isTodayView = AppState.nutrition.isToday;

      return `
        <div class="meal-block-card ${isDone ? 'completed' : ''}" id="meal-card-${meal.mealKey}">
          <div class="meal-block-header">
            <div class="meal-title-group">
              <span class="meal-time-tag">⏰ ${meal.suggestedTime || '08:00'}</span>
              <h4 class="meal-block-title">${meal.name}</h4>
            </div>
            <div>
              ${isTodayView ? `
                <button type="button" class="btn-meal-check ${isDone ? 'completed' : ''}" onclick="toggleScheduledMeal('${meal.mealKey}', this)" aria-label="Marcar ${meal.name}">
                  ${isDone ? '✔ Consumido' : 'Marcar Consumo'}
                </button>
              ` : `
                <span class="day-badge" style="font-size: 11px; background: rgba(255, 255, 255, 0.05); color: var(--text-secondary);">
                  Programado
                </span>
              `}
            </div>
          </div>

          <p class="meal-block-desc">${meal.description || 'Alimentos equilibrados ricos en nutrientes'}</p>

          <div class="meal-macros-row">
            <span class="macro-chip cal">🔥 ${meal.calories} kcal</span>
            <span class="macro-chip prot">🥩 ${meal.p}g Proteína</span>
            <span class="macro-chip carb">🌾 ${meal.c}g Carbos</span>
            <span class="macro-chip fat">🥑 ${meal.f}g Grasas</span>
          </div>
        </div>
      `;
    }).join('');
  }
}

/**
 * Alterna el estado de una comida programada para hoy (Desayuno, Almuerzo, etc.)
 * y sincroniza con el backend sin popups ni bloqueos invasivos.
 * @param {string} mealKey
 * @param {HTMLElement|null} btnElement
 */
window.toggleScheduledMeal = async function (mealKey, btnElement) {
  if (!AppState.pendingMeals) AppState.pendingMeals = new Set();
  if (AppState.pendingMeals.has(mealKey)) return;

  // Solo se puede marcar o desmarcar en el día de hoy
  if (!AppState.nutrition.isToday) return;

  AppState.pendingMeals.add(mealKey);

  // 1. Actualización optimista inmediata en la interfaz (sin alertas emergentes)
  const mealItem = AppState.nutrition.schedule.find(m => m.mealKey === mealKey);
  const wasDone = mealItem ? !!mealItem.isCompleted : false;
  if (mealItem) {
    mealItem.isCompleted = !wasDone;
    renderNutritionSchedule();
  }

  // 2. Persistencia en base de datos
  try {
    const res = await ApiClient.request('controllers/api_nutrition.php?action=toggle_scheduled_meal', {
      method: 'POST',
      body: JSON.stringify({ meal_key: mealKey })
    });

    if (res && res.success) {
      if (res.data?.streak) {
        AppState.streak = res.data.streak;
        renderStreakComponent();
      }
      // Re-sincronizar totales precisos desde el backend
      loadNutritionData(AppState.nutrition.selectedDayKey);
    } else if (res && !res.success) {
      if (mealItem) {
        mealItem.isCompleted = wasDone;
        renderNutritionSchedule();
      }
    }
  } catch (err) {
    // Si falla la petición, mantener estado local
  } finally {
    AppState.pendingMeals.delete(mealKey);
  }
};

/**
 * Renderiza el balance calórico, barras de macronutrientes, hidratación y comidas libres.
 */
function renderNutritionSummary() {
  const { targets, consumed, freeMeals } = AppState.nutrition;

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
    if (!freeMeals || freeMeals.length === 0) {
      list.innerHTML = `<li style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 12px;">No hay alimentos extra registrados hoy.</li>`;
    } else {
      list.innerHTML = freeMeals.map(m => `
        <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <div style="font-weight: 600; font-size: 14px;">${m.name}</div>
            <div style="font-size: 12px; color: var(--text-secondary);">${m.type || 'Snack'} • P: ${m.p}g | C: ${m.c}g | G: ${m.f}g</div>
          </div>
          <span style="font-weight: 700; color: var(--accent-emerald); font-size: 14px;">+${m.calories} kcal</span>
        </li>
      `).join('');
    }
  }
}

/**
 * Alias de compatibilidad hacia atrás.
 */
function renderNutrition() {
  renderNutritionSchedule();
  renderNutritionSummary();
}

/**
 * Ajusta la cantidad de agua consumida en mililitros.
 * Actualiza la interfaz de inmediato sin ventanas emergentes ni bloqueo de botones.
 * @param {number} deltaMl 
 */
let _waterDebounceTimer = null;
let _pendingWaterDelta = 0;
window.adjustWater = function (deltaMl) {
  const current = AppState.nutrition.consumed.waterMl;
  AppState.nutrition.consumed.waterMl = Math.max(0, current + deltaMl);
  renderNutritionSummary();

  _pendingWaterDelta += deltaMl;
  clearTimeout(_waterDebounceTimer);
  _waterDebounceTimer = setTimeout(async () => {
    const toSend = _pendingWaterDelta;
    _pendingWaterDelta = 0;
    try {
      await ApiClient.request('controllers/api_nutrition.php?action=adjust_water', {
        method: 'POST',
        body: JSON.stringify({ delta: toSend })
      });
    } catch (e) {
      // Persistencia local
    }
  }, 400);
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
 * Deshabilita el botón de envío durante el proceso para evitar peticiones repetidas.
 */
let _isAddMealPending = false;
window.submitAddMealForm = async function (e) {
  if (e) e.preventDefault();
  if (_isAddMealPending) return;

  const nameInput = document.getElementById('mealNameInput');
  const typeInput = document.getElementById('mealTypeInput');
  const pInput = document.getElementById('mealProteinInput');
  const cInput = document.getElementById('mealCarbsInput');
  const fInput = document.getElementById('mealFatsInput');

  const name = nameInput ? nameInput.value.trim() : 'Alimento libre';
  const type = typeInput ? typeInput.value : 'Snack';
  const p = Math.max(0, Number(pInput?.value) || 0);
  const c = Math.max(0, Number(cInput?.value) || 0);
  const f = Math.max(0, Number(fInput?.value) || 0);
  const cal = Math.round((p * 4) + (c * 4) + (f * 9));

  if (!name) {
    showToast('warning', 'Campo Requerido', 'Por favor ingresa el nombre de la comida.');
    return;
  }

  _isAddMealPending = true;
  const submitBtn = document.querySelector('#addMealModal button[type="submit"]') || document.querySelector('#formAddMeal button[type="submit"]');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.classList.add('btn-throttled');
  }

  const newMeal = { id: Date.now(), name, type, p, c, f, calories: cal };
  AppState.nutrition.freeMeals.unshift(newMeal);
  AppState.nutrition.consumed.calories += cal;
  AppState.nutrition.consumed.proteinG += p;
  AppState.nutrition.consumed.carbsG += c;
  AppState.nutrition.consumed.fatsG += f;

  renderNutritionSummary();
  closeAddMealModal();
  showToast('success', 'Alimento Registrado', `${name} (+${cal} kcal añadidos al día).`);

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
  } finally {
    setTimeout(() => {
      _isAddMealPending = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-throttled');
      }
    }, 2500);
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

let _isAiCoachPending = false;
window.sendAICoachMessage = async function () {
  if (_isAiCoachPending) return;
  const input = document.getElementById('aiCoachInput');
  const box = document.getElementById('aiCoachMessages');
  const sendBtn = document.getElementById('aiCoachSendBtn') || document.querySelector('#aiCoachDrawer button[onclick*="sendAICoachMessage"]');
  if (!input || !box) return;

  const text = input.value.trim();
  if (!text) return;

  _isAiCoachPending = true;
  if (sendBtn) {
    sendBtn.disabled = true;
    sendBtn.classList.add('btn-throttled');
  }

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
    const token = localStorage.getItem('kynap_auth_token');
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch('chat_kynap_ai.php', {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({ message: text })
    });
    const data = await res.json();
    const reply = data.response || 'No se recibió respuesta del entrenador virtual.';
    botMsg.innerHTML = reply.replace(/\n/g, '<br>');
  } catch (e) {
    botMsg.innerHTML = `Entendido atleta. Te sugiero mantener una hidratación de ${AppState.nutrition.targets.waterMl} ml y priorizar tu descanso nocturno para optimizar la hipertrofia.`;
  } finally {
    _isAiCoachPending = false;
    if (sendBtn) {
      sendBtn.disabled = false;
      sendBtn.classList.remove('btn-throttled');
    }
  }
  box.scrollTop = box.scrollHeight;
};

// ==============================================================================
// 9.1 MODAL DE GENERACIÓN DE RUTINAS CON GROQ (LLAMA 3.3)
// ==============================================================================
window.openAiRoutineModal = function () {
  const modal = document.getElementById('aiRoutineModal');
  if (modal) modal.classList.add('open');
};

window.closeAiRoutineModal = function () {
  const modal = document.getElementById('aiRoutineModal');
  if (modal) modal.classList.remove('open');
};

window.handleGenerateAIRoutine = async function (e) {
  if (e) e.preventDefault();
  const objetivo = document.getElementById('aiRoutineObjetivo')?.value || 'ganar_musculo';
  const dias = parseInt(document.getElementById('aiRoutineDias')?.value || '4', 10);
  const equipamiento = document.getElementById('aiRoutineEquipamiento')?.value || 'gimnasio_completo';

  const statusBox = document.getElementById('aiRoutineStatusBox');
  const statusText = document.getElementById('aiRoutineStatusText');
  const submitBtn = document.getElementById('btnSubmitAiRoutine');

  if (statusBox) statusBox.style.display = 'block';
  if (statusText) statusText.textContent = '🤖 Kynap AI (Llama 3.3) analizando biometría y estructurando periodización...';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⚡ Diseñando con Llama 3.3...';
  }

  try {
    const res = await ApiClient.request('controllers/api_routines.php?action=generate_ai', {
      method: 'POST',
      body: JSON.stringify({
        objetivo: objetivo,
        dias_semana: dias,
        equipamiento: equipamiento
      })
    });

    if (res && res.success && res.data?.routine) {
      AppState.activeRoutine = res.data.routine;
      AppState.checkIns = new Set();
      renderRoutines();
      closeAiRoutineModal();
      showToast('success', '¡Rutina IA Diseñada!', `Nueva rutina "${res.data.routine.name}" programada y guardada en tu perfil.`);
    } else {
      showToast('error', 'Error en la IA', res?.message || 'No se pudo generar la rutina.');
    }
  } catch (err) {
    showToast('error', 'Error de Conexión', 'No se pudo conectar con el servidor para generar la rutina.');
  } finally {
    if (statusBox) statusBox.style.display = 'none';
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '✨ Generar Rutina con Llama 3.3';
    }
  }
};

// ==============================================================================
// 10. MÓDULO 6: MENTE SANA & RESPIRACIÓN GUIADA
// ==============================================================================
const BREATHING_PRESETS = {
// ==============================================================================
// 10. MÓDULO 6: MENTE SANA & RESPIRACIÓN GUIADA (MOTOR REACTIVO INTERNO)
// ==============================================================================
const BREATHING_CONFIG = {
  '4-4-4': {
    name: '4-4-4 Box Breathing (Equilibrio Mental & Enfoque)',
    phases: [
      { name: 'Inhala', duration: 4, scale: 2.1, glow: '0 0 50px rgba(0, 217, 255, 0.75)' },
      { name: 'Mantén', duration: 4, scale: 2.1, glow: '0 0 50px rgba(0, 217, 255, 0.75)' },
      { name: 'Exhala', duration: 4, scale: 1.0, glow: '0 0 20px rgba(0, 217, 255, 0.25)' },
      { name: 'Pausa', duration: 4, scale: 1.0, glow: '0 0 15px rgba(0, 217, 255, 0.15)' }
    ]
  },
  '4-7-8': {
    name: '4-7-8 Respiración Profunda (Recuperación y Calma)',
    phases: [
      { name: 'Inhala', duration: 4, scale: 2.1, glow: '0 0 50px rgba(0, 217, 255, 0.75)' },
      { name: 'Mantén', duration: 7, scale: 2.1, glow: '0 0 50px rgba(0, 217, 255, 0.75)' },
      { name: 'Exhala', duration: 8, scale: 1.0, glow: '0 0 20px rgba(0, 217, 255, 0.25)' }
    ]
  },
  '2-2': {
    name: '2-2 Respiración Energizante (Activación Pre-Entreno)',
    phases: [
      { name: 'Inhala', duration: 2, scale: 2.0, glow: '0 0 45px rgba(0, 255, 136, 0.75)' },
      { name: 'Exhala', duration: 2, scale: 1.0, glow: '0 0 20px rgba(0, 255, 136, 0.25)' }
    ]
  },
  '5-5': {
    name: '5-5 Coherencia Cardíaca (Estabilidad Cardiorrespiratoria)',
    phases: [
      { name: 'Inhala', duration: 5, scale: 2.1, glow: '0 0 50px rgba(0, 217, 255, 0.75)' },
      { name: 'Exhala', duration: 5, scale: 1.0, glow: '0 0 20px rgba(0, 217, 255, 0.25)' }
    ]
  }
};

let _breathingClockInterval = null;
let _sessionSeconds = 0;
let _currentPhaseIdx = 0;
let _phaseSecondsLeft = 0;
let _activePresetKey = '4-4-4';

/**
 * Obtiene el preset activo consultando los selectores del DOM.
 */
function getActiveBreathingPresetKey() {
  const openModal = document.querySelector('#breathingModal.open');
  if (openModal) {
    const sel = openModal.querySelector('.breathing-preset-select, select[id*="breathing"]');
    if (sel && sel.value) return sel.value;
  }
  const anySel = document.querySelector('.breathing-preset-select, select[id*="breathing"]');
  return anySel ? anySel.value : _activePresetKey;
}

/**
 * Actualiza la visualización del reloj de sesión (MM:SS) en todos los contenedores.
 */
function updateSessionClockDisplay() {
  const mins = String(Math.floor(_sessionSeconds / 60)).padStart(2, '0');
  const secs = String(_sessionSeconds % 60).padStart(2, '0');
  const str = `${mins}:${secs}`;
  document.querySelectorAll('.breathing-session-clock, #breathingSessionClock').forEach(el => {
    el.textContent = str;
  });
}

/**
 * Inicializa explícitamente el módulo de respiración y enlaza de forma segura todos los elementos del DOM.
 * Incluye comprobaciones previas contra null/undefined para evitar errores en tiempo de ejecución.
 */
window.initRespiracion = function () {
  _activePresetKey = getActiveBreathingPresetKey();

  // 1. Sincronizar selectores de patrón y registrar eventos con verificación de presencia
  const selects = document.querySelectorAll('.breathing-preset-select, select[id*="breathingPresetSelect"]');
  selects.forEach(sel => {
    if (!sel) return;
    if (sel.value !== _activePresetKey) sel.value = _activePresetKey;
    if (!sel.dataset.listenerBound) {
      sel.dataset.listenerBound = 'true';
      sel.addEventListener('change', (e) => {
        window.changeBreathingPreset(e.target.value);
      });
    }
  });

  // 2. Enlazar botones de Play / Pausa con guarda contra múltiples listeners
  const toggleBtns = document.querySelectorAll('.btn-breathing-toggle, #breathingToggleBtn');
  toggleBtns.forEach(btn => {
    if (!btn) return;
    if (!btn.dataset.listenerBound) {
      btn.dataset.listenerBound = 'true';
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.toggleBreathingExercise();
      });
    }
  });

  // 3. Enlazar botones de reinicio
  const resetBtns = document.querySelectorAll('.btn-breathing-reset');
  resetBtns.forEach(btn => {
    if (!btn) return;
    if (!btn.dataset.listenerBound) {
      btn.dataset.listenerBound = 'true';
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.resetBreathingExercise();
      });
    }
  });

  // 4. Enlazar clic directo en la esfera de respiración
  const spheres = document.querySelectorAll('.breathing-sphere');
  spheres.forEach(sphere => {
    if (!sphere) return;
    if (!sphere.dataset.listenerBound) {
      sphere.dataset.listenerBound = 'true';
      sphere.addEventListener('click', (e) => {
        e.preventDefault();
        window.toggleBreathingExercise();
      });
    }
  });

  // 5. Restablecer interfaz visual únicamente si la sesión no está en ejecución
  if (!AppState.breathing.isActive) {
    document.querySelectorAll('.breathing-text').forEach(t => { if (t) t.textContent = 'Iniciar'; });
    document.querySelectorAll('.breathing-countdown').forEach(c => { if (c) c.textContent = ''; });
    spheres.forEach(s => {
      if (s) {
        s.style.transition = 'transform 0.4s ease, box-shadow 0.4s ease';
        s.style.transform = 'scale(1)';
        s.style.boxShadow = '';
      }
    });
    toggleBtns.forEach(b => {
      if (b) b.textContent = '▶ Comenzar Sesión';
    });
    updateSessionClockDisplay();
  }
};

/**
 * Cambia el preset de respiración en tiempo real y reinicia el ciclo.
 */
window.changeBreathingPreset = function (presetKey) {
  if (BREATHING_CONFIG[presetKey]) {
    _activePresetKey = presetKey;
  }
  const selects = document.querySelectorAll('.breathing-preset-select, select[id*="breathingPresetSelect"]');
  selects.forEach(sel => { if (sel) sel.value = _activePresetKey; });

  if (AppState.breathing.isActive) {
    _currentPhaseIdx = 0;
    const config = BREATHING_CONFIG[_activePresetKey];
    _phaseSecondsLeft = config.phases[0].duration;
    applyPhaseVisuals(config.phases[0]);
  }
};
window.cambiarRutina = window.changeBreathingPreset;

/**
 * Aplica los estilos visuales de la fase actual a todas las esferas y textos activos.
 */
function applyPhaseVisuals(phase) {
  if (!phase) return;
  const spheres = document.querySelectorAll('.breathing-sphere');
  const textEls = document.querySelectorAll('.breathing-text');
  const countEls = document.querySelectorAll('.breathing-countdown');

  textEls.forEach(el => { if (el) el.textContent = phase.name; });
  countEls.forEach(el => { if (el) el.textContent = `${_phaseSecondsLeft}s`; });

  spheres.forEach(s => {
    if (s) {
      s.style.transition = `transform ${phase.duration}s cubic-bezier(0.4, 0, 0.2, 1), box-shadow ${phase.duration}s ease`;
      s.style.transform = `scale(${phase.scale})`;
      s.style.boxShadow = phase.glow;
    }
  });
}

/**
 * Inicia la ejecución de la respiración guiada con temporizador reactivo segundo a segundo.
 * Ejecuta la animación y el cronómetro de forma explícita.
 */
window.startBreathingExercise = function () {
  if (_breathingClockInterval) clearInterval(_breathingClockInterval);

  AppState.breathing.isActive = true;
  _activePresetKey = getActiveBreathingPresetKey();
  const config = BREATHING_CONFIG[_activePresetKey] || BREATHING_CONFIG['4-4-4'];

  _currentPhaseIdx = 0;
  const initialPhase = config.phases[_currentPhaseIdx];
  _phaseSecondsLeft = initialPhase.duration;

  // Actualizar botones a estado Pausar
  document.querySelectorAll('.btn-breathing-toggle, #breathingToggleBtn').forEach(b => {
    if (b) b.textContent = '⏸ Pausar Sesión';
  });

  applyPhaseVisuals(initialPhase);

  // Reloj de ejecución a 1 segundo de intervalo (1000ms)
  _breathingClockInterval = setInterval(() => {
    if (!AppState.breathing.isActive) return;

    // 1. Avanzar cronómetro de sesión general
    _sessionSeconds++;
    updateSessionClockDisplay();

    // 2. Decrementar cuenta regresiva de la fase activa
    _phaseSecondsLeft--;
    document.querySelectorAll('.breathing-countdown').forEach(el => {
      if (el) el.textContent = _phaseSecondsLeft > 0 ? `${_phaseSecondsLeft}s` : '1s';
    });

    // 3. Cuando termina la fase, avanzar a la siguiente
    if (_phaseSecondsLeft <= 0) {
      _currentPhaseIdx = (_currentPhaseIdx + 1) % config.phases.length;
      const nextPhase = config.phases[_currentPhaseIdx];
      _phaseSecondsLeft = nextPhase.duration;
      applyPhaseVisuals(nextPhase);
    }
  }, 1000);
};
window.iniciarRespiracion = window.startBreathingExercise;
window.startTimer = window.startBreathingExercise;

/**
 * Pausa la respiración guiada manteniendo el tiempo acumulado de sesión.
 */
window.stopBreathingExercise = function () {
  if (_breathingClockInterval) {
    clearInterval(_breathingClockInterval);
    _breathingClockInterval = null;
  }
  AppState.breathing.isActive = false;

  document.querySelectorAll('.btn-breathing-toggle, #breathingToggleBtn').forEach(b => {
    if (b) b.textContent = '▶ Reanudar Sesión';
  });
  document.querySelectorAll('.breathing-text').forEach(t => { if (t) t.textContent = 'Pausado'; });
  document.querySelectorAll('.breathing-countdown').forEach(c => { if (c) c.textContent = ''; });

  document.querySelectorAll('.breathing-sphere').forEach(s => {
    if (s) {
      s.style.transition = 'transform 0.4s ease, box-shadow 0.4s ease';
      s.style.transform = 'scale(1)';
      s.style.boxShadow = '';
    }
  });
};
window.pausarRespiracion = window.stopBreathingExercise;

/**
 * Reinicia completamente la sesión de respiración y el cronómetro a cero.
 */
window.resetBreathingExercise = function () {
  if (_breathingClockInterval) {
    clearInterval(_breathingClockInterval);
    _breathingClockInterval = null;
  }
  AppState.breathing.isActive = false;
  _sessionSeconds = 0;
  _phaseSecondsLeft = 0;
  _currentPhaseIdx = 0;

  window.initRespiracion();
};

/**
 * Alterna entre iniciar y pausar con protección de debounce (300ms) para evitar
 * dobles llamadas cuando coexisten inline onclick y addEventListener.
 */
let _lastToggleTime = 0;
window.toggleBreathingExercise = function () {
  const now = Date.now();
  if (now - _lastToggleTime < 300) return;
  _lastToggleTime = now;

  if (AppState.breathing.isActive) {
    window.stopBreathingExercise();
  } else {
    window.startBreathingExercise();
  }
};

/**
 * Alterna entre sub-secciones dentro de Entrenamiento en Vivo (Series vs Mente Sana).
 * Inicia la sesión de respiración automáticamente al ingresar a la sub-pestaña.
 * @param {'series'|'breathing'} subtab 
 */
window.switchWorkoutSubTab = function (subtab) {
  AppState.workoutSubtab = subtab;
  const btnSeries = document.getElementById('btnSubnavSeries');
  const btnBreathing = document.getElementById('btnSubnavBreathing');
  const viewSeries = document.getElementById('workoutSubtabSeries');
  const viewBreathing = document.getElementById('workoutSubtabBreathing');

  if (subtab === 'breathing') {
    if (btnSeries) {
      btnSeries.classList.remove('active');
      btnSeries.setAttribute('aria-selected', 'false');
    }
    if (btnBreathing) {
      btnBreathing.classList.add('active');
      btnBreathing.setAttribute('aria-selected', 'true');
    }
    if (viewSeries) viewSeries.style.display = 'none';
    if (viewBreathing) viewBreathing.style.display = 'block';
    window.initRespiracion();
    window.startBreathingExercise();
  } else {
    if (btnSeries) {
      btnSeries.classList.add('active');
      btnSeries.setAttribute('aria-selected', 'true');
    }
    if (btnBreathing) {
      btnBreathing.classList.remove('active');
      btnBreathing.setAttribute('aria-selected', 'false');
    }
    if (viewSeries) viewSeries.style.display = '';
    if (viewBreathing) viewBreathing.style.display = 'none';
    window.stopBreathingExercise();
  }
};

/**
 * Abre el modal interactivo de Mente Sana & Respiración Guiada.
 * Ejecuta explícitamente initRespiracion() y startBreathingExercise() / startTimer()
 * al momento exacto de abrir el modal para que no quede congelado.
 */
window.openBreathingModal = function () {
  const modal = document.getElementById('breathingModal');
  if (modal) {
    modal.classList.add('open');
  }
  window.initRespiracion();
  window.startBreathingExercise();
};

window.closeBreathingModal = function () {
  const modal = document.getElementById('breathingModal');
  if (modal) {
    modal.classList.remove('open');
  }
  window.stopBreathingExercise();
};

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
  if (tabId === 'workouts') {
    renderWorkoutLogger();
    if (AppState.workoutSubtab) {
      switchWorkoutSubTab(AppState.workoutSubtab);
    }
  }
  if (tabId === 'nutrition') renderNutrition();
  if (tabId === 'exercises') renderExerciseCatalog();

  // Scroll suave al tope en móvil
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ==============================================================================
// 12. MÓDULO DE RACHAS (STREAK SYSTEM & CALENDARIO COMPACTO - REQUERIMIENTO 3)
// ==============================================================================
/**
 * Renderiza el componente de la racha en el header y en el modal detallado.
 */
function renderStreakComponent() {
  const streak = AppState.streak || {};
  const currentStreak = streak.currentStreak ?? streak.current_streak ?? 1;
  const longestStreak = streak.longestStreak ?? streak.longest_streak ?? currentStreak;
  const totalActive = streak.totalActiveDays ?? streak.total_active_days ?? currentStreak;
  const badge = streak.badge || { name: 'Fuego Inicial', icon: '🔥', level: 1, nextGoal: '2 días para Bronce 🥉' };
  const compactWeek = Array.isArray(streak.compactWeek) && streak.compactWeek.length > 0
    ? streak.compactWeek
    : [
        { dayLetter: 'L', isActive: true, isToday: false },
        { dayLetter: 'M', isActive: true, isToday: false },
        { dayLetter: 'X', isActive: false, isToday: false },
        { dayLetter: 'J', isActive: true, isToday: false },
        { dayLetter: 'V', isActive: false, isToday: false },
        { dayLetter: 'S', isActive: false, isToday: false },
        { dayLetter: 'D', isActive: false, isToday: true }
      ];

  // 1. Badge visible en el header superior
  const headerCounter = document.getElementById('streakCounter');
  const headerMedal = document.getElementById('streakMedalTag');
  if (headerCounter) {
    headerCounter.textContent = `${currentStreak} ${currentStreak === 1 ? 'día activo' : 'días activos'}`;
  }
  if (headerMedal) {
    headerMedal.textContent = `${badge.icon} ${badge.name}`;
  }

  // 2. Hero y métricas dentro del modal
  const modalDays = document.getElementById('streakModalDays');
  const modalMedal = document.getElementById('streakModalMedal');
  const modalGoal = document.getElementById('streakModalGoal');
  const modalProgress = document.getElementById('streakModalProgressBar');
  const modalLongest = document.getElementById('streakModalLongest');
  const modalTotal = document.getElementById('streakModalTotal');

  if (modalDays) modalDays.textContent = currentStreak;
  if (modalMedal) modalMedal.textContent = `${badge.icon} ${badge.name}`;
  if (modalGoal) modalGoal.textContent = badge.nextGoal || '¡Sigue así!';

  let nextTarget = 3;
  let prevTarget = 0;
  if (currentStreak >= 30) {
    nextTarget = 30;
    prevTarget = 30;
  } else if (currentStreak >= 14) {
    nextTarget = 30;
    prevTarget = 14;
  } else if (currentStreak >= 7) {
    nextTarget = 14;
    prevTarget = 7;
  } else if (currentStreak >= 3) {
    nextTarget = 7;
    prevTarget = 3;
  } else {
    nextTarget = 3;
    prevTarget = 0;
  }
  const span = nextTarget - prevTarget;
  const progressPct = span > 0 ? Math.min(100, Math.max(10, Math.round(((currentStreak - prevTarget) / span) * 100))) : 100;
  if (modalProgress) modalProgress.style.width = `${progressPct}%`;

  if (modalLongest) modalLongest.textContent = `${longestStreak} ${longestStreak === 1 ? 'día' : 'días'}`;
  if (modalTotal) modalTotal.textContent = `${totalActive} ${totalActive === 1 ? 'día' : 'días'}`;

  // 3. Mini Calendario Semanal Compacto (Últimos 7 días)
  const stripContainer = document.getElementById('streakCalendarStrip');
  if (stripContainer) {
    stripContainer.innerHTML = compactWeek.map(day => {
      const isDone = !!day.isActive;
      const isToday = !!day.isToday;
      let dayClass = 'strip-day-card';
      if (isDone) dayClass += ' active';
      if (isToday) dayClass += ' today';

      return `
        <div class="${dayClass}">
          <span class="strip-day-letter">${day.dayLetter || 'D'}</span>
          <span class="strip-day-icon">${isDone ? '🔥' : (isToday ? '⚡' : '⭕')}</span>
          <span class="strip-day-status">${isDone ? 'Activo' : (isToday ? 'Hoy' : 'Libre')}</span>
        </div>
      `;
    }).join('');
  }
}

window.openStreakModal = function () {
  renderStreakComponent();
  const modal = document.getElementById('streakModal');
  if (modal) modal.classList.add('open');
};

window.closeStreakModal = function () {
  const modal = document.getElementById('streakModal');
  if (modal) modal.classList.remove('open');
};

// ==============================================================================
// 13. MÓDULO DE CONDICIÓN FÍSICA & EVOLUCIÓN (7 DÍAS / 24H - REQUERIMIENTO 4)
// ==============================================================================
/**
 * Alterna entre pestañas del modal de biometría (Formulario vs Historial).
 * @param {'form'|'history'} subTab 
 */
window.switchBioSubTab = function (subTab) {
  const formBtn = document.getElementById('btnTabBioForm');
  const histBtn = document.getElementById('btnTabBioHistory');
  const formPane = document.getElementById('bioTabContentForm');
  const histPane = document.getElementById('bioTabContentHistory');

  if (formBtn) formBtn.classList.toggle('active', subTab === 'form');
  if (histBtn) histBtn.classList.toggle('active', subTab === 'history');
  if (formPane) formPane.style.display = subTab === 'form' ? 'block' : 'none';
  if (histPane) histPane.style.display = subTab === 'history' ? 'block' : 'none';
};

window.openBiometricsModal = function () {
  const modal = document.getElementById('biometricsModal');
  if (modal) {
    modal.classList.add('open');
    loadBiometricsData();
  }
};

window.closeBiometricsModal = function () {
  const modal = document.getElementById('biometricsModal');
  if (modal) modal.classList.remove('open');
};

/**
 * Carga desde el backend el perfil, la política de actualización (7d/24h) y el historial de evolución.
 */
async function loadBiometricsData() {
  try {
    const res = await ApiClient.request('controllers/api_profile.php');
    if (res && res.success && res.data) {
      const d = res.data;
      if (d.policy) AppState.biometrics.policy = d.policy;
      if (Array.isArray(d.history)) AppState.biometrics.history = d.history;
      if (d.biometrics) AppState.biometrics.current = d.biometrics;

      renderBiometricsPolicyBanner(d.policy);
      renderBiometricsForm(d.biometrics, d.policy);
      renderBiometricsHistory(d.history);
    }
  } catch (err) {
    showToast('error', 'Error de Carga', 'No se pudieron recuperar las métricas corporales.');
  }
}

/**
 * Renderiza el banner dinámico según el modo de negocio: corrección (24h), bloqueado (7d), o disponible.
 * @param {object} policy 
 */
function renderBiometricsPolicyBanner(policy) {
  const banner = document.getElementById('bioPolicyBanner');
  if (!banner || !policy) return;

  banner.className = 'policy-banner';

  if (policy.mode === 'correction') {
    banner.classList.add('correction');
    banner.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 26px;">✏️</span>
        <div>
          <strong style="color: var(--accent-amber); font-size: 14px;">Modo Corrección Activo (Ventana de 24 horas)</strong>
          <p style="font-size: 12px; margin: 3px 0 0; color: var(--text-secondary);">
            ${policy.message || 'Puedes editar o corregir cualquier error tipográfico en tu última medición sin esperar la semana.'}
          </p>
        </div>
      </div>
    `;
  } else if (policy.mode === 'locked') {
    banner.classList.add('locked');
    banner.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 26px;">🔒</span>
        <div>
          <strong style="color: var(--accent-rose); font-size: 14px;">Actualización Periódica en Pausa (Regla de 7 días)</strong>
          <p style="font-size: 12px; margin: 3px 0 0; color: var(--text-secondary);">
            Para mantener un registro fisiológico consistente y evitar fluctuaciones de agua, la actualización se realiza <strong>1 vez por semana</strong>. Próxima fecha: <strong>${policy.nextAllowedDate || 'En unos días'}</strong> (${policy.daysRemainingNextUpdate} días restantes).
          </p>
        </div>
      </div>
    `;
  } else {
    banner.classList.add('available');
    banner.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 26px;">✅</span>
        <div>
          <strong style="color: var(--accent-emerald); font-size: 14px;">Actualización Semanal Habilitada</strong>
          <p style="font-size: 12px; margin: 3px 0 0; color: var(--text-secondary);">
            Ha transcurrido al menos una semana desde tu registro anterior. Registra tu peso y medidas en ayunas para evaluar tu evolución.
          </p>
        </div>
      </div>
    `;
  }
}

/**
 * Pre-llena los inputs del formulario y ajusta el estado del botón submit.
 * @param {object} bio 
 * @param {object} policy 
 */
function renderBiometricsForm(bio, policy) {
  const pInput = document.getElementById('bioInputPeso');
  const aInput = document.getElementById('bioInputAltura');
  const gInput = document.getElementById('bioInputGrasa');
  const cInput = document.getElementById('bioInputCintura');
  const peInput = document.getElementById('bioInputPecho');
  const bInput = document.getElementById('bioInputBrazo');
  const nInput = document.getElementById('bioInputNotas');
  const submitBtn = document.getElementById('btnSubmitBiometrics');

  if (pInput && bio.peso) pInput.value = bio.peso;
  if (aInput && bio.altura) aInput.value = bio.altura;
  if (gInput) gInput.value = bio.grasa_corporal ?? '';
  if (cInput) cInput.value = bio.cintura_cm ?? '';
  if (peInput) peInput.value = bio.pecho_cm ?? '';
  if (bInput) bInput.value = bio.brazo_cm ?? '';
  if (nInput) nInput.value = bio.notas ?? '';

  if (submitBtn) {
    if (policy && !policy.canUpdate) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.5';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.innerHTML = `🔒 Registro Bloqueado (${policy.daysRemainingNextUpdate} días restantes)`;
    } else {
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitBtn.style.cursor = 'pointer';
      if (policy && policy.mode === 'correction') {
        submitBtn.innerHTML = `✏️ Guardar Corrección (Ventana 24h)`;
      } else {
        submitBtn.innerHTML = `💾 Registrar Nueva Medición Semanal`;
      }
    }
  }
}

/**
 * Renderiza la tabla de historial de evolución física con deltas de peso e IMC.
 * @param {Array} history 
 */
function renderBiometricsHistory(history) {
  const tbody = document.getElementById('bioHistoryTableBody');
  if (!tbody) return;

  if (!history || history.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
          Aún no tienes registros en tu historial de evolución.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = history.map(item => {
    const delta = Number(item.deltaPeso) || 0;
    let deltaClass = 'neutral';
    let deltaText = '0.0 kg';
    if (delta > 0) {
      deltaClass = 'up';
      deltaText = `+${delta.toFixed(1)} kg`;
    } else if (delta < 0) {
      deltaClass = 'down';
      deltaText = `${delta.toFixed(1)} kg`;
    }

    const bmi = Number(item.bmi) || 0;
    let bmiTagText = 'Normal';
    if (bmi < 18.5) {
      bmiTagText = 'Bajo Peso';
    } else if (bmi >= 25 && bmi < 30) {
      bmiTagText = 'Sobrepeso';
    } else if (bmi >= 30) {
      bmiTagText = 'Obesidad';
    }

    return `
      <tr>
        <td><strong>${item.date || item.created_at || 'Hoy'}</strong></td>
        <td><strong style="color: var(--accent-cyan);">${item.peso} kg</strong></td>
        <td><span class="delta-tag ${deltaClass}">${deltaText}</span></td>
        <td>
          <span style="font-weight: 700;">${bmi}</span>
          <span class="day-badge" style="font-size: 10px; margin-left: 4px;">${bmiTagText}</span>
        </td>
        <td>${item.cinturaCm ? `${item.cinturaCm} cm` : '-'}</td>
        <td style="color: var(--text-secondary); font-size: 12px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${item.notas || ''}">
          ${item.notas || 'Sin observaciones'}
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Procesa el envío del formulario de biometría respetando la validación del backend.
 * @param {Event} e 
 */
window.handleBiometricsSubmit = async function (e) {
  if (e) e.preventDefault();

  const pVal = parseFloat(document.getElementById('bioInputPeso')?.value);
  const aVal = parseFloat(document.getElementById('bioInputAltura')?.value);
  const gVal = parseFloat(document.getElementById('bioInputGrasa')?.value);
  const cVal = parseFloat(document.getElementById('bioInputCintura')?.value);
  const peVal = parseFloat(document.getElementById('bioInputPecho')?.value);
  const bVal = parseFloat(document.getElementById('bioInputBrazo')?.value);
  const nVal = document.getElementById('bioInputNotas')?.value?.trim();

  if (!pVal || !aVal) {
    showToast('warning', 'Campos Obligatorios', 'Por favor ingresa al menos tu peso (kg) y altura (cm).');
    return;
  }

  const submitBtn = document.getElementById('btnSubmitBiometrics');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Guardando en Servidor...';
  }

  try {
    const res = await ApiClient.request('controllers/api_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        peso: pVal,
        altura: aVal,
        grasa_corporal: isNaN(gVal) ? null : gVal,
        cintura_cm: isNaN(cVal) ? null : cVal,
        pecho_cm: isNaN(peVal) ? null : peVal,
        brazo_cm: isNaN(bVal) ? null : bVal,
        notas: nVal || ''
      })
    });

    if (res && res.success) {
      showToast('success', '¡Registro Exitoso!', res.message || 'Tu condición física ha sido actualizada.');
      await loadBiometricsData();
      switchBioSubTab('history');
    } else {
      showToast('warning', 'Restricción de Periodicidad', res.message || 'No se pudo guardar la medición.');
      if (res.data?.daysRemaining) {
        showToast('info', 'Regla Semanal Activa', `Podrás actualizar tus métricas en ${res.data.daysRemaining} días.`);
      }
    }
  } catch (err) {
    showToast('warning', 'Restricción de Tiempo', err.message || 'Solo se permite actualizar una vez cada 7 días.');
  } finally {
    setTimeout(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💾 Guardar Medición Semanal';
      }
    }, 2500);
  }
};

// ==============================================================================
// 14. HIDRATACIÓN INICIAL Y SINCRONIZACIÓN CON BACKEND
// ==============================================================================
/**
 * Actualiza dinámicamente el nombre de usuario y el avatar con iniciales automáticas.
 * @param {string} name - Nombre completo del usuario (ej: "Juan Pérez")
 */
function updateSidebarUserProfile(name) {
  if (!name) return;
  const nameEl = document.getElementById('sidebarUserName');
  const avatarEl = document.getElementById('sidebarAvatar');

  if (nameEl) {
    nameEl.textContent = name;
  }

  if (avatarEl) {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    let initials = 'K';
    if (parts.length === 1 && parts[0]) {
      initials = parts[0].slice(0, 2).toUpperCase();
    } else if (parts.length >= 2) {
      initials = (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    avatarEl.textContent = initials;
    avatarEl.setAttribute('aria-label', `Avatar de ${name}`);
    avatarEl.title = name;
  }
}
window.updateSidebarUserProfile = updateSidebarUserProfile;

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
    // Hidratar biometría desde sesión PHP para que openConfigModal tenga datos inmediatos
    if (s.biometrics) {
      const b = s.biometrics;
      if (b.peso) AppState.user.peso = b.peso;
      if (b.altura) AppState.user.altura = b.altura;
      if (b.edad) AppState.user.edad = b.edad;
      if (b.sexo) AppState.user.sexo = b.sexo;
      if (b.objetivo) AppState.user.objetivo = b.objetivo;
      if (b.nivel_actividad) AppState.user.nivel_actividad = b.nivel_actividad;
      if (b.agua) AppState.user.agua = b.agua;
      if (b.condiciones) AppState.user.condiciones = b.condiciones;
      if (b.alergias) AppState.user.alergias = b.alergias;
      if (b.preferencia) AppState.user.preferencia = b.preferencia;
    }
    updateSidebarUserProfile(AppState.user.name);
  }

  // 2. Sincronizar perfil, biometría, racha y metas (controllers/api_profile.php)
  try {
    const profRes = await ApiClient.request('controllers/api_profile.php');
    if (profRes && profRes.success && profRes.data) {
      const d = profRes.data;
      if (d.user) {
        AppState.user.name = d.user.nombre || AppState.user.name;
        AppState.user.email = d.user.email || AppState.user.email;
        if (d.user.imagen_user) AppState.user.avatar = d.user.imagen_user;
        updateSidebarUserProfile(AppState.user.name);
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
      if (d.streak) {
        AppState.streak = d.streak;
        renderStreakComponent();
      }
      if (d.policy) AppState.biometrics.policy = d.policy;
      if (d.history) AppState.biometrics.history = d.history;
      if (d.biometrics) {
        AppState.biometrics.current = d.biometrics;
        // Sincronizar también en AppState.user para que openConfigModal use datos frescos
        const b = d.biometrics;
        if (b.peso) AppState.user.peso = b.peso;
        if (b.altura) AppState.user.altura = b.altura;
        if (b.edad) AppState.user.edad = b.edad;
        if (b.sexo) AppState.user.sexo = b.sexo;
        if (b.objetivo) AppState.user.objetivo = b.objetivo;
        if (b.nivel_actividad) AppState.user.nivel_actividad = b.nivel_actividad;
        if (b.agua) AppState.user.agua = b.agua;
        if (b.condiciones) AppState.user.condiciones = b.condiciones;
        if (b.alergias) AppState.user.alergias = b.alergias;
        if (b.preferencia) AppState.user.preferencia = b.preferencia;
      }

      renderNutrition();
    }
  } catch (e) {
    // Continuar con valores por defecto
  }

  // 3. Sincronizar rutina activa, check-ins y día del servidor (controllers/api_routines.php)
  try {
    const routRes = await ApiClient.request('controllers/api_routines.php');
    if (routRes && routRes.success) {
      if (routRes.data?.currentDayKey) AppState.currentServerDayKey = routRes.data.currentDayKey;
      if (routRes.data?.currentDayName) AppState.currentServerDayName = routRes.data.currentDayName;
      if (routRes.data?.routine && Array.isArray(routRes.data.routine.days)) {
        AppState.activeRoutine = routRes.data.routine;
      }
      if (routRes.data?.completedDays) {
        AppState.checkIns = new Set(routRes.data.completedDays);
      }
      if (routRes.data?.streak) {
        AppState.streak = routRes.data.streak;
        renderStreakComponent();
      }
      renderRoutines();
    }
  } catch (e) {
    // Continuar en memoria
  }

  // 4. Sincronizar nutrición del día con horarios estructurados y reinicio a medianoche
  try {
    await loadNutritionData(AppState.currentServerDayKey);
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

  // Los inputs ya tienen value="" pre-rellenado por PHP en el HTML.
  // Solo actualizamos si AppState tiene datos más frescos (post-API sync).
  const n = document.getElementById('cfgNombre');
  const em = document.getElementById('cfgEmail');
  const p = document.getElementById('cfgPeso');
  const al = document.getElementById('cfgAltura');
  const ed = document.getElementById('cfgEdad');
  const ag = document.getElementById('cfgAgua');
  const obj = document.getElementById('cfgObjetivo');
  const act = document.getElementById('cfgNivelActividad');

  if (n && AppState.user.name) n.value = AppState.user.name;
  if (em && AppState.user.email) em.value = AppState.user.email;
  if (p && AppState.user.peso) p.value = AppState.user.peso;
  if (al && AppState.user.altura) al.value = AppState.user.altura;
  if (ed && AppState.user.edad) ed.value = AppState.user.edad;
  if (ag) {
    // Prioridad: agua en litros del user > waterMl de targets > valor actual del input
    if (AppState.user.agua) {
      ag.value = AppState.user.agua;
    } else if (AppState.nutrition.targets.waterMl) {
      ag.value = (AppState.nutrition.targets.waterMl / 1000).toFixed(1);
    }
  }
  if (obj && AppState.user.objetivo) obj.value = AppState.user.objetivo;
  if (act && AppState.user.nivel_actividad) act.value = AppState.user.nivel_actividad;

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
  const p = parseFloat(document.getElementById('cfgPeso')?.value) || (AppState.user.peso || 75);
  const al = parseFloat(document.getElementById('cfgAltura')?.value) || (AppState.user.altura || 175);
  const ed = parseInt(document.getElementById('cfgEdad')?.value, 10) || (AppState.user.edad || 25);
  const ag = parseFloat(document.getElementById('cfgAgua')?.value) || (AppState.user.agua || 3.0);
  const obj = document.getElementById('cfgObjetivo')?.value || (AppState.user.objetivo || 'ganar_musculo');
  const act = document.getElementById('cfgNivelActividad')?.value || (AppState.user.nivel_actividad || 'moderado');

  if (n) AppState.user.name = n;
  if (em) AppState.user.email = em;
  AppState.user.peso = p;
  AppState.user.altura = al;
  AppState.user.edad = ed;
  AppState.user.agua = ag;
  AppState.user.objetivo = obj;
  AppState.user.nivel_actividad = act;
  AppState.nutrition.targets.waterMl = Math.round(ag * 1000);

  // Recalcular macros en cliente con Mifflin-St Jeor
  const isFemale = AppState.user.sexo === 'femenino';
  const bmr = isFemale
    ? (10 * p) + (6.25 * al) - (5 * ed) - 161
    : (10 * p) + (6.25 * al) - (5 * ed) + 5;
  const actFactors = { sedentario: 1.2, ligero: 1.375, moderado: 1.55, muy_activo: 1.725, activo: 1.725 };
  const factor = actFactors[act] || 1.55;
  const tdee = Math.round(bmr * factor);
  const goalAdj = (obj === 'ganar_musculo') ? 350 : (obj === 'perder_grasa') ? -450 : 0;
  AppState.nutrition.targets.calories = Math.max(1300, tdee + goalAdj);
  const protMult = (obj === 'perder_grasa') ? 2.2 : (obj === 'ganar_musculo') ? 2.0 : 1.8;
  AppState.nutrition.targets.proteinG = Math.round(p * protMult);
  AppState.nutrition.targets.fatsG = Math.round((AppState.nutrition.targets.calories * 0.25) / 9);
  const remainingCal = AppState.nutrition.targets.calories - (AppState.nutrition.targets.proteinG * 4) - (AppState.nutrition.targets.fatsG * 9);
  AppState.nutrition.targets.carbsG = Math.max(50, Math.round(remainingCal / 4));

  const sidebarName = document.getElementById('sidebarUserName');
  if (sidebarName && n) sidebarName.textContent = n;
  if (n) updateSidebarUserProfile(n);
  renderNutrition();
  closeConfigModal();

  showToast('success', 'Perfil Actualizado', 'Guardando configuración...');

  try {
    const res = await ApiClient.request('controllers/api_profile.php', {
      method: 'POST',
      body: JSON.stringify({
        nombre: n,
        email: em,
        peso: p,
        altura: al,
        edad: ed,
        agua: ag,
        objetivo: obj,
        nivel_actividad: act,
        sexo: AppState.user.sexo || 'masculino',
        condiciones: AppState.user.condiciones || '',
        alergias: AppState.user.alergias || '',
        preferencia: AppState.user.preferencia || 'omnivora'
      })
    });
    if (res && res.success && res.data && res.data.targets) {
      AppState.nutrition.targets = {
        calories: res.data.targets.calories || AppState.nutrition.targets.calories,
        proteinG: res.data.targets.proteinG || AppState.nutrition.targets.proteinG,
        carbsG: res.data.targets.carbsG || AppState.nutrition.targets.carbsG,
        fatsG: res.data.targets.fatsG || AppState.nutrition.targets.fatsG,
        waterMl: res.data.targets.waterMl || AppState.nutrition.targets.waterMl
      };
      renderNutrition();
      showToast('success', 'Perfil Guardado', 'Metas nutricionales actualizadas correctamente.');
    }
  } catch (err) {
    // Los cambios ya están en memoria; la sincronización con BD fallará silenciosamente
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
// 16. INICIALIZACIÓN DE LA APLICACIÓN AL CARGAR DOM
// ==============================================================================
document.addEventListener('DOMContentLoaded', () => {
  renderRoutines();
  renderWorkoutLogger();
  renderNutrition();
  renderExerciseCatalog();
  renderStreakComponent();
  updateSidebarUserProfile(AppState.user.name);
  updateTimerUI();
  window.initRespiracion();

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

  // Formulario de condición física (regla de 7 días y 24h de margen)
  const biometricsForm = document.getElementById('biometricsForm');
  if (biometricsForm) {
    biometricsForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleBiometricsSubmit(e);
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

