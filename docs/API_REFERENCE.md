# Referencia de la API REST - Kynap Fitness Platform

## 1. Estándares Globales de la API

### Formato de Intercambio
- **Content-Type**: `application/json; charset=UTF-8`
- **Métodos Soportados**: `GET`, `POST`, `OPTIONS`

### Autenticación y Autorización
La API implementa un modelo de autenticación dual:
1. **Bearer Token (Móvil / PWA / Headless)**:
   - Cabecera: `Authorization: Bearer <token_hmac>`
2. **Sesión Web Tradicional**:
   - Cookie segura: `PHPSESSID` (HttpOnly, SameSite=Lax).

### Esquema Estándar de Respuestas

#### Respuesta Exitosa (HTTP 200 / 201)
```json
{
  "success": true,
  "message": "Operación completada con éxito",
  "data": { ... },
  "timestamp": "2026-10-29T20:00:00+00:00"
}
```

#### Respuesta de Error (HTTP 400 / 401 / 403 / 422 / 500)
```json
{
  "success": false,
  "message": "Descripción clara del motivo del error",
  "errors": { ... },
  "timestamp": "2026-10-29T20:00:00+00:00"
}
```

---

## 2. Endpoints de Autenticación (`/controllers/api_auth.php`)

### 2.1 Iniciar Sesión (Login)
- **Método**: `POST`
- **URL**: `/controllers/api_auth.php?action=login`
- **Cuerpo (JSON)**:
  ```json
  {
    "email": "atleta@kynap.com",
    "password": "miPasswordSeguro123"
  }
  ```
- **Códigos HTTP**:
  - `200 OK`: Inicio de sesión exitoso. Devuelve token HMAC firmado, objeto `user` y `biometrics`.
  - `401 Unauthorized`: Credenciales erróneas (mensaje genérico que previene la enumeración de usuarios).
  - `422 Unprocessable Entity`: Campos requeridos vacíos o email inválido.

### 2.2 Registro de Usuario con Biometría
- **Método**: `POST`
- **URL**: `/controllers/api_auth.php?action=register`
- **Cuerpo (JSON)**:
  ```json
  {
    "nombre": "Carlos Mendoza",
    "email": "carlos@kynap.com",
    "password": "PasswordFuerte2026!",
    "confirm_password": "PasswordFuerte2026!",
    "edad": 28,
    "peso": 80.5,
    "altura": 178.0,
    "sexo": "masculino",
    "objetivo": "ganar_musculo",
    "nivel_actividad": "moderado"
  }
  ```
- **Códigos HTTP**:
  - `201 Created`: Usuario creado exitosamente con metas nutricionales automáticas y rutina asignada.
  - `409 Conflict`: Correo electrónico ya registrado.
  - `422 Unprocessable Entity`: Validación de longitud de contraseña o parámetros biométricos fuera de rango.

### 2.3 Verificar Sesión / Token
- **Método**: `GET`
- **URL**: `/controllers/api_auth.php?action=verify`
- **Cabeceras**: `Authorization: Bearer <token>`
- **Códigos HTTP**:
  - `200 OK`: Estado de autenticación (`authenticated: true/false`), perfil y datos actualizados.

### 2.4 Cerrar Sesión (Logout)
- **Método**: `POST`
- **URL**: `/controllers/api_auth.php?action=logout`
- **Códigos HTTP**:
  - `200 OK`: Sesión destruida limpiamente.

---

## 3. Endpoints de Rutinas & IA (`/controllers/api_routines.php`)

### 3.1 Obtener Rutina Activa y Estado Semanal
- **Método**: `GET`
- **URL**: `/controllers/api_routines.php`
- **Cabeceras**: `Authorization: Bearer <token>`
- **Respuesta (JSON)**:
  - Rutina completa de 7 días (`days`), días completados esta semana (`completedDays`), porcentaje de adherencia (`compliancePercentage`), racha activa y día actual del servidor.

### 3.2 Marcar / Tachar Día de Rutina (Check-in)
- **Método**: `POST`
- **URL**: `/controllers/api_routines.php`
- **Cuerpo (JSON)**:
  ```json
  {
    "day_id": "jueves"
  }
  ```
- **Regla Crítica de Negocio**:
  - `200 OK`: Se permite el check-in **exclusivamente si `day_id` coincide con el día actual del servidor**.
  - `403 Forbidden`: Si el atleta intenta marcar o desmarcar un día diferente al día actual, la petición es bloqueada con `isBlocked: true`.

### 3.3 Generar Rutina Personalizada con Groq IA (Llama 3.3)
- **Método**: `POST`
- **URL**: `/controllers/api_routines.php?action=generate_ai`
- **Cabeceras**: `Authorization: Bearer <token>`
- **Cuerpo (JSON)**:
  ```json
  {
    "dias_semana": 4,
    "objetivo": "ganar_musculo",
    "equipamiento": "gimnasio_completo"
  }
  ```
- **Códigos HTTP**:
  - `201 Created`: Rutina estructurada en 7 días y persistida relacionalmente en MySQL.
  - `401 Unauthorized`: Requiere autenticación de usuario.

---

## 4. Endpoints de Nutrición y Comidas (`/controllers/api_nutrition.php`)

### 4.1 Consultar Plan Alimenticio y Horarios
- **Método**: `GET`
- **URL**: `/controllers/api_nutrition.php?day=jueves`
- **Cabeceras**: `Authorization: Bearer <token>`
- **Respuesta (JSON)**:
  - Estructura de las 5 comidas programadas (`desayuno`, `media_manana`, `almuerzo`, `merienda`, `cena`), alimentos recomendados, macronutrientes por comida (Calorías, Proteínas, Carbohidratos, Grasas), estado de check-in y consumo de agua.

### 4.2 Alternar Estado de Comida Programada (Toggle)
- **Método**: `POST`
- **URL**: `/controllers/api_nutrition.php?action=toggle_scheduled_meal`
- **Cuerpo (JSON)**:
  ```json
  {
    "meal_key": "almuerzo"
  }
  ```
- **Respuesta (JSON)**:
  - Alterna el check-in diario vinculado a `CURDATE()`, actualiza el porcentaje de cumplimiento y recalcula la racha activa del atleta.

### 4.3 Ajuste Rápido de Agua
- **Método**: `POST`
- **URL**: `/controllers/api_nutrition.php?action=adjust_water`
- **Cuerpo (JSON)**:
  ```json
  {
    "delta": 250
  }
  ```
- **Respuesta (JSON)**:
  - Incrementa o decrementa los mililitros consumidos en la fecha actual.

---

## 5. Endpoints de Perfil y Evolución Física (`/controllers/api_profile.php`)

### 5.1 Consultar Perfil, Política de Actualización e Historial
- **Método**: `GET`
- **URL**: `/controllers/api_profile.php`
- **Respuesta (JSON)**:
  - Datos biométricos actuales, metas energéticas calculadas, política de actualización (`policy: canUpdate, mode, hoursRemainingCorrection`), historial cronológico y última recomendación de IA.

### 5.2 Actualizar Condición Física y Evaluación de Progreso
- **Método**: `POST`
- **URL**: `/controllers/api_profile.php`
- **Cuerpo (JSON)**:
  ```json
  {
    "peso": 78.0,
    "altura": 178.0,
    "grasa_corporal": 14.5,
    "cintura_cm": 81.0,
    "notas": "Entrenamientos completados con excelente recuperación"
  }
  ```
- **Regla de Negocio (Ventana de 24 horas y Ciclo de Rutina)**:
  - `200 OK (mode: correction)`: Permitido dentro de las 24 horas posteriores a una medición previa para corregir errores tipográficos.
  - `200 OK (mode: routine_completed)`: Permitido tras haber completado todos los días planificados de la rutina activa.
  - `403 Forbidden (mode: locked)`: Bloqueado si la rutina tiene días pendientes y ha expirado la ventana de corrección de 24 horas.

---

## 6. Endpoints de Entrenamiento en Vivo (`/controllers/api_workouts.php`)

### 6.1 Sesión Actual y Series
- **Método**: `GET`
- **URL**: `/controllers/api_workouts.php?action=current`

### 6.2 Guardar Serie Ejecutada
- **Método**: `POST`
- **URL**: `/controllers/api_workouts.php?action=save_set`
- **Cuerpo (JSON)**:
  ```json
  {
    "exercise_id": "sentadillas",
    "set_number": 1,
    "weight_kg": 90.0,
    "reps": 10,
    "rpe": 8.5
  }
  ```
- **Respuesta (JSON)**:
  - Guarda la serie atómica, calcula el 1RM estimado según la fórmula de Epley ($1RM = 90 \times (1 + 10/30) = 120\text{ kg}$) y actualiza el tonelaje total.

