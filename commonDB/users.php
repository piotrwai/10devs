<?php
// Plik zawierający funkcje bazodanowe do obsługi użytkowników

// Dołączenie plików wspólnych - tylko raz na początku pliku
require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/errorLogs.php';
require_once __DIR__ . '/../classes/ErrorLogger.php';

/**
 * Pobiera profil użytkownika na podstawie identyfikatora
 * 
 * @param int $userId Identyfikator użytkownika
 * @return array|null Dane profilu użytkownika lub null w przypadku braku użytkownika
 */
function getUserProfile($userId) {
    // Walidacja parametru wejściowego
    $userId = (int)$userId;
    
    if ($userId <= 0) {
        return null;
    }
    
    try {
        // Pobranie połączenia z bazą danych
        $db = getDbConnection();
        
        // Przygotowanie zapytania SQL z użyciem prepared statement
        $query = "SELECT usr_id, usr_login, usr_city, usr_admin 
                  FROM users 
                  WHERE usr_id = ?";
        
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            // Błąd podczas przygotowania zapytania
            throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
        }
        
        // Powiązanie parametrów z zapytaniem
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        
        // Wykonanie zapytania
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        // Powiązanie wyników zapytania ze zmiennymi
        mysqli_stmt_bind_result($stmt, $id, $login, $cityBase, $isAdmin);
        
        // Pobranie wyników zapytania
        if (mysqli_stmt_fetch($stmt)) {
            // Zamknięcie zapytania
            mysqli_stmt_close($stmt);
            
            // Zwrócenie danych użytkownika w odpowiednim formacie
            return [
                'id' => $id,
                'login' => $login,
                'cityBase' => $cityBase,
                'isAdmin' => (bool)$isAdmin
            ];
        } else {
            // Zamknięcie zapytania
            mysqli_stmt_close($stmt);
            
            // Użytkownik nie został znaleziony
            return null;
        }
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania profilu użytkownika: ' . $e->getMessage(), $userId);
        
        // Zwrócenie null w przypadku błędu
        return null;
    }
} 