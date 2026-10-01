<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - PROFILE UPDATE BRIDGE (update_profile.php)
 * ==============================================================================
 * Controlador puente para actualización de perfil compatible con peticiones
 * directas POST. Redirige limpiamente a configuration.php.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

require_once __DIR__ . '/controllers/api_bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $imagenUser = trim($_POST['imagen_user'] ?? 'iconos/profile.svg');
    $password = (string)($_POST['password'] ?? '');

    if (!empty($nombre) && $email && $dbAvailable) {
        if (!empty($password) && strlen($password) >= 6) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre, $email, $imagenUser, $hashed, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET nombre = ?, email = ?, imagen_user = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $email, $imagenUser, $userId);
        }

        if ($stmt && $stmt->execute()) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            $_SESSION['imagen_user'] = $imagenUser;
            $stmt->close();
        }
    }
}

header('Location: configuration.php');
exit();
