# Kynap Fitness Platform

> Plataforma inteligente para prescripción de entrenamiento deportivo, cálculo metabólico de macronutrientes, hidratación y seguimiento de la evolución física con Inteligencia Artificial (Llama 3.3 vía Groq AI).

---

## 🚀 Características Principales

- **Algoritmos Fisiológicos Validados**:
  - Gasto Energético en Reposo mediante la fórmula de **Mifflin-St Jeor**.
  - Cálculo dinámico de **TDEE** (Gasto Energético Diario Total) y partición de macronutrientes adaptada al objetivo (superávit controlado para ganar músculo, déficit seguro para perder grasa, o normocalórico).
  - Cálculo de repetición máxima estimada (**1RM Epley**) y tonelaje acumulativo en sesiones de entrenamiento.
- **Microservicio Aislado de Inteligencia Artificial**:
  - Planificación personalizada de 7 días generada por **Llama 3.3 70B** a través de Groq AI Cloud API.
  - **Aislamiento estricto de base de datos**: el servicio de IA opera sin acceso ni credenciales a MySQL, recibiendo contexto por `stdin` y respondiendo por `stdout`.
  - **Motor de Resiliencia Deportiva**: fallback automático a rutinas de periodización ondulante si no hay conexión a internet o la API externa no está disponible.
- **Reglas de Negocio Estrictas**:
  - **Bloqueo Temporal de Rutina**: los check-ins solo pueden realizarse en el día actual del servidor (`CURDATE()`); los demás días permanecen bloqueados en modo solo lectura para garantizar la integridad del plan.
  - **Política de Evolución Físico-Biométrica**: las actualizaciones de medidas requieren haber completado el 100% de la rutina activa, con una **ventana de corrección de 24 horas** para subsanar errores tipográficos.
  - **Motor de Rachas y Gamificación**: cálculo continuo de días activos consecutivos con niveles de medallas y margen de gracia hasta la medianoche.
- **Ciberseguridad y Mitigación OWASP**:
  - Hashing seguro con `PASSWORD_BCRYPT` (factor de coste 12).
  - Autenticación dual: Sesiones Web blindadas contra Session Fixation + Bearer Tokens firmados con HMAC-SHA256 para clientes móviles/PWA.
  - Consultas preparadas al 100% en MySQL (PDO y MySQLi).
  - Sanitización exhaustiva contra XSS y bloqueo de archivos sensibles (`.env`, `.sql`, `.git`) vía `.htaccess`.

---

## 📁 Estructura del Proyecto

```text
Rutine-App/
├── config/
│   └── db.php                       # Conexión centralizada con PDO y lectura segura de .env
├── controllers/
│   ├── api_bootstrap.php            # Middleware de seguridad, CORS, HMAC tokens, sesión y polyfills
│   ├── api_auth.php                 # Endpoints de login, registro, verify y logout
│   ├── api_routines.php             # Rutina semanal, check-ins de días y generador IA
│   ├── api_nutrition.php            # 5 comidas programadas, macros, agua y toggles
│   ├── api_profile.php              # Perfil, biometría, política 24h y evolución
│   ├── api_workouts.php             # Workout en vivo, registro de series, 1RM y tonelaje
│   ├── guardar_progreso.php         # Persistencia legacy de progreso
│   └── registroController.php       # Controlador auxiliar de registro
├── db/
│   ├── schema.sql                   # Esquema maestro unificado de la base de datos (18 tablas)
│   ├── seed.sql                     # Población inicial de ejercicios y usuario demo
│   └── migrations/                  # Migraciones secuenciales 001 a 005
├── docs/
│   ├── ARCHITECTURE.md              # Diagrama de arquitectura del sistema y flujo de datos
│   ├── DATABASE.md                  # Diccionario de datos y diagrama entidad-relación (ER)
│   ├── API_REFERENCE.md             # Especificación completa de endpoints, payloads y respuestas
│   ├── SECURITY.md                  # Arquitectura de seguridad y matriz OWASP Top 10
│   ├── BUSINESS_LOGIC.md            # Fórmulas matemáticas, fisiología deportiva y reglas de negocio
│   └── TESTING_REPORT.md            # Informe exhaustivo de aseguramiento de calidad (QA)
├── services/
│   └── groq_routine_service.py      # Microservicio de IA deportiva con Llama 3.3 (Groq)
├── tests/
│   ├── UnitTests.php                # Suite de 34 pruebas unitarias de algoritmos puros
│   └── run_all_tests.php            # Master Test Runner unificado (52 pruebas)
├── public/                          # Recursos públicos y multimedia
├── icon_users/                      # Avatares de atletas
├── iconos/                          # Iconos SVG de navegación
├── rutinas_semanales/               # Animaciones GIF y fotos técnicas de ejercicios
├── .env.example                     # Plantilla de variables de entorno seguras
├── .htaccess                        # Directivas Apache para bloqueo de archivos sensibles
├── test_auth_security.php           # Suite de pruebas directas en base de datos
├── test_complete_integration.php    # Suite de pruebas de integración completa E2E
├── dashboard.php                    # Panel principal del atleta (PWA/Web)
├── configuration.php                # Configuración de perfil y biometría
├── login.php                        # Pantalla de inicio de sesión
├── registro.php                     # Formulario de registro con biometría
├── chat_kynap_ai.php                # Asistente virtual deportivo interactivo
└── index.html                       # Página de bienvenida
```

---

## 🧪 Ejecución de Pruebas Automatizadas

La plataforma cuenta con un sistema de pruebas integrado con **100% de tasa de éxito** en **52 pruebas**:

### Ejecutar todas las pruebas (Master Runner)
```bash
php tests/run_all_tests.php
```

### Ejecutar suites individualmente
```bash
# 1. Pruebas Unitarias (34 pruebas de algoritmos y criptografía)
php tests/UnitTests.php

# 2. Pruebas de Base de Datos y Autenticación BCRYPT (3 pruebas)
php test_auth_security.php

# 3. Pruebas de Integración Completa y Endpoints (15 pruebas)
php test_complete_integration.php
```

---

## 📚 Documentación Técnica Completa

Para profundizar en el diseño e implementación de la plataforma, consulte las guías especializadas:

1. [Arquitectura del Sistema](docs/ARCHITECTURE.md)
2. [Diccionario de Datos y Modelo Entidad-Relación](docs/DATABASE.md)
3. [Referencia de la API REST](docs/API_REFERENCE.md)
4. [Seguridad y Mitigación OWASP](docs/SECURITY.md)
5. [Lógica de Negocio y Fórmulas Fisiológicas](docs/BUSINESS_LOGIC.md)
6. [Informe Técnico de Pruebas (QA)](docs/TESTING_REPORT.md)

---

## ⚙️ Requisitos del Entorno

- **PHP**: 7.3+ (con extensiones `mysqli`, `pdo_mysql`, `json`, `mbstring`, `curl`)
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.4+
- **Servidor Web**: Apache 2.4+ (con soporte para `.htaccess` y `mod_rewrite`)
- **Python**: 3.10+ (para el microservicio de rutinas con IA)

