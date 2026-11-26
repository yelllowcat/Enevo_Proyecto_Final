<?php
// Carrito.php - PÁGINA DE VISTA DEL CARRITO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Incluir la CLASE, no el controlador AJAX
require_once "../assets/app/controllers/CarritoClass.php";

// Obtener puntos del usuario para mostrar opción de pago con puntos
require_once __DIR__ . "/../assets/app/config/ConnectionController.php";
require_once __DIR__ . "/../assets/app/models/PerfilModel.php";

$dbConn = (new ConnectionController())->connect();
$perfilModel = new PerfilModel($dbConn);
$perfil = $perfilModel->obtenerPerfil($_SESSION['user_id']);
$puntosUsuario = $perfil['puntos_acumulados'] ?? 0;

// Manejar acciones GET (eliminar/vaciar)
if (isset($_GET['eliminar'])) {
    CarritoController::eliminar($_GET['eliminar']);
    // Recargar para ver cambios
    header('Location: Carrito.php');
    exit();
}

if (isset($_GET['vaciar'])) {
    CarritoController::vaciar();
    // Recargar para ver cambios
    header('Location: Carrito.php');
    exit();
}

if (isset($_GET['eliminar_todo'])) {
    CarritoController::eliminarTodo($_GET['eliminar_todo']);
    header('Location: Carrito.php');
    exit();
}

$carrito = CarritoController::obtenerCarrito();
$total = CarritoController::total();

$puntosRequeridos = 0;
foreach ($carrito as $item) {
    $puntosRequeridos += (int)floor($item['precio'] / 50) * (int)$item['cantidad'];
}
$tienePuntosSuficientes = intval($puntosUsuario) >= $puntosRequeridos;
$puntosFaltantes = max(0, $puntosRequeridos - intval($puntosUsuario));

$userName = $_SESSION['user_nombre'] ?? "Usuario";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Carrito</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style/main.css">
    
</head>
<body>

    <nav class="navbar">
        <a href="principal.php" class="logo logo-link" aria-label="Ir al inicio de Enevo">
            <img src="../assets/img/LogoEnevo2.png" alt="Logo Enevo">
            <span class="brand">Enevo</span>
        </a>

        <div class="nav-links">
            <a href="principal.php">Tienda</a>
            <a href="NivelesClientes.php">Niveles</a>
            <a class="active" href="#">Carrito</a>

            <div class="user-section">
                <a href="Perfil.php" class="user-link"><?php echo htmlspecialchars($userName); ?></a>
                <button id="logoutBtn" class="logout-btn" title="Cerrar sesión">
                    <img src="../assets/img/cerrar-sesion.png" alt="Cerrar sesión" />
                </button>
            </div>
        </div>
    </nav>

    <main>
        <section class="carrito-container">

            <h2 class="carrito-title">Tu carrito</h2>

            <p class="usuario-puntos">Tus puntos: <strong><?php echo intval($puntosUsuario); ?></strong></p>

            <div class="carrito-card">

                <?php if (empty($carrito)) { ?>
                    <p class="carrito-empty">Tu carrito está vacío</p>
                    <a href="principal.php" class="btn-volver-tienda">← Volver a la tienda</a>
                <?php } else { ?>

                    <?php foreach ($carrito as $id => $item) { ?>
                        <div class="carrito-item" data-item-id="<?php echo htmlspecialchars((string)$id); ?>">
                            <div class="item-info">
                                <span class="item-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                <div class="qty-controls" role="group" aria-label="Cantidad de <?php echo htmlspecialchars($item['nombre']); ?>">
                                    <button
                                        type="button"
                                        class="qty-btn btn-qty-dec"
                                        data-id="<?php echo htmlspecialchars((string)$id); ?>"
                                        aria-label="Disminuir cantidad de <?php echo htmlspecialchars($item['nombre']); ?>"
                                        title="Quitar 1"
                                    >−</button>
                                    <span class="qty-value" aria-live="polite"><?php echo (int)$item['cantidad']; ?></span>
                                    <button
                                        type="button"
                                        class="qty-btn btn-qty-inc"
                                        data-id="<?php echo htmlspecialchars((string)$id); ?>"
                                        aria-label="Aumentar cantidad de <?php echo htmlspecialchars($item['nombre']); ?>"
                                        title="Agregar 1"
                                    >+</button>
                                </div>
                            </div>
                            <div class="item-precio">
                                <span>Mex$ <?php echo number_format($item['precio'], 2); ?> c/u</span>
                                <span class="item-subtotal">
                                    Subtotal: Mex$ <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                                </span>
                            </div>
                            <div class="item-acciones">
                                <button
                                    type="button"
                                    class="btn-eliminar btn-remove-item"
                                    data-id="<?php echo htmlspecialchars((string)$id); ?>"
                                    data-nombre="<?php echo htmlspecialchars($item['nombre']); ?>"
                                    title="Eliminar todas las unidades de este juego"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="carrito-total">
                        <span>Total a pagar:</span>
                        <strong>Mex$ <?php echo number_format($total, 2); ?></strong>
                    </div>

                    <div class="puntos-resumen <?php echo $tienePuntosSuficientes ? 'puntos-ok' : 'puntos-insuficientes'; ?>">
                        <div class="puntos-resumen-fila">
                            <span>Costo pagando con puntos:</span>
                            <strong><?php echo $puntosRequeridos; ?> pts</strong>
                        </div>
                        <div class="puntos-resumen-fila">
                            <span>Tus puntos disponibles:</span>
                            <strong><?php echo intval($puntosUsuario); ?> pts</strong>
                        </div>
                        <p class="puntos-resumen-estado">
                            <?php if ($tienePuntosSuficientes): ?>
                                ¡Tienes puntos suficientes para canjear este pedido!
                            <?php else: ?>
                                Te faltan <strong><?php echo $puntosFaltantes; ?> pts</strong> para pagar este pedido con puntos.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="carrito-acciones">
                        <button type="button" id="btn-vaciar-carrito" class="btn-vaciar">
                            Vaciar carrito
                        </button>
                        <a class="btn-comprar" href="Checkout.php">
                            Proceder al pago
                        </a>
                        <button
                            type="button"
                            id="btn-pagar-puntos"
                            class="btn-pagar-puntos"
                            data-puntos-requeridos="<?php echo $puntosRequeridos; ?>"
                            <?php echo !$tienePuntosSuficientes ? 'disabled aria-disabled="true" title="Te faltan ' . $puntosFaltantes . ' puntos para pagar con puntos"' : ''; ?>
                        >
                            Pagar con puntos (<?php echo $puntosRequeridos; ?> pts)
                        </button>
                    </div>

                <?php } ?>
            </div>

        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-logo">
                <img src="../assets/img/LogoEnevo.png" alt="Logo Enevo">
                <p>© 2025 – Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        // Logout SIN confirmación - cierra sesión directamente
        document.getElementById('logoutBtn').addEventListener('click', function() {
            window.location.href = '../assets/app/controllers/LogoutController.php';
        });

        // Controles de cantidad (+ / -), eliminar ítem y vaciar carrito con confirmación
        async function actualizarCarritoAccion(action, idJuego = null) {
            const body = new URLSearchParams();
            if (idJuego !== null) body.append('id_juego', idJuego);

            const res = await fetch(`../assets/app/controllers/CarritoController.php?action=${encodeURIComponent(action)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data && data.success) {
                window.location.reload();
            } else {
                showNotification({ message: (data && data.message) || 'No se pudo actualizar el carrito.', type: 'error' });
            }
        }

        document.querySelectorAll('.btn-qty-inc').forEach(btn => {
            btn.addEventListener('click', function() {
                this.disabled = true;
                actualizarCarritoAccion('increment', this.dataset.id).catch(() => { this.disabled = false; });
            });
        });

        document.querySelectorAll('.btn-qty-dec').forEach(btn => {
            btn.addEventListener('click', function() {
                this.disabled = true;
                actualizarCarritoAccion('decrement', this.dataset.id).catch(() => { this.disabled = false; });
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre || 'este juego';
                showNotification({
                    message: `¿Quitar "${nombre}" del carrito?`,
                    type: 'info',
                    primaryText: 'Quitar',
                    onPrimary: function() {
                        actualizarCarritoAccion('remove', id);
                    },
                    secondaryText: 'Cancelar'
                });
            });
        });

        const btnVaciarCarrito = document.getElementById('btn-vaciar-carrito');
        if (btnVaciarCarrito) {
            btnVaciarCarrito.addEventListener('click', function() {
                showNotification({
                    message: '¿Estás seguro de que deseas vaciar todo tu carrito?',
                    type: 'info',
                    primaryText: 'Sí, vaciar',
                    onPrimary: function() {
                        actualizarCarritoAccion('clear');
                    },
                    secondaryText: 'Cancelar'
                });
            });
        }

        // Manejar pago con puntos (con modal unificado)
        const btnPagarPuntos = document.getElementById('btn-pagar-puntos');
        if (btnPagarPuntos) {
            btnPagarPuntos.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.disabled) return;

                const puntosReq = this.dataset.puntosRequeridos || '0';
                showNotification({
                    message: `¿Confirmas pagar este pedido usando ${puntosReq} puntos acumulados?`,
                    type: 'info',
                    primaryText: 'Confirmar pago',
                    onPrimary: function() {
                        const modal = document.getElementById('globalNotification');
                        if (modal) modal.classList.add('oculto');

                        fetch('../assets/app/controllers/CompraController.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=procesar_compra_puntos'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification({
                                    message: data.message || 'Compra con puntos realizada',
                                    type: 'success',
                                    autoHide: 2500,
                                    primaryText: 'Ver historial',
                                    primaryHref: 'HistorialCompras.php'
                                });
                            } else {
                                if (data.message === 'Puntos insuficientes') {
                                    showNotification({
                                        message: 'Puntos insuficientes. Necesitas ' + (data.puntos_requeridos || 'N/A') + ' puntos. Tienes: ' + (data.puntos_actuales || 0),
                                        type: 'error',
                                        primaryText: 'Ver perfil',
                                        primaryHref: 'Perfil.php'
                                    });
                                } else {
                                    showNotification({ message: data.message || 'Error al procesar la compra', type: 'error' });
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showNotification({ message: 'Error en la petición. Intenta nuevamente.', type: 'error' });
                        });
                    },
                    secondaryText: 'Cancelar'
                });
            });
        }
    </script>

</body>
</html>