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
        $config = require_once __DIR__ . '/../config.php';
        
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
        // Pobranie tokenu z nagłówka Authorization
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
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
} 