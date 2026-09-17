// src/core/types/fitness.ts

// ==========================================
// 1. DÍAS Y COMUNES
// ==========================================
export type DayOfWeek =
  | 'monday'
  | 'tuesday'
  | 'wednesday'
  | 'thursday'
  | 'friday'
  | 'saturday'
  | 'sunday';

export type MuscleGroup =
  | 'chest'
  | 'back'
  | 'quads'
  | 'hamstrings'
  | 'glutes'
  | 'calves'
  | 'shoulders'
  | 'biceps'
  | 'triceps'
  | 'forearms'
  | 'abs'
  | 'full_body'
  | 'cardio';

// ==========================================
// 2. CORE 2: EJERCICIOS Y SERIES (SETS)
// ==========================================
export interface Exercise {
  id: string;
  name: string;
  muscleGroup: MuscleGroup;
  secondaryMuscles?: MuscleGroup[];
  equipment: 'barbell' | 'dumbbell' | 'cable' | 'machine' | 'bodyweight' | 'bands' | 'other';
  mediaUrl?: string; // GIF demostrativo o video
  instructions?: string[];
  isCustom: boolean;
  userId?: string;
}

export interface WorkoutSet {
  id: string;
  setNumber: number;
  weightKg: number;
  reps: number;
  rpe?: number; // Rate of Perceived Exertion (1 al 10)
  rir?: number; // Reps in Reserve (0 a 4+)
  isWarmup: boolean;
  isCompleted: boolean;
}

// Configuración del ejercicio dentro de una plantilla de rutina
export interface RoutineExerciseConfig {
  exerciseId: string;
  exercise?: Exercise;
  orderIndex: number;
  targetSets: number;
  targetReps: string; // Ej: "8-10" o "12"
  targetRpe?: number;
  restSeconds: number;
}

// ==========================================
// 3. CORE 1: RUTINAS Y SEGUIMIENTO SEMANAL
// ==========================================
export interface RoutineDay {
  id: string;
  routineId: string;
  dayOfWeek: DayOfWeek;
  name: string; // Ej: "Pierna Pesada + Glúteos"
  orderIndex: number;
  isRestDay: boolean;
  exercises: RoutineExerciseConfig[];
}

export interface Routine {
  id: string;
  userId: string;
  name: string; // Ej: "Torso / Pierna 4 Días"
  description?: string;
  isActive: boolean;
  days: RoutineDay[];
  createdAt: string;
  updatedAt: string;
}

// Log de ejecución real (cuando el atleta entrena y tacha el día)
export interface WorkoutLog {
  id: string;
  userId: string;
  routineDayId?: string; // Referencia opcional al día planificado
  date: string; // YYYY-MM-DD
  startedAt: string;
  completedAt?: string;
  status: 'in_progress' | 'completed' | 'skipped';
  totalVolumeKg: number; // Sumatoria de (weight * reps)
  sets: Record<string, WorkoutSet[]>; // Key: exerciseId -> Lista de sets ejecutados
  notes?: string;
}

// Estado de la semana para el check-in / tachado
export interface WeeklyCheckInSummary {
  weekStartDate: string; // YYYY-MM-DD (Lunes)
  completedDays: DayOfWeek[];
  totalPlannedDays: number;
  compliancePercentage: number;
}

// ==========================================
// 4. CORE 3: NUTRICIÓN Y MACROS
// ==========================================
export interface MacroTargets {
  calories: number;
  proteinG: number;
  carbsG: number;
  fatsG: number;
  waterMl: number;
}

export type MealCategory = 'breakfast' | 'lunch' | 'dinner' | 'snack';

export interface MealFoodItem {
  id: string;
  name: string;
  servingAmount: number; // Ej: 150
  servingUnit: string;   // Ej: "g", "ml", "unidad"
  calories: number;
  proteinG: number;
  carbsG: number;
  fatsG: number;
}

export interface MealLog {
  id: string;
  category: MealCategory;
  loggedAt: string;
  items: MealFoodItem[];
}

export interface DailyNutritionLog {
  id: string;
  userId: string;
  date: string; // YYYY-MM-DD
  targets: MacroTargets;
  meals: MealLog[];
  waterConsumedMl: number;
  totalConsumed: {
    calories: number;
    proteinG: number;
    carbsG: number;
    fatsG: number;
  };
}
