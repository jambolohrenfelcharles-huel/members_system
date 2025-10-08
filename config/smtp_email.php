<?php
/**
 * Simple SMTP Email Class
 * Provides basic SMTP functionality for sending emails
 */

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $socket;
    private $debug;
    
    public function __construct($host, $port, $username, $password, $encryption = 'tls', $debug = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->debug = $debug;
    }
    
    public function send($to, $subject, $message, $fromEmail, $fromName = '') {
        try {
            // Connect to SMTP server
            if (!$this->connect()) {
                return false;
            }
            
            // Send EHLO command
            $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->sendCommand("EHLO " . $serverName);
            
            // Start TLS if required (only for TLS, not SSL)
            if ($this->encryption === 'tls') {
                $this->sendCommand("STARTTLS");
                if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to enable TLS");
                }
                $this->sendCommand("EHLO " . $serverName);
            }
            // For SSL, the connection is already encrypted, so no STARTTLS needed
            
            // Authenticate
            if (!empty($this->username) && !empty($this->password)) {
                if (!$this->sendCommand("AUTH LOGIN")) {
                    $this->disconnect();
                    return false;
                }
                if (!$this->sendCommand(base64_encode($this->username))) {
                    $this->disconnect();
                    return false;
                }
                if (!$this->sendCommand(base64_encode($this->password))) {
                    $this->disconnect();
                    return false;
                }
            }
            
            // Set sender
            $this->sendCommand("MAIL FROM: <$fromEmail>");
            
            // Set recipient
            $this->sendCommand("RCPT TO: <$to>");
            
            // Send data
            $this->sendCommand("DATA");
            
            // Create email headers
            $headers = "From: $fromName <$fromEmail>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            
            // Send email content
            $this->sendRaw($headers . "\r\n" . $message);
            $this->sendCommand(".");
            
            // Quit
            $this->sendCommand("QUIT");
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("SMTP Error: " . $e->getMessage());
            }
            $this->disconnect();
            return false;
        }
    }
    
    private function connect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $protocol = '';
        if ($this->encryption === 'ssl') {
            $protocol = 'ssl://';
        }
        
        $this->socket = stream_socket_client(
            $protocol . "{$this->host}:{$this->port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$this->socket) {
            if ($this->debug) {
                error_log("SMTP Connection failed: $errstr ($errno)");
            }
            return false;
        }
        
        // Read initial response
        $response = fgets($this->socket, 512);
        if ($this->debug) {
            error_log("SMTP Response: " . trim($response));
        }
        
        return strpos($response, '220') === 0;
    }
    
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        $response = fgets($this->socket, 512);
        
        if ($this->debug) {
            error_log("SMTP Command: $command");
            error_log("SMTP Response: " . trim($response));
        }
        
        $code = intval(substr($response, 0, 3));
        return $code >= 200 && $code < 400;
    }
    
    private function sendRaw($data) {
        fwrite($this->socket, $data);
    }
    
    private function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}

/**
 * Enhanced email sending function using SMTP
 */
function sendEmailViaSMTP($to, $subject, $message, $fromEmail = null, $fromName = '') {
    $config = getEmailConfig();
    
    $fromEmail = $fromEmail ?: $config['from_address'];
    $fromName = $fromName ?: $config['from_name'];
    
    // Check if SMTP is configured
    if (empty($config['smtp_host']) || $config['smtp_host'] === 'localhost') {
        return false;
    }
    
    // Create SMTP instance
    $smtp = new SimpleSMTP(
        $config['smtp_host'],
        $config['smtp_port'],
        $config['smtp_username'],
        $config['smtp_password'],
        $config['smtp_encryption'],
        $config['debug']
    );
    
    // Send email
    $result = $smtp->send($to, $subject, $message, $fromEmail, $fromName);
    
    if ($config['log_attempts']) {
        error_log("SMTP Email attempt: To=$to, Subject=$subject, From=$fromEmail, Result=" . ($result ? 'Success' : 'Failed'));
    }
    
    return $result;
}
?>
