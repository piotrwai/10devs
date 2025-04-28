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

// Sprawdzenie czy żądanie jest metodą POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie dozwolona. Oczekiwano POST.');
    exit;
}

// Pobranie i dekodowanie danych JSON
$requestData = json_decode(file_get_contents('php://input'), true);

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
//echo "<br>UID: $userId<br>";
    //$userId = 1;
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
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
        
        $cityName = $cityData['cit_name'];
        $cityDesc = $cityData['cit_desc'];
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
            
            // Diagnostyka - miasto znalezione
            error_log("Miasto '$cityName' znalezione w bazie, ID: $cityId, opis: " . ($cityDesc ? "istnieje" : "brak"));
        }
    }
    
    // Ustawienie limitu czasu wykonania skryptu
    // Dajemy trochę więcej czasu niż timeout AI, aby móc obsłużyć ewentualne błędy
    set_time_limit(70); // 70 sekund na całe wykonanie skryptu
    
    // Wywołanie serwisu AI dla generowania podsumowania i rekomendacji
    $aiService = new AiService();
    $aiResult = $aiService->generateCityRecommendations($cityName, $userId, $cityId);
    
    error_log("Wywołano AiService z ID miasta: " . ($cityId ? $cityId : "null"));
    
    if (!$aiResult || empty($aiResult['recommendations'])) {
        // Błąd podczas generowania rekomendacji - logujemy błąd i zwracamy informację o błędzie
        error_log("Generowanie rekomendacji nie powiodło się lub zwrócono puste rekomendacje");
        ErrorLogger::logError('ai_error', 'Nie udało się wygenerować rekomendacji dla miasta: ' . $cityName, $userId);
        
        Response::error(500, 'Nie udało się wygenerować rekomendacji dla miasta. Spróbuj ponownie później.');
        exit;
    }
    
    // Jeśli miasto nie istnieje, a odpowiedź AI jest poprawna, dodajemy miasto do bazy
    if (!$cityId) {
        // Miasto nie istnieje - dodajemy je do bazy
        error_log("Próba dodania nowego miasta '$cityName' dla użytkownika $userId");
        
        // Używamy opisu miasta wygenerowanego przez AI
        $cityDesc = $aiResult['city']['summary'];
        
        $cityId = addCity($cityName, $userId, $cityDesc);
        
        if ($cityId) {
            // Diagnostyka - miasto dodane
            error_log("Miasto '$cityName' dodane do bazy, przydzielono ID: $cityId");
            
            // Sprawdzamy jeszcze raz, czy miasto zostało poprawnie dodane
            $checkCity = getCityById($cityId, $userId);
            if ($checkCity) {
                error_log("Weryfikacja: miasto o ID $cityId istnieje w bazie");
            } else {
                error_log("BŁĄD weryfikacji: miasto o ID $cityId NIE istnieje w bazie mimo dodania!");
                // Zwracamy błąd, ponieważ nie udało się zweryfikować dodania miasta
                Response::error(500, 'Wystąpił błąd podczas dodawania miasta. Spróbuj ponownie później.');
                exit;
            }
        } else {
            // Diagnostyka - błąd dodawania
            error_log("BŁĄD: Nie udało się dodać miasta '$cityName' do bazy");
            
            // Logowanie błędu
            ErrorLogger::logError('db_error', 'Nie udało się dodać miasta: ' . $cityName, $userId);
            
            // Zwracamy błąd
            Response::error(500, 'Wystąpił błąd podczas dodawania miasta. Spróbuj ponownie później.');
            exit;
        }
    } else if (($cityDesc === null || empty(trim($cityDesc))) && !empty($aiResult['city']['summary'])) {
        // Jeśli miasto już istnieje, ale nie ma opisu, aktualizujemy opis
        $updateResult = updateCityDescription($cityId, $userId, $aiResult['city']['summary']);
        error_log("Aktualizacja opisu miasta: " . ($updateResult ? "sukces" : "niepowodzenie"));
    }
    
    // Ustawiamy prawidłowe ID miasta w odpowiedzi AI
    $aiResult['city']['id'] = $cityId;
    
    // Zwracamy pełną odpowiedź z danymi AI
    $response = $aiResult;
    
    error_log("Zwracam odpowiedź z " . count($aiResult['recommendations']) . " rekomendacjami dla miasta o ID: $cityId");
    
    // Wysłanie odpowiedzi
    Response::success($response);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 