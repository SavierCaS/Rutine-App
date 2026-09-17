<?php
session_start(); 

// Verifica si la sesión del usuario está activa
if (!isset($_SESSION['nombre'])) {
    header('Location: login.php'); 
    exit();
}

include('kynap_db.php');

// Obtener el nombre del usuario y formatearlo
if (isset($_SESSION['nombre'])) {
    $user_name = $_SESSION['nombre'];  
    $user_name = ucwords(strtolower($user_name));
} else {
    $user_name = 'Usuario';  
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutinas Kynap</title>
    <link rel="stylesheet" href="rutinas/rutinas.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>  <!-- FullCalendar JS si es necesario más tarde -->
</head>
<body>
    <div class="sidebar">
        <h2 class="logo"><span class="kynap">Kynap</span> <span class="ai">AI</span></h2>
        <hr class="divider">
        <nav>
            <ul>
                <li><img src="iconos/home.svg" class="icon"> Inicio</li>
                <li><img src="iconos/dieta.svg" class="icon"> Mi Dieta</li>
                <li class="active"><img src="iconos/progreso.svg" class="icon"> Progresos</li>
                <hr class="divider">
                <li><img src="iconos/settings.svg" class="icon"> Configuración</li>
                <li class="logout-item">
                    <a href="logout.php" class="logout-link">
                        <img src="iconos/close.svg" class="icon"> Cerrar sesión
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <input type="text" class="search-bar" placeholder="Buscar...">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span> 
                <img src="iconos/profile.svg" alt="Perfil" class="user-avatar">
            </div>
        </header>

<section class="dashboard">
  <h1 class="welcome-text">Mi Rutina Semanal</h1>

  <div class="week-routine">
    <!-- Ejemplo para cada día -->
    <div class="day-card">
      <h3>Lunes</h3>
      <p class="routine-title">Piernas + Glúteos</p>
      <p class="routine-time">Hora sugerida: 7:00 AM</p>
      <ul class="exercise-list">
        <li>Sentadillas - 4x12</li>
        <li>Prensa - 3x10</li>
        <li>Hip Thrust - 4x15</li>
      </ul>
      <button class="mark-done" onclick="toggleDone(this)">✓ Completado</button>
    </div>

    <div class="day-card">
      <h3>Martes</h3>
      <p class="routine-title">Cardio + Abdomen</p>
      <p class="routine-time">Hora sugerida: 6:30 AM</p>
      <ul class="exercise-list">
        <li>Cinta 30min</li>
        <li>Crunches - 3x20</li>
        <li>Planchas - 3x1min</li>
      </ul>
     <button class="mark-done" onclick="toggleDone(this)">✓ Completado</button>

    </div>

    <!-- Puedes continuar con los demás días -->
  </div>
</section>

<div class="progress-container">
  <label>Progreso semanal:</label>
  <div class="progress-bar">
    <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
  </div>
</div>

  
</div>


    </div>

    <script src="script.js"></script>
</body>
</html>
