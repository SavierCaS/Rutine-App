<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - RUTINAS CONTROLLER & REDIRECT
 * ==============================================================================
 * Redirige de forma segura hacia el panel unificado de rutinas con sincronización
 * en tiempo real para evitar páginas huérfanas o desincronizadas.
 *
 * @author Senior Full-Stack Engineer & UI/UX Architect
 * ==============================================================================
 */

session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: login.php');
    exit();
}

header('Location: dashboard.php');
exit();
