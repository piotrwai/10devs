<?php
/**
 * Klasa obsługująca uwierzytelnianie i autoryzację użytkowników w aplikacji 10devs.
 * 
 * Klasa zapewnia mechanizmy:
 * - Generowania tokenów JWT (JSON Web Token)
 * - Walidacji tokenów JWT
 * - Zarządzania uwierzytelnianiem użytkowników
 * - Uzyskiwania informacji o zalogowanym użytkowniku
 * 
 * @package 10devs
 * @author 10devs Team
 * @version 1.0
 */
class Auth {
    /**
     * Klucz tajny używany do podpisywania i weryfikacji tokenów JWT.
     * 
     * @var string
     */
    private $secretKey;
    
    /**
     * Czas ważności tokenu JWT w sekundach.
     * 
     * @var int
     */
    private $expiration;
    
    /**
     * Identyfikator wydawcy (issuer) tokenu JWT.
     * 
     * @var string
     */
    private $issuer;
    
    /**
     * Inicjalizuje nową instancję klasy Auth z konfiguracją JWT.
     * 
     * Wczytuje konfigurację z pliku config.php, w tym tajny klucz, 
     * czas ważności tokenu i identyfikator wydawcy.
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
     * Uwierzytelnia użytkownika na podstawie tokenu JWT i zwraca ID użytkownika.
     * 
     * Metoda próbuje odczytać token JWT z ciasteczka lub nagłówka HTTP Authorization,
     * a następnie weryfikuje jego poprawność i zwraca ID użytkownika.
     * 
     * @return int|null ID użytkownika, jeśli uwierzytelnienie się powiodło, lub null w przypadku niepowodzenia
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
     * Dekoduje token JWT i zwraca zawarte w nim dane.
     * 
     * Metoda weryfikuje wszystkie trzy części tokenu JWT (nagłówek, ładunek, podpis),
     * sprawdza czy token nie wygasł oraz czy podpis jest poprawny.
     * 
     * @param string $token Token JWT do zdekodowania
     * @return object Zdekodowane dane z tokenu (payload)
     * @throws Exception Gdy token jest nieprawidłowy (nieważny format, wygasły, nieprawidłowy podpis)
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
     * Generuje token JWT dla użytkownika.
     * 
     * Metoda tworzy token JWT zawierający ID użytkownika, login, flagę administratora,
     * czas wygenerowania (iat), czas wygaśnięcia (exp) i identyfikator wydawcy (iss).
     * 
     * @param array $userData Dane użytkownika zawierające:
     *                        - usr_id (int): ID użytkownika
     *                        - usr_login (string): Login użytkownika
     *                        - usr_admin (bool, opcjonalnie): Flaga administratora
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