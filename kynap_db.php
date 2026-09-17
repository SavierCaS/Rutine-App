<?php
$host = "localhost"; 
$usuario = "root";  
$password = "123456789";     
$dbname = "kynap_db"; 

$conn = new mysqli($host, $usuario, $password, $dbname);

if ($conn->connect_error) {
    die("La conexión ha fallado: " . $conn->connect_error);
}
?>
