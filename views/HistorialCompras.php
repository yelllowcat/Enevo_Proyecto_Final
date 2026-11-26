<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SESSION['user_rol'] === 'admin') {
    header('Location: principalAdmin.php');
    exit();
}

// Incluir el modelo de compras
require_once "../assets/app/models/CompraModel.php";

$userName = $_SESSION['user_nombre'] ?? 'Usuario';

// Obtener historial de compras del cliente
$compraModel = new CompraModel();
$historial = $compraModel->obtenerHistorialCompras($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Historial de Compras — Enevo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style/main.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="principal.php" class="logo logo-link" aria-label="Ir al inicio de Enevo">
            <img src="../assets/img/LogoEnevo2.png" alt="Logo Enevo">
            <span class="brand">Enevo</span>
        </a>

        <div class="nav-links">
            <a href="principal.php">Tienda</a>
            <a href="NivelesClientes.php">Niveles</a>
            <a href="Carrito.php">Carrito</a>

            <div class="user-section">
                <a href="Perfil.php" class="user-link active"><?php echo htmlspecialchars($userName); ?></a>
                <button id="logoutBtn" type="button" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión" />
                </button>
            </div>
        </div>
    </nav>

    <main>
        <section class="historial-container">

            <div class="back-nav-bar">
                <a href="Perfil.php" class="back-nav-link">← Volver a mi perfil</a>
            </div>

            <h2 class="historial-title">Historial de compras</h2>

            <div class="historial-card">

                <?php if (empty($historial)) { ?>
                    <p style="text-align: center; color: #666; padding: 40px 20px;">
                        No tienes compras registradas aún.<br>
                        <a href="principal.php" style="color: #2196F3; text-decoration: none;">← Ir a la tienda</a>
                    </p>
                <?php } else { ?>

                    <table class="historial-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Juego</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Puntos Generados</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($historial as $compra) { ?>
                                <tr>
                                    <td><?php echo $compra['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($compra['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($compra['juego_nombre'] ?? 'N/A'); ?></td>
                                    <td><?php echo $compra['cantidad']; ?></td>
                                    <td>Mex$ <?php echo number_format($compra['monto'], 2); ?></td>
                                    <td><?php echo $compra['puntos_generados']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <div class="historial-btn-box">
                        <a
                            href="../ExportarDatos.php?tipo=historial&formato=pdf"
                            class="historial-btn"
                            target="_blank"
                            rel="noopener"
                        >Descargar PDF</a>
                    </div>

                <?php } ?>

            </div>

        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-logo">
                <img src="../assets/img/LogoEnevo.png" alt="Enevo Logo" loading="lazy" decoding="async" >
                <p>© 2025 – Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="../assets/js/main.js"></script>
</body>

</html>