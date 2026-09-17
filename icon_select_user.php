<?php
session_start();
include("kynap_db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $imagen_user = $_POST['imagen_user'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE accounts SET imagen_user = ? WHERE id = ?");
    $stmt->bind_param("si", $imagen_user, $user_id);

    if ($stmt->execute()) {
        $_SESSION['imagen_user'] = $imagen_user;
        header("Location: loading.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error al guardar la imagen. Intenta nuevamente.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecciona tu icono</title>
    <link rel="stylesheet" href="">
    <style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,rgb(252, 253, 253), #0056b3);
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    box-sizing: border-box;
    overflow-x: hidden;
}

.container{
  background:#fff;
  padding:20px;
  border-radius:10px;
  box-shadow:0 4px 8px rgba(0,0,0,.1);
  text-align:center;
  width:90%;
  max-width:600px;
}

h1{
  font-size:24px;
  margin-bottom:20px;
  color:#333;
}
.image-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(80px,1fr));
  gap:10px;
  margin-bottom:20px;
}

.image-grid label{
  display:block;
  position:relative;
}

.image-grid input[type=radio]{display:none;}

.image-grid img{
  width:100%;    
  aspect-ratio:1/1; 
  max-width:100px; 
  border-radius:8px;
  object-fit:cover;
  cursor:pointer;
  transition:transform .2s, box-shadow .2s;
}

.image-grid input[type=radio]:checked + img{
  transform:scale(1.05);
  box-shadow:0 0 0 4px #0072ff;
}
.image-grid input[type=radio]:focus-visible + img{
  box-shadow:0 0 0 4px #00c6ff;
}
.submit-btn{
  width:100%;
  max-width:200px; 
  margin-inline:auto;
  padding:10px 20px;
  background:#0072ff;
  color:#fff;
  border:none;
  border-radius:5px;
  cursor:pointer;
  font-size:16px;
  transition:background .15s;
}

.submit-btn:hover{background:#005bb5;}
@media (max-width:360px){
  .container{padding:12px;}
  h1{font-size:20px;}
  .submit-btn{
    font-size:14px;
    padding:8px 16px;
  }
}
</style>

</head>
<body>

    <div class="container">
        <h1>Selecciona tu icono</h1>
        <?php
        if (isset($_SESSION['error_message'])) {
            echo "<p style='color:red;'>".$_SESSION['error_message']."</p>";
            unset($_SESSION['error_message']);
        }
        ?>
        <form method="POST" action="icon_select_user.php">
            <div class="image-grid">
                <?php
                $iconos = [
                    'Girl.jpg', 'Boy.jpg', 'Hombre.jpg','Mujer.jpg', 'BlueMenAdmin.jpg',
                    'Men_Worked.jpg', 'WomenWorked.jpg', 'Old_Man.jpg', 'Old_Woman.jpg', 'MapacheAdm.jpg',
                    'Skull_Boy.jpg', 'Skull_Girl.jpg', 'King.jpg','Queen.jpg', 'Gygamen_Stone.jpg'
                ];

                foreach ($iconos as $icono) {
                    $path = "icon_users/{$icono}";
                    $checked = (isset($_SESSION['imagen_user']) && $_SESSION['imagen_user'] === $path) ? 'checked' : '';
                    echo "<label>";
                    echo   "<input type='radio' name='imagen_user' value='{$path}' {$checked} required>";
                    echo   "<img src='{$path}' alt='Icono'>";
                    echo "</label>";
                }
                ?>
            </div>
            <button type="submit" class="submit-btn">Guardar selección</button>
        </form>
    </div>

</body>
</html>
