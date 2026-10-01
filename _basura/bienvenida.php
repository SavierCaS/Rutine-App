<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Kynap</title>
    <link rel="stylesheet" href="public/style.css">
   <style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}

body{
  background: linear-gradient(135deg,rgb(252, 253, 253), #0056b3);
  display:flex;
  justify-content:center;
  align-items:center;
  min-height:100vh;
  text-align:center;
}

.container{
  background: #0056b3;
  backdrop-filter:blur(10px);
  padding:clamp(24px,6vw,50px);
  border-radius:12px;
  box-shadow:0 4px 8px rgba(0,0,0,.25);
  color:#fff;
  width:90%;
  max-width:500px;
  animation:fadeUp .6s ease-out;
}

h1{
  font-size:clamp(1.6rem,2.5vw,2rem);
  margin-bottom:.5em;
  text-shadow:0 1px 2px rgba(0,0,0,.3);
}
p{
  font-size:clamp(1rem,2vw,1.2rem);
  margin-bottom:1.5em;
}

.buttons{
  display:flex;
  flex-direction:column;
  gap:15px;
}
.btn{
  display:inline-block;
  text-decoration:none;
  color:#000;
  background:#ffcc00;
  padding:12px;
  font-size:clamp(1rem,2.2vw,1.2rem);
  font-weight:bold;
  border-radius:8px;
  transition:background .25s,transform .1s;
}
.btn:hover{background:#e6b800;}
.btn:active{transform:scale(.97);}

@media(min-width:600px){
  .buttons{flex-direction:row;justify-content:center;}
  .btn{width:45%;text-align:center;}
}

@media(max-width:320px){
  .buttons{gap:10px;}
}
</style>

</head>
<body>
    <div class="container">
        <h1>Bienvenido a Kynap</h1>
        <p>Tu asesor personal de nutrición con inteligencia artificial</p>
        <div class="buttons">
            <a href="registro.php" class="btn">Registrarse</a>
            <a href="login.php" class="btn">Iniciar sesión</a>
        </div>
    </div>
</body>
</html>
