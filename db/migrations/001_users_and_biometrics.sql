-- db/migrations/001_users_and_biometrics.sql
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS user_biometrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    weight_kg NUMERIC(5,2) NOT NULL,
    height_cm NUMERIC(5,2) NOT NULL,
    age INT,
    gender VARCHAR(20),
    activity_level VARCHAR(50) DEFAULT 'moderado', -- 'sedentario', 'ligero', 'moderado', 'muy_activo'
    fitness_goal VARCHAR(50) DEFAULT 'ganar_musculo', -- 'perder_peso', 'ganar_musculo', 'mantenimiento'
    water_target_ml INT DEFAULT 2500,
    recorded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_user_biometrics_user ON user_biometrics(user_id, recorded_at DESC);
