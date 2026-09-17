-- db/migrations/003_routines.sql

CREATE TABLE IF NOT EXISTS routines (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_routines_user_active ON routines(user_id, is_active);

CREATE TABLE IF NOT EXISTS routine_days (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    routine_id UUID NOT NULL REFERENCES routines(id) ON DELETE CASCADE,
    day_name VARCHAR(50) NOT NULL,        -- 'Lunes', 'Martes', 'Día 1 - Pierna'
    day_order INT NOT NULL,               -- 1 al 7
    is_rest_day BOOLEAN DEFAULT FALSE,
    suggested_time TIME
);

CREATE INDEX IF NOT EXISTS idx_routine_days_routine ON routine_days(routine_id, day_order);

CREATE TABLE IF NOT EXISTS routine_exercises (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    routine_day_id UUID NOT NULL REFERENCES routine_days(id) ON DELETE CASCADE,
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE RESTRICT,
    order_index INT NOT NULL,
    target_sets INT NOT NULL DEFAULT 3,
    target_reps VARCHAR(20) NOT NULL DEFAULT '10-12',
    target_rpe NUMERIC(3,1),              -- Ej: 8.0
    rest_seconds INT DEFAULT 90
);

CREATE INDEX IF NOT EXISTS idx_routine_exercises_day ON routine_exercises(routine_day_id, order_index);
