-- db/seed.sql
-- Población inicial con los ejercicios y assets GIF reales del workspace

INSERT INTO exercises (name, slug, muscle_group, secondary_muscles, equipment, media_url, instructions) VALUES
('Sentadillas', 'squats', 'cuadriceps', ARRAY['gluteos'], 'corporal', '/assets/exercises/Squats.gif', 'Pies al ancho de hombros, desciende manteniendo la espalda recta.'),
('Puente de Glúteos', 'butt-bridge', 'gluteos', ARRAY['isquiotibiales'], 'corporal', '/assets/exercises/Butt bridge.gif', 'Eleva la pelvis contrayendo glúteos en el punto más alto.'),
('Crunch Abdominal', 'crunch-abdominales', 'abdomen', ARRAY[]::TEXT[], 'corporal', '/assets/exercises/Crunch Abdominales.gif', 'Flexión de tronco controlada sin tirar del cuello.'),
('Crunch de Bicicleta', 'crunch-bicicleta', 'abdomen', ARRAY['oblicuos'], 'corporal', '/assets/exercises/Crunch de bicicleta.gif', 'Alterna codo hacia la rodilla opuesta manteniendo tensión.'),
('Crunch Inverso', 'crunch-inverso', 'abdomen', ARRAY[]::TEXT[], 'corporal', '/assets/exercises/Crunch inverso.gif', 'Lleva las rodillas hacia el pecho elevando levemente la cadera.'),
('Burpees', 'burpees', 'cardio', ARRAY['pecho', 'cuadriceps'], 'corporal', '/assets/exercises/Burpess.gif', 'Cuerpo al suelo, flexión y salto explosivo vertical.'),
('Elevación de Rodillas', 'elevacion-rodillas', 'cardio', ARRAY['abdomen'], 'corporal', '/assets/exercises/Elevacion de rodillas.gif', 'Carrera en el sitio llevando rodillas a la altura de la cadera.'),
('Elevación de Piernas para Glúteos', 'elevacion-piernas-gluteos', 'gluteos', ARRAY['isquiotibiales'], 'corporal', '/assets/exercises/Elevaciones de piernas para gluteos.gif', 'Patada de glúteo controlada en cuatro apoyos.'),
('Mountain Climbers', 'mountain-climbers', 'abdomen', ARRAY['cardio', 'hombros'], 'corporal', '/assets/exercises/Escalada de montaña.gif', 'Posición de plancha alternando rodillas al pecho con ritmo.'),
('Estocada Hacia Atrás', 'estocada-hacia-atras', 'cuadriceps', ARRAY['gluteos'], 'corporal', '/assets/exercises/Estocada hacia atras.gif', 'Paso hacia atrás descendiendo rodilla a 90 grados.'),
('Flexiones con Inclinación', 'flexiones-inclinadas', 'pecho', ARRAY['triceps', 'hombros'], 'corporal', '/assets/exercises/Flexiones con inclinación.gif', 'Manos apoyadas en superficie elevada manteniendo línea recta corporal.'),
('Step en Silla', 'step-silla', 'cuadriceps', ARRAY['gluteos'], 'silla', '/assets/exercises/Step en silla.gif', 'Subida alterna a silla o banco con control en la bajada.'),
('Plancha Tradicional', 'plancha-tradicional', 'abdomen', ARRAY['hombros', 'gluteos'], 'corporal', '/assets/exercises/Tablon.jpg', 'Apoyo en antebrazos manteniendo cuerpo recto y abdomen contraído.'),
('Fondos de Tríceps en Silla', 'triceps-en-silla', 'triceps', ARRAY['pecho'], 'silla', '/assets/exercises/Triceps en silla.gif', 'Desciende flexionando codos a 90 grados junto al cuerpo.'),
('Twist Ruso', 'twist-ruso', 'abdomen', ARRAY['oblicuos'], 'corporal', '/assets/exercises/Twist Ruso.gif', 'Torso inclinado a 45 grados rotando tronco a ambos lados.'),
('Zancada Frontal', 'zancada-frontal', 'cuadriceps', ARRAY['gluteos'], 'corporal', '/assets/exercises/Zancada Frontal.gif', 'Paso largo al frente flexionando ambas piernas a 90 grados.')
ON CONFLICT (slug) DO NOTHING;
