<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - REGISTRO CONTROLLER (LEGACY BRIDGE)
 * ==============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/api_bootstrap.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = sanitizeString($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');

    if (!$email || empty($nombre) || strlen($password) < 6) {
        header("Location: ../registro.php?error=invalid_data");
        exit();
    }

    if ($conn) {
        try {
            // Comprobar duplicado
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                header("Location: ../registro.php?error=email_exists");
                exit();
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("INSERT INTO users (nombre, email, password, imagen_user, chat_activado) VALUES (?, ?, ?, 'iconos/profile.svg', 1)");
            $stmt->execute([$nombre, $email, $hashed]);
            $newId = (int)$conn->lastInsertId();

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = $newId;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;

            header("Location: ../dashboard.php");
            exit();
        } catch (Exception $e) {
            error_log("[REGISTRO CONTROLLER ERROR]: " . $e->getMessage());
            header("Location: ../registro.php?error=server_error");
            exit();
        }
    } else {
        header("Location: ../registro.php?error=db_unavailable");
        exit();
    }
}
?>
