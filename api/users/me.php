<?php
// Plik obsługujący endpoint /api/users/me
// Metoda: GET
// Parametry wejściowe: Brak (autoryzacja przez token JWT w nagłówku)
// Odpowiedź: Profil zalogowanego użytkownika (id, login, cityBase, isAdmin)

// Dołączenie potrzebnych plików
require_once '../../classes/Auth.php';
require_once '../../classes/Response.php';
require_once '../../classes/ErrorLogger.php';
require_once '../../commonDB/users.php';
// Plik errorLogs.php zostanie dołączony przez users.php jeśli potrzeba

// Sprawdzenie czy żądanie jest metodą GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::sendError(405, 'Metoda nie dozwolona. Oczekiwano GET.');
    exit;
}

try {
    // Sprawdzenie autentykacji przez token JWT
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    // Jeśli nie udało się uwierzytelnić użytkownika, zwracamy błąd 401
    if (!$userId) {
        Response::sendError(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }
    
    // Wywołanie funkcji serwisowej pobierającej dane użytkownika
    $userProfile = getUserProfile($userId);
    
    // Jeśli użytkownik nie został znaleziony, zwracamy błąd 404
    if (!$userProfile) {
        // Logowanie błędu
        ErrorLogger::logError('user_error', 'Użytkownik o ID ' . $userId . ' nie został znaleziony w bazie', $userId);
        
        Response::sendError(404, 'Użytkownik nie został znaleziony');
        exit;
    }
    
    // Wysłanie odpowiedzi z danymi użytkownika
    Response::sendSuccess($userProfile);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::sendError(500, 'Wystąpił błąd podczas przetwarzania żądania');
    exit;
} 