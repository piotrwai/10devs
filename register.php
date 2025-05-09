<?php
/**
 * Plik obsługujący ścieżkę /register - widok strony rejestracji
 */

// Dołączenie pliku konfiguracyjnego
require_once 'config.php';

// Dołączenie pliku konfiguracyjnego Smarty
require_once __DIR__ . '/smarty/configs/config.php';

// Sprawdzenie, czy użytkownik jest już zalogowany
if (isset($_COOKIE['jwtToken'])) {
    // Jeśli tak, przekieruj do strony głównej
    header('Location: /');
    exit;
}

// Sprawdzenie, czy istnieje komunikat o błędzie
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'login_exists':
            $smarty->assign('errorMessage', 'Podany login jest już zajęty. Wybierz inny login.');
            break;
        case 'validation':
            $smarty->assign('errorMessage', 'Formularz zawiera błędy. Sprawdź poprawność wprowadzonych danych.');
            break;
        case 'server':
            $smarty->assign('errorMessage', 'Wystąpił błąd serwera. Spróbuj ponownie później.');
            break;
        default:
            $smarty->assign('errorMessage', 'Wystąpił błąd. Spróbuj ponownie.');
    }
}

// Renderowanie szablonu register.tpl
$smarty->assign('isLogged', false);
$smarty->display('register.tpl'); 