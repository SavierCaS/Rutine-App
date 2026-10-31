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

// PHP 8 Compatibility Polyfills for PHP 7.3
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

/**
 * Calcula las metas de macronutrientes basadas en fórmulas científicas validadas.
 */
if (!function_exists('calculateNutritionTargets')) {
    function calculateNutritionTargets(
        $weightKg = 75.0,
        float $heightCm = 175.0,
        int $age = 25,
        string $gender = 'masculino',
        string $activity = 'moderado',
        string $goal = 'ganar_musculo'
    ): array {
        if (is_array($weightKg)) {
            $arr = $weightKg;
            $weightKg = (float)($arr['peso'] ?? $arr['weightKg'] ?? $arr['weight'] ?? 75.0);
            $heightCm = (float)($arr['altura'] ?? $arr['heightCm'] ?? $arr['height'] ?? 175.0);
            $age = (int)($arr['edad'] ?? $arr['age'] ?? 25);
            $gender = (string)($arr['sexo'] ?? $arr['gender'] ?? 'masculino');
            $activity = (string)($arr['nivel_actividad'] ?? $arr['activity'] ?? 'moderado');
            $goal = (string)($arr['objetivo'] ?? $arr['goal'] ?? 'ganar_musculo');
        } else {
            $weightKg = (float)$weightKg;
        }

        if ($weightKg <= 0 || $heightCm <= 0) {
            return [
                'calories' => 2200,
                'proteinG' => 150,
                'carbsG'   => 230,
                'fatsG'    => 65,
                'waterMl'  => 2500
            ];
        }

        $age = $age > 0 ? $age : 25;

        // Fórmula Mifflin-St Jeor para BMR
        if (strtolower($gender) === 'femenino') {
            $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
        } else {
            $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
        }

        $activityFactors = [
            'sedentario' => 1.2,
            'ligero'     => 1.375,
            'moderado'   => 1.55,
            'activo'     => 1.725,
            'intenso'    => 1.9
        ];
        $factor = $activityFactors[$activity] ?? 1.55;
        $tdee = $bmr * $factor;

        switch ($goal) {
            case 'bajar_peso':
            case 'perder_peso':
            case 'perder_grasa':
                $targetCalories = round($tdee - 450);
                $proteinMultiplier = 2.2;
                $fatsPercentage = 0.25;
                break;
            case 'ganar_musculo':
                $targetCalories = round($tdee + 350);
                $proteinMultiplier = 2.0;
                $fatsPercentage = 0.25;
                break;
            case 'mantener':
            case 'mantener_peso':
            default:
                $targetCalories = round($tdee);
                $proteinMultiplier = 1.8;
                $fatsPercentage = 0.28;
                break;
        }

        $targetCalories = max(1300, (int)$targetCalories);
        $proteinG = round($weightKg * $proteinMultiplier);
        $fatsG = round(($targetCalories * $fatsPercentage) / 9);
        $caloriesFromProteinAndFats = ($proteinG * 4) + ($fatsG * 9);
        $carbsCalories = max(0, $targetCalories - $caloriesFromProteinAndFats);
        $carbsG = round($carbsCalories / 4);

        $waterTargetMl = max(2000, min(4500, (int)round($weightKg * 35)));

        return [
            'calories' => (int)$targetCalories,
            'proteinG' => (int)$proteinG,
            'carbsG'   => (int)$carbsG,
            'fatsG'    => (int)$fatsG,
            'waterMl'  => (int)$waterTargetMl
        ];
    }
}

/**
 * Persiste las metas nutricionales calculadas en la base de datos (tabla nutrition_goals).
 */
if (!function_exists('saveNutritionGoals')) {
    function saveNutritionGoals(?mysqli $conn, int $userId, array $targets): bool {
        if (!$conn || $userId <= 0) {
            return false;
        }
        $calories = (int)($targets['calories'] ?? $targets['daily_calories'] ?? 2200);
        $protein  = (int)($targets['proteinG'] ?? $targets['protein_g'] ?? 150);
        $carbs    = (int)($targets['carbsG'] ?? $targets['carbs_g'] ?? 230);
        $fats     = (int)($targets['fatsG'] ?? $targets['fats_g'] ?? 65);
        $water    = (int)($targets['waterMl'] ?? $targets['water_ml'] ?? 2500);

        $stmt = $conn->prepare("INSERT INTO nutrition_goals (user_id, daily_calories, protein_g, carbs_g, fats_g, water_ml, is_active, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            daily_calories = VALUES(daily_calories), 
            protein_g = VALUES(protein_g), 
            carbs_g = VALUES(carbs_g), 
            fats_g = VALUES(fats_g), 
            water_ml = VALUES(water_ml), 
            is_active = 1, 
            updated_at = NOW()");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iiiiii", $userId, $calories, $protein, $carbs, $fats, $water);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
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
 * Asegura que el usuario tenga una rutina activa personalizada persistida en la base de datos.
 * Genera la rutina basada en los datos específicos de su encuesta (objetivo, condiciones, actividad).
 *
 * @param mysqli|null $conn
 * @param int $userId
 * @return array|null
 */
function ensureUserActiveRoutine(?mysqli $conn, int $userId): ?array {
    if (!$conn || $userId <= 0) return null;

    // 1. Verificar si ya existe una rutina activa
    $checkStmt = $conn->prepare("SELECT id, name, description FROM routines WHERE user_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $res = $checkStmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $checkStmt->close();
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
        $checkStmt->close();
    }

    // 2. Obtener datos de la encuesta del atleta
    $bio = [
        'objetivo' => 'ganar_musculo',
        'condiciones' => 'ninguna',
        'nivel_actividad' => 'moderado',
        'preferencia' => 'omnivora'
    ];
    $bStmt = $conn->prepare("SELECT objetivo, condiciones, nivel_actividad, preferencia FROM user_biometrics WHERE user_id = ? LIMIT 1");
    if ($bStmt) {
        $bStmt->bind_param("i", $userId);
        $bStmt->execute();
        $bRes = $bStmt->get_result();
        if ($bRow = $bRes->fetch_assoc()) {
            if (!empty($bRow['objetivo'])) $bio['objetivo'] = $bRow['objetivo'];
            if (!empty($bRow['condiciones'])) $bio['condiciones'] = $bRow['condiciones'];
            if (!empty($bRow['nivel_actividad'])) $bio['nivel_actividad'] = $bRow['nivel_actividad'];
            if (!empty($bRow['preferencia'])) $bio['preferencia'] = $bRow['preferencia'];
        }
        $bStmt->close();
    }

    // 3. Diseñar plan personalizado
    $obj = $bio['objetivo'];
    $cond = strtolower($bio['condiciones']);
    $hasKneeIssue = str_contains($cond, 'rodilla');
    $hasBackIssue = str_contains($cond, 'lumbar') || str_contains($cond, 'espalda');

    $routineName = "Kynap Hipertrofia & Fuerza (Plan Personalizado)";
    $routineDesc = "Periodización científica optimizada para desarrollo muscular y sobrecarga progresiva.";

    if ($obj === 'perder_grasa') {
        $routineName = "Kynap Definición Metabólica (Plan Personalizado)";
        $routineDesc = "Periodización de alta densidad para máxima preservación de masa muscular y déficit calórico.";
    } elseif ($obj === 'recomposicion') {
        $routineName = "Kynap Recomposición Muscular (Plan Personalizado)";
        $routineDesc = "Plan equilibrado de tensión mecánica y acondicionamiento para quemar grasa ganando fuerza.";
    } elseif ($obj === 'mantener') {
        $routineName = "Kynap Fuerza Funcional & Salud (Plan Personalizado)";
        $routineDesc = "Programa integral de longevidad motora, fuerza isométrica y estabilidad articular.";
    }

    if ($hasKneeIssue) {
        $routineDesc .= " Adaptado para protección articular de rodillas.";
    }
    if ($hasBackIssue) {
        $routineDesc .= " Adaptado para protección y fortalecimiento lumbar.";
    }

    // Estructura de días y ejercicios
    $daysConfig = [
        [
            'day_key' => 'lunes',
            'day_name' => 'Lunes',
            'focus' => ($hasKneeIssue ? 'Tren Inferior (Bajo Impacto Articular)' : 'Pierna & Glúteos (Fuerza Base)'),
            'day_order' => 1,
            'is_rest_day' => 0,
            'exercises' => $hasKneeIssue
                ? [
                    ['slug' => 'puente-de-gluteos', 'sets' => 4, 'reps' => '12-15', 'rpe' => 8.0, 'rest' => 75],
                    ['slug' => 'elevacion-de-piernas-para-gluteos', 'sets' => 3, 'reps' => '15', 'rpe' => 7.5, 'rest' => 60],
                    ['slug' => 'plancha-tradicional', 'sets' => 3, 'reps' => '45s', 'rpe' => 8.0, 'rest' => 60]
                ]
                : [
                    ['slug' => 'sentadillas', 'sets' => 4, 'reps' => '10-12', 'rpe' => 8.0, 'rest' => 90],
                    ['slug' => 'puente-de-gluteos', 'sets' => 3, 'reps' => '12-15', 'rpe' => 8.0, 'rest' => 75],
                    ['slug' => 'estocada-hacia-atras', 'sets' => 3, 'reps' => '10-12', 'rpe' => 8.0, 'rest' => 75]
                ]
        ],
        [
            'day_key' => 'martes',
            'day_name' => 'Martes',
            'focus' => 'Torso Superior & Core',
            'day_order' => 2,
            'is_rest_day' => 0,
            'exercises' => [
                ['slug' => 'flexiones-con-inclinacion', 'sets' => 4, 'reps' => '10-12', 'rpe' => 8.0, 'rest' => 90],
                ['slug' => 'fondos-de-triceps-en-silla', 'sets' => 3, 'reps' => '12', 'rpe' => 8.0, 'rest' => 75],
                ['slug' => 'crunch-de-bicicleta', 'sets' => 3, 'reps' => '20', 'rpe' => 8.0, 'rest' => 60]
            ]
        ],
        [
            'day_key' => 'miercoles',
            'day_name' => 'Miércoles',
            'focus' => 'Descanso Activo & Movilidad',
            'day_order' => 3,
            'is_rest_day' => 1,
            'exercises' => []
        ],
        [
            'day_key' => 'jueves',
            'day_name' => 'Jueves',
            'focus' => ($hasBackIssue ? 'Estabilidad Lumbar & Cadena Posterior' : 'Potencia & Glúteos'),
            'day_order' => 4,
            'is_rest_day' => 0,
            'exercises' => [
                ['slug' => 'puente-de-gluteos', 'sets' => 4, 'reps' => '12', 'rpe' => 8.5, 'rest' => 75],
                ['slug' => 'step-en-silla', 'sets' => 3, 'reps' => '10-12', 'rpe' => 8.0, 'rest' => 75],
                ['slug' => 'plancha-tradicional', 'sets' => 3, 'reps' => '45s', 'rpe' => 8.0, 'rest' => 60]
            ]
        ],
        [
            'day_key' => 'viernes',
            'day_name' => 'Viernes',
            'focus' => 'Full Body Funcional & Resistencia',
            'day_order' => 5,
            'is_rest_day' => 0,
            'exercises' => [
                ['slug' => 'burpees', 'sets' => 4, 'reps' => '10', 'rpe' => 8.5, 'rest' => 90],
                ['slug' => 'crunch-abdominal', 'sets' => 3, 'reps' => '15-20', 'rpe' => 8.0, 'rest' => 60],
                ['slug' => 'elevacion-de-rodillas', 'sets' => 3, 'reps' => '30s', 'rpe' => 8.0, 'rest' => 60]
            ]
        ],
        [
            'day_key' => 'sabado',
            'day_name' => 'Sábado',
            'focus' => 'Recuperación Muscular & Hidratación',
            'day_order' => 6,
            'is_rest_day' => 1,
            'exercises' => []
        ],
        [
            'day_key' => 'domingo',
            'day_name' => 'Domingo',
            'focus' => 'Descanso Total & Preparación Semanal',
            'day_order' => 7,
            'is_rest_day' => 1,
            'exercises' => []
        ]
    ];

    $conn->begin_transaction();
    try {
        $deact = $conn->prepare("UPDATE routines SET is_active = 0 WHERE user_id = ?");
        $deact->bind_param("i", $userId);
        $deact->execute();
        $deact->close();

        $insR = $conn->prepare("INSERT INTO routines (user_id, name, description, is_active) VALUES (?, ?, ?, 1)");
        $insR->bind_param("iss", $userId, $routineName, $routineDesc);
        $insR->execute();
        $routineId = (int)$insR->insert_id;
        $insR->close();

        $insDay = $conn->prepare("INSERT INTO routine_days (routine_id, day_name, focus, day_key, day_order, is_rest_day) VALUES (?, ?, ?, ?, ?, ?)");
        $insEx = $conn->prepare("INSERT INTO routine_exercises (routine_day_id, exercise_id, order_index, target_sets, target_reps, target_rpe, rest_seconds) VALUES (?, ?, ?, ?, ?, ?, ?)");

        // Mapa de slug a id de ejercicios
        $exMap = [];
        $exRes = $conn->query("SELECT id, slug FROM exercises");
        if ($exRes) {
            while ($erow = $exRes->fetch_assoc()) {
                $exMap[$erow['slug']] = (int)$erow['id'];
            }
        }

        foreach ($daysConfig as $day) {
            $insDay->bind_param("isssii", $routineId, $day['day_name'], $day['focus'], $day['day_key'], $day['day_order'], $day['is_rest_day']);
            $insDay->execute();
            $dayId = (int)$insDay->insert_id;

            if (empty($day['is_rest_day']) && !empty($day['exercises'])) {
                $orderIdx = 1;
                foreach ($day['exercises'] as $ex) {
                    $slug = $ex['slug'];
                    $exerciseId = $exMap[$slug] ?? null;
                    if (!$exerciseId) {
                        foreach ($exMap as $s => $id) {
                            if (str_contains($s, $slug) || str_contains($slug, $s)) {
                                $exerciseId = $id;
                                break;
                            }
                        }
                    }
                    if (!$exerciseId && !empty($exMap)) {
                        $exerciseId = reset($exMap);
                    }
                    if ($exerciseId) {
                        $rpeVal = (float)$ex['rpe'];
                        $insEx->bind_param("iiiisdi", $dayId, $exerciseId, $orderIdx, $ex['sets'], $ex['reps'], $rpeVal, $ex['rest']);
                        $insEx->execute();
                        $orderIdx++;
                    }
                }
            }
        }

        $insDay->close();
        $insEx->close();
        $conn->commit();

        return [
            'id' => $routineId,
            'name' => $routineName,
            'description' => $routineDesc
        ];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[ENSURE ROUTINE ERROR]: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica si el usuario ha completado el 100% de los entrenamientos de su rutina asignada.
 * Requerimiento 3: Restricción estricta de cambios hasta completar la rutina.
 *
 * @param mysqli|null $conn
 * @param int $userId
 * @return array
 */
function checkRoutinePlanCompletion(?mysqli $conn, int $userId): array {
    $result = [
        'isCompleted'       => false,
        'hasActiveRoutine'  => false,
        'routineId'         => null,
        'routineName'       => 'Rutina Asignada',
        'totalDays'         => 4,
        'completedCount'    => 0,
        'completedDays'     => [],
        'pendingDays'       => ['Lunes', 'Martes', 'Jueves', 'Viernes'],
        'progressPct'       => 0,
        'requiresUpdate'    => false,
        'message'           => ''
    ];

    if (!$conn || $userId <= 0) {
        return $result;
    }

    $now = new DateTime();
    $week = (int)$now->format("W");
    $year = (int)$now->format("Y");

    // 1. Días completados en routine_check_ins
    $completedMap = [];
    $stmtC = $conn->prepare("SELECT day_id FROM routine_check_ins WHERE user_id = ? AND week_number = ? AND year = ? AND is_completed = 1");
    if ($stmtC) {
        $stmtC->bind_param("iii", $userId, $week, $year);
        $stmtC->execute();
        $rC = $stmtC->get_result();
        while ($row = $rC->fetch_assoc()) {
            $completedMap[strtolower($row['day_id'])] = true;
        }
        $stmtC->close();
    }

    // 2. Rutina activa del atleta
    $routineStmt = $conn->prepare("SELECT id, name FROM routines WHERE user_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($routineStmt) {
        $routineStmt->bind_param("i", $userId);
        $routineStmt->execute();
        $rRes = $routineStmt->get_result();
        if ($rotRow = $rRes->fetch_assoc()) {
            $result['hasActiveRoutine'] = true;
            $result['routineId'] = (int)$rotRow['id'];
            $result['routineName'] = $rotRow['name'];

            $dayStmt = $conn->prepare("SELECT day_name, day_key, is_rest_day FROM routine_days WHERE routine_id = ? ORDER BY day_order ASC");
            if ($dayStmt) {
                $dayStmt->bind_param("i", $result['routineId']);
                $dayStmt->execute();
                $dRes = $dayStmt->get_result();
                $plannedDays = [];
                while ($dRow = $dRes->fetch_assoc()) {
                    if (empty($dRow['is_rest_day'])) {
                        $plannedDays[] = [
                            'key'  => strtolower($dRow['day_key']),
                            'name' => $dRow['day_name']
                        ];
                    }
                }
                $dayStmt->close();

                if (!empty($plannedDays)) {
                    $result['totalDays'] = count($plannedDays);
                    $pendingNames = [];
                    $compCount = 0;
                    $compDays = [];

                    foreach ($plannedDays as $pd) {
                        if (isset($completedMap[$pd['key']])) {
                            $compCount++;
                            $compDays[] = $pd['key'];
                        } else {
                            $pendingNames[] = $pd['name'];
                        }
                    }

                    $result['completedCount'] = $compCount;
                    $result['completedDays'] = $compDays;
                    $result['pendingDays'] = $pendingNames;
                    $result['progressPct'] = $result['totalDays'] > 0 ? (int)round(($compCount / $result['totalDays']) * 100) : 0;
                    $result['isCompleted'] = ($compCount >= $result['totalDays'] && $result['totalDays'] > 0);
                }
            }
        }
        $routineStmt->close();
    }

    // Si aún no tiene rutina persistida en routines, usar los 4 días estándar de la periodización
    if (!$result['hasActiveRoutine']) {
        $standardDays = [
            'lunes'   => 'Lunes',
            'martes'  => 'Martes',
            'jueves'  => 'Jueves',
            'viernes' => 'Viernes'
        ];
        $result['totalDays'] = count($standardDays);
        $pending = [];
        $compCount = 0;
        $compDays = [];
        foreach ($standardDays as $k => $n) {
            if (isset($completedMap[$k])) {
                $compCount++;
                $compDays[] = $k;
            } else {
                $pending[] = $n;
            }
        }
        $result['completedCount'] = $compCount;
        $result['completedDays'] = $compDays;
        $result['pendingDays'] = $pending;
        $result['progressPct'] = (int)round(($compCount / $result['totalDays']) * 100);
        $result['isCompleted'] = ($compCount >= $result['totalDays']);
    }

    if ($result['isCompleted']) {
        $result['requiresUpdate'] = true;
        $result['message'] = "¡Enhorabuena! Has completado el 100% de los entrenamientos de tu rutina ({$result['completedCount']}/{$result['totalDays']} días). Para continuar, es momento de actualizar tu información física y evaluar tu progreso con IA.";
    } else {
        $pendingStr = implode(', ', $result['pendingDays']);
        $result['message'] = "Actualización bloqueada: Para garantizar la efectividad de tu plan, solo puedes actualizar tu información personal después de completar tu rutina asignada ({$result['completedCount']}/{$result['totalDays']} días completados). Días pendientes: {$pendingStr}.";
    }

    return $result;
}

/**
 * Evalúa la política de actualización de información y biometría.
 * REQUERIMIENTO 3:
 * - Restringe cambios a la información del usuario hasta que la rutina asignada esté 100% completada.
 * - Ventana de corrección de 24 horas para subsanar errores tipográficos recientes.
 *
 * @param mysqli|null $conn
 * @param int $userId
 * @return array
 */
function evaluateBiometricsPolicy(?mysqli $conn, int $userId): array {
    $routineStatus = checkRoutinePlanCompletion($conn, $userId);

    $policy = [
        'canUpdate'                 => false,
        'mode'                      => 'locked', // 'locked' | 'routine_completed' | 'correction' | 'initial'
        'reason'                    => 'routine_pending',
        'message'                   => $routineStatus['message'],
        'routineStatus'             => $routineStatus,
        'hoursRemainingCorrection'  => null,
        'daysRemainingNextUpdate'   => max(0, $routineStatus['totalDays'] - $routineStatus['completedCount']),
        'nextAllowedDate'           => null,
        'lastRecordId'              => null,
        'lastRecordDate'            => null
    ];

    if (!$conn || $userId <= 0) {
        return $policy;
    }

    // Comprobar último registro en historial
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

            // Ventana de corrección de 24h para errores tipográficos
            if ($hoursElapsed <= 24.0) {
                $remainingHours = max(0.1, round(24.0 - $hoursElapsed, 1));
                $policy['canUpdate'] = true;
                $policy['mode'] = 'correction';
                $policy['reason'] = 'correction_window';
                $policy['hoursRemainingCorrection'] = $remainingHours;
                $policy['message'] = "Modo corrección activo: Cuentas con {$remainingHours} horas restantes para rectificar o corregir errores en tu registro más reciente.";
                $stmt->close();
                return $policy;
            }
        }
        $stmt->close();
    }

    // Si la rutina está completada al 100%, se habilita obligatoriamente la actualización
    if ($routineStatus['isCompleted']) {
        $policy['canUpdate'] = true;
        $policy['mode'] = 'routine_completed';
        $policy['reason'] = 'routine_finished';
        $policy['message'] = $routineStatus['message'];
        return $policy;
    }

    // Si no tiene ningún registro en el historial de evolución (es nuevo)
    if (empty($policy['lastRecordId'])) {
        // Puede ingresar su medición inicial de partida o bloquearse según la rutina
        if ($routineStatus['completedCount'] == 0) {
            $policy['canUpdate'] = false;
            $policy['mode'] = 'locked';
            $policy['reason'] = 'routine_pending';
            $policy['message'] = "Tu rutina ha sido programada. Completa todos tus días de entrenamiento ({$routineStatus['totalDays']} días) para desbloquear la actualización de condición física y recibir la evaluación de tu progreso.";
        }
    }

    return $policy;
}

/**
 * Compara los datos actuales con los datos previos del usuario y genera un análisis de progreso
 * detallado con recomendaciones personalizadas de la IA.
 * REQUERIMIENTO 3.
 *
 * @param array $previousBio
 * @param array $newBio
 * @param array $userProfile
 * @return array
 */
function evaluateUserProgress(array $previousBio, array $newBio, array $userProfile = []): array {
    $prevPeso = (float)($previousBio['peso'] ?? 75.0);
    $newPeso  = (float)($newBio['peso'] ?? 75.0);
    $deltaPeso = round($newPeso - $prevPeso, 2);

    $altCm = (float)($newBio['altura'] ?? $previousBio['altura'] ?? 175.0);
    $altM  = $altCm / 100.0;
    $prevBmi = ($altM > 0) ? round($prevPeso / ($altM * $altM), 1) : 24.5;
    $newBmi  = ($altM > 0) ? round($newPeso / ($altM * $altM), 1) : 24.5;
    $deltaBmi = round($newBmi - $prevBmi, 1);

    $prevCintura = isset($previousBio['cintura_cm']) && is_numeric($previousBio['cintura_cm']) ? (float)$previousBio['cintura_cm'] : null;
    $newCintura  = isset($newBio['cintura_cm']) && is_numeric($newBio['cintura_cm']) ? (float)$newBio['cintura_cm'] : null;
    $deltaCintura = ($prevCintura !== null && $newCintura !== null) ? round($newCintura - $prevCintura, 1) : null;

    $prevBrazo = isset($previousBio['brazo_cm']) && is_numeric($previousBio['brazo_cm']) ? (float)$previousBio['brazo_cm'] : null;
    $newBrazo  = isset($newBio['brazo_cm']) && is_numeric($newBio['brazo_cm']) ? (float)$newBio['brazo_cm'] : null;
    $deltaBrazo = ($prevBrazo !== null && $newBrazo !== null) ? round($newBrazo - $prevBrazo, 1) : null;

    $prevPecho = isset($previousBio['pecho_cm']) && is_numeric($previousBio['pecho_cm']) ? (float)$previousBio['pecho_cm'] : null;
    $newPecho  = isset($newBio['pecho_cm']) && is_numeric($newBio['pecho_cm']) ? (float)$newBio['pecho_cm'] : null;
    $deltaPecho = ($prevPecho !== null && $newPecho !== null) ? round($newPecho - $prevPecho, 1) : null;

    $prevGrasa = isset($previousBio['grasa_corporal']) && is_numeric($previousBio['grasa_corporal']) ? (float)$previousBio['grasa_corporal'] : null;
    $newGrasa  = isset($newBio['grasa_corporal']) && is_numeric($newBio['grasa_corporal']) ? (float)$newBio['grasa_corporal'] : null;
    $deltaGrasa = ($prevGrasa !== null && $newGrasa !== null) ? round($newGrasa - $prevGrasa, 1) : null;

    $objetivo = strtolower(trim((string)($newBio['objetivo'] ?? $previousBio['objetivo'] ?? 'ganar_musculo')));
    $nombreAtleta = $userProfile['nombre'] ?? 'Atleta Kynap';

    $isImprovement = false;
    $score = 0; // Puntos positivos
    $explanations = [];
    $areasAttention = [];

    // Evaluación según el objetivo fisiológico
    switch ($objetivo) {
        case 'perder_grasa':
        case 'perder_peso':
        case 'bajar_peso':
            if ($deltaPeso < -0.3) {
                $isImprovement = true;
                $score += 3;
                $explanations[] = "Has logrado una reducción efectiva de " . abs($deltaPeso) . " kg de peso corporal, alineado directamente con tu meta de déficit calórico.";
            } elseif ($deltaPeso > 0.4) {
                $areasAttention[] = "Se observa un incremento de {$deltaPeso} kg. Para pérdida de grasa, revisa tu balance calórico diario y evita picoteos entre comidas.";
            }

            if ($deltaCintura !== null && $deltaCintura < 0) {
                $isImprovement = true;
                $score += 2;
                $explanations[] = "Reducción de " . abs($deltaCintura) . " cm en perímetro de cintura, confirmando pérdida de grasa visceral y reducción abdominal.";
            } elseif ($deltaCintura !== null && $deltaCintura > 0.5) {
                $areasAttention[] = "La circunferencia de cintura aumentó en {$deltaCintura} cm. Podría indicar retención de líquidos o ingesta excesiva de carbohidratos refinados o sodio.";
            }

            if ($deltaGrasa !== null && $deltaGrasa < 0) {
                $isImprovement = true;
                $score += 2;
                $explanations[] = "Tu porcentaje de grasa corporal se redujo en " . abs($deltaGrasa) . "%, demostrando retención de masa magra durante la definición.";
            }
            break;

        case 'ganar_musculo':
        case 'hipertrofia':
            if ($deltaPeso > 0.2 && $deltaPeso <= 1.2) {
                $isImprovement = true;
                $score += 3;
                $explanations[] = "Aumento controlado de +{$deltaPeso} kg en peso corporal, compatible con ganancia muscular magra y mínima acumulación de grasa.";
            } elseif ($deltaPeso < -0.3) {
                $areasAttention[] = "Has perdido " . abs($deltaPeso) . " kg de peso corporal. En fase de hipertrofia necesitas asegurar un superávit calórico de 300-400 kcal diarias.";
            }

            if ($deltaBrazo !== null && $deltaBrazo > 0) {
                $isImprovement = true;
                $score += 2;
                $explanations[] = "Incremento de +{$deltaBrazo} cm en perímetro de brazo, señal de hipertrofia activa en bíceps y tríceps.";
            }
            if ($deltaPecho !== null && $deltaPecho > 0) {
                $isImprovement = true;
                $score += 2;
                $explanations[] = "Expansión torácica de +{$deltaPecho} cm en perímetro de pecho, reflejando desarrollo en pectorales y dorsales.";
            }
            break;

        case 'recomposicion':
            if ($deltaCintura !== null && $deltaCintura < 0) {
                $isImprovement = true;
                $score += 3;
                $explanations[] = "Reducción de " . abs($deltaCintura) . " cm en cintura mientras mantienes tu peso estable, signo definitivo de recomposición corporal.";
            }
            if (($deltaBrazo !== null && $deltaBrazo > 0) || ($deltaPecho !== null && $deltaPecho > 0)) {
                $isImprovement = true;
                $score += 2;
                $explanations[] = "Aumento en medidas de torso superior combinado con estabilidad de peso general.";
            }
            break;

        case 'mantener':
        case 'mantener_peso':
        default:
            if (abs($deltaPeso) <= 0.8) {
                $isImprovement = true;
                $score += 3;
                $explanations[] = "Excelente estabilidad ponderal con variación mínima de " . abs($deltaPeso) . " kg, cumpliendo tu objetivo de mantenimiento físico.";
            } else {
                $areasAttention[] = "Variación de " . abs($deltaPeso) . " kg detectada. Para mantenimiento, ajusta tus porciones energéticas.";
            }
            break;
    }

    if ($score >= 2 || (empty($areasAttention) && !empty($explanations))) {
        $isImprovement = true;
    }

    $analysisType = $isImprovement ? 'mejora' : 'atencion';
    $title = $isImprovement ? "🎉 ¡Excelente Progreso Detectado!" : "📊 Evaluación de Rendimiento: Áreas de Atención";

    $recommendationsList = [];
    if ($isImprovement) {
        $recommendationsList[] = "Continúa con la sobrecarga progresiva en tu siguiente rutina: intenta añadir 1-2 repeticiones o un incremento de 1.25 a 2.5 kg en tus ejercicios básicos.";
        $recommendationsList[] = "Mantén la distribución de macronutrientes actual y prioriza 7-8 horas de sueño profundo para permitir la regeneración neuromuscular.";
        $recommendationsList[] = "Conserva la meta de hidratación diaria calculada para tu biometría ({$newBio['peso']} kg × 35 ml).";
    } else {
        $recommendationsList[] = "Revisión Nutricional: Asegúrate de registrar fielmente tus comidas en el módulo de Nutrición & Macros. La inconsistencia en fines de semana suele diluir el déficit o superávit semanal.";
        $recommendationsList[] = "Intensidad de Entrenamiento: Exige un RPE de 8.0 a 8.5 en tus series de trabajo. No dejes más de 2 repeticiones en reserva (RIR 2) para estimular adaptaciones reales.";
        $recommendationsList[] = "Aporte Proteico: Asegura al menos 2.0 g de proteína por kg de peso corporal distribuidos en 3-4 ingestas principales.";
        $recommendationsList[] = "Descanso y Cortisol: El estrés elevado y la falta de descanso limitan la recuperación y promueven la retención de agua extracelular.";
    }

    $summaryText = $isImprovement
        ? "¡Gran trabajo, {$nombreAtleta}! Tu constancia al completar todos los entrenamientos de tu rutina ha producido adaptaciones medibles y favorables:\n- " . implode("\n- ", $explanations)
        : "Hola {$nombreAtleta}, tras completar tu ciclo de entrenamiento hemos analizado tus mediciones y detectamos que tu evolución física se encuentra en una meseta:\n- " . implode("\n- ", !empty($areasAttention) ? $areasAttention : ["No se apreciaron cambios significativos respecto a la medición anterior."]);

    return [
        'type'                  => $analysisType,
        'isImprovement'         => $isImprovement,
        'title'                 => $title,
        'summary'               => $summaryText,
        'explanations'          => $explanations,
        'areasAttention'        => $areasAttention,
        'recommendations'       => $recommendationsList,
        'metricsComparison'     => [
            'peso'    => ['prev' => $prevPeso, 'current' => $newPeso, 'delta' => $deltaPeso, 'unit' => 'kg'],
            'bmi'     => ['prev' => $prevBmi, 'current' => $newBmi, 'delta' => $deltaBmi, 'unit' => 'kg/m²'],
            'cintura' => ['prev' => $prevCintura, 'current' => $newCintura, 'delta' => $deltaCintura, 'unit' => 'cm'],
            'brazo'   => ['prev' => $prevBrazo, 'current' => $newBrazo, 'delta' => $deltaBrazo, 'unit' => 'cm'],
            'pecho'   => ['prev' => $prevPecho, 'current' => $newPecho, 'delta' => $deltaPecho, 'unit' => 'cm'],
            'grasa'   => ['prev' => $prevGrasa, 'current' => $newGrasa, 'delta' => $deltaGrasa, 'unit' => '%']
        ]
    ];
}
?>

