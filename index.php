<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/ErrorLogger.php';
require_once 'commonDB/users.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/smarty/configs/config.php';

// Sprawdzenie stanu zalogowania
$auth = new Auth();
$userId = $auth->authenticateAndGetUserId();
$isLogged = false;
$currentUser = null;

if ($userId) {
    $isLogged = true;
    // Pobranie danych użytkownika
    $currentUser = getUserProfile($userId);
}

// Przekazanie zmiennych do szablonu
$smarty->assign('isLogged', $isLogged);
$smarty->assign('currentUser', $currentUser);

// Wyświetlenie szablonu
$smarty->display('index.tpl');