<?php
require_once __DIR__ . '/../assets/app/config/SessionSecurity.php';

// Validación estricta de sesión activa y rol administrador en el servidor (ISO 27001 Control 5.15)
SessionSecurity::requireAdmin('login.html');

$userName = $_SESSION['user_nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PrincipalAdmin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style/main.css">
</head>

<body>
  <!-- ===== NAVBAR ===== -->
  <nav class="navbar">
    <a href="principalAdmin.php" class="logo logo-link" aria-label="Ir al inicio de Enevo">
      <img src="../assets/img/LogoEnevo2.png" alt="Logo Enevo">
      <span class="brand">Enevo</span>
    </a>

    <div class="nav-links">
      <a href="#" class="active">Tienda</a>
      <a href="NivelesAdmin.php">Niveles</a>
      <a href="ClientesAdmin.php">Clientes</a>
      <a href="Ventas.php">Ventas</a>

      <div class="user-section">
        <span class="user-link"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
        <button id="logoutBtn" type="button" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
          <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión">
        </button>
      </div>
    </div>
  </nav>

  <main>
    <!-- ===== HERO ===== -->
    <section class="hero">
      <h1>Dark Souls</h1>
      <img src="../assets/img/DarkSoulsGIF.gif" alt="Dark Souls Banner">
    </section>

    <!-- ===== CATÁLOGO DINÁMICO DESDE MYSQL ===== -->
    <section class="novedades">
      <div class="novedades-titulo">
        <h2>Novedades Destacadas</h2>
      </div>
      <p class="catalogo-loading" style="text-align: center; color: #aaa; padding: 20px;">
        Cargando catálogo desde base de datos...
      </p>
    </section>
  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-logo">
        <img src="../assets/img/LogoEnevo.png" alt="Enevo Logo">
        <p>© 2025 – Todos los derechos reservados</p>
      </div>
    </div>
  </footer>

  <script src="../assets/js/juegos.js"></script>
  <script src="../assets/js/main.js"></script>
</body>

</html>
