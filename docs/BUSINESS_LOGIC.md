# Lógica de Negocio y Modelos Fisiológicos - Kynap Platform

## 1. Algoritmos Nutricionales y Energéticos

### 1.1 Tasa Metabólica Basal (BMR) - Fórmula de Mifflin-St Jeor
El gasto energético en reposo se calcula mediante la ecuación de Mifflin-St Jeor, el estándar clínico más preciso según la literatura de nutrición deportiva:

$$
\text{BMR}_{\text{Hombre}} = (10 \times \text{peso}_{\text{kg}}) + (6.25 \times \text{altura}_{\text{cm}}) - (5 \times \text{edad}) + 5
$$

$$
\text{BMR}_{\text{Mujer}} = (10 \times \text{peso}_{\text{kg}}) + (6.25 \times \text{altura}_{\text{cm}}) - (5 \times \text{edad}) - 161
$$

### 1.2 Gasto Energético Total Diario (TDEE)
Se multiplica el BMR por el factor de actividad física del atleta:
| Nivel de Actividad | Factor Multiplicador ($f$) |
|---|---|
| Sedentario (poca actividad) | $1.200$ |
| Ligero (ejercicio 1-3 días/sem) | $1.375$ |
| Moderado (ejercicio 3-5 días/sem) | $1.550$ |
| Activo (entrenamiento duro 6-7 días/sem) | $1.725$ |
| Intenso (doble sesión diaria / atleta élite) | $1.900$ |

$$
\text{TDEE} = \text{BMR} \times f
$$

### 1.3 Ajuste Calórico según Objetivo Deportivo
1. **Ganar Músculo (Superávit Calórico Controlado)**:
   $$\text{Calorías Objetivo} = \text{TDEE} + 350\text{ kcal}$$
   - Proteína: $2.0\text{ g/kg de peso corporal}$
   - Grasas: $25\%\text{ del total calórico}$
   - Carbohidratos: Balance calórico restante dividido entre $4\text{ kcal/g}$.
2. **Perder Grasa / Definición (Déficit Calórico Sostenible)**:
   $$\text{Calorías Objetivo} = \max(1300, \text{TDEE} - 450\text{ kcal})$$
   - Proteína: $2.2\text{ g/kg de peso corporal}$ (para preservación de masa magra).
   - Grasas: $25\%\text{ del total calórico}$.
3. **Mantenimiento**:
   $$\text{Calorías Objetivo} = \text{TDEE}$$
   - Proteína: $1.8\text{ g/kg}$
   - Grasas: $28\%\text{ del total calórico}$.

### 1.4 Estimación de Hidratación Óptima
El requerimiento base de agua se calcula en función de la masa corporal con un rango de seguridad:
$$\text{Agua (ml)} = \max(2000, \min(4500, \text{peso}_{\text{kg}} \times 35))$$

---

## 2. Modelos de Fuerza y Entrenamiento

### 2.1 Estimación de 1RM (Fórmula de Epley)
Para calcular la repetición máxima estimada (1RM) a partir de series submáximas ejecutadas en el módulo de workout:

$$
1\text{RM} = \text{Peso} \times \left(1 + \frac{\text{Repeticiones}}{30}\right)
$$

- Si $\text{Repeticiones} = 1 \implies 1\text{RM} = \text{Peso levantado}$.

### 2.2 Tonelaje de Sesión de Entrenamiento (Volumen de Carga)
El volumen total levantado por sesión se acumula atómicamente:

$$
\text{Tonelaje Total (kg)} = \sum_{i=1}^{n} (\text{peso}_i \times \text{repeticiones}_i)
$$

---

## 3. Reglas de Negocio de la Plataforma

### 3.1 Bloqueo Estricto de Días en la Rutina Semanal (Requerimiento 2)
- **Principio**: La adherencia al plan exige integridad temporal. Un atleta no puede marcar días futuros por adelantado ni modificar arbitrariamente días anteriores.
- **Implementación**:
  - El backend resuelve el día actual del servidor mediante `getCurrentServerDayKey()` (`lunes` a `domingo`).
  - Si una petición `POST` de check-in recibe un `day_id` diferente al día actual, el servidor responde con **HTTP 403 Forbidden** y detalle explicativo:
    ```json
    {
      "success": false,
      "message": "Acción no permitida: Hoy es Jueves... El día Martes permanece en modo solo lectura.",
      "errors": { "isBlocked": true, "currentServerDayKey": "jueves", "attemptedDay": "martes" }
    }
    ```

### 3.2 Política de Actualización Físico-Biométrica (Requerimiento 4)
- **Regla del Ciclo de Rutina**: La actualización de peso y medidas corporales se restringe al cumplimiento del 100% de los días planificados de la rutina semanal (ej. 4 de 4 días). Esto previene la dismorfia y las correcciones impulsivas diarias por retención de agua.
- **Ventana de Corrección de 24 Horas**: Si el atleta ingresó un dato erróneo por error tipográfico (ej. escribió 750 kg en vez de 75 kg), el sistema le otorga una ventana de **24 horas exactas** desde la creación del registro para editarlo libremente (`mode: 'correction'`). Superado dicho margen, el perfil queda bloqueado hasta completar el ciclo.

### 3.3 Motor de Rachas (Gamificación y Adherencia)
- **Evaluación**: La racha se incrementa sumando días consecutivos con actividad física (check-in de rutina) o nutricional (al menos una comida completada).
- **Margen de Gracia (Grace Period)**: Si el usuario completó una actividad ayer, su racha se mantiene vigente durante el transcurso del día de hoy hasta la medianoche (`23:59:59`).
- **Niveles de Medallas**:
  - $\ge 1\text{ día}$: Fuego Inicial 🔥 (Nivel 1)
  - $\ge 3\text{ días}$: Bronce Constante 🥉 (Nivel 2)
  - $\ge 7\text{ días}$: Plata Disciplinada 🥈 (Nivel 3)
  - $\ge 14\text{ días}$: Oro Imparable 🥇 (Nivel 4)
  - $\ge 30\text{ días}$: Leyenda Titán 🏆 (Nivel 5)

