import { Exercise, MuscleGroup } from '../../../core/types/fitness';
import { INITIAL_EXERCISES } from '../data/initialExercises';

export interface ExerciseFilterOptions {
  muscleGroup?: MuscleGroup;
  equipment?: string;
  searchQuery?: string;
  isCustom?: boolean;
}

// Helper para búsqueda insensible a mayúsculas, minúsculas y tildes/acentos
function normalizeSearchText(text: string): string {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

export class ExerciseService {
  private exercises: Exercise[];

  constructor(customExercises: Exercise[] = []) {
    this.exercises = [...INITIAL_EXERCISES, ...customExercises];
  }

  /**
   * Obtiene todos los ejercicios disponibles
   */
  public getAll(): Exercise[] {
    return this.exercises;
  }

  /**
   * Busca un ejercicio por su ID único
   */
  public getById(id: string): Exercise | undefined {
    return this.exercises.find((ex) => ex.id === id);
  }

  /**
   * Filtra ejercicios por grupo muscular principal o secundario
   */
  public getByMuscleGroup(group: MuscleGroup): Exercise[] {
    return this.exercises.filter(
      (ex) => ex.muscleGroup === group || ex.secondaryMuscles?.includes(group)
    );
  }

  /**
   * Búsqueda por texto con normalización de acentos y tildes
   */
  public search(query: string): Exercise[] {
    const term = normalizeSearchText(query);
    if (!term) return this.exercises;

    return this.exercises.filter((ex) => {
      const name = normalizeSearchText(ex.name);
      const muscle = normalizeSearchText(ex.muscleGroup);
      const equip = normalizeSearchText(ex.equipment);
      return name.includes(term) || muscle.includes(term) || equip.includes(term);
    });
  }

  /**
   * Filtrado multicriterio combinando músculo, equipamiento y texto normalizado
   */
  public filter(options: ExerciseFilterOptions): Exercise[] {
    return this.exercises.filter((ex) => {
      if (
        options.muscleGroup &&
        ex.muscleGroup !== options.muscleGroup &&
        !ex.secondaryMuscles?.includes(options.muscleGroup)
      ) {
        return false;
      }
      if (options.equipment && ex.equipment !== options.equipment) {
        return false;
      }
      if (options.isCustom !== undefined && ex.isCustom !== options.isCustom) {
        return false;
      }
      if (options.searchQuery) {
        const term = normalizeSearchText(options.searchQuery);
        const name = normalizeSearchText(ex.name);
        const muscle = normalizeSearchText(ex.muscleGroup);
        const equip = normalizeSearchText(ex.equipment);
        const matches = name.includes(term) || muscle.includes(term) || equip.includes(term);
        if (!matches) return false;
      }
      return true;
    });
  }
}

// Instancia singleton para uso rápido en frontend o APIs
export const exerciseService = new ExerciseService();
