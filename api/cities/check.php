<?php
/**
 * Endpoint sprawdzający czy miasto o danej nazwie istnieje dla użytkownika
 * Metoda: POST /api/cities/check
 */

// Dołączenie potrzebnych plików
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Response.php';
require_once __DIR__ . '/../../classes/ErrorLogger.php';
require_once __DIR__ . '/../../commonDB/cities.php';

// Ustawienie typu odpowiedzi
header('Content-Type: application/json; charset=UTF-8');

// Sprawdzenie metody HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie dozwolona.');
    exit;
}

try {
    // Sprawdzenie autentykacji
    $auth = new Auth();
    $userId = $auth->authenticateAndGetUserId();
    
    if (!$userId) {
        Response::error(401, 'Brak autoryzacji lub nieprawidłowy token');
        exit;
    }

    // Pobranie danych z żądania
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['cityName']) || empty(trim($data['cityName']))) {
        Response::error(400, 'Nie podano nazwy miasta');
        exit;
    }

    $cityName = trim($data['cityName']);

    // Sprawdzenie czy miasto istnieje
    $city = getCityByNameAndUserId($cityName, $userId);
    
    if ($city) {
        Response::success(200, '', [
            'exists' => true,
            'cityId' => (int)$city['cit_id']
        ]);
    } else {
        Response::success(200, '', [
            'exists' => false
        ]);
    }

} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', $e->getMessage(), $userId ?? null, $_SERVER['REQUEST_URI'] ?? null);
    
    // Wysłanie odpowiedzi z błędem
    Response::error(500, 'Wystąpił błąd wewnętrzny serwera podczas sprawdzania miasta.');
    exit;
} 