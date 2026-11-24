<?php
/**
 * ConnectionController.php
 * Conexión centralizada a MySQL/MariaDB compatible con XAMPP local e iFastNet
 */

mysqli_report(MYSQLI_REPORT_OFF);

class ConnectionController {

    private string $HOST;
    private string $USER;
    private string $PASS;
    private string $DBNM;
    private int $PORT;

    public function __construct() {
        $this->HOST = getenv('DB_HOST') ?: '127.0.0.1';
        $this->USER = getenv('DB_USER') ?: 'root';
        $this->PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->DBNM = getenv('DB_NAME') ?: 'enevo';
        $this->PORT = (int)(getenv('DB_PORT') ?: 3307);
    }

    public function connect(): ?mysqli {
        $candidatePorts = array_values(array_unique([$this->PORT, 3307, 3306, 3308]));

        foreach ($candidatePorts as $port) {
            try {
                $conn = @new mysqli($this->HOST, $this->USER, $this->PASS, $this->DBNM, $port);
                if ($conn && !$conn->connect_errno) {
                    $conn->set_charset('utf8mb4');
                    return $conn;
                }
            } catch (Throwable $e) {
                error_log("Error ConnectionController en puerto {$port}: " . $e->getMessage());
            }
        }

        error_log("ConnectionController: no fue posible conectar a MySQL ({$this->DBNM}).");
        return null;
    }
}
?>
