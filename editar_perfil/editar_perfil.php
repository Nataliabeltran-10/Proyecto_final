<?php
require_once("conexion.php");
session_start();
$rutaBase = '../';

// Verifica si el usuario esta logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// Obtiene los datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Formulario para actualizar nombre y email del usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    
    if ($nombre !== '' && $email !== '') {
        $sqlUpdate = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([$nombre, $email, $usuario_id]);

        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_email'] = $email;

        header("Location: ../index.php");
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>AndaRally</title>
  <link rel="icon" href="../fotos/logo.png" type="image/png">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../header/style.css">
</head>
<body>
  <?php require_once("{$rutaBase}header/header.php"); ?>
  <h2>Editar Perfil</h2>
  
  <!-- Formulario para que el usuario actualice su nombre y correo electronico -->
  <form method="POST">
    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

    <input type="submit" value="Actualizar">
  </form>

  <!-- Asigna atributo 'data-fondo' del body la ruta de la imagen de fondo -->
  <script>
    document.body.setAttribute('data-fondo', '../fotos/fondo.jpg');
    const fondo = document.body.getAttribute('data-fondo');
    if (fondo) {
      document.body.style.background = `url('${fondo}') no-repeat center center fixed`;
      document.body.style.backgroundSize = 'cover';
    }
  </script>
</body>
</html>
