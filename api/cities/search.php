<?php
// Plik obsługujący endpoint /api/cities/search
// Metoda: POST
// Parametry wejściowe: cityName (string) - nazwa miasta do wyszukania
// Odpowiedź: Dane miasta oraz lista rekomendacji generowanych przez AI

// Dołączenie potrzebnych plików
require_once '../../classes/Auth.php';
require_once '../../classes/Response.php';
require_once '../../classes/ErrorLogger.php';
require_once '../../classes/AiService.php';
require_once '../../commonDB/cities.php';
require_once '../../commonDB/errorLogs.php';
require_once '../../classes/GeoHelper.php';
require_once '../../commonDB/users.php';

// Ustawienie nagłówków
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Sprawdzenie czy żądanie jest metodą POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie dozwolona. Oczekiwano POST.');
    exit;
}

// Pobranie i dekodowanie danych JSON
$requestData = json_decode(file_get_contents('php://input'), true);

// Konfiguracja jest ładowana wewnątrz klas pomocniczych (np. GeoHelper, AiService, Auth)

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth(); // Konfiguracja ładowana wewnątrz Auth
    $userId = $auth->authenticateAndGetUserId();

    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }

    // Pobranie danych użytkownika, w tym miasta bazowego
    $userData = getUserProfile($userId);
    if (!$userData || empty($userData['cityBase'])) {
        ErrorLogger::logError('user_error', 'Nie udało się pobrać danych użytkownika lub brak miasta bazowego.', $userId);
        Response::error(500, 'Błąd wewnętrzny - nie można pobrać danych użytkownika.');
        exit;
    }
    $userBaseCity = $userData['cityBase'];

    // Walidacja danych wejściowych
    if (isset($requestData['supplement']) && $requestData['supplement'] === true) {
        // Tryb uzupełniania rekomendacji
        if (!isset($requestData['cityId']) || !is_numeric($requestData['cityId'])) {
            Response::error(400, 'ID miasta jest wymagane dla uzupełnienia rekomendacji');
            exit;
        }
        
        $cityId = (int)$requestData['cityId'];
        
        // Sprawdzenie czy miasto należy do użytkownika
        $cityData = getCityById($cityId, $userId);
        if (!$cityData) {
            Response::error(404, 'Miasto nie zostało znalezione');
            exit;
        }
        
        $cityName = $cityData['name'];
        $cityDesc = $cityData['description'];
    } else {
        // Standardowe wyszukiwanie
        if (!isset($requestData['cityName']) || empty(trim($requestData['cityName']))) {
            Response::error(400, 'Nazwa miasta jest wymagana');
            exit;
        }

        $cityName = trim($requestData['cityName']);
        
        // Sprawdzenie długości nazwy miasta (max 150 znaków)
        if (mb_strlen($cityName) > 150) {
            Response::error(400, 'Nazwa miasta nie może przekraczać 150 znaków');
            exit;
        }
        
        // Sprawdzenie czy miasto już istnieje w bazie dla tego użytkownika
        $cityData = getCityByNameAndUserId($cityName, $userId);
        $cityId = null;
        $cityDesc = null;
        
        if ($cityData) {
            $cityId = $cityData['cit_id'];
            $cityDesc = $cityData['cit_desc'];
        }
    }
    
    // Ustawienie limitu czasu wykonania skryptu
    set_time_limit(90);
    
    // Sprawdzenie czy to miasto za pomocą statycznej metody GeoHelper
    $cityCheck = GeoHelper::isCity($cityName);
    if ($cityCheck === false) {
        Response::error(500, 'Błąd podczas weryfikacji miasta. Spróbuj ponownie później.');
        exit;
    }
    
    if (!$cityCheck['isCity']) {
        Response::error(400, 'Wprowadzona nazwa "' . htmlspecialchars($cityName) . '" nie jest rozpoznawana jako miasto. Sprawdź pisownię lub podaj inną nazwę.');
        exit;
    }

    // Aktualizacja nazwy miasta jeśli różni się od wprowadzonej
    $cityName = $cityCheck['properName'];
    
    // --------------------------------------------------------------------
    // NOWY KROK: Sprawdzenie trasy z miasta bazowego użytkownika (metoda statyczna)
    // --------------------------------------------------------------------
    $directionsRecommendation = null; 
    $directionsData = GeoHelper::getDirections($userBaseCity, $cityName);

    if ($directionsData) {
        $directionsTitle = sprintf(
            '%s - %s: %d km',
            $userBaseCity,
            $cityName,
            $directionsData['distance_km']
        );
        $directionsDescription = implode("\n\r", $directionsData['steps']);
        
        $directionsRecommendation = [
            'id' => null,
            'title' => mb_substr($directionsTitle, 0, 150),
            'description' => $directionsDescription,
            'model' => 'route_planner',
            'status' => null
        ];
    } else {
         // Nie udało się znaleźć trasy lub wystąpił błąd
         $directionsTitle = sprintf('%s - %s', $userBaseCity, $cityName);
         $directionsDescription = 'Prawdopodobnie nie da się jej pokonać samochodem lub błąd odczytania trasy z Google API.';
         $directionsRecommendation = [
            'id' => null,
            'title' => mb_substr($directionsTitle, 0, 150),
            'description' => $directionsDescription,
            'model' => 'route_planner_error',
            'status' => null
        ];
        // Można dodać logowanie błędu z GeoHelper
        // ErrorLogger::logError('directions_error', "Nie udało się pobrać trasy: $userBaseCity -> $cityName", $userId); // Można usunąć lub zostawić dla dodatkowego kontekstu
    }
    // --------------------------------------------------------------------
    
    // Wywołanie serwisu AI dla generowania podsumowania i rekomendacji
    $aiService = new AiService(); // Konfiguracja ładowana wewnątrz AiService
    $aiResult = $aiService->generateCityRecommendations($cityName, $userId, $cityId);
    
    if (!$aiResult || !isset($aiResult['recommendations'])) {
        // Błąd podczas generowania rekomendacji
        ErrorLogger::logError('ai_error', 'Nie udało się wygenerować rekomendacji AI dla miasta: ' . $cityName, $userId);
        Response::error(500, 'Nie udało się wygenerować rekomendacji AI dla miasta. Spróbuj ponownie później.');
        exit;
    }
    
    // Dodanie rekomendacji z trasą na początek listy
    if ($directionsRecommendation) {
        array_unshift($aiResult['recommendations'], $directionsRecommendation);
    }
    
    // Jeśli miasto nie istnieje, a odpowiedź AI jest poprawna, dodajemy miasto do bazy
    if (!$cityId) {        
        // Używamy opisu miasta wygenerowanego przez AI
        $cityDesc = $aiResult['city']['summary'];
        
        // Usuwamy dodawanie miasta - będzie dodawane dopiero przy zapisie rekomendacji
        $cityId = null;
    } else if (($cityDesc === null || empty(trim($cityDesc))) && !empty($aiResult['city']['summary'])) {
        // Jeśli miasto już istnieje, ale nie ma opisu, aktualizujemy opis
        $updateResult = updateCityDescription($cityId, $userId, $aiResult['city']['summary']);
    }
    
    // Ustawiamy prawidłowe ID miasta w odpowiedzi AI
    $aiResult['city']['id'] = $cityId;
    
    // Zwracamy pełną odpowiedź z danymi AI
    $response = $aiResult;
    
    // Wysłanie odpowiedzi
    Response::success(200, 'Pomyślnie wygenerowano rekomendacje dla miasta', $aiResult);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
}

?> 