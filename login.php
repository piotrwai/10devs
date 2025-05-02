<?php
/**
 * Plik obsługujący ścieżkę /login - widok strony logowania
 */

// Dołączenie pliku konfiguracyjnego
require_once 'config.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/smarty/configs/config.php';

// Sprawdzenie, czy użytkownik jest już zalogowany
if (isset($_COOKIE['jwtToken'])) {
    // Jeśli tak, przekieruj do strony głównej
    header('Location: /profile');
    exit;
}

// Sprawdzenie, czy istnieje komunikat o prawidłowej rejestracji
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $smarty->assign('successMessage', 'Rejestracja przebiegła pomyślnie. Możesz się teraz zalogować.');
}

// Sprawdzenie, czy istnieje komunikat o wylogowaniu
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $smarty->assign('successMessage', 'Wylogowanie przebiegło prawidłowo.');
} else if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $smarty->assign('successMessage', 'Zostałeś pomyślnie wylogowany.');
}

// Sprawdzenie, czy istnieje komunikat o błędzie
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'auth':
            $smarty->assign('errorMessage', 'Nieprawidłowy login lub hasło.');
            break;
        case 'session':
            $smarty->assign('errorMessage', 'Twoja sesja wygasła. Zaloguj się ponownie.');
            break;
        case 'access':
            $smarty->assign('errorMessage', 'Dostęp zabroniony. Zaloguj się, aby kontynuować.');
            break;
        case 'logout_failed':
            $smarty->assign('errorMessage', 'Wystąpił problem podczas wylogowywania. Spróbuj ponownie.');
            break;
        case 'internal':
            $smarty->assign('errorMessage', 'Wystąpił wewnętrzny błąd systemu. Zaloguj się ponownie.');
            break;
        default:
            $smarty->assign('errorMessage', 'Wystąpił błąd. Spróbuj ponownie.');
    }
}

// Renderowanie szablonu login.tpl
$smarty->display('login.tpl'); 