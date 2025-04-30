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

// Pobierz ID z parametru GET przekazanego przez .htaccess
$cityId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Sprawdzenie czy ID jest liczbą
if ($cityId === null || $cityId <= 0) { // Sprawdź czy null lub niepoprawna liczba
    Response::error(400, 'Nieprawidłowe lub brakujące ID miasta');
    exit;
}

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
    // Pobranie i dekodowanie danych JSON
    $requestJson = file_get_contents('php://input');
    $requestData = json_decode($requestJson, true);
    
    // Sprawdzenie czy json_decode się powiodło
    if ($requestData === null && json_last_error() !== JSON_ERROR_NONE) {
        Response::error(400, 'Nieprawidłowy format danych JSON.');
        exit;
    }
    
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
    
    // Upewnij się, że dane zostały pobrane
    if (!$updatedCity) {
        // To nie powinno się zdarzyć, skoro update się powiódł, ale dla bezpieczeństwa
        ErrorLogger::logError('api_error', 'Nie udało się pobrać danych miasta po aktualizacji statusu.', $userId, $_SERVER['REQUEST_URI'] ?? null);
        Response::error(500, 'Wystąpił błąd podczas pobierania zaktualizowanych danych miasta.');
        exit;
    }
    
    // Formatowanie odpowiedzi z użyciem poprawnych kluczy
    $response = [
        'id' => (int)$updatedCity['id'],         // Poprawiono na 'id'
        'name' => $updatedCity['name'],       // Poprawiono na 'name'
        'visited' => (bool)$updatedCity['visited'], // Używamy teraz wartości z $updatedCity
        'description' => $updatedCity['description'] // Poprawiono na 'description'
    ];
    
    // Wysłanie odpowiedzi
    Response::success(200, '', $response);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 