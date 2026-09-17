import { Routine } from '../../../core/types/fitness';

export const INITIAL_ROUTINES: Routine[] = [
  {
    id: 'routine-starter-4days',
    userId: 'global-template',
    name: 'Kynap Acondicionamiento & Fuerza (4 Días)',
    description: 'Programa semanal equilibrado combinando tren inferior, torso y core.',
    isActive: true,
    createdAt: '2026-09-01T00:00:00.000Z',
    updatedAt: '2026-09-01T00:00:00.000Z',
    days: [
      {
        id: 'day-mon',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'monday',
        name: 'Lunes - Pierna & Glúteos',
        orderIndex: 1,
        isRestDay: false,
        exercises: [
          {
            exerciseId: 'ex-squats',
            orderIndex: 1,
            targetSets: 4,
            targetReps: '12-15',
            targetRpe: 8.0,
            restSeconds: 90
          },
          {
            exerciseId: 'ex-butt-bridge',
            orderIndex: 2,
            targetSets: 3,
            targetReps: '15',
            targetRpe: 7.5,
            restSeconds: 60
          },
          {
            exerciseId: 'ex-estocada-atras',
            orderIndex: 3,
            targetSets: 3,
            targetReps: '10 por pierna',
            targetRpe: 8.0,
            restSeconds: 60
          }
        ]
      },
      {
        id: 'day-tue',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'tuesday',
        name: 'Martes - Core & Cardio Intenso',
        orderIndex: 2,
        isRestDay: false,
        exercises: [
          {
            exerciseId: 'ex-burpees',
            orderIndex: 1,
            targetSets: 4,
            targetReps: '12',
            targetRpe: 8.5,
            restSeconds: 60
          },
          {
            exerciseId: 'ex-crunch-bicicleta',
            orderIndex: 2,
            targetSets: 3,
            targetReps: '20',
            targetRpe: 8.0,
            restSeconds: 45
          },
          {
            exerciseId: 'ex-plancha-tradicional',
            orderIndex: 3,
            targetSets: 3,
            targetReps: '45 seg',
            targetRpe: 8.0,
            restSeconds: 60
          }
        ]
      },
      {
        id: 'day-wed',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'wednesday',
        name: 'Miércoles - Descanso Activo',
        orderIndex: 3,
        isRestDay: true,
        exercises: []
      },
      {
        id: 'day-thu',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'thursday',
        name: 'Jueves - Torso & Brazos',
        orderIndex: 4,
        isRestDay: false,
        exercises: [
          {
            exerciseId: 'ex-flexiones-inclinadas',
            orderIndex: 1,
            targetSets: 4,
            targetReps: '10-12',
            targetRpe: 8.0,
            restSeconds: 90
          },
          {
            exerciseId: 'ex-triceps-silla',
            orderIndex: 2,
            targetSets: 3,
            targetReps: '12',
            targetRpe: 8.0,
            restSeconds: 60
          },
          {
            exerciseId: 'ex-mountain-climbers',
            orderIndex: 3,
            targetSets: 3,
            targetReps: '30 seg',
            targetRpe: 8.5,
            restSeconds: 45
          }
        ]
      },
      {
        id: 'day-fri',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'friday',
        name: 'Viernes - Full Body Funcional',
        orderIndex: 5,
        isRestDay: false,
        exercises: [
          {
            exerciseId: 'ex-zancada-frontal',
            orderIndex: 1,
            targetSets: 3,
            targetReps: '12 por pierna',
            targetRpe: 8.0,
            restSeconds: 60
          },
          {
            exerciseId: 'ex-step-silla',
            orderIndex: 2,
            targetSets: 3,
            targetReps: '10 por pierna',
            targetRpe: 7.5,
            restSeconds: 60
          },
          {
            exerciseId: 'ex-twist-ruso',
            orderIndex: 3,
            targetSets: 3,
            targetReps: '20 totales',
            targetRpe: 8.0,
            restSeconds: 45
          }
        ]
      },
      {
        id: 'day-sat',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'saturday',
        name: 'Sábado - Recuperación',
        orderIndex: 6,
        isRestDay: true,
        exercises: []
      },
      {
        id: 'day-sun',
        routineId: 'routine-starter-4days',
        dayOfWeek: 'sunday',
        name: 'Domingo - Recuperación',
        orderIndex: 7,
        isRestDay: true,
        exercises: []
      }
    ]
  }
];
