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
    Response::sendError(405, 'Metoda nie dozwolona. Oczekiwano POST.');
    exit;
}

// Pobranie i dekodowanie danych JSON
$requestData = json_decode(file_get_contents('php://input'), true);

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    if (!$userId) {
        Response::sendError(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
    // Walidacja danych wejściowych
    if (!isset($requestData['cityName']) || empty(trim($requestData['cityName']))) {
        Response::sendError(400, 'Nazwa miasta jest wymagana');
        exit;
    }
    
    $cityName = trim($requestData['cityName']);
    
    // Sprawdzenie długości nazwy miasta (max 150 znaków)
    if (mb_strlen($cityName) > 150) {
        Response::sendError(400, 'Nazwa miasta nie może przekraczać 150 znaków');
        exit;
    }
    
    // Sprawdzenie czy miasto już istnieje w bazie dla tego użytkownika
    $cityData = getCityByNameAndUserId($cityName, $userId);
    $cityId = null;
    $cityDesc = null;
    
    if ($cityData) {
        $cityId = $cityData['cit_id'];
        $cityDesc = $cityData['cit_desc'];
    } else {
        // Miasto nie istnieje - dodajemy je do bazy
        $cityId = addCity($cityName, $userId);
        
        if (!$cityId) {
            // Logowanie błędu
            ErrorLogger::logError('db_error', 'Nie udało się dodać miasta: ' . $cityName, $userId);
        }
    }
    
    // Ustawienie limitu czasu wykonania skryptu
    // Dajemy trochę więcej czasu niż timeout AI, aby móc obsłużyć ewentualne błędy
    set_time_limit(70); // 70 sekund na całe wykonanie skryptu
    
    // Wywołanie serwisu AI dla generowania podsumowania i rekomendacji
    $aiService = new AiService();
    $aiResult = $aiService->generateCityRecommendations($cityName, $userId);
    
    if (!$aiResult) {
        // Błąd podczas generowania rekomendacji - zwracamy minimalną odpowiedź
        $response = [
            'city' => [
                'id' => $cityId,
                'name' => $cityName,
                'summary' => $cityDesc ?? 'Nie udało się wygenerować podsumowania dla miasta.'
            ],
            'recommendations' => [] // Pusta lista
        ];
    } else {
        // Jeśli miasto jeszcze nie ma opisu lub opis jest pusty, a mamy nowe podsumowanie, aktualizujemy je
        if (($cityDesc === null || empty(trim($cityDesc))) && !empty($aiResult['summary'])) {
            // Aktualizujemy opis miasta
            updateCityDescription($cityId, $userId, $aiResult['summary']);
        }
        
        // Zwracamy pełną odpowiedź z danymi AI
        $response = [
            'city' => [
                'id' => $cityId,
                'name' => $cityName,
                'summary' => $aiResult['summary']
            ],
            'recommendations' => $aiResult['recommendations']
        ];
    }
    
    // Wysłanie odpowiedzi
    Response::sendSuccess($response);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::sendError(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 