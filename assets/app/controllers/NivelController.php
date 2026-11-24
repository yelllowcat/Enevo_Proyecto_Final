<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/SessionSecurity.php';
require_once __DIR__ . '/../config/ConnectionController.php';
require_once __DIR__ . '/../models/NivelModel.php';

class NivelController {
    private ?mysqli $conn = null;
    public ?NivelModel $nivelModel = null;

    public function __construct() {
        try {
            $connection = new ConnectionController();
            $this->conn = $connection->connect();

            if (!$this->conn) {
                throw new Exception("No se pudo conectar a la base de datos");
            }

            $this->nivelModel = new NivelModel($this->conn);
        } catch (Exception $e) {
            error_log("Error constructor NivelController: " . $e->getMessage());
            $this->sendJson(['success' => false, 'error' => 'connection_failed'], 500);
        }
    }

    private function sendJson(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function getAllNiveles(): void {
        try {
            $niveles = $this->nivelModel->getAllNiveles();
            $this->sendJson(['success' => true, 'data' => $niveles], 200);
        } catch (Exception $e) {
            error_log("Error getAllNiveles: " . $e->getMessage());
            $this->sendJson(['success' => false, 'error' => 'fetch_failed'], 500);
        }
    }

    public function getNivelById($id): void {
        if (!$id || !is_numeric($id)) {
            $this->sendJson(['success' => false, 'error' => 'invalid_id'], 400);
        }

        $nivel = $this->nivelModel->getNivelById((int)$id);
        if (!$nivel) {
            $this->sendJson(['success' => false, 'error' => 'not_found'], 404);
        }

        $this->sendJson(['success' => true, 'data' => $nivel], 200);
    }

    public function createNivel($nombre, $puntos, $compras, $descripcion): void {
        SessionSecurity::requireAdminJson();
        $cleanNombre = htmlspecialchars(trim((string)$nombre), ENT_QUOTES, 'UTF-8');
        $cleanDesc   = htmlspecialchars(trim((string)$descripcion), ENT_QUOTES, 'UTF-8');
        $result = $this->nivelModel->createNivel($cleanNombre, (int)$puntos, (int)$compras, $cleanDesc);
        $this->sendJson($result, $result['success'] ? 200 : 400);
    }

    public function updateNivel($id, $nombre, $puntos, $compras, $descripcion): void {
        SessionSecurity::requireAdminJson();
        $cleanNombre = htmlspecialchars(trim((string)$nombre), ENT_QUOTES, 'UTF-8');
        $cleanDesc   = htmlspecialchars(trim((string)$descripcion), ENT_QUOTES, 'UTF-8');
        $result = $this->nivelModel->updateNivel((int)$id, $cleanNombre, (int)$puntos, (int)$compras, $cleanDesc);
        $this->sendJson($result, $result['success'] ? 200 : 400);
    }

    public function deleteNivel($id): void {
        SessionSecurity::requireAdminJson();
        $result = $this->nivelModel->deleteNivel((int)$id);
        $this->sendJson($result, $result['success'] ? 200 : 400);
    }

    public function clientesByNivel($id): void {
        SessionSecurity::requireAdminJson();
        if (!$id || !is_numeric($id)) {
            $this->sendJson(['success' => false, 'data' => []], 400);
        }

        $clientes = $this->nivelModel->getClientesByNivel((int)$id);
        $this->sendJson([
            'success' => true,
            'data'    => $clientes
        ], 200);
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'NivelController.php') {
    $action = htmlspecialchars(trim((string)($_GET['action'] ?? $_POST['action'] ?? '')), ENT_QUOTES, 'UTF-8');
    $controller = new NivelController();

    switch ($action) {
        case 'getAll':
            $controller->getAllNiveles();
            break;

        case 'getById':
            $controller->getNivelById($_GET['id'] ?? null);
            break;

        case 'create':
            $controller->createNivel($_POST['nombre'] ?? '', $_POST['puntos'] ?? 0, $_POST['compras'] ?? 0, $_POST['descripcion'] ?? '');
            break;

        case 'update':
            $controller->updateNivel($_POST['nivelId'] ?? 0, $_POST['nombre'] ?? '', $_POST['puntos'] ?? 0, $_POST['compras'] ?? 0, $_POST['descripcion'] ?? '');
            break;

        case 'delete':
            $controller->deleteNivel($_POST['id'] ?? null);
            break;

        case 'clientesByNivel':
            $controller->clientesByNivel($_GET['id'] ?? null);
            break;

        default:
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'invalid_action']);
            break;
    }
}
?>
