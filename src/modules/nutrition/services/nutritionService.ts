import { DailyNutritionLog, MacroTargets, MealCategory, MealFoodItem, MealLog } from '../../../core/types/fitness';

function toLocalDateString(d: Date = new Date()): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export class NutritionService {
  private userTargets: Map<string, MacroTargets> = new Map();
  private dailyLogs: Map<string, DailyNutritionLog> = new Map();

  constructor() {
    this.userTargets.set('default', {
      calories: 2200,
      proteinG: 140,
      carbsG: 240,
      fatsG: 65,
      waterMl: 2500
    });
  }

  public getUserTargets(userId: string): MacroTargets {
    return this.userTargets.get(userId) || this.userTargets.get('default')!;
  }

  public setUserTargets(userId: string, targets: Partial<MacroTargets>): MacroTargets {
    const current = this.getUserTargets(userId);
    const updated: MacroTargets = {
      calories: Math.max(0, Number(targets.calories ?? current.calories)),
      proteinG: Math.max(0, Number(targets.proteinG ?? current.proteinG)),
      carbsG: Math.max(0, Number(targets.carbsG ?? current.carbsG)),
      fatsG: Math.max(0, Number(targets.fatsG ?? current.fatsG)),
      waterMl: Math.max(0, Number(targets.waterMl ?? current.waterMl))
    };
    this.userTargets.set(userId, updated);
    return updated;
  }

  public getOrCreateDailyLog(userId: string, targetDate?: string): DailyNutritionLog {
    const date = targetDate || toLocalDateString();
    const key = `${userId}_${date}`;

    if (!this.dailyLogs.has(key)) {
      const targets = this.getUserTargets(userId);
      const newLog: DailyNutritionLog = {
        id: `nutrilog-${Date.now()}`,
        userId,
        date,
        targets,
        waterConsumedMl: 0,
        meals: [
          { id: `meal-b-${Date.now()}`, category: 'breakfast', loggedAt: new Date().toISOString(), items: [] },
          { id: `meal-l-${Date.now()}`, category: 'lunch', loggedAt: new Date().toISOString(), items: [] },
          { id: `meal-d-${Date.now()}`, category: 'dinner', loggedAt: new Date().toISOString(), items: [] },
          { id: `meal-s-${Date.now()}`, category: 'snack', loggedAt: new Date().toISOString(), items: [] }
        ],
        totalConsumed: {
          calories: 0,
          proteinG: 0,
          carbsG: 0,
          fatsG: 0
        }
      };
      this.dailyLogs.set(key, newLog);
    }

    return this.dailyLogs.get(key)!;
  }

  /**
   * Registra un alimento sanitizando números y auto-calculando calorías si faltan
   */
  public logFoodItem(
    userId: string,
    category: MealCategory,
    food: Omit<MealFoodItem, 'id'>,
    targetDate?: string
  ): MealFoodItem {
    const normalizedCategory = (category.toLowerCase() as MealCategory);
    const log = this.getOrCreateDailyLog(userId, targetDate);
    let mealGroup = log.meals.find((m) => m.category === normalizedCategory);

    if (!mealGroup) {
      mealGroup = {
        id: `meal-${normalizedCategory}-${Date.now()}`,
        category: normalizedCategory,
        loggedAt: new Date().toISOString(),
        items: []
      };
      log.meals.push(mealGroup);
    }

    const proteinG = Math.max(0, Number(food.proteinG) || 0);
    const carbsG = Math.max(0, Number(food.carbsG) || 0);
    const fatsG = Math.max(0, Number(food.fatsG) || 0);
    const servingAmount = Math.max(0, Number(food.servingAmount) || 0);

    // Auto-cálculo de calorías si vienen en 0 o vacías (Regla 4-4-9)
    let calories = Math.max(0, Number(food.calories) || 0);
    if (calories === 0 && (proteinG > 0 || carbsG > 0 || fatsG > 0)) {
      calories = Math.round(proteinG * 4 + carbsG * 4 + fatsG * 9);
    }

    const newItem: MealFoodItem = {
      id: `food-${Date.now()}-${mealGroup.items.length + 1}`,
      name: food.name.trim() || 'Alimento sin nombre',
      servingAmount,
      servingUnit: food.servingUnit || 'g',
      calories,
      proteinG,
      carbsG,
      fatsG
    };

    mealGroup.items.push(newItem);
    this.recalculateTotals(log);
    return newItem;
  }

  public removeFoodItem(userId: string, itemId: string, targetDate?: string): boolean {
    const log = this.getOrCreateDailyLog(userId, targetDate);
    let found = false;

    log.meals.forEach((meal) => {
      const index = meal.items.findIndex((i) => i.id === itemId);
      if (index !== -1) {
        meal.items.splice(index, 1);
        found = true;
      }
    });

    if (found) {
      this.recalculateTotals(log);
    }
    return found;
  }

  public addWater(userId: string, amountMl: number, targetDate?: string): number {
    const log = this.getOrCreateDailyLog(userId, targetDate);
    const delta = Number(amountMl) || 0;
    log.waterConsumedMl = Math.max(0, log.waterConsumedMl + delta);
    return log.waterConsumedMl;
  }

  public setWater(userId: string, totalMl: number, targetDate?: string): number {
    const log = this.getOrCreateDailyLog(userId, targetDate);
    log.waterConsumedMl = Math.max(0, Number(totalMl) || 0);
    return log.waterConsumedMl;
  }

  private recalculateTotals(log: DailyNutritionLog): void {
    let calories = 0;
    let proteinG = 0;
    let carbsG = 0;
    let fatsG = 0;

    log.meals.forEach((meal) => {
      meal.items.forEach((item) => {
        calories += Number(item.calories) || 0;
        proteinG += Number(item.proteinG) || 0;
        carbsG += Number(item.carbsG) || 0;
        fatsG += Number(item.fatsG) || 0;
      });
    });

    log.totalConsumed = {
      calories: Math.round(calories),
      proteinG: Math.round(proteinG * 10) / 10,
      carbsG: Math.round(carbsG * 10) / 10,
      fatsG: Math.round(fatsG * 10) / 10
    };
  }
}

export const nutritionService = new NutritionService();
