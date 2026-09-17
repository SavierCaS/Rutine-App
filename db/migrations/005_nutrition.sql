-- db/migrations/005_nutrition.sql

CREATE TABLE IF NOT EXISTS nutrition_goals (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    daily_calories INT NOT NULL,
    protein_g INT NOT NULL,
    carbs_g INT NOT NULL,
    fats_g INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_nutrition_goals_user ON nutrition_goals(user_id, is_active);

CREATE TABLE IF NOT EXISTS nutrition_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    log_date DATE NOT NULL DEFAULT CURRENT_DATE,
    water_ml INT DEFAULT 0,
    notes TEXT,
    CONSTRAINT uq_user_nutrition_date UNIQUE (user_id, log_date)
);

CREATE INDEX IF NOT EXISTS idx_nutrition_logs_date ON nutrition_logs(user_id, log_date);

CREATE TABLE IF NOT EXISTS meal_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nutrition_log_id UUID NOT NULL REFERENCES nutrition_logs(id) ON DELETE CASCADE,
    meal_type VARCHAR(20) NOT NULL,       -- 'desayuno', 'almuerzo', 'cena', 'snack'
    food_name VARCHAR(150) NOT NULL,
    portion_amount NUMERIC(6,2),
    portion_unit VARCHAR(30) DEFAULT 'g', -- 'g', 'ml', 'unidad'
    calories INT NOT NULL,
    protein_g NUMERIC(5,1) DEFAULT 0,
    carbs_g NUMERIC(5,1) DEFAULT 0,
    fats_g NUMERIC(5,1) DEFAULT 0,
    logged_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_meal_items_log ON meal_items(nutrition_log_id, meal_type);
