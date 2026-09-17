<?php
session_start(); 

if (!isset($_SESSION['nombre'])) {
    header('Location: login.php'); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario Nutricional</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
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


.form-container {
    background-color: #0056b3;
    padding: 50px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 100%;     
    max-width: 500px; 
    color: white;
    position: relative;
    box-sizing: border-box;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

.form-style {
    display: flex;
    flex-direction: column;
}

label {
    margin-top: 10px;
    font-weight: bold;
}



.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

button {
    background-color: #ffcc00;
    color: black;
    font-size: 18px;
    font-weight: bold;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 20px;
    transition: background 0.3s ease;
}

button:hover {
    background-color: #e6b800;
}

@media (max-width: 360px) {
    .form-container {
        padding: 30px 20px;
    }
    button {
        font-size: 16px;
        padding: 8px;
    }
}

@media (min-width: 768px) {
    .form-container {
        padding: 60px 80px;
        max-width: 700px;
    }

    .form-style input,
    .form-style select,
    .form-style textarea {
        font-size: 18px;
    }

    button {
        font-size: 20px;
        padding: 12px;
    }
}

.choices__inner {
  background: #ffffff;     
  color: #000;
  border: none;
  border-radius: 5px;
  padding: 10px 12px;
  font-size: 16px;
  min-height: auto;       
}
.choices[data-type*="select-one"] .choices__inner:after {
  border-color: #000 transparent transparent;
  top: 45%;  
}
.choices__list--dropdown,
.choices__list[aria-expanded="true"] {
  background:rgb(255, 255, 255); 
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0,0,0,.25);
  padding: 6px 0;
}
.choices__item--choice {
  color: #fff; 
  padding: 8px 12px;
}
.choices__item--choice.is-highlighted,
.choices__item--choice.is-selected {
  background:rgb(255, 255, 255); 
  color: #000;
}
.choices__item--selectable {
  background: rgb(255, 255, 255);
  color: #000;
  border-radius: 4px;
  margin-right: 6px;
}
.choices__inner:focus {
  outline: none;
  box-shadow: 0 0 0 2px #ffcc0080;
}
.choices {
  width: 100%;
}

    </style>
</head>
<body>
    <div class="form-container">
        <h2>Información Nutricional</h2>
        <form action="formulario_registro.php" method="POST" class="form-style">
            <label for="edad">Edad:</label>
            <input style="height: 35px" type="number" id="edad" name="edad" step="0.1" required>
        
            <label for="peso">Peso (kg):</label>
            <input style="height: 35px" type="number" id="peso" name="peso" step="0.1" required>

            <label for="altura">Altura (cm):</label>
            <input style="height: 35px" type="number" id="altura" name="altura" step="0.1" required>

            <label for="objetivo">Objetivo:</label>
            <select id="objetivo" name="objetivo" required>
                <option value="" disabled selected>Seleccione su objetivo</option>
                <option value="perder_peso">Perder peso</option>
                <option value="mantener_peso">Mantener peso</option>
                <option value="ganar_musculo">Ganar masa muscular</option>
            </select>

            <label for="nivel_actividad">Nivel de Actividad Física:</label>
            <select id="nivel_actividad" name="nivel_actividad" required>
                <option value="" disabled selected>Seleccione su nivel</option>
                <option value="sedentario">Sedentario</option>
                <option value="ligero">Ligero (1-3 veces por semana)</option>
                <option value="moderado">Moderado (4-5 veces por semana)</option>
                <option value="activo">Activo (Diariamente)</option>
            </select>

            <label for="sexo">Sexo:</label>
            <select id="sexo" name="sexo" required>
                <option value="" disabled selected>Seleccione su sexo</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
            </select>

            <label>Condiciones Médicas:</label>
            <div class="checkbox-group">
                <label><input type="checkbox" name="condiciones[]" value="diabetes"> Diabetes</label>
                <label><input type="checkbox" name="condiciones[]" value="hipertension"> Hipertensión</label>
                <label><input type="checkbox" name="condiciones[]" value="colesterol"> Colesterol alto</label>
            </div>

            <label for="alergias">Alergias Alimentarias:</label>
            <input style="height: 35px" type="text" id="alergias" name="alergias" placeholder="Ej: Maní, Mariscos, Lácteos">

            <label for="preferencia">Preferencias Alimentarias:</label>
            <select id="preferencia" name="preferencia">
                <option value="cualquierCosa">Cualquier Cosa</option>
                <option value="vegetariano">Vegetariano</option>
                <option value="vegano">Vegano</option>
            </select>

            <label for="agua">¿Cuánta agua bebes al día? (litros)</label>
            <input style="height: 35px" type="number" id="agua" name="agua" step="0.1">

            <button type="submit">Guardar</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
      new Choices(select, {
        searchEnabled: false, 
        itemSelectText: '',  
        shouldSort: false,  
        position: 'bottom', 
        removeItemButton: false 
      });
    });
  });
</script>

</body>
</html>
