-- db/migrations/004_workouts_and_sets.sql

-- Control de días de rutina tachados por fecha/semana
CREATE TABLE IF NOT EXISTS routine_check_ins (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    routine_day_id UUID NOT NULL REFERENCES routine_days(id) ON DELETE CASCADE,
    check_in_date DATE NOT NULL,
    week_number INT NOT NULL,             -- Semana ISO (1-53)
    year INT NOT NULL,                    -- Año calendario (ej: 2026)
    is_completed BOOLEAN DEFAULT TRUE,
    completed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_user_routine_day_date UNIQUE (user_id, routine_day_id, check_in_date)
);

CREATE INDEX IF NOT EXISTS idx_check_ins_user_week ON routine_check_ins(user_id, year, week_number);
CREATE INDEX IF NOT EXISTS idx_check_ins_date ON routine_check_ins(check_in_date);

-- Sesión de entrenamiento en vivo
CREATE TABLE IF NOT EXISTS workout_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    routine_day_id UUID REFERENCES routine_days(id) ON DELETE SET NULL,
    started_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    completed_at TIMESTAMP WITH TIME ZONE,
    status VARCHAR(20) DEFAULT 'in_progress', -- 'in_progress', 'completed', 'cancelled'
    total_volume_kg NUMERIC(10,2) DEFAULT 0,  -- Sumatoria de (peso * reps)
    notes TEXT
);

CREATE INDEX IF NOT EXISTS idx_workout_logs_user ON workout_logs(user_id, started_at DESC);

-- Historial granular de series (Sets)
CREATE TABLE IF NOT EXISTS workout_sets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    workout_log_id UUID NOT NULL REFERENCES workout_logs(id) ON DELETE CASCADE,
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE RESTRICT,
    set_number INT NOT NULL,
    weight_kg NUMERIC(6,2) NOT NULL DEFAULT 0,
    reps INT NOT NULL DEFAULT 0,
    rpe NUMERIC(3,1),                     -- Esfuerzo percibido (1-10)
    rir INT,                              -- Repeticiones en reserva (0-4+)
    is_warmup BOOLEAN DEFAULT FALSE,
    is_completed BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_workout_sets_log ON workout_sets(workout_log_id, exercise_id);
CREATE INDEX IF NOT EXISTS idx_workout_sets_pr ON workout_sets(exercise_id, weight_kg DESC, reps DESC);
