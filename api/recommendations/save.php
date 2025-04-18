<?php
// Plik obsługujący endpoint /api/recommendations/save
// Metoda: POST
// Parametry wejściowe: 
// - cityId (int) - ID miasta
// - recommendations (array) - lista rekomendacji do zapisania
// Odpowiedź: Zapisane rekomendacje z przypisanymi ID

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
    if (!isset($requestData['cityId']) || !is_numeric($requestData['cityId'])) {
        Response::sendError(400, 'ID miasta jest wymagane i musi być liczbą');
        exit;
    }
    
    if (!isset($requestData['recommendations']) || !is_array($requestData['recommendations']) || empty($requestData['recommendations'])) {
        Response::sendError(400, 'Lista rekomendacji jest wymagana i nie może być pusta');
        exit;
    }
    
    $cityId = (int)$requestData['cityId'];
    $recommendations = $requestData['recommendations'];
    
    // Sprawdzenie czy miasto istnieje i należy do użytkownika
    $city = getCityById($cityId, $userId);
    
    if (!$city) {
        Response::sendError(404, 'Miasto o podanym ID nie zostało znalezione lub nie należy do tego użytkownika');
        exit;
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
        
        // Ograniczenie długości
        if (mb_strlen($title) > 200) {
            $title = mb_substr($title, 0, 200);
        }
        
        if (mb_strlen($description) > 64000) {
            $description = mb_substr($description, 0, 64000);
        }
        
        // Zapis do bazy danych
        $recId = addRecommendation($userId, $cityId, $title, $description, $model);
        
        if ($recId) {
            // Dodanie do listy zapisanych rekomendacji
            $savedRecommendations[] = [
                'id' => $recId,
                'title' => $title,
                'description' => $description,
                'model' => $model
            ];
            
            // Logowanie udanego zapisu rekomendacji AI
            setAiLog($userId, $recId, 'saved');
        }
    }
    
    // Sprawdzenie czy udało się zapisać jakiekolwiek rekomendacje
    if (empty($savedRecommendations)) {
        Response::sendError(500, 'Nie udało się zapisać żadnej rekomendacji');
        exit;
    }
    
    // Wysłanie odpowiedzi z zapisanymi rekomendacjami
    Response::sendSuccess([
        'message' => 'Rekomendacje zostały zapisane',
        'count' => count($savedRecommendations),
        'recommendations' => $savedRecommendations
    ]);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::sendError(500, 'Wystąpił błąd podczas przetwarzania żądania: ' . $e->getMessage());
    exit;
} 