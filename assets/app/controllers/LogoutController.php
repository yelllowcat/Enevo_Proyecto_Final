<?php
/**
 * LogoutController.php
 * Cierre seguro y destrucción completa de sesión en servidor
 */

require_once __DIR__ . '/../config/SessionSecurity.php';

SessionSecurity::startSecureSession();
SessionSecurity::preventCaching();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'] ?: '/',
            'domain'   => $params['domain'] ?: '',
            'secure'   => (bool)$params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite'] ?: 'Lax'
        ]
    );
}

session_destroy();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';

$assetsPos = strpos($scriptPath, '/assets/');
$projectPath = ($assetsPos !== false) ? substr($scriptPath, 0, $assetsPos) : '';

$baseUrl = "{$protocol}://{$host}{$projectPath}";

http_response_code(302);
header("Location: {$baseUrl}/index.html");
exit();
?>
