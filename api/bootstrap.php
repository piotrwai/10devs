<?php
/**
 * Plik inicjalizujący dla endpointów API
 */

require_once __DIR__ . '/../classes/AuthMiddleware.php';
require_once __DIR__ . '/../classes/Response.php';

// Ustawienie typu odpowiedzi
header('Content-Type: application/json; charset=UTF-8');

/**
 * Funkcja wymuszająca autoryzację
 * 
 * @return int ID zalogowanego użytkownika
 */
function requireAuth() {
    $authMiddleware = new AuthMiddleware();
    $userId = $authMiddleware->authenticate();
    
    if ($userId === null) {
        Response::error(401, 'Brak autoryzacji');
        exit();
    }
    
    return $userId;
} 