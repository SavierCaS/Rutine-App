<?php
session_start(); 

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

        
       
       
    }
}
$user_name = $_SESSION['nombre'];

$mensaje = 'Lo sentimos, pero en este momento Kynap está pasando por problemas. Lo solucionaremos rapidamente 😉';
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
    <title>Chat de Nutrición</title>
    <link rel="stylesheet" href="chat_styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
 
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

        <section class="chat">
            <div class="chat-container">
        <div class="chat-box" id="chat-box">
        <div class="chat-message ai-message"><strong>Kynap AI:</strong> <span id="kynap_mensaje"></span></div>
        </div>
        <div class="input-container">
            <button class="back-button" onclick="backToDashboard()">Volver</button>
            <button class="download-button" onclick="descargarPDF()">Descargar PDF</button>
        </div>
    </div>

    <script>

        const kynap_mensaje = sessionStorage.getItem('kynap_mensaje');

         document.getElementById('kynap_mensaje').innerText = kynap_mensaje || 'Lo sentimos, pero en este momento Kynap no quiere responder. Lo solucionaremos rapidamente 😉';

        function backToDashboard() { 
            sessionStorage.removeItem('kynap_mensaje');
            window.location.href = 'dashboard.php'; 
        }
        
        async function descargarPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        let mensaje = document.getElementById('kynap_mensaje').innerText || 'No hay recomendaciones disponibles.';

        mensaje = mensaje
         .normalize("NFD")
         .replace(/[\u0300-\u036f]/g, "")
         .replace(/[^a-zA-Z0-9.,;:¡!¿?()\s]/g, "");

         doc.setFontSize(16);
         doc.text('Recomendación de Kynap AI', 20, 20);
         doc.setFontSize(12);
         doc.text(mensaje, 20, 40, { maxWidth: 170 });

        doc.save('KynapAI_Recomendacion.pdf');
}
    </script>
        </section>
    <div id="userData" 
    data-objetivo="<?php echo htmlspecialchars($user_objetivo); ?>" 
    data-actividad="<?php echo htmlspecialchars($user_nivel_actividad); ?>" 
    data-agua="<?php echo htmlspecialchars($user_agua); ?>" 
    data-peso="<?php echo htmlspecialchars($user_peso); ?>" 
    data-altura="<?php echo htmlspecialchars($user_altura); ?>"
    data-edad="<?php echo htmlspecialchars($user_edad); ?>">
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
