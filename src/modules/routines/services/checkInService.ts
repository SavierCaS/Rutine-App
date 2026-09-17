import { DayOfWeek, Routine, WeeklyCheckInSummary } from '../../../core/types/fitness';

export interface CheckInRecord {
  id: string;
  userId: string;
  routineDayId: string;
  dayOfWeek: DayOfWeek;
  date: string; // YYYY-MM-DD
  weekNumber: number;
  year: number;
  isCompleted: boolean;
  completedAt: string;
}

// Helper puro para obtener YYYY-MM-DD en la zona horaria LOCAL del cliente
function toLocalDateString(d: Date): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export class CheckInService {
  private records: CheckInRecord[] = [];

  /**
   * Obtiene la fecha del lunes correspondiente a la semana de una fecha dada (Inmutable y Local)
   */
  public getMondayOfWeek(d: Date = new Date()): string {
    const date = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const day = date.getDay();
    // En JS: Domingo = 0. Para semana Lunes-Domingo: Lunes diff = 0 si es lunes, Domingo diff = -6
    const diff = day === 0 ? -6 : 1 - day;
    date.setDate(date.getDate() + diff);
    return toLocalDateString(date);
  }

  /**
   * Calcula el número de semana ISO (1 al 53) en base a fecha local
   */
  public getIsoWeekNumber(d: Date = new Date()): { week: number; year: number } {
    const target = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const dayNr = (target.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNr + 3);
    const firstThursday = target.valueOf();
    target.setMonth(0, 1);
    if (target.getDay() !== 4) {
      target.setMonth(0, 1 + ((4 - target.getDay() + 7) % 7));
    }
    const week = 1 + Math.ceil((firstThursday - target.valueOf()) / 604800000);
    return { week, year: new Date(firstThursday).getFullYear() };
  }

  /**
   * Tacha o desmarca un día de rutina asegurando fecha local
   */
  public toggleCheckIn(
    userId: string,
    routineDayId: string,
    dayOfWeek: DayOfWeek,
    targetDate: Date = new Date()
  ): { record: CheckInRecord; isCompleted: boolean } {
    const dateStr = toLocalDateString(targetDate);
    const { week, year } = this.getIsoWeekNumber(targetDate);

    const existingIndex = this.records.findIndex(
      (r) => r.userId === userId && r.routineDayId === routineDayId && r.date === dateStr
    );

    if (existingIndex >= 0) {
      const current = this.records[existingIndex];
      current.isCompleted = !current.isCompleted;
      current.completedAt = new Date().toISOString();
      return { record: current, isCompleted: current.isCompleted };
    }

    const newRecord: CheckInRecord = {
      id: `checkin-${Date.now()}`,
      userId,
      routineDayId,
      dayOfWeek,
      date: dateStr,
      weekNumber: week,
      year,
      isCompleted: true,
      completedAt: new Date().toISOString()
    };

    this.records.push(newRecord);
    return { record: newRecord, isCompleted: true };
  }

  /**
   * Resumen semanal: solo computa días planificados activos
   */
  public getWeeklySummary(
    userId: string,
    routine: Routine,
    referenceDate: Date = new Date()
  ): WeeklyCheckInSummary {
    const { week, year } = this.getIsoWeekNumber(referenceDate);
    const weekStartDate = this.getMondayOfWeek(referenceDate);

    // Conjunto de IDs de días planificados reales (no descanso)
    const validPlannedDayIds = new Set(
      routine.days.filter((d) => !d.isRestDay).map((d) => d.id)
    );
    const totalPlannedDays = validPlannedDayIds.size;

    // Filtrar solo los check-ins de días que pertenezcan a la rutina activa
    const completedRecords = this.records.filter(
      (r) =>
        r.userId === userId &&
        r.weekNumber === week &&
        r.year === year &&
        r.isCompleted &&
        validPlannedDayIds.has(r.routineDayId)
    );

    const completedDays: DayOfWeek[] = Array.from(
      new Set(completedRecords.map((r) => r.dayOfWeek))
    );

    const compliancePercentage =
      totalPlannedDays > 0
        ? Math.min(100, Math.round((completedDays.length / totalPlannedDays) * 100))
        : 0;

    return {
      weekStartDate,
      completedDays,
      totalPlannedDays,
      compliancePercentage
    };
  }

  /**
   * Verifica si un día específico de la rutina ya está tachado hoy en hora local
   */
  public isDayCompletedToday(userId: string, routineDayId: string): boolean {
    const todayStr = toLocalDateString(new Date());
    return this.records.some(
      (r) => r.userId === userId && r.routineDayId === routineDayId && r.date === todayStr && r.isCompleted
    );
  }
}

export const checkInService = new CheckInService();
