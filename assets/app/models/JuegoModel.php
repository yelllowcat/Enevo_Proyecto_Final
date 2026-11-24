<?php
/**
 * JuegoModel.php
 * Modelo de catálogo de videojuegos con consultas preparadas
 */

class JuegoModel {
    private mysqli $conn;
    private string $table = 'juegos';

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Obtiene todos los juegos mediante sentencia preparada
     */
    public function getAllJuegos(): array {
        $sql = "SELECT id, nombre, descripcion, precio, genero, plataforma, fecha_creacion
                FROM {$this->table}
                ORDER BY id ASC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare getAllJuegos: " . $this->conn->error);
            throw new RuntimeException("Error al preparar la consulta del catálogo");
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            error_log("Error execute getAllJuegos: " . $err);
            throw new RuntimeException("Error al ejecutar la consulta del catálogo");
        }

        $result = $stmt->get_result();
        $juegos = [];
        while ($row = $result->fetch_assoc()) {
            $juegos[] = $row;
        }

        $stmt->close();
        return $juegos;
    }

    /**
     * Obtiene un juego por su ID mediante sentencia preparada
     */
    public function getById(int $id): ?array {
        $sql = "SELECT id, nombre, descripcion, precio, genero, plataforma, fecha_creacion
                FROM {$this->table}
                WHERE id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare getById: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Error execute getById: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $juego = $result->fetch_assoc() ?: null;
        $stmt->close();

        return $juego;
    }
}
?>
