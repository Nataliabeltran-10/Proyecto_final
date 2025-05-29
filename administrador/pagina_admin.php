<?php
session_start();
$rutaBase = '../';
require_once("conexion.php");

// Solo puede acceder usuarios con rol de administador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: {$rutaBase}login/login.php");
    exit;
}


// Solicitud POST para actualizar el estado de la foto 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['foto_id'])) {
    $fotoId = intval($_POST['foto_id']);
    $accion = $_POST['accion'];
    $nuevoEstado = ($accion === 'admitir') ? 'admitida' : 'rechazada';
    $stmt = $conn->prepare("UPDATE fotos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevoEstado, $fotoId]);
    exit;
}

// Solicitud POST para eliminar o actualizar un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_accion'])) {
    $usuarioId = intval($_POST['usuario_id']);
    if ($_POST['usuario_accion'] === 'eliminar') {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nuevo_nombre'],
            $_POST['nuevo_email'],
            $usuarioId
        ]);
    }
    exit;
}

// Solicitud POST para editar los datos de los concursos 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concurso_accion']) && $_POST['concurso_accion']==='editar_concurso') {
    $id = intval($_POST['concurso_id']);
    $stmt = $conn->prepare(
        "UPDATE concursos SET nombre=?, fecha_inicio=?, fecha_fin=?, tamano_maximo=?, formatos_permitidos=?, limite_fotos=? WHERE id=?"
    );
    $stmt->execute([
        $_POST['nombre'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin'],
        intval($_POST['tamano']),
        $_POST['formatos'],
        intval($_POST['limite']),
        $id
    ]);
    exit;
}

// Consulta de fotos en estado 'pendiente'
$fotos = $conn->query("SELECT f.id,f.titulo_imagen,f.descripcion,f.concurso,u.nombre,f.imagen FROM fotos f JOIN usuarios u ON f.usuario_id=u.id WHERE f.estado='pendiente' ORDER BY f.id DESC")->fetchAll(PDO::FETCH_ASSOC);
// Consulta ranking de fotos admitidas por concurso basada en la suma de sus puntos
$ganadores = $conn->query(
    "SELECT f.concurso,f.id AS foto_id,f.titulo_imagen,f.imagen,u.nombre,SUM(v.puntuacion) AS total FROM fotos f JOIN votos v ON f.id=v.foto_id JOIN usuarios u ON f.usuario_id=u.id WHERE f.estado='admitida' GROUP BY f.id ORDER BY f.concurso,total DESC"
)->fetchAll(PDO::FETCH_ASSOC);
// Consulta para listar los usuarios
$usuarios = $conn->query("SELECT * FROM usuarios ")->fetchAll(PDO::FETCH_ASSOC);
// Concurso para lsitar los concursos 
$concursos = $conn->query("SELECT * FROM concursos")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="../fotos/logo.png" type="image/png">
  <title>AndaRally</title>
  <link rel="stylesheet" href="../header/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <?php require_once("../header/header.php"); ?>
  <div id="toast" class="toast" style="display:none;"></div>

  <!-- Confirmación para borrar usuario -->
  <div id="modal-confirm" class="modal modal-confirm" style="display:none;">
    <div class="modal-contentu">
      <p>¿Estás seguro de que quieres eliminar este usuario?</p>
      <div class="modal-buttons">
        <button id="confirm-delete" class="btn-confirm">Sí, eliminar</button>
        <button id="cancel-delete" class="btn-cancel">Cancelar</button>
      </div>
    </div>
  </div>

  <h1 class="titulo-principal">Panel de Administrador</h1>

  <!-- Administrar fotos enviada por los participantes-->
  <section>
    <h2 class="titulo">Admisión de Fotos</h2>
    <div class="galeria-admin">
      <?php if(empty($fotos)): ?>
        <p>No hay fotos pendientes.</p>
      <?php else: foreach($fotos as $f): ?>
        <div class="foto-carta">
          <img src="data:image/jpeg;base64,<?= base64_encode($f['imagen']) ?>" alt="<?=htmlspecialchars($f['titulo_imagen'])?>">
          <h3  class="titulo-foto" ><?=htmlspecialchars($f['titulo_imagen'])?></h3>
          <p><strong>Participante:</strong> <?=$f['nombre']?></p>
          <p><strong>Concurso:</strong> <?=$f['concurso']?></p>
          <p><?=nl2br(htmlspecialchars($f['descripcion']))?></p>
          <div class="form-admision" data-id="<?=$f['id']?>">
            <button class="btn-admitir">Admitir</button>
            <button class="btn-rechazar">Rechazar</button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </section>

  <!-- Muestra ganadores de cada concurso -->
  <section>
    <h2 class="titulo">Ganadores por Concurso</h2>
    <div class="ganadores-grid">
      <?php if(empty($ganadores)): ?>
        <p>No hay ganadores disponibles todavía.</p>
      <?php else: foreach($ganadores as $g): ?>
        <div class="ganador-carta">
          <img src="data:image/jpeg;base64,<?= base64_encode($g['imagen']) ?>" alt="<?=htmlspecialchars($g['titulo_imagen'])?>">
          <h3><?= htmlspecialchars($g['titulo_imagen']) ?></h3>
          <p><strong>Concurso:</strong> <?= htmlspecialchars($g['concurso']) ?></p>
          <p><strong>Participante:</strong> <?= htmlspecialchars($g['nombre']) ?></p>
          <p><strong>Puntos:</strong> <?= $g['total'] ?></p>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </section>

  <!-- Actualización y eliminación de usuarios -->
  <section>
    <h2 class="titulo">Edición de Usuarios</h2>
    <table class="tabla_formato">
      <thead><tr><th>Nombre</th><th>Email</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($usuarios as $u): ?>
        <tr>
          <td><input type="text" class="input-nombre" value="<?=htmlspecialchars($u['nombre'])?>"></td>
          <td><input type="email" class="input-email" value="<?=htmlspecialchars($u['email'])?>"></td>
          <td>
            <div class="form-usuario" data-id="<?=$u['id']?>">
              <button class="btn-guardar-usuario">Guardar</button>
              <button class="btn-eliminar-usuario">Eliminar</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Actualización de concursos -->
  <section>
    <h2 class="titulo">Edición de Concursos</h2>
    <table class="tabla_formato">
      <thead><tr><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Tamaño</th><th>Formatos</th><th>Límite</th><th>Acción</th></tr></thead>
      <tbody>
      <?php foreach($concursos as $c): ?>
        <tr>
          <td><input type="text" class="input-nombre-c" value="<?=htmlspecialchars($c['nombre'])?>"></td>
          <td><input type="datetime-local" class="input-inicio" value="<?=date('Y-m-d\TH:i',strtotime($c['fecha_inicio']))?>"></td>
          <td><input type="datetime-local" class="input-fin" value="<?=date('Y-m-d\TH:i',strtotime($c['fecha_fin']))?>"></td>
          <td><input type="number" class="input-tamano" value="<?=$c['tamano_maximo']?>"></td>
          <td><input type="text" class="input-formatos" value="<?=htmlspecialchars($c['formatos_permitidos'])?>"></td>
          <td><input type="number" class="input-limite" value="<?=$c['limite_fotos']?>"></td>
          <td><button class="btn-guardar-concurso" data-id="<?=$c['id']?>">Guardar</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <script>
  let usuarioAEliminar = null;

  // Mensaje breve 
  function showToast(mensaje) {
    const toast = document.getElementById('toast');
    toast.textContent = mensaje;
    toast.style.display = 'block';
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => {
        toast.style.display = 'none';
        toast.style.opacity = '1';
      }, 500);
    }, 2500);
  }

  // Mostrar modal de confirmación
  function mostrarModalConfirmacion(div) {
    usuarioAEliminar = div;
    document.getElementById('modal-confirm').style.display = 'flex';
  }

  // Cerrar modal
  function cerrarModal() {
    usuarioAEliminar = null;
    document.getElementById('modal-confirm').style.display = 'none';
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    // Admicion o rechazo de fotos 
    document.querySelectorAll('.form-admision').forEach(div=>{
      const id = div.dataset.id;
      div.querySelector('.btn-admitir').onclick = _=>ajax({foto_id:id,accion:'admitir'},()=>div.closest('.foto-carta').remove());
      div.querySelector('.btn-rechazar').onclick = _=>ajax({foto_id:id,accion:'rechazar'},()=>div.closest('.foto-carta').remove());
    });

    // Edición y eliminación de usuarios en la tabla 
    document.querySelectorAll('.form-usuario').forEach(div=>{
      const id = div.dataset.id;
      div.querySelector('.btn-guardar-usuario').onclick = _=>{
        const row = div.closest('tr');
        const nombre = row.querySelector('.input-nombre').value;
        const email = row.querySelector('.input-email').value;
        ajax(
          {usuario_id:id, usuario_accion:'editar', nuevo_nombre:nombre, nuevo_email:email},
          ()=> showToast('Usuario modificado correctamente')
        );
      };
      div.querySelector('.btn-eliminar-usuario').onclick = _=>{
        mostrarModalConfirmacion(div);
      };
    });

    // Boton de confirmacion para borrar un usuario
    document.getElementById('confirm-delete').onclick = ()=>{
      if (!usuarioAEliminar) return cerrarModal();
      const id = usuarioAEliminar.dataset.id;
      ajax(
        {usuario_id:id,usuario_accion:'eliminar'},
        ()=>{
          usuarioAEliminar.closest('tr').remove();
          showToast('Usuario eliminado correctamente');
          cerrarModal();
        }
      );
    };
    document.getElementById('cancel-delete').onclick = cerrarModal;

    // Boton para guardar cambios en el concurso
    document.querySelectorAll('.btn-guardar-concurso').forEach(btn=>{
      btn.onclick = _=>{
        const id = btn.dataset.id;
        const tr = btn.closest('tr');
        const data = {
          concurso_id:id, concurso_accion:'editar_concurso',
          nombre: tr.querySelector('.input-nombre-c').value,
          fecha_inicio: tr.querySelector('.input-inicio').value,
          fecha_fin: tr.querySelector('.input-fin').value,
          tamano: tr.querySelector('.input-tamano').value,
          formatos: tr.querySelector('.input-formatos').value,
          limite: tr.querySelector('.input-limite').value
        };
        ajax(data, ()=> showToast('Concurso modificado correctamente'));
      };
    });
  });

  // Función ajax para enviar los datos
  function ajax(data, onSuccess){
    fetch('', {
      method: 'POST',
      body: new URLSearchParams(data)
    })
    .then(r => r.ok ? onSuccess() : showToast('Error inesperado'))
    .catch(_ => showToast('Error de red'));
  }
  </script>
</body>
</html>
