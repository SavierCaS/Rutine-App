<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - SECURE MYSQLI CONNECTION BRIDGE
 * ==============================================================================
 * Centraliza la conexión mysqli para módulos tradicionales y chat AI.
 * Lee de forma segura de .env y silencia errores de conexión sensibles.
 *
 * @version 3.0.0
 * ==============================================================================
 */

if (!function_exists('kynap_get_env')) {
    function kynap_get_env(string $key, $default = null) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envFile = __DIR__ . '/.env';
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

$host     = kynap_get_env('DB_HOST', 'localhost');
$port     = (int)kynap_get_env('DB_PORT', 3306);
$usuario  = kynap_get_env('DB_USER', 'root');  
$password = kynap_get_env('DB_PASS', '123456789');     
$dbname   = kynap_get_env('DB_NAME', 'kynap_db'); 

$conn = @new mysqli($host, $usuario, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log("[KYNAP SECURE MYSQLI ERROR]: " . $conn->connect_error);
    // No exponer el error de base de datos directamente al cliente
} else {
    $conn->set_charset('utf8mb4');
}
?>
