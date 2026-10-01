<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - LOGOUT CONTROLLER
 * ==============================================================================
 * Destruye la sesión de usuario de forma segura tanto en el servidor (PHP Session)
 * como en el almacenamiento local del cliente (LocalStorage Bearer Token).
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

session_start();

// Destruir todas las variables de sesión
$_SESSION = [];

// Invalidar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cerrando sesión...</title>
</head>
<body style="background: #0a0d14; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh;">
  <p>Cerrando sesión de Kynap de forma segura...</p>
  <script>
    // Limpiar token de autenticación móvil/web local
    localStorage.removeItem('kynap_auth_token');
    window.location.href = 'login.php';
  </script>
</body>
</html>
