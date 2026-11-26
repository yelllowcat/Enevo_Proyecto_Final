<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "CarritoClass.php";

if (isset($_GET['action'])) {
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    error_reporting(0);
    
    $response = ['success' => false, 'message' => 'Acción no válida'];
    
    try {
        switch ($_GET['action']) {
            case 'add':
                $id = $_POST['id_juego'] ?? null;
                
                if (!$id) {
                    $response = ['success' => false, 'message' => 'No se recibió ID del juego'];
                    break;
                }
                
                require_once "JuegoController.php";
                
                if (class_exists('JuegoController')) {
                    $controllerJuego = new JuegoController();
                    $datosJuego = $controllerJuego->getById($id);
                    
                    if ($datosJuego['success'] && isset($datosJuego['data'])) {
                        $juego = $datosJuego['data'];
                        CarritoController::agregar($juego['id'], $juego['nombre'], $juego['precio']);
                    } else {
                        CarritoController::agregar($id, "Juego " . $id, 99.99);
                    }
                } else {
                    CarritoController::agregar($id, "Juego " . $id, 99.99);
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Juego agregado al carrito correctamente',
                    'carrito' => $_SESSION['carrito'],
                    'total_items' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
                ];
                break;
                
            case 'get':
                CarritoController::initCarrito();
                $response = [
                    'success' => true,
                    'carrito' => $_SESSION['carrito'],
                    'total' => CarritoController::total(),
                    'total_items' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
                ];
                break;

            case 'increment':
                $id = $_POST['id_juego'] ?? null;
                if (!$id || !isset($_SESSION['carrito'][$id])) {
                    $response = ['success' => false, 'message' => 'Producto no encontrado en el carrito'];
                    break;
                }
                $_SESSION['carrito'][$id]['cantidad'] += 1;
                $response = [
                    'success' => true,
                    'message' => 'Cantidad aumentada',
                    'carrito' => $_SESSION['carrito'],
                    'total' => CarritoController::total(),
                    'total_items' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
                ];
                break;

            case 'decrement':
                $id = $_POST['id_juego'] ?? null;
                if (!$id || !isset($_SESSION['carrito'][$id])) {
                    $response = ['success' => false, 'message' => 'Producto no encontrado en el carrito'];
                    break;
                }
                CarritoController::eliminar($id);
                $response = [
                    'success' => true,
                    'message' => 'Cantidad actualizada',
                    'carrito' => $_SESSION['carrito'],
                    'total' => CarritoController::total(),
                    'total_items' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
                ];
                break;

            case 'remove':
                $id = $_POST['id_juego'] ?? null;
                if (!$id || !isset($_SESSION['carrito'][$id])) {
                    $response = ['success' => false, 'message' => 'Producto no encontrado en el carrito'];
                    break;
                }
                CarritoController::eliminarTodo($id);
                $response = [
                    'success' => true,
                    'message' => 'Producto eliminado del carrito',
                    'carrito' => $_SESSION['carrito'],
                    'total' => CarritoController::total(),
                    'total_items' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
                ];
                break;

            case 'clear':
                CarritoController::vaciar();
                $response = [
                    'success' => true,
                    'message' => 'Carrito vaciado correctamente',
                    'carrito' => [],
                    'total' => '0.00',
                    'total_items' => 0
                ];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Acción no válida'];
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error interno'];
    }
    
    echo json_encode($response);
    exit;
}
?>