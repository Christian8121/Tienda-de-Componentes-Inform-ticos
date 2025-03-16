<?php
class Database {
    private $host = "localhost";
    private $db_name = "tienda_informatica";
    private $username = "root";
    private $password = "";
    private $conn;
    private $port = 3307;

    // Método para conectar a la base de datos
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=$this->host;port=$this->port;dbname=$this->db_name", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4"); // Actualizado a utf8mb4 para mejor soporte de caracteres
        } catch(PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
