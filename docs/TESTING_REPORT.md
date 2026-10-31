# Informe Técnico de Pruebas y Aseguramiento de Calidad (QA)

## 1. Resumen Ejecutivo
Para garantizar el funcionamiento óptimo de la plataforma **Kynap Fitness Platform** requerido por la cátedra académica, se implementó y ejecutó una batería integral de **52 pruebas automatizadas** divididas en 3 niveles de verificación:

| Nivel de Prueba | Suite | Pruebas | Pasaron | Fallaron | Tasa de Éxito | Tiempo |
|---|---|---|---|---|---|---|
| **Nivel 1** | Pruebas Unitarias de Algoritmos & Criptografía | 34 | 34 | 0 | **100%** | ~5.1 ms |
| **Nivel 2** | Seguridad Directa en BD & Autenticación BCRYPT | 3 | 3 | 0 | **100%** | ~990 ms |
| **Nivel 3** | Integración Completa End-to-End & APIs REST | 15 | 15 | 0 | **100%** | ~1300 ms |
| **TOTAL** | **Batería Completa Automatizada** | **52** | **52** | **0** | **100%** | **~2.3 s** |

---

## 2. Matriz de Pruebas Detallada

### 2.1 Suite 1: Pruebas Unitarias (`tests/UnitTests.php`)
Valida la exactitud matemática, límites biológicos, criptografía y algoritmos puros de forma desacoplada de la red:
- **Cálculos Metabólicos**:
  - [x] Hombre ganar músculo: Calorías y macros (80kg $\times 2.0 = 160\text{g}$ proteína).
  - [x] Mujer perder peso: Salvaguarda de calorías mínimas ($\ge 1300\text{ kcal}$).
  - [x] Entrada inválida o cero: Fallback seguro a valores estándar (2200 kcal, 150g prot).
  - [x] Límites de hidratación: Piso de 2000 ml y techo de 4500 ml.
- **Seguridad Criptográfica & Tokens HMAC**:
  - [x] Formato y estructura de token `base64(payload).signature`.
  - [x] Validación y extracción íntegra de `uid` y `email`.
  - [x] Rechazo de token con firma HMAC adulterada.
  - [x] Rechazo de payload manipulado sin firma válida.
  - [x] Rechazo de token expirado (`exp < time()`).
- **Sanitización de Entradas**:
  - [x] Neutralización de vectores XSS (`<script>alert()</script>`).
  - [x] Preservación de cadenas legítimas y codificación de entidades HTML.
- **Fisiología Deportiva & Fuerza**:
  - [x] 1RM Epley para 1 repetición ($1\text{RM} = \text{peso}$).
  - [x] 1RM Epley para 10 repeticiones ($100\text{ kg} \times (1 + 10/30) = 133.33\text{ kg}$).
  - [x] Cálculo acumulativo de tonelaje por sesión.
- **Motor de Calendario del Servidor**:
  - [x] Clave de día en español (`lunes` .. `domingo`).
  - [x] Formateo con mayúscula inicial.
- **Política de Condición Física**:
  - [x] Medición reciente ($< 24\text{ horas}$) activa `mode = 'correction'`.
  - [x] Medición antigua ($> 24\text{ horas}$) bloquea rectificación (`mode = 'locked'`).
- **Motor de Rachas y Gamificación**:
  - [x] Asignación de insignias (Fuego Inicial, Bronce Constante, Plata Disciplinada, Oro Imparable, Leyenda Titán).

### 2.2 Suite 2: Seguridad en BD y Hashing (`test_auth_security.php`)
- [x] Detección de usuario inexistente en BD.
- [x] Verificación de password correcto con `password_verify` y hash BCRYPT cost 12.
- [x] Rechazo estricto de password incorrecto.
- [x] Registro transaccional completo: inserción en `users`, `user_biometrics` y `nutrition_goals`.
- [x] Detección y bloqueo de correo duplicado.
- [x] Limpieza atómica del usuario de prueba.

### 2.3 Suite 3: Integración E2E & Reglas de Negocio (`test_complete_integration.php`)
- **Fase 1: Conexión y Protección**:
  - [x] Bloqueo HTTP público al archivo `.env` (código 404/403).
  - [x] Login incorrecto responde con error genérico sin exponer nombres de tablas o columnas SQL.
  - [x] Login correcto emite Bearer token firmado con HMAC.
  - [x] Registro de usuario sanitiza XSS en base de datos.
- **Fase 2: Microservicio Groq AI & Aislamiento**:
  - [x] Verificación de cero bibliotecas de BD en el código Python de IA.
  - [x] Endpoint de generación de IA bloqueado si no hay autenticación (HTTP 401).
  - [x] Generación exitosa de rutina de 7 días.
  - [x] Persistencia relacional en MySQL (`routines` $\to$ `routine_days` $\to$ `routine_exercises`).
  - [x] Endpoint GET recupera la rutina activa de la base de datos.
- **Fase 3: Reglas de Negocio & Módulos Rediseñados**:
  - [x] Bloqueo estricto de check-in en días diferentes al día actual (HTTP 403 Forbidden).
  - [x] Marcado exitoso en el día actual del servidor (HTTP 200).
  - [x] Estructura de 5 comidas programadas con macronutrientes.
  - [x] Check-in diario de comidas vinculado a `CURDATE()`.
  - [x] Sincronización dinámica de rachas y medalla activa.
  - [x] Actualización de condición física dentro de la ventana de 24 horas.

---

## 3. Guía de Ejecución de Pruebas

Para reproducir la ejecución completa de pruebas:

```bash
# 1. Ejecutar el Master Test Runner (Las 52 pruebas unificadas)
php tests/run_all_tests.php

# 2. O ejecutar cada suite de manera individual:
php tests/UnitTests.php
php test_auth_security.php
php test_complete_integration.php
```

