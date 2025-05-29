<?php
require_once("conexion.php");

$rutaFondo = '../fotos/fondo.jpg';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoge datos del formulario 
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];
    $rol = $_POST['rol'];

    try {
        // Verifica si el nombre del usuario ya está registrado 
        $sql = "SELECT * FROM usuarios WHERE nombre = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre]);
        $usuarioExistente = $stmt->fetch();

        if ($usuarioExistente) {
            // Mostrar error si ya existe el usuario
            $errorMsg = "Ya existe un usuario con ese nombre. Por favor, elige otro.";
        } else {
            // Insertar nuevo usuario con contraseña cifrada
            $sqlInsert = "INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->execute([
                $nombre,
                $email,
                password_hash($contraseña, PASSWORD_DEFAULT),
                $rol
            ]);

            header("Location: ../login/login.php");
            exit;
        }
    } catch (PDOException $e) {
        $errorMsg = "Error al consultar la base de datos: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AndaRally</title>
    <link rel="icon" href="../fotos/logo.png" type="image/png">
    <link rel="stylesheet" href="style.css">
</head>
<body data-fondo="<?= $rutaFondo ?>">

    <?php if (!empty($errorMsg)): ?>
        <div class="toast-error visible" id="toast-error">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <h2>Formulario de Registro</h2>
    <form action="registroGeneral.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="contraseña">Contraseña:</label>
        <input type="password" id="contraseña" name="contraseña" required>

        <label for="rol">Tipo de usuario:</label>
        <select id="rol" name="rol" required>
            <option value="participante">Participante</option>
            <option value="usuario_normal">Usuario Normal</option>
        </select>

        <input type="submit" value="Registrar">
    </form>

    <script>
        // Fondo
        const fondo = document.body.getAttribute('data-fondo');
        if (fondo) {
            document.body.style.background = `url('${fondo}') no-repeat center center fixed`;
            document.body.style.backgroundSize = 'cover';
        }

        // Oculta el mensaje de error despues de segundos 
        const toast = document.getElementById('toast-error');
        if (toast) {
            setTimeout(() => {
                toast.classList.remove('visible');
            }, 4000);
        }
    </script>
</body>
</html>
