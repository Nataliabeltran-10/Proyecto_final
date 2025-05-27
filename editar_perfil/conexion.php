<?php
// Configuración de la conexión a la base de datos
$host = "PMYSQL182.dns-servicio.com";  // Cambia si tu base de datos está en otro servidor
$dbname = "10868095_RallyAndaluz";  // Reemplaza con el nombre de tu base de datos
$username = "Rally";  // Usuario de la base de datos
$password = "PR0ye(tOR@ly";  // Contraseña (si tienes una)

// Intenta la conexión con PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Configurar para lanzar excepciones en caso de error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
