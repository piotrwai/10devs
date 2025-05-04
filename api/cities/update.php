<?php
// Plik obsługujący endpoint /api/cities/{cityId}
// Metoda: PUT
// Parametry: visited (boolean), newName (string)
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
    
    // Sprawdzenie czy miasto istnieje i należy do użytkownika
    $city = getCityById($cityId, $userId);
    
    if (!$city) {
        Response::error(404, 'Miasto nie zostało znalezione');
        exit;
    }

    // Określenie typu aktualizacji na podstawie przesłanych danych
    $updateType = isset($requestData['visited']) ? 'visited' : (isset($requestData['newName']) ? 'name' : null);

    if (!$updateType) {
        Response::error(400, 'Brak wymaganych parametrów. Oczekiwano "visited" lub "newName".');
        exit;
    }

    $updateResult = false;
    
    if ($updateType === 'visited') {
        // Aktualizacja statusu odwiedzenia
        $visited = filter_var($requestData['visited'], FILTER_VALIDATE_BOOLEAN);
        $updateResult = updateCityVisitedStatus($cityId, $userId, $visited);
    } else {
        // Aktualizacja nazwy miasta
        $newName = trim($requestData['newName']);
        
        // Walidacja nowej nazwy
        if (empty($newName)) {
            Response::error(400, 'Nowa nazwa miasta jest wymagana');
            exit;
        }

        if (mb_strlen($newName) > 150) {
            Response::error(400, 'Nazwa miasta nie może przekraczać 150 znaków');
            exit;
        }

        $updateResult = updateCityName($cityId, $userId, $newName);
    }
    
    if (!$updateResult) {
        Response::error(500, 'Nie udało się zaktualizować miasta');
        exit;
    }
    
    // Pobranie zaktualizowanych danych miasta
    $updatedCity = getCityById($cityId, $userId);
    
    // Upewnij się, że dane zostały pobrane
    if (!$updatedCity) {
        ErrorLogger::logError('api_error', 'Nie udało się pobrać danych miasta po aktualizacji.', $userId, $_SERVER['REQUEST_URI'] ?? null);
        Response::error(500, 'Wystąpił błąd podczas pobierania zaktualizowanych danych miasta.');
        exit;
    }
    
    // Formatowanie odpowiedzi
    $response = [
        'id' => (int)$updatedCity['id'],
        'name' => $updatedCity['name'],
        'visited' => (bool)$updatedCity['visited'],
        'description' => $updatedCity['description']
    ];
    
    // Wysłanie odpowiedzi
    $message = $updateType === 'visited' ? 'Status miasta został zaktualizowany' : 'Nazwa miasta została zaktualizowana';
    Response::success(200, $message, ['city' => $response]);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 