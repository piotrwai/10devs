<?php
/**
 * Endpoint profilu zalogowanego użytkownika
 * GET /api/users/me
 */

require_once __DIR__ . '/../../api/bootstrap.php';
require_once __DIR__ . '/../../commonDB/users.php';

// Tylko dla zalogowanych użytkowników
$userId = requireAuth();

try {
    $userProfile = getUserProfile($userId);
    
    if (!$userProfile) {
        Response::error(404, 'Nie znaleziono profilu użytkownika');
        exit();
    }
    
    Response::success(200, 'Pobrano profil użytkownika', $userProfile);
    
} catch (Exception $e) {
    Response::error(500, 'Wystąpił błąd podczas pobierania profilu użytkownika');
} 