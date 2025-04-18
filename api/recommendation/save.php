<?php
// Plik obsługujący endpoint /api/recommendation/save
// Metoda: POST
// Parametry wejściowe: 
// - city: {name: string, summary: string}
// - recommendations: [{title: string, description: string, model: string, status: string}, ...]
// Odpowiedź: Zapisane miasto i rekomendacje z przypisanymi ID

// Dołączenie potrzebnych plików
require_once '../../classes/Auth.php';
require_once '../../classes/Response.php';
require_once '../../classes/ErrorLogger.php';
require_once '../../commonDB/cities.php';
require_once '../../commonDB/recommendations.php';
require_once '../../commonDB/aiLogs.php';
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
    if (!isset($requestData['city']) || !is_array($requestData['city'])) {
        Response::sendError(400, 'Dane miasta są wymagane');
        exit;
    }
    
    if (!isset($requestData['city']['name']) || empty(trim($requestData['city']['name']))) {
        Response::sendError(400, 'Nazwa miasta jest wymagana');
        exit;
    }
    
    if (!isset($requestData['recommendations']) || !is_array($requestData['recommendations']) || empty($requestData['recommendations'])) {
        Response::sendError(400, 'Lista rekomendacji jest wymagana i nie może być pusta');
        exit;
    }
    
    $cityName = trim($requestData['city']['name']);
    $citySummary = isset($requestData['city']['summary']) ? trim($requestData['city']['summary']) : null;
    $recommendations = $requestData['recommendations'];
    
    // Ograniczenie długości nazwy miasta i opisu
    if (mb_strlen($cityName) > 150) {
        Response::sendError(400, 'Nazwa miasta nie może przekraczać 150 znaków');
        exit;
    }
    
    if ($citySummary !== null && mb_strlen($citySummary) > 200) {
        $citySummary = mb_substr($citySummary, 0, 200);
    }
    
    // Sprawdzenie czy miasto już istnieje w bazie dla tego użytkownika
    $cityData = getCityByNameAndUserId($cityName, $userId);
    $cityId = null;
    
    if ($cityData) {
        $cityId = $cityData['cit_id'];
        
        // Aktualizacja opisu miasta jeśli podano nowy
        if ($citySummary !== null) {
            updateCityDescription($cityId, $userId, $citySummary);
        }
    } else {
        // Miasto nie istnieje - dodajemy je do bazy
        $cityId = addCity($cityName, $userId, $citySummary);
        
        if (!$cityId) {
            Response::sendError(500, 'Nie udało się zapisać miasta');
            exit;
        }
    }
    
    // Zapisanie rekomendacji do bazy danych
    $savedRecommendations = [];
    
    foreach ($recommendations as $rec) {
        // Walidacja danych rekomendacji
        if (!isset($rec['title']) || empty(trim($rec['title'])) || !isset($rec['description']) || empty(trim($rec['description']))) {
            continue; // Pomijamy nieprawidłowe rekomendacje
        }
        
        $title = trim($rec['title']);
        $description = trim($rec['description']);
        $model = isset($rec['model']) ? trim($rec['model']) : 'manual';
        $status = isset($rec['status']) && in_array($rec['status'], ['accepted', 'edited', 'rejected']) 
                  ? $rec['status'] : 'accepted';
        
        // Ograniczenie długości
        if (mb_strlen($title) > 200) {
            $title = mb_substr($title, 0, 200);
        }
        
        if (mb_strlen($description) > 64000) {
            $description = mb_substr($description, 0, 64000);
        }
        
        // Zapis do bazy danych
        $recId = addRecommendation($userId, $cityId, $title, $description, $model, $status);
        
        if ($recId) {
            // Dodanie do listy zapisanych rekomendacji
            $savedRecommendations[] = [
                'id' => $recId,
                'title' => $title,
                'description' => $description,
                'model' => $model,
                'status' => $status
            ];
            
            // Logowanie udanego zapisu rekomendacji AI
            setAiLog($userId, $recId, $status);
        }
    }
    
    // Sprawdzenie czy udało się zapisać jakiekolwiek rekomendacje
    if (empty($savedRecommendations)) {
        Response::sendError(500, 'Nie udało się zapisać żadnej rekomendacji');
        exit;
    }
    
    // Wysłanie odpowiedzi z zapisanym miastem i rekomendacjami
    Response::sendSuccess([
        'city' => [
            'id' => $cityId,
            'name' => $cityName,
            'summary' => $citySummary
        ],
        'savedRecommendations' => count($savedRecommendations),
        'recommendations' => $savedRecommendations
    ], 201); // Używamy kodu 201 Created dla nowo utworzonych zasobów
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::sendError(500, 'Wystąpił błąd podczas przetwarzania żądania: ' . $e->getMessage());
    exit;
} 