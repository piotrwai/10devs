<?php
/**
 * Plik obsługujący ścieżkę /profile - widok danych użytkownika
 */

// Dołączenie pliku konfiguracyjnego
require_once 'config.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/smarty/configs/config.php';

// Dołączenie niezbędnych plików
require_once 'classes/Auth.php';
require_once 'commonDB/users.php';

// Weryfikacja czy użytkownik jest zalogowany
$auth = new Auth();
$userId = $auth->authenticateAndGetUserId();

if (!$userId) {
    // Użytkownik nie jest zalogowany - przekierowanie do strony logowania
    header('Location: /login?error=access');
    exit;
}

// Pobranie danych użytkownika
$currentUser = getUserProfile($userId);

if (!$currentUser) {
    // Nie znaleziono użytkownika w bazie - przekierowanie do strony logowania
    header('Location: /login?error=access');
    exit;
}

// Przypisanie danych użytkownika do Smarty
$smarty->assign('currentUser', $currentUser);

// Renderowanie szablonu profile.tpl
$smarty->display('profile.tpl'); 