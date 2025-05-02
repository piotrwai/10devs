<?php
// Plik obsługujący operacje na pojedynczej rekomendacji: /api/recommendations/{id}

require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Response.php';
require_once __DIR__ . '/../../classes/ErrorLogger.php';
require_once __DIR__ . '/../../commonDB/recommendations.php';
require_once __DIR__ . '/../../commonDB/aiLogs.php';

// Ustawienie nagłówka JSON
header('Content-Type: application/json; charset=UTF-8');

// Pobranie ID rekomendacji z query string (htaccess przekazuje jako id)
$recId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($recId <= 0) {
    Response::error(400, 'Nieprawidłowe lub brakujące ID rekomendacji.');
}

try {
    // Autoryzacja
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token.');
    }

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            // Pobranie szczegółów rekomendacji
            $rec = getRecommendationById($userId, $recId);
            if (!$rec) {
                Response::error(404, 'Rekomendacja nie została znaleziona.');
            }
            Response::success(200, '', $rec);
            break;

        case 'PUT':
            // Aktualizacja rek. odczytaj JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                Response::error(400, 'Nieprawidłowy format JSON.');
            }
            $updated = false;

            // Edycja treści
            if (isset($data['title']) || isset($data['description'])) {
                $title = isset($data['title']) ? trim($data['title']) : null;
                $desc  = isset($data['description']) ? trim($data['description']) : null;
                if ($title !== null && $desc !== null) {
                    $updated = updateRecommendationContent($userId, $recId, $title, $desc);
                    // Logowanie edycji rekomendacji
                    if ($updated) {
                        setAiLog($userId, $recId, 'edited');
                    }
                }
            }
            // Zmiana statusu
            if (isset($data['status'])) {
                $status = trim($data['status']);
                $changed = updateRecommendationStatus($userId, $recId, $status);
                if ($changed) {
                    setAiLog($userId, $recId, $status);
                    $updated = true;
                }
            }
            // Zmiana done
            if (isset($data['done'])) {
                $done = filter_var($data['done'], FILTER_VALIDATE_BOOLEAN);
                $changed = setRecommendationDoneStatus($userId, $recId, $done);
                if ($changed) {
                    $updated = true;
                    // Logowanie zmiany statusu done
                    $logStatus = $done ? 'done' : 'undone';
                    setAiLog($userId, $recId, $logStatus);
                }
            }

            if (!$updated) {
                Response::error(400, 'Nie podano poprawnych danych do aktualizacji lub brak zmian.');
            }
            // Pobierz odświeżone dane
            $recUpdated = getRecommendationById($userId, $recId);
            Response::success(200, 'Rekomendacja została zaktualizowana.', $recUpdated);
            break;

        case 'DELETE':
            // Usuwanie rekomendacji
            $deleted = deleteRecommendationById($userId, $recId);
            if ($deleted) {
                http_response_code(204);
                exit;
            }
            Response::error(404, 'Rekomendacja nie istnieje lub nie masz uprawnień.');
            break;

        default:
            Response::error(405, 'Metoda nie dozwolona.');
            break;
    }

} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    Response::error(500, 'Wystąpił błąd wewnętrzny serwera.');
} 