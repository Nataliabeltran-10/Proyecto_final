<?php
session_start();
$rutaBase = '../';



// Solo participantes pueden acceder
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'participante') {
    header("Location: {$rutaBase}login/login.php");
    exit;
}

$error = isset($_GET['error']) && $_GET['error'] === 'nombre_repetido';

$rutaFondo = "{$rutaBase}fotos/fondo.jpg";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= $rutaBase ?>fotos/logo.png" type="image/png">
    <title>AndaRally</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="<?= $rutaBase ?>header/style.css">
</head>
    <?php require_once("{$rutaBase}header/header.php");?>
    <body data-fondo="<?= $rutaFondo ?>">

    <div class="container">
        <h1>¿En qué concurso quieres participar?</h1>
        <div class="cuadrados-container">
            <a href="<?= $rutaBase ?>concursos/concurso_lugares.php" class="cuadro">
                <img src="<?= $rutaBase ?>fotos/concurso_1.jpg" alt="Lugares Bonitos">
                <span class="title">Lugares Bonitos</span>
            </a>
            <a href="<?= $rutaBase ?>concursos/concurso_tradiciones.php" class="cuadro">
                <img src="<?= $rutaBase ?>fotos/concurso_2.jpg" alt="Tradiciones">
                <span class="title">Tradiciones</span>
            </a>
        </div>
    </div>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast("Ya existe una foto con este título.");
        });
    </script>
<?php endif; ?>


    <script>
        // Fondo dinámico
        const fondo = document.body.getAttribute('data-fondo');
        if (fondo) {
            document.body.style.background = `url('${fondo}') no-repeat center center fixed`;
            document.body.style.backgroundSize = 'cover';
        }
    </script>
</body>
</html>
