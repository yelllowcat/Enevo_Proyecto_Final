<?php
session_start();

// Verificar si el usuario está logueado Y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: login.html');
    exit();
}

$userName = $_SESSION['user_nombre'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ClientesAdmin</title>

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <!-- Tu CSS global -->
    <link rel="stylesheet" href="../assets/style/main.css">


</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="principalAdmin.php" class="logo logo-link" aria-label="Ir al inicio de Enevo">
            <img src="../assets/img/LogoEnevo2.png" alt="Logo Enevo">
            <span class="brand">Enevo</span>
        </a>

        <div class="nav-links">
            <a href="principalAdmin.php">Tienda</a>
            <a href="NivelesAdmin.php">Niveles</a>
            <a href="ClientesAdmin.php" class="active">Clientes</a>
            <a href="Ventas.php">Ventas</a>

            <div class="user-section">
                <span class="user-link"><?php echo htmlspecialchars($userName); ?></span>
                <button id="logoutBtn" type="button" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión" />
                </button>
            </div>
        </div>
    </nav>

    <!-- CONTENIDO -->
    <main class="clientes-admin-wrapper">

        <div class="titulo-con-agregar">
            <h2 class="clientes-titulo">Bienvenido al apartado de Clientes</h2>
            <button class="btn-agregar-cliente" onclick="irAgregarCliente()">Agregar</button>
        </div>


        <!-- TABLA -->
        <div class="clientes-tabla-contenedor">
            <table class="tabla-clientes">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Nivel</th>
                        <th>Correo</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>

                <tbody id="clienteListado">
                    <!-- generado dinámicamente -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-logo">
                <img src="../assets/img/LogoEnevo.png" alt="Enevo Logo">
                <p>© 2025 – Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/clientes.js"></script>
</body>

</html>