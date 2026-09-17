import { Exercise } from '../../../core/types/fitness';

export const INITIAL_EXERCISES: Exercise[] = [
  {
    id: 'ex-squats',
    name: 'Sentadillas',
    muscleGroup: 'quads',
    secondaryMuscles: ['glutes', 'calves'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Squats.gif',
    instructions: [
      'Pies al ancho de los hombros con las puntas ligeramente hacia afuera.',
      'Desciende flexionando rodillas y cadera manteniendo el pecho erguido.',
      'Baja hasta romper el paralelo y empuja con fuerza contra el suelo para volver a subir.'
    ],
    isCustom: false
  },
  {
    id: 'ex-butt-bridge',
    name: 'Puente de Glúteos',
    muscleGroup: 'glutes',
    secondaryMuscles: ['hamstrings'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Butt bridge.gif',
    instructions: [
      'Acuéstate boca arriba con las rodillas flexionadas y los pies firmes en el suelo.',
      'Eleva la pelvis contrayendo fuertemente los glúteos en el punto más alto.',
      'Mantén 1 segundo la contracción y desciende de forma controlada.'
    ],
    isCustom: false
  },
  {
    id: 'ex-crunch-abdominal',
    name: 'Crunch Abdominal',
    muscleGroup: 'abs',
    secondaryMuscles: [],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Crunch Abdominales.gif',
    instructions: [
      'Tumbado boca arriba con rodillas flexionadas y manos tras la cabeza sin tirar del cuello.',
      'Eleva los hombros y parte superior de la espalda contrayendo el abdomen.',
      'Expulsa el aire arriba y baja lentamente.'
    ],
    isCustom: false
  },
  {
    id: 'ex-crunch-bicicleta',
    name: 'Crunch de Bicicleta',
    muscleGroup: 'abs',
    secondaryMuscles: [],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Crunch de bicicleta.gif',
    instructions: [
      'Tumbado boca arriba, rota el torso llevando el codo hacia la rodilla contraria.',
      'Extiende la pierna opuesta sin que toque el suelo.',
      'Alterna de forma fluida y controlada.'
    ],
    isCustom: false
  },
  {
    id: 'ex-crunch-inverso',
    name: 'Crunch Inverso',
    muscleGroup: 'abs',
    secondaryMuscles: [],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Crunch inverso.gif',
    instructions: [
      'Boca arriba con las piernas elevadas a 90 grados.',
      'Contrae el abdomen bajo despegando levemente la cadera del suelo hacia el pecho.',
      'Controla el descenso sin impulsos bruscos.'
    ],
    isCustom: false
  },
  {
    id: 'ex-burpees',
    name: 'Burpees',
    muscleGroup: 'cardio',
    secondaryMuscles: ['chest', 'quads', 'shoulders'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Burpess.gif',
    instructions: [
      'Desde posición de pie, agáchate y apoya las manos en el suelo.',
      'Lanza los pies atrás a posición de plancha, realiza una flexión rozando el pecho.',
      'Recupera la posición de cuclillas con un salto y elévate dando una palmada sobre la cabeza.'
    ],
    isCustom: false
  },
  {
    id: 'ex-elevacion-rodillas',
    name: 'Elevación de Rodillas (High Knees)',
    muscleGroup: 'cardio',
    secondaryMuscles: ['abs'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Elevacion de rodillas.gif',
    instructions: [
      'Corre en el sitio elevando las rodillas a la altura de la cadera.',
      'Acompaña el ritmo con el braceo dinámico y mantén el torso erguido.'
    ],
    isCustom: false
  },
  {
    id: 'ex-patada-gluteos',
    name: 'Elevación de Piernas para Glúteos',
    muscleGroup: 'glutes',
    secondaryMuscles: ['hamstrings'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Elevaciones de piernas para gluteos.gif',
    instructions: [
      'En posición de cuadrupedia (cuatro apoyos) sobre una colchoneta.',
      'Eleva una pierna hacia atrás y arriba manteniendo la flexión de rodilla a 90 grados.',
      'Aprieta el glúteo arriba y desciende sin tocar el suelo.'
    ],
    isCustom: false
  },
  {
    id: 'ex-mountain-climbers',
    name: 'Mountain Climbers (Escaladores)',
    muscleGroup: 'abs',
    secondaryMuscles: ['cardio', 'shoulders'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Escalada de montaña.gif',
    instructions: [
      'Comienza en posición de plancha alta con brazos estirados.',
      'Lleva una rodilla hacia el pecho y regresa mientras alternas velozmente con la otra.',
      'Mantén la cadera baja y alineada con los hombros.'
    ],
    isCustom: false
  },
  {
    id: 'ex-estocada-atras',
    name: 'Estocada Hacia Atrás',
    muscleGroup: 'quads',
    secondaryMuscles: ['glutes', 'hamstrings'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Estocada hacia atras.gif',
    instructions: [
      'Da un paso amplio hacia atrás flexionando ambas rodillas a 90 grados.',
      'La rodilla trasera roza casi el suelo sin golpearlo.',
      'Empuja con el talón de la pierna delantera para regresar a la posición inicial.'
    ],
    isCustom: false
  },
  {
    id: 'ex-flexiones-inclinadas',
    name: 'Flexiones con Inclinación',
    muscleGroup: 'chest',
    secondaryMuscles: ['triceps', 'shoulders'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Flexiones con inclinación.gif',
    instructions: [
      'Apoya las manos en un banco, silla o escalón por encima de la altura de los pies.',
      'Baja el pecho hacia el soporte manteniendo el core activado.',
      'Empuja hasta extender los brazos.'
    ],
    isCustom: false
  },
  {
    id: 'ex-step-silla',
    name: 'Step en Silla',
    muscleGroup: 'quads',
    secondaryMuscles: ['glutes'],
    equipment: 'other',
    mediaUrl: '/assets/exercises/Step en silla.gif',
    instructions: [
      'Colócate frente a una silla o cajón estable.',
      'Sube apoyando toda la planta de un pie y empuja para elevar el cuerpo completo.',
      'Baja de forma controlada y repite alternando pierna.'
    ],
    isCustom: false
  },
  {
    id: 'ex-plancha-tradicional',
    name: 'Plancha Abdominal',
    muscleGroup: 'abs',
    secondaryMuscles: ['shoulders', 'glutes'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Tablon.jpg',
    instructions: [
      'Apoya los antebrazos y las puntas de los pies en el suelo.',
      'Mantén el cuerpo en línea recta perfecta desde la cabeza hasta los talones.',
      'Contrae glúteos y abdomen durante todo el tiempo prescrito.'
    ],
    isCustom: false
  },
  {
    id: 'ex-triceps-silla',
    name: 'Fondos de Tríceps en Silla',
    muscleGroup: 'triceps',
    secondaryMuscles: ['chest', 'shoulders'],
    equipment: 'other',
    mediaUrl: '/assets/exercises/Triceps en silla.gif',
    instructions: [
      'De espaldas a una silla, apoya las palmas en el borde con piernas adelantadas.',
      'Desciende flexionando los codos hacia atrás hasta formar un ángulo de 90 grados.',
      'Extiende con fuerza usando los tríceps para subir.'
    ],
    isCustom: false
  },
  {
    id: 'ex-twist-ruso',
    name: 'Twist Ruso',
    muscleGroup: 'abs',
    secondaryMuscles: [],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Twist Ruso.gif',
    instructions: [
      'Sentado con rodillas flexionadas, reclina el torso 45 grados hacia atrás.',
      'Gira el torso de lado a lado tocando el suelo a cada costado.',
      'Mantén la tensión continua en la pared abdominal.'
    ],
    isCustom: false
  },
  {
    id: 'ex-zancada-frontal',
    name: 'Zancada Frontal',
    muscleGroup: 'quads',
    secondaryMuscles: ['glutes'],
    equipment: 'bodyweight',
    mediaUrl: '/assets/exercises/Zancada Frontal.gif',
    instructions: [
      'Da un paso firme al frente bajando la cadera.',
      'Ambas rodillas deben quedar en ángulos de 90 grados al final del descenso.',
      'Empuja con la pierna adelantada para volver a la posición de inicio.'
    ],
    isCustom: false
  }
];
