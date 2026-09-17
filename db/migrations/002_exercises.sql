-- db/migrations/002_exercises.sql

CREATE TABLE IF NOT EXISTS exercises (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    muscle_group VARCHAR(50) NOT NULL,    -- 'pecho', 'espalda', 'cuadriceps', 'gluteos', 'abdomen', etc.
    secondary_muscles TEXT[],             -- Grupos musculares accesorios
    equipment VARCHAR(50) NOT NULL,       -- 'barra', 'mancuernas', 'polea', 'corporal', 'silla'
    media_url TEXT,                       -- Ruta o CDN del GIF demostrativo
    instructions TEXT,
    is_custom BOOLEAN DEFAULT FALSE,
    created_by_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_exercises_muscle ON exercises(muscle_group);
CREATE INDEX IF NOT EXISTS idx_exercises_equipment ON exercises(equipment);
CREATE INDEX IF NOT EXISTS idx_exercises_custom ON exercises(is_custom, created_by_user_id);
