import { Routine, RoutineDay, RoutineExerciseConfig } from '../../../core/types/fitness';
import { INITIAL_ROUTINES } from '../data/initialRoutines';

export class RoutineService {
  private routines: Routine[];

  constructor(customRoutines: Routine[] = []) {
    this.routines = [...INITIAL_ROUTINES, ...customRoutines];
  }

  /**
   * Obtiene la rutina activa de un usuario (o la plantilla predeterminada)
   */
  public getActiveRoutine(userId: string): Routine {
    const userRoutine = this.routines.find((r) => r.userId === userId && r.isActive);
    if (userRoutine) return userRoutine;

    // Retorna la plantilla por defecto si el usuario aún no tiene una personalizada
    return this.routines[0];
  }

  /**
   * Obtiene una rutina por su ID
   */
  public getById(routineId: string): Routine | undefined {
    return this.routines.find((r) => r.id === routineId);
  }

  /**
   * Crea una nueva rutina para el usuario
   */
  public createRoutine(userId: string, name: string, description?: string): Routine {
    const newRoutine: Routine = {
      id: `routine-${Date.now()}`,
      userId,
      name,
      description,
      isActive: true,
      days: [],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Desactivar otras rutinas activas del usuario
    this.routines.forEach((r) => {
      if (r.userId === userId) r.isActive = false;
    });

    this.routines.push(newRoutine);
    return newRoutine;
  }

  /**
   * Agrega o actualiza un ejercicio en un día de entrenamiento
   */
  public addExerciseToDay(
    routineId: string,
    dayId: string,
    exerciseConfig: Omit<RoutineExerciseConfig, 'orderIndex'>
  ): RoutineDay | null {
    const routine = this.getById(routineId);
    if (!routine) return null;

    const day = routine.days.find((d) => d.id === dayId);
    if (!day) return null;

    const nextOrder = day.exercises.length + 1;
    day.exercises.push({
      ...exerciseConfig,
      orderIndex: nextOrder
    });

    routine.updatedAt = new Date().toISOString();
    return day;
  }

  /**
   * Cambia el estado de día de descanso
   */
  public toggleRestDay(routineId: string, dayId: string): RoutineDay | null {
    const routine = this.getById(routineId);
    if (!routine) return null;

    const day = routine.days.find((d) => d.id === dayId);
    if (!day) return null;

    day.isRestDay = !day.isRestDay;
    if (day.isRestDay) {
      day.exercises = [];
    }

    routine.updatedAt = new Date().toISOString();
    return day;
  }
}

export const routineService = new RoutineService();
