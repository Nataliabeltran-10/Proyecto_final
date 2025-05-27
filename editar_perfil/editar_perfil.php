<?php
require_once("conexion.php");
session_start();
$rutaBase = '../';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// Obtener los datos actuales del usuario
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el formulario ha sido enviado, actualizar los datos del usuario
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

// Incluimos el header solo después de cualquier posible redirección
require_once("{$rutaBase}header/header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Perfil</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../header/style.css">
</head>
<body>
  <h2>Editar Perfil</h2>
  
  <form method="POST">
    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

    <input type="submit" value="Actualizar">
  </form>

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
