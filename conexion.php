<?php
$conexion = mysqli_connect("PMYSQL182.dns-servicio.com", "Rally", "PR0ye(tOR@ly", "10868095_RallyAndaluz");


if (!$conexion) {
  die("Error al conectar con la base de datos: " . mysqli_connect_error());
}
?>
