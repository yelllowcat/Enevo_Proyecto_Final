<?php
require_once __DIR__ . '/../assets/app/config/SessionSecurity.php';

// Validación estricta de sesión activa de cliente en el servidor (ISO 27001 Control 5.15)
SessionSecurity::requireClient('login.html', 'principalAdmin.php');

$userName = $_SESSION['user_nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tienda — Enevo</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../assets/style/main.css" />
  <link rel="preload" as="image" href="../assets/img/DarkSoulsGIF.webp" type="image/webp" />
</head>

<body>
  <nav class="navbar">
    <a href="principal.php" class="logo logo-link" aria-label="Ir al inicio de Enevo">
      <img src="../assets/img/LogoEnevo2.png" alt="Logo Enevo" />
      <span class="brand">Enevo</span>
    </a>

    <div class="nav-links">
      <a href="#" class="active">Tienda</a>
      <a href="NivelesClientes.php">Niveles</a>
      <a href="Carrito.php">Carrito</a>

      <div class="user-section">
        <a href="Perfil.php" class="user-link"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></a>
        <button id="logoutBtn" type="button" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
          <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión" />
        </button>
      </div>
    </div>
  </nav>

  <main>
    <section class="hero">
      <h1>Dark Souls</h1>
      <picture>
        <source srcset="../assets/img/DarkSoulsGIF.webp" type="image/webp" />
        <img
          src="../assets/img/DarkSoulsGIF.gif"
          alt="Dark Souls Banner"
          width="900"
          height="502"
          fetchpriority="high"
          decoding="async"
        />
      </picture>
    </section>

    <section class="novedades">
      <div class="novedades-titulo">
        <h2>Novedades Destacadas</h2>
      </div>
      <p class="catalogo-loading" style="text-align: center; color: #aaa; padding: 20px;">
        Cargando catálogo desde base de datos...
      </p>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-logo">
        <img src="../assets/img/LogoEnevo.png" alt="Enevo Logo" loading="lazy" decoding="async" />
        <p>© 2025 – Todos los derechos reservados</p>
      </div>
    </div>
  </footer>

  <script src="../assets/js/juegos.js"></script>
  <script src="../assets/js/main.js"></script>
</body>

</html>
