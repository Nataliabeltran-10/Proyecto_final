<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rutaBase = $rutaBase ?? '/clase/Proyecto/';
$tipo = $_SESSION['usuario_rol'] ?? null;
?>

<link rel="stylesheet" href="<?= $rutaBase ?>style.css">

<header class="main-header">
  <div class="container-header">
    <div class="logo">
      <a href="<?= $rutaBase ?>index.php" class="logo-link">
        <span class="logo-anda">Anda</span><span class="logo-rally">Rally</span>
      </a>
    </div>

    <nav class="nav-buttons">
      <div class="menu-user-group">
        <button id="menu-toggle" class="btn-menu">Menú</button>
        <div id="menu-modal" class="modal oculto">
          <div class="modal-content">
            <a href="<?= $rutaBase ?>galeria/galeria.php">Concurso Lugares</a>
            <a href="<?= $rutaBase ?>galeria/galeria_tradiciones.php">Concurso Tradiciones</a>
            <a href="<?= $rutaBase ?>rankings/rankings.php">Rankings</a>
            <a href="#" id="admin-link" class="menu-item">Administrador</a>
            <div class="submenu-container">
              <a href="#" id="participante-link" class="menu-item">Participante</a>
              <div class="submenu oculto" id="submenu-participante">
                <a href="<?= $rutaBase ?>participante/gestion_participante.php">Mi página</a>
                <a href="<?= $rutaBase ?>participante/pagina_participante.php">Participa</a>
              </div>
            </div>
          </div>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>
          <button id="usuario-nombre" class="btn-usuario">
            <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
          </button>
        <?php else: ?>
          <a href="<?= $rutaBase ?>login/login.php" class="btn-accede">Accede</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<?php if (isset($_SESSION['usuario_id'])): ?>
  <div id="modal-usuario" class="modal oculto">
    <div class="modal-content">
      <h3>Mi Perfil</h3>
      <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['usuario_email']) ?></p>
      <div class="modal-actions">
        <a href="<?= $rutaBase ?>editar_perfil/editar_perfil.php" class="btn-editar">Editar Perfil</a>
        <button onclick="cerrarSesion()" class="btn-cerrar">Cerrar Sesión</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<div id="toast-error" class="toast-error oculto"></div>

<script>
  function cerrarSesion() {
    window.location.replace("<?= $rutaBase ?>login/logout.php");
  }

  function showToast(message) {
    const toast = document.getElementById("toast-error");
    toast.textContent = message;
    toast.classList.remove("oculto");
    toast.classList.add("visible");

    setTimeout(() => {
      toast.classList.remove("visible");
      setTimeout(() => {
        toast.classList.add("oculto");
      }, 400);
    }, 3000);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const usuarioBtn = document.getElementById("usuario-nombre");
    const usuarioModal = document.getElementById("modal-usuario");

    usuarioBtn?.addEventListener("click", function () {
      usuarioModal.classList.toggle("visible");
    });

    document.addEventListener("click", function (event) {
      if (usuarioModal && !usuarioModal.contains(event.target) && event.target !== usuarioBtn) {
        usuarioModal.classList.remove("visible");
      }
    });

    const menuBtn = document.getElementById("menu-toggle");
    const menuModal = document.getElementById("menu-modal");

    menuBtn?.addEventListener("click", function () {
      menuModal.classList.toggle("visible");
    });

    document.addEventListener("click", function (event) {
      if (menuModal && !menuModal.contains(event.target) && event.target !== menuBtn) {
        menuModal.classList.remove("visible");
      }
    });

    const participanteLink = document.getElementById('participante-link');
    const submenuParticipante = document.getElementById('submenu-participante');
    const userType = <?= json_encode($tipo) ?>;

    if (participanteLink && submenuParticipante) {
      participanteLink.addEventListener('click', function (e) {
        e.preventDefault();
        if (userType !== 'participante') {
          showToast("No tienes acceso. Solo los participantes pueden acceder.");
          submenuParticipante.classList.add("oculto");
        } else {
          submenuParticipante.classList.toggle("visible");
        }
      });

      document.addEventListener("click", function (event) {
        if (
          !submenuParticipante.contains(event.target) &&
          event.target !== participanteLink
        ) {
          submenuParticipante.classList.remove("visible");
        }
      });
    }

    const adminLink = document.getElementById('admin-link');
    if (adminLink) {
      adminLink.addEventListener('click', function(e) {
        e.preventDefault();
        if (userType === 'administrador') {
          window.location.href = "<?= $rutaBase ?>administrador/pagina_admin.php";
        } else {
          showToast("No tienes acceso. Solo los administradores pueden acceder.");
        }
      });
    }
  });
</script>
