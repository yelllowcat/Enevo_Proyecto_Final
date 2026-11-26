<?php
session_start();

require_once __DIR__ . '/../assets/app/config/ConnectionController.php';
require_once __DIR__ . '/../assets/app/controllers/PerfilController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SESSION['user_rol'] === 'admin') {
    header('Location: principalAdmin.php');
    exit();
}

$connection = new ConnectionController();
$conn = $connection->connect();

if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}

$controller = new PerfilController($conn);
$userName = $_SESSION['user_nombre'] ?? 'Usuario';

$datosPerfil = $controller->mostrarPerfil($_SESSION['user_id']);
$perfil = $datosPerfil['perfil'];
$proximo_nivel = $datosPerfil['proximo_nivel'];
$compras_faltantes = $datosPerfil['compras_faltantes'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil</title>
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
                <a class="user-link active"><?php echo htmlspecialchars($userName); ?></a>
                <button id="logoutBtn" type="button" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión" />
                </button>
            </div>
        </div>
    </nav>

    <main>
        <!-- CONTENIDO DEL PERFIL -->
        <section class="perfil-container">
            <h2 class="perfil-title">Mi Perfil</h2>

            <div class="perfil-card">
                <div class="perfil-left">
                    <img src="../assets/img/usuario.png" alt="Foto usuario" class="perfil-avatar">

                    <!-- Información del usuario (solo lectura) -->
                    <div class="perfil-info">
                        <div class="info-item">
                            <label>Nombre:</label>
                            <p><?php echo htmlspecialchars($perfil['nombre'] ?? $perfil['username'] ?? ''); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <label>Correo:</label>
                            <p><?php echo htmlspecialchars($perfil['email'] ?? ''); ?></p>
                        </div>
                    </div>

                    <div class="nivel-info">
                        <p><strong>Nivel Actual:</strong> <?php echo htmlspecialchars($perfil['nivel_nombre'] ?? 'Bronce'); ?></p>
                        
                        <?php if ($proximo_nivel): ?>
                            <?php
                                $puntosActuales = (int)($perfil['puntos_acumulados'] ?? 0);
                                $puntosMeta = max(1, (int)($proximo_nivel['puntos_minimos'] ?? 1));
                                $porcentajePuntos = min(100, (int)round(($puntosActuales / $puntosMeta) * 100));

                                $comprasActuales = (int)($perfil['total_compras'] ?? 0);
                                $comprasMeta = max(1, (int)($proximo_nivel['compras_necesarias'] ?? 1));
                                $porcentajeCompras = min(100, (int)round(($comprasActuales / $comprasMeta) * 100));
                            ?>
                            <p><strong>Siguiente nivel:</strong> <?php echo htmlspecialchars($proximo_nivel['nombre']); ?></p>
                            <div class="level-progress">
                                <div class="progress-header">
                                    <span>Progreso en puntos (<?php echo $puntosActuales; ?> / <?php echo $puntosMeta; ?>)</span>
                                    <strong><?php echo $porcentajePuntos; ?>%</strong>
                                </div>
                                <div class="progress-track" role="progressbar" aria-valuenow="<?php echo $porcentajePuntos; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso en puntos hacia el siguiente nivel">
                                    <div class="progress-fill" style="width: <?php echo $porcentajePuntos; ?>%;"></div>
                                </div>
                                <small class="progress-caption">Faltan <?php echo max(0, $puntosMeta - $puntosActuales); ?> puntos</small>
                            </div>
                            <div class="level-progress">
                                <div class="progress-header">
                                    <span>Progreso en compras (<?php echo $comprasActuales; ?> / <?php echo $comprasMeta; ?>)</span>
                                    <strong><?php echo $porcentajeCompras; ?>%</strong>
                                </div>
                                <div class="progress-track" role="progressbar" aria-valuenow="<?php echo $porcentajeCompras; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso en compras hacia el siguiente nivel">
                                    <div class="progress-fill progress-fill-secondary" style="width: <?php echo $porcentajeCompras; ?>%;"></div>
                                </div>
                                <small class="progress-caption">Faltan <?php echo (int)$compras_faltantes; ?> compras</small>
                            </div>
                        <?php else: ?>
                            <div class="level-progress">
                                <div class="progress-header">
                                    <span>¡Felicidades! Has alcanzado el nivel máximo.</span>
                                    <strong>100%</strong>
                                </div>
                                <div class="progress-track" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" aria-label="Nivel máximo alcanzado">
                                    <div class="progress-fill" style="width: 100%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="perfil-right">
                    <h3>Puntos Totales</h3>
                    <span class="puntos-num"><?php echo $perfil['puntos_acumulados'] ?? 0; ?></span>

                    <div class="perfil-buttons">
                        <a href="HistorialCompras.php" class="btn-perfil">Ver Historial de Compras</a>
                        <a href="../ExportarDatos.php?tipo=perfil&formato=pdf" class="btn-perfil">Descargar PDF</a>
                    </div>

                    <div class="info-adicional">
                        <p><strong>Total de Compras:</strong> <?php echo $perfil['total_compras'] ?? 0; ?></p>
                        <p><strong>Fecha de Registro:</strong> <?php echo date('d/m/Y', strtotime($perfil['fecha_registro'] ?? 'now')); ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
</body>
</html>