<?php
// Configuración de la conexión a la base de datos
$host = "PMYSQL182.dns-servicio.com";  
$dbname = "10868095_RallyAndaluz";  
$username = "Rally";  
$password = "PR0ye(tOR@ly";  

// Intenta la conexión con PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
