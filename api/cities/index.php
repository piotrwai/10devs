<?php
// Plik obsługujący endpoint /api/cities/ oraz /api/cities/{id}
// Metoda: GET /api/cities/ - Lista miast użytkownika
// Metoda: DELETE /api/cities/{id} - Usuwanie miasta użytkownika

// Dołączenie potrzebnych plików
require_once __DIR__ . '/../../classes/Auth.php'; // Poprawiona ścieżka
require_once __DIR__ . '/../../classes/Response.php'; // Poprawiona ścieżka
require_once __DIR__ . '/../../classes/ErrorLogger.php'; // Poprawiona ścieżka
require_once __DIR__ . '/../../commonDB/cities.php'; // Poprawiona ścieżka

// Ładowanie konfiguracji
$config = require __DIR__ . '/../../config.php';
$defaultPerPage = $config['app']['max_cities_per_page'] ?? 10; // Użyj wartości z configu lub domyślnej 10

// Ustawienie typu odpowiedzi (przeniesione z bootstrapa dla spójności)
header('Content-Type: application/json; charset=UTF-8');

$requestMethod = $_SERVER['REQUEST_METHOD'];
// ID z URL jest nadal potrzebne dla DELETE
$cityId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }

    // Obsługa różnych metod HTTP
    switch ($requestMethod) {
        case 'GET':
            // --- Obsługa GET /api/cities (Listowanie/filtrowanie) --- 
            // Poprawiony warunek: Zwracaj błąd tylko jeśli żądanie GET zawiera prawidłowe (dodatnie) ID miasta.
            // Żądania bez ID (null) lub z potencjalnie pustym ID (0) powinny być traktowane jak listowanie.
            if ($cityId !== null && $cityId > 0) { 
                Response::error(404, 'Endpoint GET /api/cities/{id} nie jest obsługiwany.');
                exit;
            }
            
            // Pobranie parametrów zapytania z $_GET
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : $defaultPerPage;
            $visited = isset($_GET['visited']) ? filter_var($_GET['visited'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
            
            // Walidacja parametrów
            if ($page < 1) $page = 1;
            if ($perPage < 1 || $perPage > 100) $perPage = $defaultPerPage;
            
            // Pobranie danych miast użytkownika (logika bez zmian)
            $result = getUserCitiesWithRecommendationCount($userId, $page, $perPage, $visited);
            $cities = $result['data'];
            $totalItems = $result['totalItems'];
            $totalPages = ceil($totalItems / $perPage);
            
            // Sprawdzenie czy są jakieś dane (logika bez zmian)
            if (empty($cities) && $page === 1 && $visited === null) { // Sprawdzaj tylko na pierwszej stronie bez filtrów
                 Response::success(200, 'Nie masz jeszcze żadnych zapisanych miast.', ['data' => [], 'pagination' => ['currentPage' => 1, 'totalPages' => 0, 'totalItems' => 0, 'perPage' => $perPage]]);
                 exit;
            }
            
            // Formatowanie odpowiedzi zgodnie ze specyfikacją
            $formattedCities = [];
            foreach ($cities as $city) {
                $formattedCities[] = [
                    'id' => (int)$city['id'],
                    'name' => $city['name'],
                    'recommendationCount' => (int)$city['recommendationCount'],
                    'visitedRecommendationsCount' => (int)$city['visitedRecommendationsCount'],
                    'visited' => (bool)$city['visited']
                ];
            }
            
            // Przygotowanie danych paginacji
            $paginationData = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'perPage' => $perPage
            ];

            // Wysłanie odpowiedzi
            Response::success(200, '', ['data' => $formattedCities, 'pagination' => $paginationData]);
            break;

        case 'DELETE':
            // --- Obsługa DELETE /api/cities/{id} --- 
            if ($cityId === null || $cityId <= 0) {
                Response::error(400, 'Nieprawidłowe lub brakujące ID miasta w ścieżce URL.');
                exit;
            }

            // Wywołanie funkcji usuwającej miasto
            $deleted = delCityByIdAndUserId($cityId, $userId);

            if ($deleted) {
                // Sukces - kod 204 No Content
                http_response_code(204);
            } else {
                // Błąd - prawdopodobnie miasto nie istnieje lub nie należy do użytkownika
                // Możemy założyć, że funkcja delCityByIdAndUserId zwróciła false w takim przypadku
                Response::error(404, 'Nie znaleziono miasta o podanym ID lub nie masz uprawnień do jego usunięcia.');
            }
            break;

        default:
            // Metoda nieobsługiwana
            Response::error(405, 'Metoda nie dozwolona.');
            break;
    }

} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd wewnętrzny serwera podczas przetwarzania żądania.');
    exit;
} 