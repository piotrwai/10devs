<?php
// Plik obsługujący endpoint GET /api/cities/{cityId}/recommendations

require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Response.php';
require_once __DIR__ . '/../../classes/ErrorLogger.php';
require_once __DIR__ . '/../../commonDB/cities.php';
require_once __DIR__ . '/../../commonDB/recommendations.php';
require_once __DIR__ . '/../../commonDB/aiLogs.php';

// Ustawienie nagłówka JSON
header('Content-Type: application/json; charset=UTF-8');

// ID miasta z parametru zapytania
$cityId = isset($_GET['cityId']) ? (int)$_GET['cityId'] : 0;
if ($cityId <= 0) {
    Response::error(400, 'Nieprawidłowe lub brakujące ID miasta.');
}

try {
    // Autoryzacja
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token.');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Dodawanie nowych rekomendacji do miasta
        $requestData = json_decode(file_get_contents('php://input'), true);
        if ($requestData === null && json_last_error() !== JSON_ERROR_NONE) {
            Response::error(400, 'Nieprawidłowy format JSON.');
        }
        if (!isset($requestData['title']) || empty(trim($requestData['title']))
            || !isset($requestData['description']) || empty(trim($requestData['description']))) {
            Response::error(400, 'Tytuł i opis rekomendacji są wymagane.');
        }
        $title = trim($requestData['title']);
        $description = trim($requestData['description']);
        if (mb_strlen($title) > 150) $title = mb_substr($title, 0, 150);
        $model = $requestData['model'] ?? 'manual';
        $status = $requestData['status'] ?? 'accepted';

        $newId = addRecommendation($userId, $cityId, $title, $description, $model, $status);
        if (!$newId) {
            // Sprawdź, czy błąd to duplikat
            if (isRecommendationTitleDuplicate($userId, $cityId, $title)) {
                Response::error(409, 'Rekomendacja o tym tytule już istnieje dla tego miasta.');
            } else {
                Response::error(500, 'Nie udało się dodać rekomendacji. Błąd serwera.');
            }
        }
        setAiLog($userId, $newId, $status);
        $newRec = getRecommendationById($userId, $newId);
        Response::success(201, 'Rekomendacja została dodana.', $newRec);
        exit; // Zakończ po POST

    } elseif ($method === 'GET') {
        // Obsługa GET (istniejący kod)
        // Sprawdzenie istnienia miasta (przeniesione z pierwotnego miejsca, aby było po autoryzacji)
        $city = getCityById($cityId, $userId);
        if (!$city) {
            Response::error(404, 'Miasto nie zostało znalezione.');
        }

        // Pobranie wszystkich rekomendacji dla miasta
        $recommendations = getRecommendationsByCityId($userId, $cityId);
        if ($recommendations === null) {
            Response::error(500, 'Wystąpił błąd podczas pobierania rekomendacji.');
        }

        // Zwrócenie listy rekomendacji
        Response::success(200, '', $recommendations);

    } else {
        // Inne metody niż GET i POST
        Response::error(405, 'Metoda nie dozwolona.');
    }

} catch (Exception $e) {
    // Logowanie i odpowiedź o błędzie
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    Response::error(500, 'Wystąpił wewnętrzny błąd serwera.');
} 