<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Use environment variables if available (for Render), otherwise use defaults
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'members_system';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Check if we're using PostgreSQL (Render) or MySQL (local)
            $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
            
            if ($db_type === 'postgresql') {
                $dsn = "pgsql:host=" . $this->host . ";dbname=" . $this->db_name;
            } else {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            }
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
