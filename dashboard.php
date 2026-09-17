<?php
session_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activar_chat']) && isset($_SESSION['user_id'])) {
    include('kynap_db.php');
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE accounts SET chat_activado = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
    exit();
}
if (!isset($_SESSION['nombre'])) {
    header('Location: login.php'); 
    exit();
}

include('kynap_db.php');

if (isset($_SESSION['nombre'])) {
    $user_name = $_SESSION['nombre'];  
    $user_name = ucwords(strtolower($user_name));

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];  
        $stmt_chat = $conn->prepare("SELECT chat_activado FROM accounts WHERE id = ?");
        $stmt_chat->bind_param("i", $user_id);
        $stmt_chat->execute();
        $stmt_chat->bind_result($chat_activado);
        $stmt_chat->fetch();
        $stmt_chat->close();

        if (!isset($chat_activado)) {
            $chat_activado = 0;
        }
        $stmt_mensajes = $conn->prepare("SELECT mensajes_enviados FROM accounts WHERE id = ?");
        $stmt_mensajes->bind_param("i", $user_id);
        $stmt_mensajes->execute();
        $stmt_mensajes->bind_result($mensajes_enviados);
        $stmt_mensajes->fetch();
        $stmt_mensajes->close();
        if ($mensajes_enviados >= 5) {
            $limite_mensajes = true; 
        } else {
            $limite_mensajes = false;
        }
        $sql = "SELECT objetivo, nivel_actividad, agua, peso, altura FROM usuarios WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_objetivo = $row['objetivo'];  
            $user_nivel_actividad = $row['nivel_actividad'];  
            $user_agua = $row['agua'];  
            $user_peso = $row['peso'];  
            $user_altura = $row['altura'];
            $user_edad = $row['edad'];  
        } else {
            $user_objetivo = 'No definido';
            $user_nivel_actividad = 'No definido';
            $user_agua = 'No definido';
            $user_peso = 'No definido';
            $user_altura = 'No definido';
            $user_edad = 'No definido';
        }
        $stmt->close();

    } else {
        $user_name = 'Usuario';  
        $user_objetivo = 'No definido';  
        $user_nivel_actividad = 'No definido';  
        $user_agua = 'No definido';  
        $user_peso = 'No definido';  
        $user_altura = 'No definido';
        $user_edad = 'No definido';  
    }
} else {
    $user_name = 'Usuario';  
    $user_objetivo = 'No definido';  
    $user_nivel_actividad = 'No definido';  
    $user_agua = 'No definido';  
    $user_peso = 'No definido';  
    $user_altura = 'No definido';
    $user_edad = 'No definido';  
}

function formatearObjetivo($objetivo) {
    switch ($objetivo) {
        case 'mantener_peso':
            return 'Mantener peso';
        case 'bajar_peso':
            return 'Bajar de peso';
        case 'ganar_musculo':
            return 'Ganar músculo';
        case 'mejorar_resistencia':
            return 'Mejorar resistencia';
        default:
            return ucfirst(str_replace('_', ' ', $objetivo)); 
    }
}

function formatearNivelActividad($nivel) {
    $niveles = [
        'sedentario' => 'Sedentario',
        'ligero' => 'Ligero',
        'moderado' => 'Moderado',
        'intenso' => 'Intenso'
    ];
    return isset($niveles[$nivel]) ? $niveles[$nivel] : ucfirst($nivel);
}
$query = "SELECT nombre, imagen_user FROM accounts WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$user_name = $user['nombre'];
$user_icon = $user['imagen_user']; 

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kynap</title>
    <link rel="stylesheet" href="style2-1.css">
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />  
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>   
        <script src="calendar-init.js"></script>
</head>
<body>
<button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="sidebar">
        <h2 class="logo"><span class="kynap">Kynap</span> <span class="ai">AI</span></h2>
        <hr class="divider">
        <nav>
            <ul>
                <li class="active"><img src="iconos/home.svg" class="icon"> Inicio</li>
                <hr class="divider">
                <li onclick="window.location.href='configuration.php'"><img src="iconos/settings.svg" class="icon"> Configuración</li>
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
            <div class="user-info" onclick="window.location.href='configuration.php'">
              <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
               <img 
                src="<?php echo $user_icon ? htmlspecialchars($user_icon) : 'iconos/profile.svg'; ?>" 
                alt="Perfil" 
                class="user-avatar"
                onerror="this.src='iconos/profile.svg'" 
    >
</div>

        </header>

        <section class="dashboard">
            <h1 class="welcome-text">Bienvenido, <span id="username"><?php echo htmlspecialchars($user_name); ?></span></h1>
            
            <div class="cards">
                 <div class="card">
                 <h3>Objetivo 🏆</h3>
                 <p class="value good"><span id="objetivo"><?php echo formatearObjetivo(htmlspecialchars($user_objetivo)); ?></span></p>
                 <button class="btn" onclick="getRecommendationsFromPanel('objetivo')">Ver recomendaciones</button>
                  </div>

                  <div class="card">
                   <h3>Nivel de actividad 💪</h3>
                   <p class="value"><?php echo formatearNivelActividad(htmlspecialchars($user_nivel_actividad)); ?></p>
                   <button class="btn" onclick="getRecommendationsFromPanel('actividad')">Ver recomendaciones</button>
                  </div>

               <div class="card">
                    <h3>Vasos de agua al dia (L) 💧</h3>
                    <p class="value"><?php echo htmlspecialchars($user_agua); ?> L</p>
                    <button class="btn" onclick="getRecommendationsFromPanel('agua')">Ver recomendaciones</button>
               </div>

                <div class="card">
                    <h3>Peso</h3>
                    <p class="value"><?php echo htmlspecialchars($user_peso); ?> Kg</p>
                    <button class="btn" onclick="getRecommendationsFromPanel('peso')">Ver recomendaciones</button>
                </div>

            </div>
            <div class="panel-row">
    <div class="chat-panel">
        <h2>Asistente Kynap AI 🤖</h2>
        <div id="chat-box" class="chat-box"></div>

        <div class="chat-input">
            <input type="text" id="user-message" placeholder="Escribe tu mensaje...">
              <button id="sendButton">Enviar</button>  
        </div>

        <?php if ($chat_activado == 0): ?>
            <div class="chat-overlay" id="chatOverlay">
                <div class="overlay-content">
                    <p>Probar chat con IA</p>
                    <button onclick="enableChat()">Activar</button>

                </div>
            </div>
        <?php endif; ?>
    </div>
        <div class="calendar-panel">
        <h2>Rutina Semanal 📅</h2>
        <div id="calendar" style="height: 320px;"></div>
    <div id="routine-section">
        <div id="routine-button">Rutina de esta semana</div>
        <div id="routine-text">
            <p>No tienes rutinas asignadas para esta semana.</p>
        </div>
    </div>
        </div>
    <div class="mente-sana-panel">
  <h2>🧘 Mente Sana</h2>
  <div class="placeholder" id="mentePlaceholder" onclick="iniciarRespiracion()">Haz clic para comenzar</div>
  <div class="mensaje" id="mensajeGuia"></div>
  <select id="tipoRespiracion" class="select-style" onchange="cambiarRutina()">
    <option value="4-4-4" selected>4-4-4 Equilibrio</option>
    <option value="4-7-8">4-7-8 Dormir</option>
    <option value="2-2">2-2 Energizante</option>
    <option value="5-5">5-5 Relajante</option>
  </select>
  <div class="control-buttons">
    <button onclick="iniciarRespiracion()">Iniciar</button>
    <button onclick="pausarRespiracion()">Pausar</button>
  </div>
  <p>Ejercicios de meditación y respiración guiada con visual y sonido.</p>
  <audio id="breathAudio" preload="auto"></audio>
</div>
        </section>
    </div>
    <div id="userData" 
    data-objetivo="<?php echo htmlspecialchars($user_objetivo); ?>" 
    data-actividad="<?php echo htmlspecialchars($user_nivel_actividad); ?>" 
    data-agua="<?php echo htmlspecialchars($user_agua); ?>" 
    data-peso="<?php echo htmlspecialchars($user_peso); ?>"
    data-edad="<?php echo htmlspecialchars($user_edad); ?>" 
    data-altura="<?php echo htmlspecialchars($user_altura); ?>"
    data-limite-mensajes="<?php echo $limite_mensajes ? 'true' : 'false'; ?>"
    data-user-id="<?php echo $user_id; ?>">
    </div>
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('sidebar-open');
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="objetivo_reco.js"></script>
 <script src="chat_kynap_ai.js"></script>
  <script src="mente_sana.js"></script>
</body>
</html>
