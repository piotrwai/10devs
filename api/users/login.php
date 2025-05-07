<?php
/**
 * Endpoint logowania użytkownika
 * POST /api/users/login
 */
// Dołączenie pliku konfiguracyjnego
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/Response.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../commonDB/users.php';
require_once __DIR__ . '/../../commonDB/cities.php';

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
if (empty($data['login']) || empty($data['password'])) {
    Response::error(400, 'Login i hasło są wymagane');
    exit();
}

try {
    // Pobranie użytkownika z bazy danych
    $user = getUserByLogin($data['login']);
    
    if (!$user) {
        Response::error(401, 'Nieprawidłowy login lub hasło.');
        exit();
    }
    
    // Weryfikacja hasła
    if (!password_verify($data['password'], $user['usr_password'])) {
        Response::error(401, 'Nieprawidłowy login lub hasło.');
        exit();
    }
    
    // Generowanie tokena JWT
    $auth = new Auth();
    $token = $auth->generateJwtToken([
        'usr_id' => $user['usr_id'],
        'usr_login' => $user['usr_login'],
        'usr_admin' => $user['usr_admin']
    ]);
    
    // Ustawienie cookie z tokenem
    setcookie('jwtToken', $token, [
        'expires' => time() + (isset($config['jwt']['expiration']) ? $config['jwt']['expiration'] : 3600),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);

    // Zwrócenie sukcesu wraz z tokenem i podstawowymi danymi użytkownika
    Response::success(200, 'Zalogowano pomyślnie', [
        'token' => $token,
        'user' => [
            'id' => $user['usr_id'],
            'login' => $user['usr_login'],
            'cityBase' => $user['usr_city'],
            'isAdmin' => (bool)$user['usr_admin'],
            'hasCities' => userHasAnyCities($user['usr_id'])
        ]
    ]);

} catch (Exception $e) {
    Response::error(500, 'Wystąpił błąd podczas logowania', ['message' => $e->getMessage()]);
} 