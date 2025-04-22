<?php
// Funkcje do obsługi operacji na bazie danych związanych z miastami

/**
 * Pobiera dane miasta na podstawie nazwy i ID użytkownika
 * 
 * @param string $cityName Nazwa miasta do wyszukania
 * @param int $userId ID użytkownika
 * @return array|null Dane miasta lub null jeśli nie znaleziono
 */
function getCityByNameAndUserId($cityName, $userId) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT cit_id, cit_name, cit_usr_id, cit_desc, cit_date_created 
                 FROM cities 
                 WHERE cit_name = ? AND cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'si', $cityName, $userId);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        } else {
            return null;
        }
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania miasta: ' . $e->getMessage(), $userId);
        return null;
    }
}

/**
 * Pobiera dane miasta na podstawie ID miasta i ID użytkownika
 * 
 * @param int $cityId ID miasta do wyszukania
 * @param int $userId ID użytkownika
 * @return array|null Dane miasta lub null jeśli nie znaleziono
 */
function getCityById($cityId, $userId) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT cit_id, cit_name, cit_usr_id, cit_desc, cit_date_created 
                 FROM cities 
                 WHERE cit_id = ? AND cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $cityId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        } else {
            return null;
        }
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania miasta: ' . $e->getMessage(), $userId);
        return null;
    }
}

/**
 * Dodaje nowe miasto do bazy danych
 * 
 * @param string $cityName Nazwa miasta
 * @param int $userId ID użytkownika
 * @param string $description Opis/podsumowanie miasta (opcjonalne)
 * @return int|null ID dodanego miasta lub null w przypadku błędu
 */
function addCity($cityName, $userId, $description = null) {
    try {
        // Walidacja danych wejściowych
        if (empty($cityName) || !is_string($cityName)) {
            error_log("Błąd addCity: Nieprawidłowa nazwa miasta");
            return null;
        }
        
        if (!is_numeric($userId) || $userId <= 0) {
            error_log("Błąd addCity: Nieprawidłowy ID użytkownika: $userId");
            return null;
        }
        
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Sprawdzenie, czy miasto już istnieje (podwójne sprawdzenie)
        $checkQuery = "SELECT cit_id FROM cities WHERE cit_name = ? AND cit_usr_id = ?";
        $checkStmt = mysqli_prepare($db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, 'si', $cityName, $userId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            // Miasto już istnieje - pobieramy jego ID
            mysqli_stmt_bind_result($checkStmt, $existingCityId);
            mysqli_stmt_fetch($checkStmt);
            mysqli_stmt_close($checkStmt);
            
            error_log("Miasto '$cityName' już istnieje dla użytkownika $userId, ID: $existingCityId");
            return $existingCityId;
        }
        
        mysqli_stmt_close($checkStmt);
        
        // Przygotowanie zapytania INSERT
        $query = "INSERT INTO cities (cit_name, cit_usr_id, cit_desc) VALUES (?, ?, ?)";
        
        // Jeśli opis jest pusty, ustawiamy domyślny opis - kolumna cit_desc ma ograniczenie NOT NULL
        if ($description === null || $description === '') {
            $description = 'Brak opisu. Opis zostanie wygenerowany automatycznie.';
            error_log("Użyto domyślnego opisu dla miasta '$cityName'");
        }
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . mysqli_error($db));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, 'sis', $cityName, $userId, $description);
        $executeResult = mysqli_stmt_execute($stmt);
        
        if (!$executeResult) {
            $errorMessage = mysqli_stmt_error($stmt);
            error_log("Błąd wykonania zapytania: " . $errorMessage);
            mysqli_stmt_close($stmt);
            return null;
        }
        
        // Sprawdzenie czy insert się powiódł
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        if ($affectedRows > 0) {
            $newCityId = mysqli_insert_id($db);
            error_log("Dodano nowe miasto '$cityName' dla użytkownika $userId, przydzielono ID: $newCityId");
            mysqli_stmt_close($stmt);
            return $newCityId;
        } else {
            error_log("Nie dodano żadnego rekordu dla miasta '$cityName', użytkownik: $userId");
            mysqli_stmt_close($stmt);
            return null;
        }
    } catch (Exception $e) {
        // Logowanie błędu
        error_log("Wyjątek w addCity: " . $e->getMessage());
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas dodawania miasta: ' . $e->getMessage(), $userId);
        return null;
    }
}

/**
 * Aktualizuje opis/podsumowanie miasta
 * 
 * @param int $cityId ID miasta
 * @param int $userId ID użytkownika
 * @param string $description Nowy opis/podsumowanie miasta
 * @return bool Czy operacja się powiodła
 */
function updateCityDescription($cityId, $userId, $description) {
    try {
        // Dołączenie pliku z połączeniem do bazy danych
        require_once __DIR__ . '/dbConnect.php';
        
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "UPDATE cities SET cit_desc = ? WHERE cit_id = ? AND cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'sii', $description, $cityId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy update się powiódł
        return (mysqli_stmt_affected_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu
        require_once __DIR__ . '/../classes/ErrorLogger.php';
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji opisu miasta: ' . $e->getMessage(), $userId);
        return false;
    }
} 