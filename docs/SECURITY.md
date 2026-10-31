# Arquitectura de Ciberseguridad y Mitigación OWASP - Kynap Fitness

## 1. Resumen de Seguridad
La plataforma Kynap ha sido construida bajo el principio de **Defensa en Profundidad (Defense-in-Depth)** y el **Principio de Menor Privilegio (PoLP)**, mitigando activamente los riesgos identificados en el **OWASP Top 10**.

---

## 2. Mitigación Específica OWASP Top 10

### 2.1 A01: Broken Access Control (Control de Acceso Defectuoso)
- **Aislamiento Multi-Inquilino**: Cada consulta SQL filtra rigurosamente por el `user_id` autenticado resuelto por el backend. Un usuario jamás puede ver o modificar check-ins, rutinas o perfiles de otros atletas.
- **Validación Estricta de Días en Backend**: La regla de negocio que prohíbe tachar o marcar días pasados o futuros se evalúa en el servidor con el reloj del sistema, impidiendo que clientes web o apps modifiquen el historial alterando parámetros en la petición.
- **Ventana de Corrección de 24 Horas**: Controlada estrictamente por `TIMESTAMPDIFF` o `time() - createdTimestamp` en PHP; no es manipulable desde el cliente.

### 2.2 A02: Cryptographic Failures (Fallas Criptográficas)
- **Hashing de Contraseñas**: Empleo de `password_hash()` con algoritmo `PASSWORD_BCRYPT` y factor de coste calibrado en 12 (`'cost' => 12`). Las contraseñas en texto plano nunca se almacenan ni se registran en logs.
- **Mitigación de DoS en BCRYPT**: Se restringe la longitud máxima de contraseñas recibidas a 128 caracteres para neutralizar ataques de denegación de servicio por cálculo computacional excesivo de hashes largos.
- **Tokens de Autenticación HMAC-SHA256**: Los Bearer tokens se componen de `base64(payload).signature`, donde la firma se genera mediante `hash_hmac('sha256', $payload, KYNAP_AUTH_SECRET)`. La firma se valida en tiempo constante con `hash_equals()` para neutralizar ataques de tiempo (Timing Attacks).

### 2.3 A03: Injection (Inyecciones SQL, OS Command, Code)
- **Consultas Preparadas al 100%**: Todas las operaciones sobre MySQL (PDO y MySQLi) utilizan sentencias preparadas parametrizadas (`bind_param` y `prepare -> execute([$param])`). La concatenación directa de cadenas de usuario en SQL está totalmente erradicada.
- **Aislamiento del Subproceso de IA**: El script Python `groq_routine_service.py` no recibe argumentos por línea de comandos propensos a inyección; recibe el contexto biométrico sanitizado por el descriptor de tubería estándar `stdin` en formato JSON puro.
- **Aislamiento de la Base de Datos**: El microservicio en Python no contiene bibliotecas ni cadenas de conexión a base de datos (0 acceso a MySQL).

### 2.4 A04: Insecure Design (Diseño Inseguro)
- **Mecanismos de Resiliencia Deportiva**: Si la API externa de IA o la conexión de red falla, el sistema conmuta automáticamente a plantillas de periodización deportiva basadas en fisiología del ejercicio validadas por entrenadores de fuerza.
- **Salvaguardas Metabólicas**: Se imponen límites biológicamente seguros (mínimo 1300 kcal para mujeres en déficit agresivo, máximo 4500 ml de agua, mínimo 2000 ml).

### 2.5 A05: Security Misconfiguration (Configuración Insegura)
- **Protección de Archivos Sensibles**: La directiva `.htaccess` en la raíz del servidor bloquea el acceso público HTTP a archivos de entorno (`.env`), archivos de control de versiones (`.git`), volcados de base de datos (`.sql`) y scripts de prueba.
  ```apache
  <FilesMatch "^(\.env|\.git|\.gitignore|schema\.sql|seed\.sql)">
      Order Allow,Deny
      Deny from all
  </FilesMatch>
  ```
- **Cabeceras HTTP de Seguridad Activas**:
  - `X-Content-Type-Options: nosniff` (previene MIME type sniffing).
  - `X-Frame-Options: SAMEORIGIN` (mitiga ataques de Clickjacking).
  - `X-XSS-Protection: 1; mode=block` (filtro XSS de navegadores).
  - `Referrer-Policy: strict-origin-when-cross-origin`.

### 2.6 A07: Identification and Authentication Failures (Fallas de Identificación)
- **Respuestas Genéricas contra Enumeración de Usuarios**: Las fallas en login devuelven un mensaje genérico ("Credenciales incorrectas o cuenta no registrada") con código HTTP 401, impidiendo que atacantes descubran qué correos están registrados.
- **Protección contra Session Fixation**: En cada autenticación exitosa se ejecuta de inmediato `session_regenerate_id(true)`, destruyendo el identificador de sesión anterior y emitiendo uno nuevo criptográficamente aleatorio.
- **Cookies de Sesión con Atributos Seguros**: `HttpOnly = true` (inaccesible desde scripts JavaScript maliciosos), `SameSite = Lax` (protección contra CSRF en solicitudes cruzadas).

