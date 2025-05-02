<?php
/**
 * Plik obsługujący ścieżkę /dashboard - widok Dashboardu Miast Użytkownika
 */

// Dołączenie pliku konfiguracyjnego
require_once 'config.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/smarty/configs/config.php';

// Dołączenie klasy Auth
require_once 'classes/Auth.php';
// Dołączenie funkcji bazy danych użytkowników
require_once 'commonDB/users.php';

// Ładowanie konfiguracji (jeśli config.php zwraca tablicę)
$config = require 'config.php';
$maxCitiesPerPage = $config['app']['max_cities_per_page'] ?? 10;

// Weryfikacja czy użytkownik jest zalogowany
$auth = new Auth();
$userId = $auth->authenticateAndGetUserId(); // Używamy metody z klasy Auth

if (!$userId) {
    // Użytkownik nie jest zalogowany (lub token jest nieprawidłowy/wygasł)
    // Przekierowanie do strony logowania z komunikatem o błędzie
    // Klasa Auth może sama ustawić nagłówek Location, ale dla pewności dodajemy go tutaj
    if (!headers_sent()) {
        header('Location: /login?error=access');
    }
    exit;
}

// Pobranie danych zalogowanego użytkownika, aby przekazać je do nagłówka
$currentUser = getUserProfile($userId);
if (!$currentUser) {
    // To nie powinno się zdarzyć, jeśli autoryzacja przeszła, ale na wszelki wypadek
    // Można rozważyć przekierowanie do błędu lub logowania
    header('Location: /login?error=internal'); 
    exit;
}
$smarty->assign('currentUser', $currentUser);

// Przypisanie zmiennej konfiguracyjnej do Smarty
$smarty->assign('maxCitiesPerPage', $maxCitiesPerPage);

// Tutaj w przyszłości można dodać logikę pobierania wstępnych danych dla Smarty,
// np. informacji o użytkowniku, jeśli są potrzebne w szablonie nagłówka/stopki.
// $smarty->assign('userId', $userId);

// Renderowanie szablonu dashboard.tpl
// Zakładamy, że ścieżka do szablonów jest poprawnie skonfigurowana w config.php Smarty
$smarty->display('cities/dashboard.tpl'); 