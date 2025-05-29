<?php
ob_start();
session_start();
require_once("conexion.php");
$rutaBase = '../';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_rol = $_SESSION['usuario_rol'] ?? null;

$concursoNombre = 'Tradiciones';

// Consulta el concurso Tradiciones
$sqlConcurso = "SELECT * FROM concursos WHERE LOWER(nombre) = LOWER(?)";
$stmtConcurso = $conn->prepare($sqlConcurso);
$stmtConcurso->execute([$concursoNombre]);
$concurso = $stmtConcurso->fetch();

$concursoActivo = false;
$ahora = new DateTime();

// Vereficar si el concurso esta activo
if ($concurso) {
    $fechaInicio = new DateTime($concurso['fecha_inicio']);
    $fechaFin = new DateTime($concurso['fecha_fin']);
    $concursoActivo = $ahora >= $fechaInicio && $ahora <= $fechaFin;
}

// Obtiene las fotos admitidas 
$sql = "SELECT * FROM fotos WHERE estado = 'admitida' AND LOWER(concurso) = LOWER(?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$concursoNombre]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar votación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Verifica si el usuario esta logueado 
    if (!$usuario_id) {
        $mensaje = 'Debes iniciar sesión para votar.';
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $mensaje]);
            exit;
        }
        $_SESSION['mensaje'] = $mensaje;
        header("Location: ../login/login.php");
        exit;
    }

    
    $foto_id = intval($_POST['foto_id']);
    $puntuacion = intval($_POST['puntuacion']);

    // Consulta base de datos información completa de la foto con el ID proporcionado 
    $stmtFoto = $conn->prepare("SELECT * FROM fotos WHERE id = ?");
    $stmtFoto->execute([$foto_id]);
    $foto = $stmtFoto->fetch();

    // Verifica que el usuario no esté intentando votar por su porpia foto 
    if ($foto['usuario_id'] == $usuario_id) {
        $mensaje = "No puedes votar por tu propia foto.";
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $mensaje]);
            exit;
        }
        $_SESSION['mensaje'] = $mensaje;
        header("Location: galeria_tradiciones.php");
        exit;
    }

    // Obtiene las fechas de inicio y fin del concurso al que pertenece la foto 
    $sqlConcursoFoto = "
        SELECT c.fecha_inicio, c.fecha_fin 
        FROM fotos f 
        JOIN concursos c ON LOWER(f.concurso) = LOWER(c.nombre) 
        WHERE f.id = ?";
    $stmt = $conn->prepare($sqlConcursoFoto);
    $stmt->execute([$foto_id]);
    $datosConcurso = $stmt->fetch();

   // Verifica si no se encuentra datos de concurso
    if (!$datosConcurso) {
        $mensaje = "No se pudo verificar el concurso de la foto.";
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $mensaje]);
            exit;
        }
        $_SESSION['mensaje'] = $mensaje;
        header("Location: galeria_tradiciones.php");
        exit;
    }

    // Crea un objeto con la fecha de inicio del concurso
    $fechaInicio = new DateTime($datosConcurso['fecha_inicio']);
    // Crea un objeto con la fecha de fin del concurso 
    $fechaFin = new DateTime($datosConcurso['fecha_fin']);
    // Crea un objeto con la fecha y hora actual 
    $ahora = new DateTime();

    // Verifica que el concurso activo
    if ($ahora < $fechaInicio || $ahora > $fechaFin) {
        $mensaje = "El periodo de votación ha finalizado o aún no ha comenzado.";
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $mensaje]);
            exit;
        }
        $_SESSION['mensaje'] = $mensaje;
        header("Location: galeria_tradiciones.php");
        exit;
    }

    // Verifica si el usuario ya ha botado la foto 
    $sqlCheck = "SELECT * FROM votos WHERE foto_id = ? AND usuario_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$foto_id, $usuario_id]);
    $votoExistente = $stmtCheck->fetch();

    // Si ha votado se actualiza la puntuación, si no ha botado se registra nuevo voto
    if ($votoExistente) {
        $sqlUpdate = "UPDATE votos SET puntuacion = ? WHERE foto_id = ? AND usuario_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([$puntuacion, $foto_id, $usuario_id]);
    } else {
        $sqlInsert = "INSERT INTO votos (foto_id, usuario_id, puntuacion) VALUES (?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([$foto_id, $usuario_id, $puntuacion]);
    }

    $mensaje = "Gracias por dejar tu voto.";

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => $mensaje]);
        exit;
    }

    $_SESSION['mensaje'] = $mensaje;
    header("Location: galeria_tradiciones.php");
    exit;
}

require_once('../header/header.php');

$mensajeAgradecimiento = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>AndaRally</title>
  <link rel="icon" href="../fotos/logo.png" type="image/png">
  <link rel="stylesheet" href="style.css?v=1.0">
  <link rel="stylesheet" href="../header/style.css?v=1.0">
</head>
<body>
<?php require_once('../header/header.php'); ?>

<h2>Galería de Fotos: Concurso "Tradiciones"</h2>

<!-- Muestra un mensaje de agradecimiento y prepara un contenedor oculto para mensajes dinámicos. -->
<?php if ($mensajeAgradecimiento): ?>
    <div class="mensaje-error animar-mensaje"><?= htmlspecialchars($mensajeAgradecimiento) ?></div>
<?php endif; ?>
<div id="mensaje-dinamico" class="mensaje-error animar-mensaje" style="display: none;"></div>


<!-- Contenedor de foto -->
<div class="galeria-container">
    <?php foreach ($fotos as $foto): ?>
        <div class="foto-carta">
            <?php
            $imagenBase64 = base64_encode($foto['imagen']);
            $titulo = htmlspecialchars($foto['titulo_imagen']);
            $descripcion = htmlspecialchars($foto['descripcion']);
            ?>
            <img src="data:image/jpeg;base64,<?= $imagenBase64 ?>" alt="<?= $titulo ?>">
            <h3><?= $titulo ?></h3>
            <p><?= $descripcion ?></p>
            <span class="concurso"><?= htmlspecialchars($foto['concurso']) ?></span>

            <!-- Si el concurso esta activo se muestra el formulario de votación -->
            <?php if ($concursoActivo): ?>
                <form class="votacion-form" method="POST">
                    <input type="hidden" name="foto_id" value="<?= $foto['id'] ?>">
                    <div class="estrellas" data-id="<?= $foto['id'] ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="estrella" data-valor="<?= $i ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="puntuacion" id="puntuacion-<?= $foto['id'] ?>" value="0">
                    <button type="submit" class="boton-enviar">Enviar</button>
                </form>
            <?php else: ?>
                <p class="aviso-cierre">Este concurso no acepta votos actualmente.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Tiempo para que desaparezca el mensaje 
    setTimeout(() => {
        const mensaje = document.querySelector('.mensaje-error');
        if (mensaje) mensaje.classList.remove('animar-mensaje');
    }, 4000);

    // Votación con estrella 
    document.querySelectorAll('.votacion-form').forEach(form => {
        const estrellas = form.querySelectorAll('.estrella');
        const input = form.querySelector('input[name="puntuacion"]');
        const fotoId = form.querySelector('input[name="foto_id"]').value;
        let puntuacion = 0;

    estrellas.forEach((estrella, index) => {
        estrella.addEventListener('mouseover', () => {
            estrellas.forEach((e, i) => {
                e.style.color = i <= index ? 'gold' : 'black';
            });
        });

        estrella.addEventListener('mouseout', () => {
            estrellas.forEach((e, i) => {
                e.style.color = i < puntuacion ? 'gold' : 'black';
            });
        });

        estrella.addEventListener('click', () => {
            puntuacion = index + 1;
            input.value = puntuacion;
        });
    });

    // Maneja el envío del formulario de votación mediante AJAX para evitar recarga de página,
    // muestra mensajes dinámicos de éxito o error y controla la visibilidad de estos mensajes.
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const mensajeDiv = document.getElementById('mensaje-dinamico');
            mensajeDiv.textContent = data.message;
            mensajeDiv.style.display = 'block';
            mensajeDiv.classList.add('animar-mensaje');

            setTimeout(() => {
                mensajeDiv.classList.remove('animar-mensaje');
                mensajeDiv.style.display = 'none';
            }, 4000);
        })
        .catch(() => {
            const mensajeDiv = document.getElementById('mensaje-dinamico');
            mensajeDiv.textContent = "Error al enviar el voto.";
            mensajeDiv.style.display = 'block';
            mensajeDiv.classList.add('animar-mensaje');

            setTimeout(() => {
                mensajeDiv.classList.remove('animar-mensaje');
                mensajeDiv.style.display = 'none';
            }, 4000);
        });
    });
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>
