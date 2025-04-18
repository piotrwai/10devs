<?php
// Funkcje do obsługi operacji na bazie danych związanych z rekomendacjami

/**
 * Dodaje nową rekomendację do bazy danych
 * 
 * @param int $userId ID użytkownika
 * @param int $cityId ID miasta
 * @param string $title Tytuł rekomendacji
 * @param string $description Opis rekomendacji
 * @param string $model Identyfikator modelu AI
 * @param string $status Status rekomendacji (domyślnie 'accepted')
 * @return int|null ID dodanej rekomendacji lub null w przypadku błędu
 */
function addRecommendation($userId, $cityId, $title, $description, $model, $status = 'accepted') {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Sprawdzenie czy istnieje już rekomendacja o takim samym tytule dla tego miasta i użytkownika
        if (isRecommendationTitleDuplicate($userId, $cityId, $title)) {
            return null; // Tytuł jest już używany
        }
        
        // Przygotowanie zapytania
        $query = "INSERT INTO recom (rec_usr_id, rec_cit_id, rec_title, rec_desc, rec_model, rec_status, rec_done) 
                  VALUES (?, ?, ?, ?, ?, ?, 0)";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iissss', $userId, $cityId, $title, $description, $model, $status);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy insert się powiódł
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            return mysqli_insert_id($db);
        } else {
            return null;
        }
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas dodawania rekomendacji: ' . $e->getMessage(), $userId);
        return null;
    }
}

/**
 * Sprawdza czy tytuł rekomendacji jest już używany dla danego miasta i użytkownika
 * 
 * @param int $userId ID użytkownika
 * @param int $cityId ID miasta
 * @param string $title Tytuł rekomendacji do sprawdzenia
 * @return bool Czy tytuł jest już używany
 */
function isRecommendationTitleDuplicate($userId, $cityId, $title) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT COUNT(*) as count FROM recom 
                 WHERE rec_usr_id = ? AND rec_cit_id = ? AND rec_title = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iis', $userId, $cityId, $title);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyniku
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return ($row['count'] > 0);
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas sprawdzania duplikatu rekomendacji: ' . $e->getMessage(), $userId);
        return false; // W przypadku błędu zwracamy false, aby nie blokować dodawania
    }
}

/**
 * Pobiera rekomendacje dla określonego miasta i użytkownika
 * 
 * @param int $userId ID użytkownika
 * @param int $cityId ID miasta
 * @return array|null Lista rekomendacji lub null w przypadku błędu
 */
function getRecommendationsByCityId($userId, $cityId) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT rec_id, rec_title, rec_desc, rec_model, rec_status, rec_done, rec_date_created, rec_date_modified 
                 FROM recom 
                 WHERE rec_usr_id = ? AND rec_cit_id = ?
                 ORDER BY rec_date_created DESC";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $cityId);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        
        $recommendations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $recommendations[] = [
                'id' => $row['rec_id'],
                'title' => $row['rec_title'],
                'description' => $row['rec_desc'],
                'model' => $row['rec_model'],
                'status' => $row['rec_status'],
                'done' => (bool)$row['rec_done'],
                'dateCreated' => $row['rec_date_created'],
                'dateModified' => $row['rec_date_modified']
            ];
        }
        
        return $recommendations;
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania rekomendacji: ' . $e->getMessage(), $userId);
        return null;
    }
}

/**
 * Aktualizuje status rekomendacji
 * 
 * @param int $userId ID użytkownika
 * @param int $recommendationId ID rekomendacji
 * @param string $status Nowy status (accepted, edited, rejected)
 * @return bool Czy operacja się powiodła
 */
function updateRecommendationStatus($userId, $recommendationId, $status) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania (upewniamy się, że użytkownik jest właścicielem rekomendacji)
        $query = "UPDATE recom SET rec_status = ?, rec_date_modified = CURRENT_TIMESTAMP 
                  WHERE rec_id = ? AND rec_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'sii', $status, $recommendationId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy update się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji statusu rekomendacji: ' . $e->getMessage(), $userId);
        return false;
    }
}

/**
 * Oznacza rekomendację jako wykonaną
 * 
 * @param int $userId ID użytkownika
 * @param int $recommendationId ID rekomendacji
 * @return bool Czy operacja się powiodła
 */
function markRecommendationAsDone($userId, $recommendationId) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania (upewniamy się, że użytkownik jest właścicielem rekomendacji)
        $query = "UPDATE recom SET rec_done = 1, rec_date_modified = CURRENT_TIMESTAMP 
                  WHERE rec_id = ? AND rec_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $recommendationId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy update się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji rekomendacji: ' . $e->getMessage(), $userId);
        return false;
    }
} 