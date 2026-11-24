<?php
/**
 * SessionSecurity.php
 * Gestión centralizada de sesiones seguras y control de acceso en servidor
 * Alineado con ISO/IEC 27001:2022 (Control 5.15 y 8.5) y OWASP A01:2021
 */

class SessionSecurity {

    public static function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }
    }

    public static function preventCaching(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    public static function requireClient(string $loginUrl = 'login.html', string $adminUrl = 'principalAdmin.php'): void {
        self::startSecureSession();
        self::preventCaching();

        if (empty($_SESSION['user_id'])) {
            http_response_code(302);
            header("Location: {$loginUrl}");
            exit();
        }

        if (($_SESSION['user_rol'] ?? '') === 'admin') {
            http_response_code(302);
            header("Location: {$adminUrl}");
            exit();
        }
    }

    public static function requireAdmin(string $loginUrl = 'login.html'): void {
        self::startSecureSession();
        self::preventCaching();

        if (empty($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'admin') {
            http_response_code(302);
            header("Location: {$loginUrl}");
            exit();
        }
    }

    public static function requireAdminJson(): void {
        self::startSecureSession();

        if (empty($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'unauthorized',
                'message' => 'No autorizado: se requiere sesión activa de administrador'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}
?>
