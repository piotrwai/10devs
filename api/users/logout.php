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

// Logowanie debugowania
error_log("Rozpoczęcie procesu wylogowania");
error_log("Metoda HTTP: " . $_SERVER['REQUEST_METHOD']);

// Sprawdzenie metody HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Błędna metoda HTTP: " . $_SERVER['REQUEST_METHOD']);
    Response::error(405, 'Metoda nie jest dozwolona');
    exit();
}

try {
    // Sprawdzenie obecności ciasteczka przed usunięciem
    error_log("Stan ciasteczka przed usunięciem: " . (isset($_COOKIE['jwtToken']) ? 'istnieje' : 'nie istnieje'));

    // Usunięcie ciasteczka JWT
    setcookie('jwtToken', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);

    // Sprawdzenie czy ciasteczko zostało usunięte
    error_log("Stan ciasteczka po próbie usunięcia: " . (isset($_COOKIE['jwtToken']) ? 'istnieje' : 'nie istnieje'));

    // Usunięcie ciasteczka z sesji
    if (isset($_SESSION['jwtToken'])) {
        unset($_SESSION['jwtToken']);
        error_log("Usunięto token z sesji");
    }

    // Zniszczenie sesji
    //session_destroy();
    //error_log("Sesja zniszczona");

    // Wylogowanie jest zawsze successful
    error_log("Wysyłanie odpowiedzi sukcesu");
    Response::success(200, 'Wylogowano pomyślnie');

} catch (Exception $e) {
    error_log("Błąd podczas wylogowania: " . $e->getMessage());
    Response::error(500, 'Wystąpił błąd podczas wylogowania: ' . $e->getMessage());
} 