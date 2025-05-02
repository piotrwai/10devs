<?php
/**
 * Endpoint wylogowania użytkownika
 * POST /api/users/logout
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/Response.php';

// Włączenie raportowania błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ustawienie nagłówków CORS i typu odpowiedzi
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// Sprawdzenie metody HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie jest dozwolona');
    exit();
}

try {
    // Usunięcie ciasteczka JWT
    setcookie('jwtToken', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);


    // Usunięcie ciasteczka z sesji
    if (isset($_SESSION['jwtToken'])) {
        unset($_SESSION['jwtToken']);
    }

    Response::success(200, 'Wylogowano pomyślnie');

} catch (Exception $e) {
    Response::error(500, 'Wystąpił błąd podczas wylogowania: ' . $e->getMessage());
} 