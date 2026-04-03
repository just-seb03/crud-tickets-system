<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'rectoria_db'; 
    private $username = 'root';
    private $password = ''; 

    public function getConnection() {
        $conn = null;
        try {
            $conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $conn->exec("set names utf8");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die("Error de conexión: " . $exception->getMessage());
        }
        return $conn;
    }
}
?>