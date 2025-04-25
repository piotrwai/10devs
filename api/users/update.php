<?php
// Endpoint obsługujący aktualizację danych użytkownika

// Dołączamy niezbędne pliki
require_once __DIR__ . '/../../commonDB/dbConnect.php';
require_once __DIR__ . '/../../commonDB/users.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/ErrorLogger.php';

// Inicjalizacja odpowiedzi (domyślnie JSON)
header('Content-Type: application/json');

// Weryfikacja czy użytkownik jest zalogowany
$auth = new Auth();
$userId = $auth->authenticateAndGetUserId();

if (!$userId) {
    // Użytkownik nie jest zalogowany
    http_response_code(401);
    echo json_encode(['error' => 'Brak autoryzacji lub nieprawidłowy token']);
    exit;
}

// Pobranie danych użytkownika
$currentUser = getUserProfile($userId);

if (!$currentUser) {
    // Użytkownik nie został znaleziony w bazie
    http_response_code(404);
    echo json_encode(['error' => 'Użytkownik nie został znaleziony']);
    exit;
}

// Weryfikacja metody żądania
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Niedozwolona metoda HTTP']);
    exit;
}

// Pobranie danych z żądania
$requestData = json_decode(file_get_contents('php://input'), true);

// Jeśli dane przyszły jako POST (np. z formularza), użyj $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($requestData)) {
    $requestData = $_POST;
}

// Walidacja danych wejściowych
$errors = [];
$login = isset($requestData['login']) ? trim($requestData['login']) : null;
$cityBase = isset($requestData['cityBase']) ? trim($requestData['cityBase']) : null;
$password = isset($requestData['password']) ? trim($requestData['password']): null;

// Sprawdzenie wymaganych pól
if (empty($login)) {
    $errors[] = ['error' => 'Login jest wymagany', 'field' => 'login'];
}

if (empty($cityBase)) {
    $errors[] = ['error' => 'Miasto bazowe jest wymagane', 'field' => 'cityBase'];
}

// Sprawdzenie długości hasła, jeśli zostało podane
if (!empty($password) && strlen($password) < 5) {
    $errors[] = ['error' => 'Hasło musi mieć minimum 5 znaków', 'field' => 'password'];
}

// Jeśli są błędy walidacji, zwróć je
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode($errors);
    exit;
}

// Aktualizacja danych użytkownika
try {
    // Wywołanie funkcji bazodanowej do aktualizacji profilu
    $result = setUserProfile($userId, $login, $cityBase, $password);
    
    if (!$result) {
        // Ogólny błąd aktualizacji
        http_response_code(500);
        echo json_encode(['error' => 'Nie udało się zaktualizować danych użytkownika']);
        exit;
    }
    
    if (isset($result['error'])) {
        // Błąd walidacji po stronie serwera (np. login zajęty)
        http_response_code(400);
        echo json_encode($result);
        exit;
    }
    
    // Sukces - zwróć zaktualizowane dane użytkownika
    http_response_code(200);
    echo json_encode([
        'id' => $userId,
        'login' => $login,
        'cityBase' => $cityBase,
        'isAdmin' => $currentUser['isAdmin']
    ]);
    
} catch (Exception $e) {
    // Logowanie błędu
    ErrorLogger::logError('api_error', 'Błąd podczas aktualizacji profilu użytkownika: ' . $e->getMessage(), $userId);
    
    // Zwrócenie błędu
    http_response_code(500);
    echo json_encode(['error' => 'Wystąpił błąd podczas aktualizacji profilu']);
    exit;
} 