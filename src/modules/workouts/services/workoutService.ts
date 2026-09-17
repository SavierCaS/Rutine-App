import { WorkoutLog, WorkoutSet } from '../../../core/types/fitness';

function toLocalDateString(d: Date = new Date()): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export class WorkoutService {
  private activeWorkouts: Map<string, WorkoutLog> = new Map();
  private history: WorkoutLog[] = [];

  /**
   * Inicia o recupera una sesión activa en hora local
   */
  public startWorkout(userId: string, routineDayId?: string): WorkoutLog {
    // Si el usuario ya tiene un entrenamiento en curso, recuperarlo
    const existing = Array.from(this.activeWorkouts.values()).find(
      (w) => w.userId === userId && w.status === 'in_progress'
    );
    if (existing) return existing;

    const newWorkout: WorkoutLog = {
      id: `workout-${Date.now()}`,
      userId,
      routineDayId,
      date: toLocalDateString(),
      startedAt: new Date().toISOString(),
      status: 'in_progress',
      totalVolumeKg: 0,
      sets: {},
      notes: ''
    };

    this.activeWorkouts.set(newWorkout.id, newWorkout);
    return newWorkout;
  }

  public getActiveWorkout(workoutId: string): WorkoutLog | undefined {
    return this.activeWorkouts.get(workoutId);
  }

  public getActiveWorkoutByUserId(userId: string): WorkoutLog | undefined {
    return Array.from(this.activeWorkouts.values()).find(
      (w) => w.userId === userId && w.status === 'in_progress'
    );
  }

  /**
   * Registra una serie garantizando sanitización de valores numéricos
   */
  public logSet(
    workoutId: string,
    exerciseId: string,
    setData: Omit<WorkoutSet, 'id' | 'setNumber'>
  ): WorkoutSet | null {
    const workout = this.activeWorkouts.get(workoutId);
    if (!workout) return null;

    if (!workout.sets[exerciseId]) {
      workout.sets[exerciseId] = [];
    }

    const currentSets = workout.sets[exerciseId];
    const weightKg = Number.isFinite(setData.weightKg) ? Math.max(0, setData.weightKg) : 0;
    const reps = Number.isFinite(setData.reps) ? Math.max(0, Math.floor(setData.reps)) : 0;

    const newSet: WorkoutSet = {
      id: `set-${Date.now()}-${currentSets.length + 1}`,
      setNumber: currentSets.length + 1,
      ...setData,
      weightKg,
      reps
    };

    currentSets.push(newSet);
    workout.totalVolumeKg = this.calculateVolume(workout.sets);
    return newSet;
  }

  public updateSet(
    workoutId: string,
    exerciseId: string,
    setId: string,
    updates: Partial<WorkoutSet>
  ): WorkoutSet | null {
    const workout = this.activeWorkouts.get(workoutId);
    if (!workout || !workout.sets[exerciseId]) return null;

    const setIndex = workout.sets[exerciseId].findIndex((s) => s.id === setId);
    if (setIndex === -1) return null;

    const current = workout.sets[exerciseId][setIndex];
    const weightKg = updates.weightKg !== undefined 
      ? (Number.isFinite(updates.weightKg) ? Math.max(0, updates.weightKg) : current.weightKg)
      : current.weightKg;
    const reps = updates.reps !== undefined 
      ? (Number.isFinite(updates.reps) ? Math.max(0, Math.floor(updates.reps)) : current.reps)
      : current.reps;

    const updatedSet: WorkoutSet = {
      ...current,
      ...updates,
      weightKg,
      reps
    };

    workout.sets[exerciseId][setIndex] = updatedSet;
    workout.totalVolumeKg = this.calculateVolume(workout.sets);
    return updatedSet;
  }

  /**
   * Elimina una serie y reordena secuencialmente los setNumbers
   */
  public deleteSet(workoutId: string, exerciseId: string, setId: string): boolean {
    const workout = this.activeWorkouts.get(workoutId);
    if (!workout || !workout.sets[exerciseId]) return false;

    const initialLength = workout.sets[exerciseId].length;
    workout.sets[exerciseId] = workout.sets[exerciseId].filter((s) => s.id !== setId);

    if (workout.sets[exerciseId].length === initialLength) return false;

    // Reindexar series restantes: 1, 2, 3...
    workout.sets[exerciseId].forEach((set, idx) => {
      set.setNumber = idx + 1;
    });

    workout.totalVolumeKg = this.calculateVolume(workout.sets);
    return true;
  }

  public completeWorkout(workoutId: string, notes?: string): WorkoutLog | null {
    const workout = this.activeWorkouts.get(workoutId);
    if (!workout) return null;

    workout.completedAt = new Date().toISOString();
    workout.status = 'completed';
    if (notes) workout.notes = notes;
    workout.totalVolumeKg = this.calculateVolume(workout.sets);

    this.history.unshift(workout);
    this.activeWorkouts.delete(workoutId);
    return workout;
  }

  /**
   * Volumen de carga blindado contra valores NaN y excluyendo calentamiento
   */
  public calculateVolume(setsMap: Record<string, WorkoutSet[]>): number {
    let total = 0;
    Object.values(setsMap).forEach((sets) => {
      sets.forEach((set) => {
        if (set.isCompleted && !set.isWarmup) {
          const w = Number(set.weightKg) || 0;
          const r = Number(set.reps) || 0;
          total += Math.max(0, w * r);
        }
      });
    });
    return Math.round(total * 100) / 100;
  }

  /**
   * 1RM fisiológicamente acotado (Epley limitada entre 1 y 12 reps)
   */
  public estimate1RM(weightKg: number, reps: number): number {
    const validWeight = Number(weightKg) || 0;
    const validReps = Number(reps) || 0;

    if (validWeight <= 0 || validReps <= 0) return 0;
    if (validReps === 1) return validWeight;

    // A partir de 12 repeticiones la fórmula de 1RM pierde validez fisiológica
    const cappedReps = Math.min(12, validReps);
    const oneRepMax = validWeight * (1 + cappedReps / 30);
    return Math.round(oneRepMax * 10) / 10;
  }

  public getUserHistory(userId: string): WorkoutLog[] {
    return this.history.filter((w) => w.userId === userId);
  }
}

export const workoutService = new WorkoutService();
