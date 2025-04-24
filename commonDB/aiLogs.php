<?php
// Funkcje do obsługi operacji na bazie danych związanych z AI logs i inputs

// Dołączenie pliku z połączeniem do bazy danych - tylko raz na początku pliku
require_once __DIR__ . '/dbConnect.php';

/**
 * Zapisuje informacje o wywołaniu AI do tabeli ai_logs
 * 
 * @param int $userId ID użytkownika
 * @param int $recommendationId ID rekomendacji
 * @param string $status Status wywołania ('success', 'error', itp.)
 * @return bool Czy operacja się powiodła
 */
function setAiLog($userId, $recommendationId, $status) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "INSERT INTO ai_logs (ail_usr_id, ail_rec_id, ail_status) VALUES (?, ?, ?)";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iis', $userId, $recommendationId, $status);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy insert się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu do logu systemowego (nie używamy ErrorLogger aby uniknąć rekurencji)
        error_log('Błąd podczas zapisywania do ai_logs: ' . $e->getMessage());
        return false;
    }
}

/**
 * Zapisuje informacje o wejściu AI do tabeli ai_inputs
 * 
 * @param int $userId ID użytkownika
 * @param string $content Treść wejścia
 * @param string $source Źródło wejścia (np. 'city_search', 'manual_input')
 * @return bool Czy operacja się powiodła
 */
function setAiInput($userId, $content, $source = null) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "INSERT INTO ai_inputs (ain_usr_id, ain_content, ain_source) VALUES (?, ?, ?)";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iss', $userId, $content, $source);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy insert się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu do logu systemowego (nie używamy ErrorLogger aby uniknąć rekurencji)
        error_log('Błąd podczas zapisywania do ai_inputs: ' . $e->getMessage());
        return false;
    }
} 