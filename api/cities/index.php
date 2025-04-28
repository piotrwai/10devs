<?php
// Plik obsługujący endpoint /api/cities
// Metoda: GET
// Parametry opcjonalne: page, per_page, visited
// Odpowiedź: Lista miast użytkownika z licznikiem rekomendacji

// Dołączenie potrzebnych plików
require_once '../../classes/Auth.php';
require_once '../../classes/Response.php';
require_once '../../classes/ErrorLogger.php';
require_once '../../commonDB/cities.php';
// Plik errorLogs.php będzie dołączony przez cities.php jeśli będzie potrzebny

// Sprawdzenie czy żądanie jest metodą GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error(405, 'Metoda nie dozwolona. Oczekiwano GET.');
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
    
    // Pobranie parametrów zapytania
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $visited = isset($_GET['visited']) ? filter_var($_GET['visited'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
    
    // Walidacja parametrów
    if ($page < 1) {
        $page = 1;
    }
    
    if ($perPage < 1 || $perPage > 100) {
        $perPage = 10; // Domyślna wartość
    }
    
    // Pobranie danych miast użytkownika wraz z liczbą rekomendacji
    $cities = getUserCitiesWithRecommendationCount($userId, $page, $perPage, $visited);
    
    // Sprawdzenie czy są jakieś dane
    if (empty($cities)) {
        // Sprawdzenie czy użytkownik ma jakiekolwiek miasta
        $hasAnyCities = userHasAnyCities($userId);
        
        if (!$hasAnyCities) {
            // Użytkownik nie ma żadnych miast
            Response::success([], 'Nie wprowadziłeś żadnego miasta!');
        } else {
            // Użytkownik ma miasta, ale nie znaleziono ich z aktualnych filtrów
            Response::success([], 'Brak miast. Zmień filtry.');
        }
        exit;
    }
    
    // Formatowanie odpowiedzi zgodnie ze specyfikacją
    $formattedCities = [];
    foreach ($cities as $city) {
        $formattedCities[] = [
            'id' => (int)$city['id'],
            'name' => $city['name'],
            'recommendationCount' => (int)$city['recommendationCount'],
            'visited' => (bool)$city['visited']
        ];
    }
    
    // Wysłanie odpowiedzi
    Response::success($formattedCities);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 