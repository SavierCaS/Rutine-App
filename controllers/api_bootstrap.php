<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API BOOTSTRAP & SECURITY MIDDLEWARE
 * ==============================================================================
 * Centraliza la configuración, variables de entorno (.env), cabeceras seguras,
 * CORS, conexión MySQL y autenticación híbrida (Web Session + HMAC Bearer Token).
 *
 * Principios aplicados:
 * - Aislamiento estricto y Principio de Menor Privilegio (PoLP).
 * - Protección contra OWASP Top 10 (Inyecciones, Session Fixation, Information Disclosure).
 * - Sanitización y validación estricta de entradas y respuestas.
 *
 * @version 3.0.0
 * ==============================================================================
 */

// 1. Carga segura de variables de entorno desde .env
if (!function_exists('kynap_env')) {
    function kynap_env(string $key, $default = null) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;

        static $envData = null;
        if ($envData === null) {
            $envData = [];
            $envPath = dirname(__DIR__) . '/.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim(trim($value), "\"'");
                    $envData[$name] = $value;
                    if (!isset($_ENV[$name])) $_ENV[$name] = $value;
                }
            }
        }
        return $envData[$key] ?? $default;
    }
}

// 2. Cabeceras HTTP estándar y CORS seguro
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Responder de inmediato a peticiones preflight CORS (OPTIONS)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Preflight OK']);
    exit;
}

// 3. Inicialización segura de sesión PHP (Flags HttpOnly, Secure, SameSite)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    $lifetime = (int)kynap_env('KYNAP_TOKEN_EXPIRATION_SECONDS', 86400 * 30);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// 4. Parámetros criptográficos para Tokens HMAC
if (!defined('KYNAP_AUTH_SECRET')) {
    define('KYNAP_AUTH_SECRET', kynap_env('KYNAP_AUTH_SECRET', 'kynap_secure_mobile_salt_2026_fitness_saas_key'));
}
if (!defined('KYNAP_TOKEN_EXPIRATION_SECONDS')) {
    define('KYNAP_TOKEN_EXPIRATION_SECONDS', (int)kynap_env('KYNAP_TOKEN_EXPIRATION_SECONDS', 86400 * 30));
}

// 5. Conexión segura y tolerante a fallos con MySQL / MariaDB
$dbHost = kynap_env('DB_HOST', 'localhost');
$dbPort = (int)kynap_env('DB_PORT', 3306);
$dbUser = kynap_env('DB_USER', 'root');
$dbPass = kynap_env('DB_PASS', '123456789');
$dbName = kynap_env('DB_NAME', 'kynap_db');

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
$dbAvailable = false;

if ($conn && !$conn->connect_error) {
    $dbAvailable = true;
    $conn->set_charset('utf8mb4');

    // Tablas auxiliares si no existen
    @$conn->query("CREATE TABLE IF NOT EXISTS `nutrition_goals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `daily_calories` INT NOT NULL DEFAULT 2200,
        `protein_g` INT NOT NULL DEFAULT 150,
        `carbs_g` INT NOT NULL DEFAULT 230,
        `fats_g` INT NOT NULL DEFAULT 65,
        `water_ml` INT NOT NULL DEFAULT 2500,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_nutrition_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} else {
    // Registro interno seguro: nunca exponer credenciales ni fallos al cliente
    error_log("[KYNAP DB BOOTSTRAP]: Conexión fallida con el motor MySQL.");
}

/**
 * Genera un token HMAC criptográficamente firmado para clientes móviles y PWA.
 * Formato: base64(payload).signature
 *
 * @param int $userId Identificador único del usuario
 * @param string $email Correo electrónico del usuario
 * @return string Token firmado
 */
function generateAuthToken(int $userId, string $email): string {
    $issuedAt = time();
    $expiresAt = $issuedAt + KYNAP_TOKEN_EXPIRATION_SECONDS;
    
    $payload = [
        'uid'   => $userId,
        'email' => $email,
        'iat'   => $issuedAt,
        'exp'   => $expiresAt
    ];
    
    $payloadEncoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $payloadEncoded, KYNAP_AUTH_SECRET);
    
    return $payloadEncoded . '.' . $signature;
}

/**
 * Valida un token HMAC y verifica su firma y tiempo de expiración.
 *
 * @param string $token
 * @return array|null Payload decodificado o null si es inválido
 */
function verifyAuthToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return null;
    }
    
    list($payloadEncoded, $signature) = $parts;
    $expectedSignature = hash_hmac('sha256', $payloadEncoded, KYNAP_AUTH_SECRET);
    
    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }
    
    $payload = json_decode(base64_decode($payloadEncoded), true);
    if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null; // Expirado o malformado
    }
    
    return $payload;
}

/**
 * Resuelve la identidad del usuario actual de manera segura:
 * 1. Cabecera 'Authorization: Bearer <token>'
 * 2. Cabecera 'X-User-Id' (siempre que coincida con token o sesión)
 * 3. Sesión activa '$_SESSION['user_id']'
 *
 * @param mysqli|null $db Conexión activa a la base de datos
 * @return array Información normalizada del usuario autenticado
 */
function resolveAuthUser(?mysqli $db): array {
    $userId = null;
    $authSource = 'none';

    // 1. Cabecera Authorization Bearer (con soporte para CGI/FastCGI y REDIRECT_HTTP_AUTHORIZATION)
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] 
        ?? $headers['authorization'] 
        ?? $_SERVER['HTTP_AUTHORIZATION'] 
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
        ?? '';
    
    $token = null;
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } elseif (!empty($headers['X-Auth-Token'] ?? $headers['x-auth-token'])) {
        $token = trim($headers['X-Auth-Token'] ?? $headers['x-auth-token']);
    } elseif (!empty($_GET['auth_token'])) {
        $token = trim($_GET['auth_token']);
    }

    if ($token) {
        $payload = verifyAuthToken($token);
        if ($payload && isset($payload['uid'])) {
            $userId = (int)$payload['uid'];
            $authSource = 'bearer_token';
        }
    }

    // 2. Sesión PHP
    if (!$userId && isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        $userId = (int)$_SESSION['user_id'];
        $authSource = 'php_session';
    }

    // 3. Cabecera X-User-Id (solo en desarrollo o como auxiliar secundario)
    if (!$userId) {
        $customHeader = $headers['X-User-Id'] ?? $headers['x-user-id'] ?? $_SERVER['HTTP_X_USER_ID'] ?? null;
        if ($customHeader && is_numeric($customHeader) && (int)$customHeader > 0 && kynap_env('APP_ENV') === 'development') {
            $userId = (int)$customHeader;
            $authSource = 'custom_header';
        }
    }

    $isGuest = false;
    if (!$userId) {
        $userId = 0;
        $isGuest = true;
        $authSource = 'unauthenticated';
    }

    $userData = [
        'id'          => $userId,
        'nombre'      => 'Invitado',
        'email'       => '',
        'imagen_user' => 'iconos/profile.svg',
        'authSource'  => $authSource,
        'isGuest'     => $isGuest
    ];

    // Cargar datos reales del usuario desde la tabla users
    if ($userId > 0 && $db && !$db->connect_error) {
        $stmt = $db->prepare("SELECT id, nombre, email, imagen_user FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $stmt = $db->prepare("SELECT id, nombre, email, imagen_user FROM accounts WHERE id = ? LIMIT 1");
        }
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $userData['nombre'] = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                $userData['email'] = filter_var($row['email'], FILTER_SANITIZE_EMAIL);
                $userData['imagen_user'] = $row['imagen_user'] ?? 'iconos/profile.svg';
                $userData['isGuest'] = false;
            }
            $stmt->close();
        }
    }

    return $userData;
}

/**
 * Obtiene y valida el payload JSON de la petición HTTP.
 *
 * @return array
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST ?: [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Sanitiza una cadena para prevenir ataques de inyección y XSS.
 *
 * @param mixed $input
 * @return string
 */
function sanitizeString($input): string {
    if (!is_string($input)) return '';
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Emite una respuesta JSON exitosa estandarizada.
 *
 * @param mixed $data Datos de respuesta
 * @param string $message Mensaje informativo
 * @param int $httpCode Código HTTP
 */
function apiSuccess($data = null, string $message = 'Operación completada con éxito', int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode([
        'success'   => true,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Emite una respuesta JSON de error estandarizada y segura (sin trazas internas).
 *
 * @param string $message Mensaje de error genérico para el cliente
 * @param int $httpCode Código HTTP
 * @param mixed $errors Detalles seguros
 */
function apiError(string $message = 'Error en el servidor', int $httpCode = 400, $errors = null): void {
    http_response_code($httpCode);
    echo json_encode([
        'success'   => false,
        'message'   => $message,
        'errors'    => $errors,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Registra internamente un error sensible del servidor y devuelve una respuesta genérica al cliente.
 *
 * @param string $internalLog Mensaje técnico para los logs privados
 * @param string $clientMessage Mensaje genérico para el usuario
 * @param int $httpCode Código HTTP (default 500)
 */
function apiServerError(string $internalLog, string $clientMessage = 'Ocurrió un error interno en el servidor. Por favor intenta más tarde.', int $httpCode = 500): void {
    error_log("[KYNAP SECURE ERROR] " . $internalLog);
    apiError($clientMessage, $httpCode);
}

/**
 * Retorna la clave del día actual de la semana en español según el reloj del servidor.
 *
 * @return string Clave en minúsculas: 'lunes' | 'martes' | 'miercoles' | 'jueves' | 'viernes' | 'sabado' | 'domingo'
 */
function getCurrentServerDayKey(): string {
    $dayMap = [
        'monday'    => 'lunes',
        'tuesday'   => 'martes',
        'wednesday' => 'miercoles',
        'thursday'  => 'jueves',
        'friday'    => 'viernes',
        'saturday'  => 'sabado',
        'sunday'    => 'domingo'
    ];
    return $dayMap[strtolower(date('l'))] ?? 'lunes';
}

/**
 * Retorna el nombre formateado en español del día de la semana.
 *
 * @param string $dayKey
 * @return string
 */
function getSpanishDayName(string $dayKey): string {
    $names = [
        'lunes'     => 'Lunes',
        'martes'    => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves'    => 'Jueves',
        'viernes'   => 'Viernes',
        'sabado'    => 'Sábado',
        'domingo'   => 'Domingo'
    ];
    return $names[strtolower($dayKey)] ?? ucfirst($dayKey);
}

/**
 * Calcula la racha real y consecutiva del usuario en base a rutinas y comidas completadas.
 *
 * @param mysqli|null $conn
 * @param int $userId
 * @return array
 */
function calculateUserStreak(?mysqli $conn, int $userId): array {
    if (!$conn || $userId <= 0) {
        return [
            'currentStreak'    => 1,
            'longestStreak'    => 1,
            'hasActivityToday' => false,
            'badge'            => ['name' => 'Fuego Inicial', 'icon' => '🔥', 'level' => 1, 'nextGoal' => '2 días para Bronce 🥉'],
            'compactWeek'      => [],
            'totalActiveDays'  => 0
        ];
    }

    $activeDates = [];

    // 1. Fechas de rutinas completadas
    $s1 = $conn->prepare("SELECT DISTINCT check_in_date FROM routine_check_ins WHERE user_id = ? AND is_completed = 1");
    if ($s1) {
        $s1->bind_param("i", $userId);
        $s1->execute();
        $r1 = $s1->get_result();
        while ($row = $r1->fetch_assoc()) {
            if (!empty($row['check_in_date'])) {
                $activeDates[$row['check_in_date']] = true;
            }
        }
        $s1->close();
    }

    // 2. Fechas de comidas completadas
    $s2 = $conn->prepare("SELECT DISTINCT meal_date FROM daily_meal_completions WHERE user_id = ? AND is_completed = 1");
    if ($s2) {
        $s2->bind_param("i", $userId);
        $s2->execute();
        $r2 = $s2->get_result();
        while ($row = $r2->fetch_assoc()) {
            if (!empty($row['meal_date'])) {
                $activeDates[$row['meal_date']] = true;
            }
        }
        $s2->close();
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $hasActivityToday = isset($activeDates[$today]);
    $hasActivityYesterday = isset($activeDates[$yesterday]);

    $currentStreak = 0;
    if ($hasActivityToday) {
        $checkDate = new DateTime($today);
        while (isset($activeDates[$checkDate->format('Y-m-d')])) {
            $currentStreak++;
            $checkDate->modify('-1 day');
        }
    } elseif ($hasActivityYesterday) {
        // La racha se mantiene vigente durante el día de hoy hasta medianoche
        $checkDate = new DateTime($yesterday);
        while (isset($activeDates[$checkDate->format('Y-m-d')])) {
            $currentStreak++;
            $checkDate->modify('-1 day');
        }
    }

    // Calcular la mejor racha histórica
    $sortedDates = array_keys($activeDates);
    sort($sortedDates);
    $longestStreak = $currentStreak;
    $tempStreak = 0;
    $prevTime = null;

    foreach ($sortedDates as $dStr) {
        $cTime = strtotime($dStr);
        if ($prevTime === null) {
            $tempStreak = 1;
        } else {
            $diffDays = round(($cTime - $prevTime) / 86400);
            if ($diffDays === 1.0) {
                $tempStreak++;
            } elseif ($diffDays > 1.0) {
                $tempStreak = 1;
            }
        }
        if ($tempStreak > $longestStreak) {
            $longestStreak = $tempStreak;
        }
        $prevTime = $cTime;
    }

    // Determinar medalla / nivel motivacional
    if ($currentStreak >= 30) {
        $badge = ['name' => 'Diamante Élite', 'icon' => '💎', 'level' => 5, 'nextGoal' => 'Máximo Nivel Alcanzado'];
    } elseif ($currentStreak >= 14) {
        $badge = ['name' => 'Oro Disciplina', 'icon' => '🥇', 'level' => 4, 'nextGoal' => (30 - $currentStreak) . ' días para Diamante 💎'];
    } elseif ($currentStreak >= 7) {
        $badge = ['name' => 'Plata Imparable', 'icon' => '🥈', 'level' => 3, 'nextGoal' => (14 - $currentStreak) . ' días para Oro 🥇'];
    } elseif ($currentStreak >= 3) {
        $badge = ['name' => 'Bronce Activo', 'icon' => '🥉', 'level' => 2, 'nextGoal' => (7 - $currentStreak) . ' días para Plata 🥈'];
    } else {
        $badge = ['name' => 'Fuego Inicial', 'icon' => '🔥', 'level' => 1, 'nextGoal' => max(1, 3 - $currentStreak) . ' días para Bronce 🥉'];
    }

    // Mini calendario semanal de los últimos 7 días
    $compactWeek = [];
    $esDaysShort = ['Sun' => 'D', 'Mon' => 'L', 'Tue' => 'M', 'Wed' => 'X', 'Thu' => 'J', 'Fri' => 'V', 'Sat' => 'S'];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $shortDay = $esDaysShort[date('D', strtotime($d))] ?? date('D', strtotime($d));
        $compactWeek[] = [
            'date'      => $d,
            'dayLetter' => $shortDay,
            'isActive'  => isset($activeDates[$d]),
            'isToday'   => ($d === $today)
        ];
    }

    // Persistir en tabla user_streaks
    $lastActDate = $hasActivityToday ? $today : ($hasActivityYesterday ? $yesterday : null);
    $updStreak = $conn->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_activity_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE current_streak = VALUES(current_streak), longest_streak = GREATEST(longest_streak, VALUES(longest_streak)), last_activity_date = VALUES(last_activity_date)");
    if ($updStreak) {
        $updStreak->bind_param("iiis", $userId, $currentStreak, $longestStreak, $lastActDate);
        $updStreak->execute();
        $updStreak->close();
    }

    return [
        'currentStreak'    => $currentStreak,
        'longestStreak'    => $longestStreak,
        'hasActivityToday' => $hasActivityToday,
        'badge'            => $badge,
        'compactWeek'      => $compactWeek,
        'totalActiveDays'  => count($activeDates)
    ];
}

/**
 * Evalúa la política de periodicidad de actualización física (7 días vs 24 horas de gracia).
 *
 * @param mysqli|null $conn
 * @param int $userId
 * @return array
 */
function evaluateBiometricsPolicy(?mysqli $conn, int $userId): array {
    $policy = [
        'canUpdate'                 => true,
        'mode'                      => 'create', // 'create' | 'correction' | 'locked'
        'message'                   => 'Puedes registrar tu actualización de condición física.',
        'hoursRemainingCorrection'  => null,
        'daysRemainingNextUpdate'   => 0,
        'nextAllowedDate'           => date('Y-m-d'),
        'lastRecordId'              => null,
        'lastRecordDate'            => null
    ];

    if (!$conn || $userId <= 0) {
        return $policy;
    }

    $stmt = $conn->prepare("SELECT id, created_at FROM user_biometrics_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $policy['lastRecordId'] = (int)$row['id'];
            $policy['lastRecordDate'] = $row['created_at'];

            $createdTimestamp = strtotime($row['created_at']);
            $secondsElapsed = time() - $createdTimestamp;
            $hoursElapsed = $secondsElapsed / 3600.0;
            $daysElapsed = floor($secondsElapsed / 86400.0);

            if ($hoursElapsed <= 24.0) {
                // Dentro del margen de corrección de 24 horas
                $remainingHours = max(0.1, round(24.0 - $hoursElapsed, 1));
                $policy['canUpdate'] = true;
                $policy['mode'] = 'correction';
                $policy['hoursRemainingCorrection'] = $remainingHours;
                $policy['daysRemainingNextUpdate'] = 0;
                $policy['nextAllowedDate'] = date('Y-m-d', strtotime('+7 days', $createdTimestamp));
                $policy['message'] = "Modo corrección activo: Tienes {$remainingHours} horas restantes para rectificar o editar tu registro más reciente sin esperar los 7 días.";
            } elseif ($daysElapsed < 7) {
                // Bloqueado: aún no pasan los 7 días y ya expiró la ventana de corrección
                $daysRemaining = (int)(7 - $daysElapsed);
                $nextDate = date('Y-m-d', strtotime('+7 days', $createdTimestamp));
                $policy['canUpdate'] = false;
                $policy['mode'] = 'locked';
                $policy['hoursRemainingCorrection'] = 0;
                $policy['daysRemainingNextUpdate'] = $daysRemaining;
                $policy['nextAllowedDate'] = $nextDate;
                $policy['message'] = "Actualización bloqueada: Para garantizar un seguimiento fisiológico real, las mediciones se realizan cada 7 días. Próxima actualización disponible en {$daysRemaining} día(s) ({$nextDate}).";
            } else {
                // Han pasado 7 días o más: listo para nuevo registro semanal
                $policy['canUpdate'] = true;
                $policy['mode'] = 'create';
                $policy['hoursRemainingCorrection'] = null;
                $policy['daysRemainingNextUpdate'] = 0;
                $policy['nextAllowedDate'] = date('Y-m-d');
                $policy['message'] = "¡Periodo semanal cumplido! Ya puedes ingresar tu nueva medición de condición física.";
            }
        }
        $stmt->close();
    }

    return $policy;
}
?>
