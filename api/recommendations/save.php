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
// Plik errorLogs.php zostanie dołączony przez inne pliki

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
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
    // Walidacja danych wejściowych
    if (!isset($requestData['city']) || !is_array($requestData['city'])) {
        Response::error(400, 'Dane miasta są wymagane');
        exit;
    }

    if (!isset($requestData['recommendations']) || !is_array($requestData['recommendations']) || empty($requestData['recommendations'])) {
        Response::error(400, 'Lista rekomendacji jest wymagana i nie może być pusta');
        exit;
    }

    $cityData = $requestData['city'];
    $recommendations = $requestData['recommendations'];

    // Sprawdzenie czy są jakieś zaakceptowane lub edytowane rekomendacje
    $hasAcceptedRecommendations = false;
    foreach ($recommendations as $rec) {
        if (isset($rec['status']) && in_array($rec['status'], ['accepted', 'edited'])) {
            $hasAcceptedRecommendations = true;
            break;
        }
    }

    if (!$hasAcceptedRecommendations) {
        Response::error(400, 'Przynajmniej jedna rekomendacja musi być zaakceptowana lub edytowana');
        exit;
    }

    // Sprawdzenie czy miasto już istnieje dla tego użytkownika
    $existingCity = getCityByNameAndUserId($cityData['name'], $userId);
    $cityId = null;

    if ($existingCity) {
        $cityId = $existingCity['cit_id'];
    } else {
        // Dodanie nowego miasta
        $cityId = addCity($cityData['name'], $userId, $cityData['summary'] ?? null);
        if (!$cityId) {
            Response::error(500, 'Nie udało się zapisać miasta');
            exit;
        }
    }
    
    // Sprawdzanie poprawności formatu rekomendacji przed zapisaniem
    $validRecommendationsCount = 0;
    $invalidRecommendationsCount = 0;
    
    foreach ($recommendations as $rec) {
        if (!isset($rec['title']) || empty(trim($rec['title'])) || !isset($rec['description']) || empty(trim($rec['description']))) {
            $invalidRecommendationsCount++;
        } else {
            $validRecommendationsCount++;
        }
    }
    
    // Jeśli wszystkie rekomendacje są nieprawidłowe, zwracamy błąd
    if ($validRecommendationsCount === 0) {
        ErrorLogger::logError('validation_error', 'Wszystkie rekomendacje mają nieprawidłowy format', $userId, null, $cityId);
        Response::error(400, 'Wszystkie rekomendacje mają nieprawidłowy format. Wymagane są pola title i description.');
        exit;
    }
    
    // Jeśli niektóre rekomendacje są nieprawidłowe, logujemy to
    if ($invalidRecommendationsCount > 0) {
        ErrorLogger::logError('validation_error', "$invalidRecommendationsCount z " . count($recommendations) . " rekomendacji ma nieprawidłowy format i zostanie pominięte", $userId, null, $cityId);
    }
    
    // Zapisanie rekomendacji do bazy danych
    $savedRecommendations = [];
    
    foreach ($recommendations as $rec) {
        // Zapisujemy tylko zaakceptowane i edytowane rekomendacje
        if (!isset($rec['status']) || !in_array($rec['status'], ['accepted', 'edited'])) {
            continue;
        }

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
        Response::error(500, 'Nie udało się zapisać żadnej rekomendacji');
        exit;
    }
    
    // Pobranie aktualnych danych miasta
    $city = getCityById($cityId, $userId);
    
    // Wysłanie odpowiedzi z zapisanymi rekomendacjami
    Response::success(201, 'Rekomendacje zostały zapisane.', [
        'city' => [
            'id' => $cityId,
            'name' => $city['name'],
            'summary' => $city['summary']
        ],
        'savedRecommendations' => count($savedRecommendations),
        'recommendations' => $savedRecommendations
    ]);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null, json_encode($requestData ?? []));
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 