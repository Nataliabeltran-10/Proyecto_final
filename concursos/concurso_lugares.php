<?php
session_start();
$rutaBase = '../';

require_once("conexion.php");

// Solo participantes logueados pueden subir
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'participante') {
    header("Location: {$rutaBase}login/login.php");
    exit;
}

$nombreConcurso = 'lugares'; 
$hoy = date('Y-m-d H:i:s');
$errores = [];
$mensajeExito = "";
$rutaFondo = '../fotos/fondo.jpg';

// Obtener datos del concurso
$sqlConcurso = "SELECT * FROM concursos WHERE nombre = ?";
$stmt = $conn->prepare($sqlConcurso);
$stmt->execute([$nombreConcurso]);
$concurso = $stmt->fetch();

if (!$concurso) {
    $errores[] = "No se encontró el concurso.";
} else {
    if ($hoy < $concurso['fecha_inicio'] || $hoy > $concurso['fecha_fin']) {
        $errores[] = "El periodo para participar en el concurso ha finalizado o aún no ha comenzado.";
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errores)) {
    $titulo      = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);

    // Límite de fotos
    $sqlCount = "SELECT COUNT(*) FROM fotos WHERE usuario_id = ? AND concurso = ?";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute([$_SESSION['usuario_id'], $nombreConcurso]);
    $numFotos = $stmtCount->fetchColumn();
    if ($numFotos >= $concurso['limite_fotos']) {
        $errores[] = "No puede subir más de {$concurso['limite_fotos']} fotos.";
    }

    // Duplicado de título
    $sqlCheck = "SELECT 1 FROM fotos WHERE titulo_imagen = ? AND usuario_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$titulo, $_SESSION['usuario_id']]);
    if ($stmtCheck->fetch()) {
        $errores[] = "Ya existe una foto con este título.";
    }

    // Validar imagen
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Debes seleccionar una imagen válida.";
    } else {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName    = $_FILES['imagen']['name'];
        $fileSize    = $_FILES['imagen']['size'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $permitidos  = array_map('trim', explode(',', $concurso['formatos_permitidos']));

        if (!in_array($fileExt, $permitidos)) {
            $errores[] = "Formato no permitido. Usa: " . implode(', ', $permitidos);
        }
        if ($fileSize > $concurso['tamano_maximo']) {
            $errores[] = "La imagen excede " . round($concurso['tamano_maximo']/1048576,2) . " MB.";
        }
    }

    // Insertar si no hay errores
    if (empty($errores)) {
        $imagenData = file_get_contents($fileTmpPath);
        $sql = "INSERT INTO fotos (usuario_id, imagen, descripcion, titulo_imagen, concurso)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_SESSION['usuario_id'],
            $imagenData,
            $descripcion,
            $titulo,
            $nombreConcurso
        ]);
        $mensajeExito = "Tu participación ha sido enviada y está pendiente de revisión.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>AndaRally</title>
  <link rel="icon" href="../fotos/logo.png" type="image/png">
  <!-- CSS del header (incluye toast-error) -->
  <link rel="stylesheet" href="<?= $rutaBase ?>header/style.css">
  <!-- tu propio CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body data-fondo="<?= $rutaFondo ?>">

  <!-- INCLUYES EL HEADER (y con él el <div id="toast-error">) -->
  <?php require_once "{$rutaBase}header/header.php"; ?>

  <h2>Sube tu foto de “Lugares Bonitos”</h2>

  <div class="form-container">
    
    <form action="concurso_lugares.php" method="POST" enctype="multipart/form-data">
      <label for="titulo">Título de la imagen:</label>
      <input type="text" id="titulo" name="titulo" required>

      <label for="descripcion">Descripción:</label>
      <textarea id="descripcion" name="descripcion" required></textarea>

      <label for="imagen">Selecciona la imagen:</label>
      <input type="file" id="imagen" name="imagen" accept="image/*" required>

      <input type="submit" value="Subir">
    </form>
  </div>

  <script>
    // Aplica el fondo
    const fondo = document.body.getAttribute('data-fondo');
    if (fondo) {
      document.body.style.background = `url('${fondo}') no-repeat center center fixed`;
      document.body.style.backgroundSize = 'cover';
    }

    // Si hay errores PHP, los mostramos con showToast()
    <?php if (!empty($errores)): 
        // escapamos y convertimos a una sola línea
        $msg = implode(' | ', array_map('htmlspecialchars', $errores));
    ?>
      document.addEventListener('DOMContentLoaded', () => {
        showToast("<?= $msg ?>");
      });
    <?php endif; ?>

    // Si hay mensaje de éxito, también lo mostramos (reutilizando showToast)
    <?php if ($mensajeExito): ?>
      document.addEventListener('DOMContentLoaded', () => {
        showToast("<?= htmlspecialchars($mensajeExito) ?>");
      });
    <?php endif; ?>
  </script>

</body>
</html>
