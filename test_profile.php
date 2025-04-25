<?php
/**
 * Plik testowy dla interfejsu profilu użytkownika
 * Używa mechanizmu JWT identycznego jak w test_api.php i pobiera dane użytkownika z API
 */

// Ustawienie kodowania na UTF-8
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=utf-8');

// Dołączenie niezbędnych plików
require_once 'config.php';
require_once __DIR__ . '/smarty/configs/config.php';

// =============== FUNKCJE Z test_api.php ===============

/**
 * Generuje testowy token JWT dla użytkownika
 * 
 * @param int $userId ID użytkownika
 * @param string $login Login użytkownika
 * @return string Token JWT
 */
function generateTestJwtToken($userId, $login) {
    // Wczytanie konfiguracji
    $config = require __DIR__ . '/config.php';
    $secretKey = $config['jwt']['secret_key'];
    $expiration = time() + ($config['jwt']['expiration'] ?? 3600);
    $issuer = $config['jwt']['issuer'] ?? '10devs-api';
    
    // Przygotowanie nagłówka
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Przygotowanie payloadu
    $payload = [
        'sub' => $userId,          // ID użytkownika
        'login' => $login,         // Login użytkownika
        'iat' => time(),          // Czas wygenerowania tokena
        'exp' => $expiration,     // Czas wygaśnięcia tokena
        'iss' => $issuer          // Wydawca tokena
    ];
    
    // Kodowanie nagłówka i payloadu
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    // Generowanie podpisu
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    // Złożenie tokena
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Funkcja wykonująca zapytanie cURL
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init($url);
    
    // Dodanie podstawowych nagłówków
    $defaultHeaders = [
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    // Konfiguracja cURL
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Wyłączenie weryfikacji certyfikatu SSL
    
    // Dodanie danych dla metod POST, PUT
    if ($method === 'POST' || $method === 'PUT') {
        if ($data !== null) {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
    }
    
    // Wykonanie zapytania
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Sprawdzenie błędów
    if (curl_errno($ch)) {
        echo "Błąd cURL: " . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
    
    // Próba dekodowania JSON
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "Błąd dekodowania JSON: " . json_last_error_msg() . "\n";
        echo "Oryginalna odpowiedź: " . $response . "\n";
        return ['response' => $response, 'code' => $httpCode];
    }
    
    return ['response' => $decodedResponse, 'code' => $httpCode];
}

// =============== KOD GŁÓWNY ===============

// Adres bazowy API (tak jak w test_api.php)
$baseUrl = 'http://10devs.local';

// Generowanie tokenu JWT dla użytkownika testowego (ID=1, login='test')
$jwtToken = generateTestJwtToken(1, 'test');

// Pobranie danych użytkownika z API
$url = "$baseUrl/api/users/me.php";
$headers = ["Authorization: Bearer $jwtToken"];
$result = makeRequest($url, 'GET', null, $headers);


// Sprawdzenie czy odpowiedź jest poprawna
if ($result['code'] === 200) {
    // API zwraca dane w strukturze { success: true, data: { ... } }
    // Obsługa różnych możliwych formatów odpowiedzi
    
    if (isset($result['response']['success']) && $result['response']['success'] === true) {
        // Format: { success: true, data: { ... } }
        if (isset($result['response']['data'])) {
            $currentUser = $result['response']['data'];
        } else {
            echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin-bottom: 10px; border: 1px solid #ffeeba;'>
                  <strong>Ostrzeżenie:</strong> Odpowiedź API ma sukces=true, ale brak pola 'data'.
                  </div>";
            $currentUser = [];
        }
    } else {
        // Format: dane bezpośrednio w odpowiedzi
        $currentUser = $result['response'];
    }
    
    // Upewnij się, że wszystkie wymagane pola są dostępne
    $requiredFields = ['id', 'login', 'cityBase', 'isAdmin'];
    $allFieldsPresent = true;
    
    foreach ($requiredFields as $field) {
        if (!isset($currentUser[$field])) {
            $allFieldsPresent = false;
            echo "<div style='background: #fff3cd; color: #856404; padding: 5px; margin: 2px; border: 1px solid #ffeeba;'>
                  Brakujące pole '$field' w danych użytkownika.
                  </div>";
            
            // Dodajemy brakujące pole z wartością domyślną
            $currentUser[$field] = ($field == 'isAdmin') ? true : ($field == 'id' ? 1 : 'test');
        }
    }
    
    // Jeśli brakuje jakichkolwiek pól, pokazujemy pełną odpowiedź
    if (!$allFieldsPresent) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin-bottom: 10px; border: 1px solid #ffeeba;'>
              <strong>Ostrzeżenie:</strong> Struktura danych z API nie zawiera wszystkich wymaganych pól.
              </div>";
    }
} else {
    // W przypadku błędu, użyj danych testowych
    $currentUser = [
        'id' => 1,
        'login' => 'testowy_uzytkownik',
        'cityBase' => 'Warszawa',
        'isAdmin' => true
    ];
    
    // Komunikat o błędzie
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px; border: 1px solid #f5c6cb;'>
          <strong>Uwaga!</strong> Używane są dane testowe, ponieważ nie udało się pobrać danych z API.
          Kod HTTP: {$result['code']}
          </div>";
}

// Przypisanie danych użytkownika do Smarty
$smarty->assign('currentUser', $currentUser);

// Dodanie tokenu JWT jako zmiennej globalnej
$smarty->assign('jwtToken', $jwtToken);

// Renderowanie szablonu profile.tpl
$smarty->display('profile.tpl'); 