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
        
        // For Render MySQL, extract host from internal database URL if needed
        if (isset($_ENV['DB_HOST']) && strpos($_ENV['DB_HOST'], '://') !== false) {
            // Parse internal database URL format: mysql://user:pass@host:port/dbname
            $url = parse_url($_ENV['DB_HOST']);
            $this->host = $url['host'];
            if (isset($url['port'])) {
                $this->host .= ':' . $url['port'];
            }
            $this->username = $url['user'] ?? $this->username;
            $this->password = $url['pass'] ?? $this->password;
            $this->db_name = ltrim($url['path'], '/') ?? $this->db_name;
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Always use MySQL for this application
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
