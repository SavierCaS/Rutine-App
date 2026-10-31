# Arquitectura del Sistema - Plataforma Kynap Fitness

## 1. Visión General
**Kynap Fitness Platform** es una aplicación web y móvil progresiva (PWA) de alto rendimiento para la prescripción de entrenamientos personalizados, seguimiento de nutrición con macronutrientes calculados científicamente, registro de hidratación y evolución física con soporte de Inteligencia Artificial (Llama 3.3 vía Groq AI).

---

## 2. Diagrama de Arquitectura

```mermaid
graph TD
    Client["Cliente Frontend (PWA / Web Mobile / Desktop)<br/>HTML5, Modern CSS, Vanilla JS, Fetch API"]
    
    subgraph WebServer ["Servidor Web & Capa de Seguridad (Apache / AppServ)"]
        Htaccess[".htaccess (Bloqueo de archivos sensibles .env, sql)"]
        Bootstrap["api_bootstrap.php<br/>CORS, HMAC Token Validator, Session Fixation Guard, PoLP"]
    end

    subgraph API_Controllers ["Controladores de API REST (PHP 7.3+)"]
        AuthAPI["api_auth.php<br/>Login, Registro, Verificación, Logout"]
        RoutineAPI["api_routines.php<br/>Persistencia de Rutinas, Check-ins, Bloqueo de Días"]
        NutritionAPI["api_nutrition.php<br/>5 Comidas Programadas, Macros, Agua, Check-ins"]
        ProfileAPI["api_profile.php<br/>Biometría, Ventana 24h, Progreso Físico"]
        WorkoutAPI["api_workouts.php<br/>Sesión en Vivo, Series, Tonelaje, 1RM Epley"]
        ChatAPI["chat_kynap_ai.php<br/>Asistente Virtual Kynap AI"]
    end

    subgraph AIService ["Microservicio Aislado de IA (Python 3.12)"]
        GroqScript["groq_routine_service.py<br/>Llama 3.3 70B vía Groq Cloud API"]
        OfflineFallback["Generador de Resiliencia Deportiva<br/>(Plantillas fisiológicas validadas)"]
    end

    subgraph DatabaseLayer ["Capa de Datos Persistente (MySQL / MariaDB)"]
        DB[(kynap_db<br/>18 Tablas Relacionales)]
    end

    Client -->|HTTP / JSON + Bearer Token| Htaccess
    Htaccess --> Bootstrap
    Bootstrap --> API_Controllers
    RoutineAPI -->|IPC Stdin / Stdout JSON (CERO acceso a BD)| GroqScript
    GroqScript -->|Fallback offline si falla red| OfflineFallback
    API_Controllers -->|Sentencias Preparadas (PDO / MySQLi)| DB
```

---

## 3. Capas del Sistema

### 3.1 Frontend (Capa de Presentación)
- **Tecnologías**: HTML5 semántico, CSS3 estructurado (Mobile-First responsive con variables CSS modernas), JavaScript ES6+ asíncrono (`fetch`, `localStorage`, manipulación reactiva del DOM).
- **Vistas Principales**:
  - `index.html` / `app.html`: Vista de inicio y presentación de la plataforma.
  - `login.php` / `login.html`: Portal de autenticación con feedback de errores seguro.
  - `registro.php` / `registro.html`: Registro multifase con biometría completa y cálculo de metas en tiempo real.
  - `dashboard.php`: Panel central del atleta con rutina diaria interactiva, 5 comidas programadas, selector de días, contador de rachas y control de agua.
  - `configuration.php`: Gestión de perfil, biometría, historial de progreso y recomendaciones generadas por IA.
  - `chat_kynap_ai.php`: Chatbot interactivo con contexto del usuario para asesoría deportiva inmediata.

### 3.2 Capa de Enrutamiento y Middleware (`controllers/api_bootstrap.php`)
- **Cabeceras HTTP y CORS**: Configuración de `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, y `Referrer-Policy`.
- **Sesiones Seguras**: Cookies con atributos `HttpOnly`, `SameSite=Lax`, regeneración de ID (`session_regenerate_id`) en login para neutralizar Session Fixation.
- **Tokens HMAC Bearer**: Emisión y validación de tokens de sesión firmados con SHA-256 HMAC para clientes móviles y PWA.
- **Polyfills de Compatibilidad**: Retrocompatibilidad garantizada para entornos de producción PHP 7.3+ (`str_contains`, `str_starts_with`, `str_ends_with`).

### 3.3 Microservicio de IA Aislado (`services/groq_routine_service.py`)
- **Principio de Mínimo Privilegio (PoLP)**:
  - El microservicio en Python **NO tiene dependencias de bases de datos** (ni mysql, ni psycopg, ni sqlite, ni sqlalchemy).
  - Toda la interacción con la base de datos es responsabilidad exclusiva del backend PHP.
  - El script recibe el contexto biométrico del atleta exclusivamente por `stdin` en formato JSON y devuelve la planificación por `stdout`.
  - Si la API externa no está disponible o la clave de API expira, entra en funcionamiento el **Motor de Resiliencia Fisiológica**, que genera un plan de 7 días estructurado y adaptado al objetivo del atleta.

### 3.4 Capa de Persistencia (`kynap_db`)
- 18 tablas normalizadas en motor InnoDB con soporte transaccional ACID (`utf8mb4`).
- Integridad referencial con claves foráneas e índices optimizados en campos de búsqueda frecuente (`user_id`, `email`, `day_key`, `log_date`).

