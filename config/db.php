<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - SECURE DATABASE CONFIGURATION (PDO)
 * ==============================================================================
 * Conexión centralizada con PDO para compatibilidad con módulos legacy y nuevos.
 * Lee de forma segura las credenciales desde .env y previene fugas de información.
 *
 * @version 3.0.0
 * ==============================================================================
 */

// Cargar variables de entorno si están disponibles
if (!function_exists('kynap_get_env')) {
    function kynap_get_env(string $key, $default = null) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
                    list($k, $v) = explode('=', $line, 2);
                    $envCache[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }
        return $envCache[$key] ?? $default;
    }
}

$host = kynap_get_env('DB_HOST', 'localhost');
$port = (int)kynap_get_env('DB_PORT', 3306);
$dbname = kynap_get_env('DB_NAME', 'kynap_db');
$user = kynap_get_env('DB_USER', 'root');
$password = kynap_get_env('DB_PASS', '123456789');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // Registro interno seguro sin exponer trazas al cliente
    error_log("[KYNAP SECURE DB ERROR]: Fallo al conectar con MySQL: " . $e->getMessage());
    $conn = null;
}
?>
