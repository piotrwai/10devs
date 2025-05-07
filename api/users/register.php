<?php
/**
 * Endpoint rejestracji użytkownika
 * POST /api/users/register
 */

require_once __DIR__ . '/../../classes/Response.php';
require_once __DIR__ . '/../../commonDB/users.php';
require_once __DIR__ . '/../../classes/GeoHelper.php';

// Ustawienie nagłówków CORS i typu odpowiedzi
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

// Sprawdzenie czy żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie jest dozwolona');
    exit();
}

// Pobranie danych z żądania
$data = json_decode(file_get_contents('php://input'), true);

// Walidacja danych
$errors = [];

if (empty($data['login']) || strlen($data['login']) < 2 || strlen($data['login']) > 50) {
    $errors[] = 'Login musi mieć od 2 do 50 znaków,';
}

if (empty($data['password']) || strlen($data['password']) < 5) {
    $errors[] = 'Hasło musi mieć minimum 5 znaków,';
}

if (empty($data['cityBase']) || strlen($data['cityBase']) < 3 || strlen($data['cityBase']) > 150) {
    $errors[] = 'Miasto bazowe musi mieć od 3 do 150 znaków.';
}

if (!is_string($data['login']) || !is_string($data['password']) || !is_string($data['cityBase'])) {
    Response::error(400, 'Nieprawidłowy format danych.');
    exit();
}

// Jeśli są błędy, zwróć je
if (!empty($errors)) {
    Response::error(400, 'Błędy walidacji', ['errors' => $errors]);
    exit();
}

try {
    // Sprawdzenie czy miasto istnieje
    $geoHelper = new GeoHelper();
    $cityCheck = $geoHelper->isCity($data['cityBase']);
    
    if ($cityCheck === false) {
        Response::error(500, 'Błąd podczas weryfikacji miasta. Spróbuj ponownie później.');
        exit();
    }
    
    if (!$cityCheck['isCity']) {
        Response::error(400, 'Miasto nie istnieje. Wprowadź prawidłową nazwę.');
        exit();
    }
    
    // Aktualizacja nazwy miasta na poprawną formę
    $data['cityBase'] = $cityCheck['properName'];

    // Sprawdzenie czy login jest unikalny
    if (isLoginTaken($data['login'])) {
        Response::error(409, 'Login jest już zajęty');
        exit();
    }

    // Hashowanie hasła
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Utworzenie nowego użytkownika
    $userId = setNewUser([
        'login' => $data['login'],
        'password' => $hashedPassword,
        'cityBase' => $data['cityBase']
    ]);

    if (!$userId) {
        Response::error(500, 'Nie udało się utworzyć użytkownika');
        exit();
    }

    // Zwrócenie sukcesu
    Response::success(201, 'Użytkownik został zarejestrowany', [
        'userId' => $userId,
        'login' => $data['login'],
        'cityBase' => $data['cityBase']
    ]);

} catch (Exception $e) {
    Response::error(500, 'Wystąpił błąd podczas rejestracji', ['message' => $e->getMessage()]);
} 