<?php
// Plik obsługujący widok rekomendacji dla konkretnego miasta

// Konfiguracja aplikacji
require_once __DIR__ . '/../config.php';
// Konfiguracja Smarty
require_once __DIR__ . '/../smarty/configs/config.php';

// Autoryzacja użytkownika
require_once __DIR__ . '/../classes/Auth.php';
$auth = new Auth();
$userId = $auth->authenticateAndGetUserId();
if (!$userId) {
    header('Location: /login?error=access');
    exit;
}

// Dołączenie funkcji bazodanowych
require_once __DIR__ . '/../commonDB/users.php';
require_once __DIR__ . '/../commonDB/cities.php';
require_once __DIR__ . '/../commonDB/recommendations.php';

// Pobranie ID miasta z parametru
$cityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cityId <= 0) {
    $smarty->assign('errorMessage', 'Nieprawidłowe ID miasta');
    $smarty->display('error.tpl');
    exit;
}

// Pobranie danych miasta
$city = getCityById($cityId, $userId);
if (!$city) {
    $smarty->assign('errorMessage', 'Nie znaleziono wskazanego miasta');
    $smarty->display('error.tpl');
    exit;
}

// Pobranie rekomendacji dla miasta
$recommendations = getRecommendationsByCityId($userId, $cityId);
if ($recommendations === null) {
    $smarty->assign('errorMessage', 'Wystąpił błąd podczas pobierania rekomendacji');
    $smarty->display('error.tpl');
    exit;
}

// Pobranie profilu użytkownika
$currentUser = getUserProfile($userId);

// Przekazanie danych do szablonu
$smarty->assign('currentUser', $currentUser);
$smarty->assign('city', $city);
$smarty->assign('recommendations', $recommendations);

// Wyświetlenie szablonu rekomendacji
$smarty->display('cities/recommendations.tpl'); 