<?php
// Funkcje do obsługi operacji na bazie danych związanych z rejestrowaniem błędów

/**
 * Zapisuje informacje o błędzie do bazy danych
 * 
 * @param string $errorType Typ błędu (np. login_error, validation_error, ai_fetch_error)
 * @param string $errorMessage Wiadomość z opisem błędu
 * @param int|null $userId ID użytkownika (opcjonalne)
 * @param string|null $url URL na którym wystąpił błąd (opcjonalne)
 * @param string|null $payload Dodatkowe dane związane z błędem (opcjonalne)
 * @return bool Czy operacja zapisu do bazy się powiodła
 */
function setErrorLog($errorType, $errorMessage, $userId = null, $url = null, $payload = null) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "INSERT INTO error_logs (err_type, err_message, err_usr_id, err_url, err_payload) 
                  VALUES (?, ?, ?, ?, ?)";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ssiss', $errorType, $errorMessage, $userId, $url, $payload);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy insert się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // W przypadku błędu podczas logowania błędu, zapisz do logu systemowego
        error_log('Błąd podczas zapisywania do error_logs: ' . $e->getMessage());
        return false;
    }
} 