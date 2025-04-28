<?php
// Plik obsługujący endpoint /api/cities/{cityId}
// Metoda: PUT
// Parametry: visited (boolean)
// Odpowiedź: Zaktualizowane dane miasta

// Dołączenie potrzebnych plików
require_once '../../classes/Auth.php';
require_once '../../classes/Response.php';
require_once '../../classes/ErrorLogger.php';
require_once '../../commonDB/cities.php';
// Plik errorLogs.php będzie dołączony przez cities.php jeśli będzie potrzebny

// Sprawdzenie czy żądanie jest metodą PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error(405, 'Metoda nie dozwolona. Oczekiwano PUT.');
    exit;
}

// Pobranie ID miasta z URL
$requestUri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($requestUri, '/'));
// Ostatni element URL powinien być ID miasta
$cityId = end($parts);

// Sprawdzenie czy ID jest liczbą
if (!is_numeric($cityId) || (int)$cityId <= 0) {
    Response::error(400, 'Nieprawidłowe ID miasta');
    exit;
}

$cityId = (int)$cityId;

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
    // Pobranie i dekodowanie danych JSON
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    // Sprawdzenie czy istnieje parametr 'visited'
    if (!isset($requestData['visited'])) {
        Response::error(400, 'Brak wymaganego parametru: visited');
        exit;
    }
    
    // Konwersja parametru na boolean
    $visited = filter_var($requestData['visited'], FILTER_VALIDATE_BOOLEAN);
    
    // Sprawdzenie czy miasto istnieje i należy do użytkownika
    $city = getCityById($cityId, $userId);
    
    if (!$city) {
        Response::error(404, 'Miasto nie zostało znalezione');
        exit;
    }
    
    // Aktualizacja statusu odwiedzenia
    $updateResult = updateCityVisitedStatus($cityId, $userId, $visited);
    
    if (!$updateResult) {
        Response::error(500, 'Nie udało się zaktualizować statusu miasta');
        exit;
    }
    
    // Pobranie zaktualizowanych danych miasta
    $updatedCity = getCityById($cityId, $userId);
    
    // Formatowanie odpowiedzi
    $response = [
        'id' => (int)$updatedCity['cit_id'],
        'name' => $updatedCity['cit_name'],
        'visited' => (bool)$visited,
        'description' => $updatedCity['cit_desc']
    ];
    
    // Wysłanie odpowiedzi
    Response::success($response);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 