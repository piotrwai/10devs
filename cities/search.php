<?php
/**
 * Strona wyszukiwania miast i rekomendacji
 */

// Dołączenie pliku konfiguracyjnego
require_once __DIR__ . '/../config.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/../smarty/configs/config.php';

// Dołączenie niezbędnych plików
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Response.php';
require_once __DIR__ . '/../commonDB/users.php';

// Ustawienie nagłówków
header('Content-Type: text/html; charset=UTF-8');

// Sprawdzenie autoryzacji
$auth = new Auth();

// Debug: sprawdź ciasteczko
error_log("JWT Cookie: " . (isset($_COOKIE['jwtToken']) ? "present" : "not present"));
if (isset($_COOKIE['jwtToken'])) {
    error_log("JWT Token value: " . substr($_COOKIE['jwtToken'], 0, 20) . "...");
}

$userId = $auth->authenticateAndGetUserId();

// Debug: sprawdź wynik autoryzacji
error_log("Auth Result - userId: " . ($userId ? $userId : "null"));

if (!$userId) {
    // Przekierowanie do strony logowania
    header('Location: /login?error=auth'); // 
    //echo "Brak autoryzacji 1";var_dump($userId);
    exit;
}

// Pobranie danych użytkownika
$user = getUserProfile($userId);

// Debug: sprawdź dane użytkownika
error_log("User data: " . ($user ? json_encode($user) : "null"));

if (!$user) {
    // Błąd pobierania danych użytkownika
    header('Location: /login?error=auth');
    //echo "Brak autoryzacji 2";var_dump($userId);
    exit;
}

// Ustawienie tytułu strony i danych użytkownika
$smarty->assign('title', 'Wyszukaj miasto - 10x-city');
$smarty->assign('userId', $userId);
$smarty->assign('currentUser', $user);

// Wyświetlenie szablonu
$smarty->display('cities/search.tpl'); 