<?php
session_start();
include 'conexion.php';
$rutaBase = ''; 


  $fechasConcursos = [
    'lugares' => ['inicio' => null, 'fin' => null],
    'tradiciones' => ['inicio' => null, 'fin' => null]
  ];

  // Obtener las fechas de inicio y fin del concurso 
  $queryConcursos = "SELECT nombre, fecha_inicio, fecha_fin FROM concursos WHERE nombre IN ('lugares', 'tradiciones')";
  $resultConcursos = mysqli_query($conexion, $queryConcursos);

  if ($resultConcursos) {
      while ($row = mysqli_fetch_assoc($resultConcursos)) {
          $nombre = strtolower($row['nombre']);
          $fechasConcursos[$nombre]['inicio'] = $row['fecha_inicio'];
          $fechasConcursos[$nombre]['fin'] = $row['fecha_fin'];
      }
  }

  // Obtener 6 fotos admitidas
  $query = "SELECT imagen, concurso FROM fotos WHERE estado = 'admitida' ORDER BY fecha_subida DESC LIMIT 6";
  $resultado = mysqli_query($conexion, $query);

  $fotos = [];
  if ($resultado && mysqli_num_rows($resultado) > 0) {
      while ($fila = mysqli_fetch_assoc($resultado)) {
          $fotos[] = [
              'imagen' => base64_encode($fila['imagen']),
              'concurso' => strtolower($fila['concurso'])
          ];
      }
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AndaRally</title>
  <link rel="icon" href="fotos/logo.png" type="image/png">
  <link rel="stylesheet" href="header/style.css" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

  <?php include 'header/header.php'; ?>

  <div class="overlay"></div>
  <div class="content-container">

  <!-- Sección  con título, descripción y botones -->
    <section class="hero">
      <h1>Los mejores lugares y momentos de ANDALUCÍA</h1>
      <p>Participa en nuestro Rally Fotográfico y comparte tu mirada sobre nuestra tierra.</p>
      <p>Entra en uno de los concursos y deja tu huella visual: <strong>“Lugares de Andalucía”</strong> o <strong>“Tradiciones Andaluzas”</strong>.</p>

      <div class="botones-concursos">
        <div class="concurso">
          <a class="boton-concurso" href="galeria/galeria.php">📍 Concurso de Lugares</a>
          <div class="reloj" id="reloj-lugares"></div>
        </div>
        <div class="concurso">
          <a class="boton-concurso" href="galeria/galeria_tradiciones.php">🎭 Concurso de Tradiciones</a>
          <div class="reloj" id="reloj-tradiciones"></div>
        </div>
        <div class="concurso">
          <a class="boton-concurso" href="rankings/rankings.php">Rankings</a>
        </div>
      </div>
    </section>

    <!-- Galería que muestra fotos recientes de participantes con acceso según sesión -->
    <section class="galeria">
      <h2>Fotos destacadas de participantes</h2>
      <div class="galeria-grid">
        <?php foreach ($fotos as $foto): ?>
          <div class="foto-tarjeta">
            <div class="etiqueta"><?= ucfirst($foto['concurso']) ?></div>
            <a href="<?= isset($_SESSION['usuario_id']) 
                ? ($foto['concurso'] === 'lugares' 
                    ? 'galeria/galeria.php' 
                    : 'galeria/galeria_tradiciones.php') 
                : 'login/login.php' ?>">
              <img src="data:image/jpeg;base64,<?= $foto['imagen']; ?>" alt="Foto participante" />
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </div>

  <script>
    // Objeto que contiene fechas del concurso 
    const concursos = {
      lugares: {
        inicio: "<?= $fechasConcursos['lugares']['inicio'] ?>",
        fin: "<?= $fechasConcursos['lugares']['fin'] ?>"
      },
      tradiciones: {
        inicio: "<?= $fechasConcursos['tradiciones']['inicio'] ?>",
        fin: "<?= $fechasConcursos['tradiciones']['fin'] ?>"
      }
    };

    // cuenta atras del concurso 
    function iniciarCuentaAtras(id, inicioStr, finStr) {
      const reloj = document.getElementById(id);
      const inicio = new Date(inicioStr).getTime();
      const fin = new Date(finStr).getTime();

      const actualizar = () => {
        const ahora = new Date().getTime();

        if (ahora < inicio) {
          const restante = inicio - ahora;
          reloj.innerHTML = "🕒 Comienza en " + formatearTiempo(restante);
        } else if (ahora >= inicio && ahora < fin) {
          const restante = fin - ahora;
          reloj.innerHTML = "⏳ Finaliza en " + formatearTiempo(restante);
        } else {
          reloj.innerHTML = "⏰ Concurso finalizado";
        }
      };

      // Formato dle tiempo restante que queda en el concurso
      const formatearTiempo = (ms) => {
        const dias = Math.floor(ms / (1000 * 60 * 60 * 24));
        const horas = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((ms % (1000 * 60)) / 1000);
        return `${dias}d ${horas}h ${minutos}m ${segundos}s`;
      };

      actualizar();
      setInterval(actualizar, 1000);
    }

    iniciarCuentaAtras('reloj-lugares', concursos.lugares.inicio, concursos.lugares.fin);
    iniciarCuentaAtras('reloj-tradiciones', concursos.tradiciones.inicio, concursos.tradiciones.fin);
  </script>

</body>
</html>
