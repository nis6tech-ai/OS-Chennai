<?php
/**
 * Simple SMTP Helper for PHP (no external dependencies)
 * Specifically for Zoho Mail SMTP
 */
class SimpleSmtp {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $debug = [];

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $body, $headersArr = []) {
        $newline = "\r\n";
        $timeout = 10;
        
        // Connect to server (Zoho usually on 465)
        $useSsl = ($this->port == 465) ? 'ssl://' : '';
        $socket = @fsockopen($useSsl . $this->host, $this->port, $errno, $errstr, $timeout);
        
        if (!$socket) {
             throw new Exception("Connection failed: $errstr ($errno)");
        }
        
        $this->getResponse($socket); // Initial greeting
        
        $this->sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        // SMTP Auth
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->user));
        $this->sendCommand($socket, base64_encode($this->pass));
        
        // Mail Transaction
        $this->sendCommand($socket, "MAIL FROM: <" . $this->user . ">");
        $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
        $this->sendCommand($socket, "DATA");
        
        // Construct Email Content
        $headers = "To: $to" . $newline;
        $headers .= "Subject: $subject" . $newline;
        $headers .= "From: Support <" . $this->user . ">" . $newline;
        $headers .= "MIME-Version: 1.0" . $newline;
        $headers .= "Content-type: text/html; charset=UTF-8" . $newline;
        
        foreach ($headersArr as $k => $v) {
            $headers .= "$k: $v" . $newline;
        }
        
        $emailContent = $headers . $newline . $body . $newline . ".";
        $this->sendCommand($socket, $emailContent);
        
        $this->sendCommand($socket, "QUIT");
        fclose($socket);
        return true;
    }

    private function sendCommand($socket, $command) {
        fputs($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }

    private function getResponse($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        $this->debug[] = $response;
        return $response;
    }
}
?>
