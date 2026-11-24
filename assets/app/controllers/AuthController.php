<?php
/**
 * AuthController.php
 * Autenticación y registro con sanitización estricta de entradas (OWASP A03:2021),
 * gestión segura de sesiones en servidor (ISO/IEC 27001 Control 5.15 / 8.5 / 8.28)
 * y notificaciones flash en sesión sin exponer errores técnicos en la URL.
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/SessionSecurity.php';
require_once __DIR__ . '/../config/ConnectionController.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ClientesModel.php';

SessionSecurity::startSecureSession();

class AuthController {
    private ?mysqli $conn = null;
    private ?UserModel $userModel = null;

    private const MESSAGES = [
        'empty'             => 'Todos los campos son obligatorios.',
        'invalid_name'      => 'El nombre contiene caracteres o etiquetas HTML no permitidos.',
        'invalid_email'     => 'El formato del correo electrónico no es válido.',
        'weak_password'     => 'La contraseña debe tener al menos 6 caracteres.',
        'password_mismatch' => 'Las contraseñas ingresadas no coinciden.',
        'email_exists'      => 'Este correo electrónico ya está registrado.',
        'username_exists'   => 'Este nombre de usuario ya se encuentra en uso.',
        'duplicate_data'    => 'El correo o nombre de usuario ya están registrados.',
        'database'          => 'No fue posible procesar la solicitud en este momento. Intenta nuevamente.',
        'register_failed'   => 'No se pudo completar el registro. Verifica tus datos.',
        'login'             => 'Correo electrónico o contraseña incorrectos.',
        'server'            => 'Servicio temporalmente no disponible. Intenta más tarde.',
        'connection'        => 'Servicio temporalmente no disponible. Intenta más tarde.',
        'invalid_action'    => 'Solicitud no válida.',
        'registered_ok'     => 'Registro exitoso. Ya puedes iniciar sesión.'
    ];

    public function __construct(bool $requireDb = true) {
        if (!$requireDb) {
            return;
        }

        $connection = new ConnectionController();
        $this->conn = $connection->connect();

        if (!$this->conn) {
            $this->redirectWithFlash('login', 'connection');
        }

        $this->userModel = new UserModel($this->conn);
    }

    /**
     * Sanitiza cadenas generales contra inyección HTML / XSS
     */
    public static function sanitizeText(string $input): string {
        $trimmed = trim($input);
        return htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitiza correo electrónico
     */
    public static function sanitizeEmail(string $email): string {
        $cleaned = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return htmlspecialchars((string)$cleaned, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Detecta etiquetas HTML, scripts o vectores XSS en entradas de texto
     */
    public static function containsXssPayload(string $rawInput): bool {
        if ($rawInput !== strip_tags($rawInput)) {
            return true;
        }
        if (preg_match('/[<>"\'`]|javascript\s*:|on\w+\s*=/i', $rawInput)) {
            return true;
        }
        return false;
    }

    /**
     * Almacena mensaje controlado en sesión y redirige con URL limpia (0 parámetros en URL)
     */
    public function redirectWithFlash(string $page, string $code, string $type = 'error'): void {
        $_SESSION['auth_flash'] = [
            'type'    => $type,
            'message' => self::MESSAGES[$code] ?? self::MESSAGES['server']
        ];

        $baseUrl = $this->getBaseUrl();
        $pageMap = [
            'register'       => 'registro.html',
            'login'          => 'login.html',
            'principal'      => 'principal.php',
            'principalAdmin' => 'principalAdmin.php'
        ];

        $fileName = $pageMap[$page] ?? 'login.html';
        http_response_code(302);
        header("Location: {$baseUrl}/views/{$fileName}");
        exit();
    }

    private function getBaseUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $assetsPos = strpos($scriptPath, '/assets/');

        if ($assetsPos !== false) {
            $projectPath = substr($scriptPath, 0, $assetsPos);
        } else {
            $projectPath = dirname(dirname(dirname(dirname($scriptPath))));
        }

        return "{$protocol}://{$host}{$projectPath}";
    }

    public function login(string $rawEmail, string $rawPassword): void {
        try {
            $password = trim($rawPassword);

            if (trim($rawEmail) === '' || $password === '') {
                $this->redirectWithFlash('login', 'empty');
            }

            if (self::containsXssPayload($rawEmail)) {
                $this->redirectWithFlash('login', 'invalid_email');
            }

            $email = self::sanitizeEmail($rawEmail);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithFlash('login', 'invalid_email');
            }

            $user = $this->userModel->getUserByEmail($email);
            if (!$user || !password_verify($password, $user['password'])) {
                $this->redirectWithFlash('login', 'login');
            }

            session_regenerate_id(true);
            $_SESSION['user_id']     = (int)$user['id'];
            $_SESSION['user_nombre'] = self::sanitizeText((string)$user['nombre']);
            $_SESSION['user_email']  = self::sanitizeEmail((string)$user['email']);
            $_SESSION['user_rol']    = ($user['rol'] === 'admin') ? 'admin' : 'cliente';

            $baseUrl = $this->getBaseUrl();
            $targetView = ($_SESSION['user_rol'] === 'admin') ? 'principalAdmin.php' : 'principal.php';

            http_response_code(302);
            header("Location: {$baseUrl}/views/{$targetView}");
            exit();

        } catch (Throwable $e) {
            error_log("Error en AuthController::login: " . $e->getMessage());
            $this->redirectWithFlash('login', 'server');
        }
    }

    public function register(string $rawNombre, string $rawEmail, string $rawPassword, string $rawConfirm): void {
        try {
            $trimmedNombre = trim($rawNombre);
            $trimmedEmail  = trim($rawEmail);
            $password      = trim($rawPassword);
            $confirm       = trim($rawConfirm);

            if ($trimmedNombre === '' || $trimmedEmail === '' || $password === '' || $confirm === '') {
                $this->redirectWithFlash('register', 'empty');
            }

            // Bloquear payloads XSS / etiquetas HTML en nombre o correo
            if (self::containsXssPayload($trimmedNombre) || mb_strlen($trimmedNombre) > 100) {
                $this->redirectWithFlash('register', 'invalid_name');
            }

            if (self::containsXssPayload($trimmedEmail)) {
                $this->redirectWithFlash('register', 'invalid_email');
            }

            $nombre = self::sanitizeText($trimmedNombre);
            $email  = self::sanitizeEmail($trimmedEmail);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithFlash('register', 'invalid_email');
            }

            if (strlen($password) < 6) {
                $this->redirectWithFlash('register', 'weak_password');
            }

            if ($password !== $confirm) {
                $this->redirectWithFlash('register', 'password_mismatch');
            }

            if ($this->userModel->getUserByEmail($email)) {
                $this->redirectWithFlash('register', 'email_exists');
            }

            if ($this->userModel->usernameExists($nombre)) {
                $this->redirectWithFlash('register', 'username_exists');
            }

            $result = $this->userModel->createUser($nombre, $email, $password);

            if ($result['success']) {
                $userId = $result['user_id'] ?? null;
                $clientesModel = new ClientesModel($this->conn);
                $crearCliente = $clientesModel->crearClienteDesdeUsuario($userId, $nombre, $email);

                if ($crearCliente['success']) {
                    $this->redirectWithFlash('login', 'registered_ok', 'success');
                } else {
                    if ($userId) {
                        $this->userModel->deleteUserById($userId);
                    }
                    $this->redirectWithFlash('register', 'database');
                }
            } else {
                $errorKey = ($result['error'] === 'duplicate') ? 'duplicate_data' : 'register_failed';
                $this->redirectWithFlash('register', $errorKey);
            }
        } catch (Throwable $e) {
            error_log("Error en AuthController::register: " . $e->getMessage());
            $this->redirectWithFlash('register', 'server');
        }
    }
}

/* =====================================================
   ENRUTAMIENTO CONTROLADO (GET: flash / session_status | POST: login / register)
===================================================== */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'AuthController.php') {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $getAction = AuthController::sanitizeText((string)($_GET['action'] ?? ''));
        header('Content-Type: application/json; charset=utf-8');

        if ($getAction === 'flash') {
            $flash = $_SESSION['auth_flash'] ?? null;
            unset($_SESSION['auth_flash']);
            http_response_code(200);
            echo json_encode(['success' => true, 'flash' => $flash], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($getAction === 'session_status') {
            $authenticated = !empty($_SESSION['user_id']);
            http_response_code(200);
            echo json_encode([
                'success'       => true,
                'authenticated' => $authenticated,
                'rol'           => $authenticated ? ($_SESSION['user_rol'] ?? 'cliente') : null,
                'nombre'        => $authenticated ? ($_SESSION['user_nombre'] ?? '') : null
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $auth = new AuthController(false);
        $auth->redirectWithFlash('login', 'invalid_action');
    }

    try {
        $action   = AuthController::sanitizeText((string)($_POST['action'] ?? ''));
        $nombre   = (string)($_POST['nombre'] ?? $_POST['name'] ?? '');
        $email    = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if ($action === 'register') {
            $auth = new AuthController(true);
            $auth->register($nombre, $email, $password, $confirm);
        } elseif ($action === 'login') {
            $auth = new AuthController(true);
            $auth->login($email, $password);
        } else {
            $auth = new AuthController(false);
            $auth->redirectWithFlash('login', 'invalid_action');
        }
    } catch (Throwable $e) {
        error_log("Excepción global AuthController: " . $e->getMessage());
        $auth = new AuthController(false);
        $auth->redirectWithFlash('login', 'server');
    }
}
?>
