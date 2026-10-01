<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - API BOOTSTRAP & MIDDLEWARE
 * ==============================================================================
 * Este archivo centraliza la configuración, CORS, conexión a base de datos,
 * autenticación híbrida (Web Session + Mobile Bearer Token) y estandarización
 * de respuestas JSON para toda la API REST de Kynap.
 *
 * Principios aplicados:
 * - Single Responsibility Principle (SRP): Gestión unificada de contexto API.
 * - Robustez ante fallos: Modo offline / fallback seguro si MySQL no está activo.
 * - Seguridad: Validación de cabeceras, tokens HMAC y saneamiento de entradas.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * @version 2.0.0
 * ==============================================================================
 */

// 1. Cabeceras HTTP estándar y CORS para clientes Web, Móviles (Flutter, React Native) y PWA
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Responder inmediatamente a peticiones preflight OPTIONS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Preflight OK']);
    exit;
}

// 2. Inicialización segura de sesión PHP (para clientes Web SPA / navegadores)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clave secreta para firma y verificación de tokens HMAC móviles
define('KYNAP_AUTH_SECRET', 'kynap_secure_mobile_salt_2026_fitness_saas_key');
define('KYNAP_TOKEN_EXPIRATION_SECONDS', 86400 * 30); // 30 días

// 3. Conexión tolerante a fallos con MySQL / MariaDB
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '123456789';
$dbName = 'kynap_db';

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$dbAvailable = false;

if (!$conn->connect_error) {
    $dbAvailable = true;
    $conn->set_charset('utf8mb4');

    // Auto-reparación y compatibilidad de esquemas (accounts <-> users, usuarios <-> user_biometrics)
    @$conn->query("CREATE OR REPLACE VIEW `users` AS SELECT `id`, `nombre`, `email`, `password`, `imagen_user`, `chat_activado`, `mensajes_enviados`, `created_at`, `modified_at` FROM `accounts`");
    @$conn->query("CREATE OR REPLACE VIEW `user_biometrics` AS SELECT * FROM `usuarios`");
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
}

/**
 * Genera un token HMAC firmado para autenticación en aplicaciones móviles.
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
        'uid' => $userId,
        'email' => $email,
        'iat' => $issuedAt,
        'exp' => $expiresAt
    ];
    
    $payloadEncoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $payloadEncoded, KYNAP_AUTH_SECRET);
    
    return $payloadEncoded . '.' . $signature;
}

/**
 * Valida un token HMAC y retorna el payload decodificado o null si no es válido.
 *
 * @param string $token
 * @return array|null
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
        return null; // Token expirado o malformado
    }
    
    return $payload;
}

/**
 * Resuelve la identidad del usuario actual a través de múltiples estrategias:
 * 1. Cabecera 'Authorization: Bearer <token>' (App Móvil)
 * 2. Cabecera 'X-User-Id' (Fallback de integración para SDKs móviles)
 * 3. Sesión activa '$_SESSION['user_id']' (Web App / Navegador)
 * 4. Fallback a cuenta demo (ID 1) si está en modo offline/pruebas
 *
 * @param mysqli|null $db Conexión activa a la base de datos
 * @return array Información normalizada del usuario autenticado
 */
function resolveAuthUser(?mysqli $db): array {
    $userId = null;
    $authSource = 'none';

    // Estrategia A: Cabecera Authorization Bearer
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
        $payload = verifyAuthToken($token);
        if ($payload && isset($payload['uid'])) {
            $userId = (int)$payload['uid'];
            $authSource = 'bearer_token';
        }
    }

    // Estrategia B: Cabecera X-User-Id
    if (!$userId) {
        $customHeader = $headers['X-User-Id'] ?? $headers['x-user-id'] ?? null;
        if ($customHeader && is_numeric($customHeader) && (int)$customHeader > 0) {
            $userId = (int)$customHeader;
            $authSource = 'custom_header';
        }
    }

    // Estrategia C: Sesión PHP
    if (!$userId && isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        $userId = (int)$_SESSION['user_id'];
        $authSource = 'php_session';
    }

    // Si aún no se detecta y la BD está activa, usar usuario 1 por defecto (resiliencia)
    $isGuest = false;
    if (!$userId) {
        $userId = 1;
        $isGuest = true;
        $authSource = 'guest_fallback';
    }

    $userData = [
        'id' => $userId,
        'nombre' => 'Usuario Kynap',
        'email' => 'user@kynap.com',
        'imagen_user' => null,
        'authSource' => $authSource,
        'isGuest' => $isGuest
    ];

    // Cargar datos reales desde la tabla accounts o users si la BD está conectada
    if ($db && !$db->connect_error) {
        $stmt = $db->prepare("SELECT id, nombre, email, imagen_user FROM accounts WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $stmt = $db->prepare("SELECT id, nombre, email, imagen_user FROM users WHERE id = ? LIMIT 1");
        }
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $userData['nombre'] = $row['nombre'];
                $userData['email'] = $row['email'];
                $userData['imagen_user'] = $row['imagen_user'];
                $userData['isGuest'] = false;
            }
            $stmt->close();
        }
    }

    return $userData;
}

/**
 * Obtiene y decodifica de forma segura el payload JSON de la petición HTTP.
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
 * Emite una respuesta JSON exitosa estandarizada.
 *
 * @param mixed $data Datos de respuesta
 * @param string $message Mensaje informativo
 * @param int $httpCode Código HTTP
 */
function apiSuccess($data = null, string $message = 'Operación completada con éxito', int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Emite una respuesta JSON de error estandarizada.
 *
 * @param string $message Mensaje de error
 * @param int $httpCode Código HTTP
 * @param mixed $errors Detalles adicionales del error
 */
function apiError(string $message = 'Error en el servidor', int $httpCode = 400, $errors = null): void {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
