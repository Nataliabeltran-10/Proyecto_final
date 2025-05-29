<?php
session_start();
$rutaBase = '../';
require_once("conexion.php");

// Verificar sesión y rol
$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_rol = $_SESSION['usuario_rol'] ?? null;

// Verifica que el usuario este autenticado y tenga rol de participante
if (!$usuario_id || $usuario_rol !== 'participante') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No autorizado']);
        http_response_code(403);
        exit;
    } else {
        header("Location: {$rutaBase}index.php");
        exit;
    }
}

// Maneja la e
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_id'])) {
    $borrar_id = $_POST['borrar_id'];

    // Verificar que la imagen pertenece al usuario actual
    $verificar_sql = "SELECT id FROM fotos WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($verificar_sql);
    $stmt->execute([$borrar_id, $usuario_id]);

    if ($stmt->fetch()) {
        $delete_sql = "DELETE FROM fotos WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->execute([$borrar_id]);

        // Si es AJAX, devolver JSON y no redirigir
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header("Location: gestion_participante.php");
            exit;
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Imagen no encontrada o no autorizada']);
            http_response_code(404);
            exit;
        } else {
            header("Location: gestion_participante.php");
            exit;
        }
    }
}

// Consultar imágenes del usuario
$sql = "SELECT id, titulo_imagen, imagen, estado FROM fotos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>AndaRally</title>
  <link rel="icon" href="<?= $rutaBase ?>fotos/logo.png" type="image/png">
  <link rel="stylesheet" href="<?= $rutaBase ?>header/style.css">
  <link rel="stylesheet" href="style_gestion.css">
</head>
<body data-fondo="<?= $rutaBase ?>fotos/fondo.jpg">
  <?php include("{$rutaBase}header/header.php"); ?>

  <main class="gestion-participante-container">
    <h2>Estado Imágenes</h2>

    <!--
      Muestra una galería de imágenes del usuario.
      Si no hay imágenes, muestra un mensaje indicando que no se han subido aún.
    -->
    <div class="galeria">
      <?php if (empty($imagenes)): ?>
        <p class="sin-imagenes">No has subido imágenes aún.</p>
      <?php else: ?>
        <?php foreach ($imagenes as $img): ?>
          <?php
            $base64 = base64_encode($img['imagen']);
            $mimeType = 'image/jpeg';
          ?>
          <div class="imagen-carta" id="imagen-carta-<?= $img['id'] ?>">
            <img src="data:<?= $mimeType ?>;base64,<?= $base64 ?>" alt="<?= htmlspecialchars($img['titulo_imagen']) ?>" />
            <p class="titulo-imagen"><?= htmlspecialchars($img['titulo_imagen']) ?></p>
            <span class="estado <?= strtolower($img['estado']) ?>">
              <?= ucfirst($img['estado']) ?>
            </span>
            <button class="boton-borrar" onclick="confirmarBorrado(<?= $img['id'] ?>)">🗑 Borrar</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <!-- Modal de confirmación -->
  <div id="overlay-confirmacion" class="overlay-confirmacion" style="display:none;">
    <div class="modal-confirmacion">
      <p>¿Estás seguro de que quieres borrar esta foto?</p>
      <div class="botones">
        <button id="btn-confirmar-borrado" class="btn-naranja">Sí, borrar</button>
        <button id="btn-cancelar-borrado" class="btn-gris">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- Formulario oculto para enviar borrado -->
  <form id="form-borrar" method="POST" style="display: none;">
    <input type="hidden" name="borrar_id" id="borrar_id">
  </form>

  <script>
    const fondo = document.body.getAttribute('data-fondo');
    if (fondo) {
      document.body.style.background = `url('${fondo}') no-repeat center center fixed`;
      document.body.style.backgroundSize = 'cover';
    }

    let borrarId = null;

    function confirmarBorrado(id) {
      borrarId = id;
      const overlay = document.getElementById('overlay-confirmacion');
      overlay.style.display = 'flex';
    }

    document.getElementById('btn-confirmar-borrado').addEventListener('click', () => {
      if (borrarId === null) return;

      // Enviar el borrado con fetch POST
      fetch('', { 
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest' // para detectar AJAX en PHP
        },
        body: `borrar_id=${encodeURIComponent(borrarId)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Eliminar la tarjeta del DOM
          const card = document.getElementById(`imagen-carta-${borrarId}`);
          if (card) card.remove();
        } else {
          alert(data.error || 'Error al borrar la imagen');
        }
      })
      .catch(() => alert('Error de comunicación con el servidor'))
      .finally(() => {
        borrarId = null;
        document.getElementById('overlay-confirmacion').style.display = 'none';
      });
    });

    document.getElementById('btn-cancelar-borrado').addEventListener('click', () => {
      borrarId = null;
      document.getElementById('overlay-confirmacion').style.display = 'none';
    });
  </script>
</body>
</html>
