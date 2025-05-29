<?php
session_start();
require_once("conexion.php");
$rutaBase = '../';

// Obtener votos agrupados por foto y concurso
$sql = "
    SELECT f.id, f.titulo_imagen, f.concurso, SUM(v.puntuacion) as total_votos
    FROM fotos f
    LEFT JOIN votos v ON f.id = v.foto_id
    WHERE f.estado = 'admitida'
    GROUP BY f.id
    ORDER BY f.concurso, total_votos DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$datos = [
    'Lugares' => [],
    'Tradiciones' => []
];

// Agrupa las fotos por nombre de concurso y recopila los títulos e índices de votación.
foreach ($fotos as $foto) {
    $nombre = htmlspecialchars($foto['titulo_imagen']);
    $total = intval($foto['total_votos']);
    $concurso = ucfirst(strtolower($foto['concurso']));
    $datos[$concurso][] = [
        'titulo' => $nombre,
        'votos' => $total
    ];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php require_once("../header/header.php"); ?> <!-- CORRECTA UBICACIÓN -->

    <h2 class="titulo">Ranking de Votaciones</h2>

    <div class="grafico-container">
        <h3>Concurso: Lugares</h3>
        <canvas id="graficoLugares"></canvas>
    </div>

    <div class="grafico-container">
        <h3>Concurso: Tradiciones</h3>
        <canvas id="graficoTradiciones"></canvas>
    </div>

    <script>
        //Creación de graficos
    const datosLugares = <?= json_encode($datos['Lugares']) ?>;
    const datosTradiciones = <?= json_encode($datos['Tradiciones']) ?>;

    const crearGrafico = (idCanvas, datos, colorBarras) => {
        const ctx = document.getElementById(idCanvas).getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.map(d => d.titulo),
                datasets: [{
                    label: 'Puntos',
                    data: datos.map(d => d.votos),
                    backgroundColor: colorBarras,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverBorderColor: '#ffcc00'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff',
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#ffcc00',
                        bodyColor: '#fff'
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#fff', font: { size: 13 } },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#fff', stepSize: 1 },
                        grid: { color: 'rgba(255, 255, 255, 0.2)' }
                    }
                }
            }
        });
    };

    crearGrafico('graficoLugares', datosLugares, '#197813');
    crearGrafico('graficoTradiciones', datosTradiciones, 'white)');
    </script>
</body>
</html>
