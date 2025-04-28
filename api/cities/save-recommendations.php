<?php
/**
 * Endpoint zapisujący rekomendacje dla miasta
 * POST /api/cities/save-recommendations
 * 
 * Parametry wejściowe:
 * - recommendations: [{ title: string, description: string, model: string, status: string }]
 * - cityId: number
 */

require_once __DIR__ . '/../../api/bootstrap.php';
require_once __DIR__ . '/../../commonDB/cities.php';

// Sprawdzenie metody HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error(405, 'Metoda nie jest dozwolona');
    exit();
}

// Pobranie danych z żądania
$data = json_decode(file_get_contents('php://input'), true);

// Walidacja danych
if (!isset($data['cityId']) || !isset($data['recommendations']) || !is_array($data['recommendations'])) {
    Response::error(400, 'Nieprawidłowe dane wejściowe');
    exit();
}

// Sprawdzenie czy miasto istnieje
$city = getCity($data['cityId']);
if (!$city) {
    Response::error(404, 'Miasto nie zostało znalezione');
    exit();
}

// Zapisanie rekomendacji
$result = setRecommendations($data['cityId'], $data['recommendations']);
if (!$result) {
    Response::error(500, 'Wystąpił błąd podczas zapisywania rekomendacji');
    exit();
}

Response::success(200, 'Rekomendacje zostały zapisane pomyślnie'); 