<?php
/**
 * JuegoController.php
 * Endpoint REST/JSON para el catálogo dinámico de videojuegos (RFC 8259)
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/ConnectionController.php';
require_once __DIR__ . '/../models/JuegoModel.php';

class JuegoController {
    private ?mysqli $conn = null;
    public ?JuegoModel $juegoModel = null;

    public function __construct() {
        $connection = new ConnectionController();
        $this->conn = $connection->connect();

        if ($this->conn) {
            $this->juegoModel = new JuegoModel($this->conn);
        }
    }

    public function isConnected(): bool {
        return $this->conn !== null && $this->juegoModel !== null;
    }

    public function sendJson(array $data, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Obtener todos los juegos activos desde MySQL (HTTP 200 / 500)
     */
    public function getAllJuegos(): void {
        if (!$this->isConnected()) {
            $this->sendJson([
                'success' => false,
                'error'   => 'connection_failed',
                'message' => 'No fue posible establecer conexión con la base de datos'
            ], 500);
        }

        try {
            $juegos = $this->juegoModel->getAllJuegos();
            $this->sendJson([
                'success' => true,
                'count'   => count($juegos),
                'data'    => $juegos
            ], 200);
        } catch (Throwable $e) {
            error_log("Excepción en JuegoController::getAllJuegos: " . $e->getMessage());
            $this->sendJson([
                'success' => false,
                'error'   => 'fetch_failed',
                'message' => 'Error interno al consultar el catálogo de juegos'
            ], 500);
        }
    }

    /**
     * Obtener juego por ID (usado también internamente por CarritoController)
     */
    public function getById($id): array {
        if (!$this->isConnected()) {
            return ['success' => false, 'error' => 'connection_failed'];
        }

        $cleanId = filter_var($id, FILTER_VALIDATE_INT);
        if ($cleanId === false || $cleanId <= 0) {
            return ['success' => false, 'error' => 'invalid_id'];
        }

        try {
            $juego = $this->juegoModel->getById($cleanId);
            if (!$juego) {
                return ['success' => false, 'error' => 'not_found'];
            }
            return ['success' => true, 'data' => $juego];
        } catch (Throwable $e) {
            error_log("Excepción en JuegoController::getById: " . $e->getMessage());
            return ['success' => false, 'error' => 'db_error'];
        }
    }
}

/* =======================================
   DESPACHO DE PETICIONES DIRECTAS AL API
======================================= */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'JuegoController.php') {
    $rawAction = $_GET['action'] ?? $_POST['action'] ?? '';
    $action = htmlspecialchars(trim((string)$rawAction), ENT_QUOTES, 'UTF-8');

    $controller = new JuegoController();

    switch ($action) {
        case 'getAll':
            $controller->getAllJuegos();
            break;

        case 'getById':
            $rawId = $_GET['id'] ?? $_POST['id'] ?? null;
            $res = $controller->getById($rawId);
            $status = 200;
            if (!$res['success']) {
                $status = ($res['error'] === 'invalid_id') ? 400 : (($res['error'] === 'not_found') ? 404 : 500);
            }
            $controller->sendJson($res, $status);
            break;

        default:
            $controller->sendJson([
                'success' => false,
                'error'   => 'invalid_action',
                'message' => 'Acción solicitada no válida'
            ], 400);
            break;
    }
}
?>
