<?php
    require_once("conexion.php");
    session_start();

    $rutaBase = '../';
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $usuario_rol = $_SESSION['usuario_rol'] ?? null;
    $concursoNombre = 'Lugares';
    $hoy = new DateTime();

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Verifica que la solicitud sea por método POST 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        // Verifica que el usuario esté logueado; si no , envía un error y termina la ejecución 
        if (!$usuario_id) {
            echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para votar.']);
            exit;
        }

        // Concierte los datos a entero 
        $foto_id = intval($_POST['foto_id']);
        $puntuacion = intval($_POST['puntuacion']);

        // Consulta la foto correspondiente para verificar que existe
        $stmtFoto = $conn->prepare("SELECT * FROM fotos WHERE id = ?");
        $stmtFoto->execute([$foto_id]);
        $foto = $stmtFoto->fetch();

        // Si la foto no existe devuelve error
        if (!$foto) {
            echo json_encode(['success' => false, 'message' => 'Foto no encontrada.']);
            exit;
        }

        // Evita que el usuario vote por su propia foto 
        if ($foto['usuario_id'] == $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'No puedes votar por tu propia foto.']);
            exit;
        }

        // Obtener las fechas de inicio y fin del consurso asiciado a la foto 
        $stmt = $conn->prepare("SELECT c.fecha_inicio, c.fecha_fin 
                                FROM fotos f 
                                JOIN concursos c ON LOWER(f.concurso) = LOWER(c.nombre) 
                                WHERE f.id = ?");
        $stmt->execute([$foto_id]);
        $datosConcurso = $stmt->fetch();

        if (!$datosConcurso) {
            echo json_encode(['success' => false, 'message' => 'Concurso no encontrado.']);
            exit;
        }

        $fechaInicio = new DateTime($datosConcurso['fecha_inicio']);
        $fechaFin = new DateTime($datosConcurso['fecha_fin']);

        // Verifica que la fecha actual esta dentro del periodo de votación 
        if ($hoy < $fechaInicio || $hoy > $fechaFin) {
            echo json_encode(['success' => false, 'message' => 'El periodo de votación ha finalizado o aún no ha comenzado.']);
            exit;
        }

        // Consulta si el usuario ha votado ya antes 
        $stmtCheck = $conn->prepare("SELECT * FROM votos WHERE foto_id = ? AND usuario_id = ?");
        $stmtCheck->execute([$foto_id, $usuario_id]);
        $votoExistente = $stmtCheck->fetch();

        // Si ya voto le devuelve el mensaje 
        if ($votoExistente) {
            echo json_encode(['success' => false, 'message' => 'Ya has votado esta foto anteriormente.']);
        } else {
            // Si no ha votado lo inserta en la base de datos 
            $stmtInsert = $conn->prepare("INSERT INTO votos (foto_id, usuario_id, puntuacion) VALUES (?, ?, ?)");
            $stmtInsert->execute([$foto_id, $usuario_id, $puntuacion]);
            echo json_encode(['success' => true, 'message' => 'Gracias por dejar tu voto.']);
        }
        exit;
    }

    // Después de POST
    require_once('../header/header.php');

    $stmtConcurso = $conn->prepare("SELECT * FROM concursos WHERE LOWER(nombre) = LOWER(?)");
    $stmtConcurso->execute([$concursoNombre]);
    $concurso = $stmtConcurso->fetch();

    $concursoActivo = false;
    // Verifica si la fecha esta dentro del periodo activo 
    if ($concurso) {
        $fechaInicio = new DateTime($concurso['fecha_inicio']);
        $fechaFin = new DateTime($concurso['fecha_fin']);
        $concursoActivo = $hoy >= $fechaInicio && $hoy <= $fechaFin;
    }

    // Obtiene todas las fotos admitidas
    $stmt = $conn->prepare("SELECT * FROM fotos WHERE estado = 'admitida' AND LOWER(concurso) = LOWER(?)");
    $stmt->execute([$concursoNombre]);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <title>AndaRally</title>
    <link rel="icon" href="../fotos/logo.png" type="image/png">
    <link rel="stylesheet" href="style.css?v=1.0">
    <link rel="stylesheet" href="../header/style.css?v=1.0">

    <h2>Galería de Fotos: Concurso "Lugares"</h2>

    <div id="mensaje-dinamico" class="mensaje-error animar-mensaje" style="display: none;"></div>

    <!-- Contenedor que muestra la foto admitida del concurso -->
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
    document.querySelectorAll('.estrellas').forEach(contenedor => {
        const estrellas = contenedor.querySelectorAll('.estrella');
        const input = document.getElementById('puntuacion-' + contenedor.dataset.id);
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
    });

    // Maneja el envío del formulario de votación mediante AJAX para evitar recarga de página,
    // muestra mensajes dinámicos de éxito o error y controla la visibilidad de estos mensajes.
    document.querySelectorAll('.votacion-form').forEach(form => {
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

