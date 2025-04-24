<?php
/**
 * Skrypt do testowania endpointów API
 * 
 * Zawiera funkcje z przykładowymi zapytaniami cURL
 * Możesz wywołać poszczególne funkcje, odkomentowując wybrane wywołania na końcu pliku
 */

// Ustawienie kodowania na UTF-8
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=utf-8');

// ================ KONFIGURACJA ================
$baseUrl = 'http://10devs.local'; // Zmień na właściwy adres API
//$jwtToken = '$2y$10$X.8RV0y8GRd5w.YXFe5y0egD1.sOB0a97f7w2NFRAi3LvNhcfoPIO'; // Zastąp faktycznym tokenem JWT
$simulateDb = false; // Jeśli true, będziemy symulować zapis do bazy danych (w przypadku problemów z bazą)

// ================ SZABLONY ================
require_once __DIR__ . '/smarty/configs/config.php';

// ================ FUNKCJE POMOCNICZE ================
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
 * 
 * @param string $url URL zapytania
 * @param string $method Metoda HTTP (GET, POST, PUT, DELETE)
 * @param array|null $data Dane do wysłania (dla POST, PUT)
 * @param array $headers Nagłówki HTTP
 * @return array Odpowiedź z API (zdekodowany JSON) i kod odpowiedzi
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

/**
 * Funkcja wyświetlająca wynik zapytania w czytelnej formie
 * 
 * @param array $result Wynik zapytania z funkcji makeRequest
 * @param string $endpointName Nazwa testowanego endpointu
 */
function printResult($result, $endpointName) {
    echo "\n====== TEST ENDPOINTU: $endpointName ======\n";
    echo "Kod HTTP: " . $result['code'] . "\n";
    echo "Odpowiedź:\n";
    echo json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "======================================\n\n";
}

// ================ FUNKCJE TESTUJĄCE ENDPOINTY ================

/**
 * Test endpointu wyszukiwania miasta
 * 
 * @param string $cityName Nazwa miasta do wyszukania
 * @param string $token Token JWT do autoryzacji
 * @return array Wynik zapytania
 */
function testCitySearch($cityName, $token) {
    global $baseUrl;
    
    $url = "$baseUrl/api/cities/search.php";
    $method = 'POST';
    $headers = ["Authorization: Bearer $token"];
    $data = [
        "cityName" => $cityName
    ];
    
    echo "Wysyłanie zapytania o miasto: $cityName\n";
    $result = makeRequest($url, $method, $data, $headers);
    printResult($result, "Wyszukiwanie miasta");
    
    return $result;
}

/**
 * Test endpointu zapisywania rekomendacji
 * 
 * @param int $cityId ID miasta
 * @param array $recommendations Lista rekomendacji
 * @param string $token Token JWT do autoryzacji
 * @return array Wynik zapytania
 */
function testSaveRecommendations($cityId, $recommendations, $token) {
    global $baseUrl;
    
    $url = "$baseUrl/api/recommendations/save.php";
    $method = 'POST';
    $headers = ["Authorization: Bearer $token"];
    $data = [
        "cityId" => $cityId,
        "recommendations" => array_map(function($rec) {
            return [
                "title" => $rec["title"],
                "description" => $rec["description"],
                "model" => $rec["model"] ?? "gpt-4.1-mini"
            ];
        }, $recommendations)
    ];
    
    echo "Wysyłanie zapytania o zapisanie rekomendacji dla miasta ID: " . $cityId . "\n";
    $result = makeRequest($url, $method, $data, $headers);
    printResult($result, "Zapisywanie rekomendacji");
    
    return $result;
}

/**
 * Test endpointu pobierającego profil użytkownika (GET /api/users/me)
 * 
 * @param string $token Token JWT do autoryzacji
 * @return array Wynik zapytania
 */
function testGetUserProfile($token) {
    global $baseUrl;
    
    $url = "$baseUrl/api/users/me.php";
    $method = 'GET';
    $headers = ["Authorization: Bearer $token"];
    
    echo "Wysyłanie zapytania o profil zalogowanego użytkownika\n";
    $result = makeRequest($url, $method, null, $headers);
    printResult($result, "Pobieranie profilu użytkownika");
    
    return $result;
}

/**
 * Test endpointu pobierającego listę miast użytkownika (GET /api/cities)
 * 
 * @param string $token Token JWT do autoryzacji
 * @param array $params Parametry zapytania (opcjonalne: page, per_page, visited)
 * @return array Wynik zapytania
 */
function testGetCities($token, $params = []) {
    global $baseUrl;
    
    $queryString = '';
    if (!empty($params)) {
        $queryString = '?' . http_build_query($params);
    }
    
    $url = "$baseUrl/api/cities/index.php" . $queryString;
    $method = 'GET';
    $headers = ["Authorization: Bearer $token"];
    
    echo "Pobieranie listy miast" . (!empty($queryString) ? " z parametrami $queryString" : "") . "\n";
    $result = makeRequest($url, $method, null, $headers);
    printResult($result, "Lista miast użytkownika");
    
    return $result;
}

/**
 * Test endpointu aktualizującego status odwiedzenia miasta (PUT /api/cities/{cityId})
 * 
 * @param int $cityId ID miasta do aktualizacji
 * @param bool $visited Nowy status odwiedzenia
 * @param string $token Token JWT do autoryzacji
 * @return array Wynik zapytania
 */
function testUpdateCity($cityId, $visited, $token) {
    global $baseUrl;
    
    $url = "$baseUrl/api/cities/update.php/$cityId";
    $method = 'PUT';
    $headers = ["Authorization: Bearer $token"];
    $data = [
        "visited" => $visited
    ];
    
    echo "Aktualizacja statusu odwiedzenia miasta ID: $cityId na " . ($visited ? "odwiedzone" : "nieodwiedzone") . "\n";
    $result = makeRequest($url, $method, $data, $headers);
    printResult($result, "Aktualizacja statusu miasta");
    
    return $result;
}

// ================ PRZYKŁADOWE DANE DO TESTÓW ================
//$smarty->assign('name', 'Ned 222');
//$smarty->display('index.tpl');


// Generowanie tokena JWT dla użytkownika testowego (ID=1, login='test')
$jwtToken = generateTestJwtToken(1, 'test');
echo "Wygenerowany token JWT dla użytkownika testowego:\n";
echo $jwtToken . "\n\n";

// Test wyszukiwania miasta
/*
$cityToSearch = "Łódź";

$searchResult = testCitySearch($cityToSearch, $jwtToken);

// Jeśli wyszukiwanie się powiodło
if ($searchResult['code'] === 200 && 
    isset($searchResult['response']['success']) && 
    $searchResult['response']['success'] === true && 
    isset($searchResult['response']['data'])) {
    
    $cityData = $searchResult['response']['data']['city'];
    $recommendations = $searchResult['response']['data']['recommendations'] ?? [];
    
    // Jeśli ID miasta jest null, a tryb symulacji jest włączony, użyjemy tymczasowego ID
    if (($cityData['id'] === null || empty($cityData['id'])) && $simulateDb) {
        echo "Tryb symulacji bazy danych: Używamy tymczasowego ID miasta (1).\n";
        $cityId = 1; // Symulowane ID miasta
    } else {
        $cityId = $cityData['id'];
    }
    
    // Jeśli nie mamy rekomendacji, nie możemy kontynuować
    if (empty($recommendations)) {
        echo "BŁĄD: Brak rekomendacji w odpowiedzi.\n";
        echo "Pełna odpowiedź:\n";
        echo json_encode($searchResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        exit;
    }
    
    // Sprawdź czy wszystkie rekomendacje mają wymagane pola
    foreach ($recommendations as $index => $rec) {
        if (!isset($rec['title']) || !isset($rec['description'])) {
            echo "OSTRZEŻENIE: Rekomendacja #$index nie ma wszystkich wymaganych pól.\n";
        }
    }
    
    // Teraz zapisujemy rekomendacje
    testSaveRecommendations($cityId, $recommendations, $jwtToken);
} else {
    echo "BŁĄD: Nie można zapisać rekomendacji.\n";
    if ($searchResult['code'] !== 200) {
        echo "Kod odpowiedzi: " . $searchResult['code'] . "\n";
    }
    if (!isset($searchResult['response']['success']) || $searchResult['response']['success'] !== true) {
        echo "Odpowiedź nie zawiera pola success=true\n";
    }
    if (!isset($searchResult['response']['data'])) {
        echo "Brak pola data w odpowiedzi\n";
    } 
    echo "Pełna odpowiedź:\n";
    echo json_encode($searchResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
*/

/*
// Test endpointu GET /api/users/me
testGetUserProfile($jwtToken);
*/

// Test endpointu GET /api/cities - lista miast
testGetCities($jwtToken);

// Test endpointu GET /api/cities z paginacją
//testGetCities($jwtToken, ['page' => 1, 'per_page' => 5]);

// Test endpointu GET /api/cities z filtrowaniem - tylko odwiedzone miasta
//testGetCities($jwtToken, ['visited' => false]);

// Test endpointu PUT /api/cities/{cityId} - aktualizacja statusu odwiedzenia
// Uwaga: Należy zastąpić 1 faktycznym ID miasta w bazie danych
//testUpdateCity(2, true, $jwtToken);


