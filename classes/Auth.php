<?php
// Klasa obsługująca uwierzytelnianie poprzez JWT

class Auth {
    private $secretKey;
    private $expiration;
    private $issuer;
    
    /**
     * Konstruktor inicjujący parametry JWT
     */
    public function __construct() {
        // Wczytanie konfiguracji z pliku
        $config = require __DIR__ . '/../config.php';
        
        // Pobranie danych konfiguracyjnych JWT
        $this->secretKey = $config['jwt']['secret_key'] ?? 'default-secret-key';
        $this->expiration = $config['jwt']['expiration'] ?? 3600; // domyślnie 1 godzina
        $this->issuer = $config['jwt']['issuer'] ?? '10devs-api';
    }
    
    /**
     * Uwierzytelnia użytkownika na podstawie tokenu JWT i zwraca ID użytkownika
     * 
     * @return int|null ID użytkownika lub null w przypadku niepowodzenia
     */
    public function authenticateAndGetUserId() {
        $token = '';
        
        // Najpierw sprawdź ciasteczko
        if (isset($_COOKIE['jwtToken'])) {
            $token = $_COOKIE['jwtToken'];
        }
        
        // Jeśli nie ma w ciasteczku, sprawdź nagłówek Authorization
        if (empty($token)) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                         (isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '');
            
            if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (empty($token)) {
            return null;
        }
        
        try {
            // Walidacja i dekodowanie tokenu JWT
            $decodedToken = $this->decodeJwtToken($token);
            
            if (!isset($decodedToken->sub) || empty($decodedToken->sub)) {
                return null;
            }
            
            // Zwrócenie ID użytkownika z tokenu
            return (int)$decodedToken->sub;
            
        } catch (Exception $e) {
            // Logowanie błędu można dodać tutaj
            //error_log("JWT Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Dekoduje token JWT i zwraca zawarte w nim dane
     * 
     * @param string $token Token JWT do zdekodowania
     * @return object Zdekodowane dane z tokenu
     * @throws Exception Gdy token jest nieprawidłowy
     */
    private function decodeJwtToken($token) {
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            throw new Exception('Nieprawidłowy format tokenu JWT');
        }
        
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])));
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])));
        $signatureProvided = $tokenParts[2];
        
        // Sprawdzenie czy token nie wygasł
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token JWT wygasł');
        }
        
        // Weryfikacja podpisu
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64UrlSignature !== $signatureProvided) {
            throw new Exception('Nieprawidłowy podpis tokenu JWT');
        }
        
        return $payload;
    }

    /**
     * Generuje token JWT dla użytkownika
     * 
     * @param array $userData Dane użytkownika (usr_id, usr_login, usr_admin)
     * @return string Wygenerowany token JWT
     */
    public function generateJwtToken($userData) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [
            'sub' => $userData['usr_id'],
            'login' => $userData['usr_login'],
            'admin' => $userData['usr_admin'] ?? false,
            'iat' => time(),
            'exp' => time() + $this->expiration,
            'iss' => $this->issuer
        ];
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
} 