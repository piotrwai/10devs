<?php
// Funkcje do obsługi operacji na bazie danych związanych z miastami

// Dołączenie plików wspólnych - tylko raz na początku pliku
require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/errorLogs.php';
require_once __DIR__ . '/../classes/ErrorLogger.php';

/**
 * Pobiera dane miasta na podstawie nazwy i ID użytkownika
 * 
 * @param string $cityName Nazwa miasta do wyszukania
 * @param int $userId ID użytkownika
 * @return array|null Dane miasta lub null jeśli nie znaleziono
 */
function getCityByNameAndUserId($cityName, $userId) {
    try {
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
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji opisu miasta: ' . $e->getMessage(), $userId);
        return false;
    }
}

/**
 * Pobiera listę miast użytkownika wraz z liczbą rekomendacji
 * z obsługą paginacji i filtrowania
 * 
 * @param int $userId ID użytkownika
 * @param int $page Numer strony (domyślnie 1)
 * @param int $perPage Liczba elementów na stronę (domyślnie 10)
 * @param bool|null $visited Filtrowanie po statusie odwiedzenia (null = wszystkie)
 * @return array Tablica z danymi miast i licznikiem rekomendacji
 */
function getUserCitiesWithRecommendationCount($userId, $page = 1, $perPage = 10, $visited = null) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Obliczenie offsetu dla paginacji
        $offset = ($page - 1) * $perPage;
        
        // Budowanie zapytania bazowego
        $query = "SELECT 
                    c.cit_id AS id, 
                    c.cit_name AS name,
                    COALESCE(COUNT(r.rec_id), 0) AS recommendationCount,
                    CASE WHEN c.cit_visited = 1 THEN TRUE ELSE FALSE END AS visited
                  FROM 
                    cities c
                  LEFT JOIN 
                    recom r ON c.cit_id = r.rec_cit_id AND r.rec_usr_id = c.cit_usr_id
                  WHERE 
                    c.cit_usr_id = ?";
        
        // Dodanie warunku filtrowania po statusie odwiedzenia
        $params = [$userId];
        $types = 'i';
        
        if ($visited !== null) {
            $query .= " AND c.cit_visited = ?";
            $params[] = $visited ? 1 : 0;
            $types .= 'i';
        }
        
        // Dodanie grupowania, sortowania i limitu
        $query .= " GROUP BY c.cit_id, c.cit_name, c.cit_visited
                   ORDER BY c.cit_name ASC
                   LIMIT ? OFFSET ?";
        
        // Dodanie parametrów dla LIMIT i OFFSET
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        
        if ($stmt === false) {
            throw new Exception("Błąd przygotowania zapytania: " . mysqli_error($db));
        }
        
        // Bindowanie parametrów
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        // Wykonanie zapytania
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Błąd wykonania zapytania: " . mysqli_stmt_error($stmt));
        }
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        $cities = [];
        
        // Formatowanie wyników
        while ($row = mysqli_fetch_assoc($result)) {
            $cities[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $cities;
        
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania listy miast: ' . $e->getMessage(), $userId);
        return [];
    }
}

/**
 * Aktualizuje status odwiedzenia miasta
 * 
 * @param int $cityId ID miasta
 * @param int $userId ID użytkownika
 * @param bool $visited Nowy status odwiedzenia
 * @return bool Czy operacja się powiodła
 */
function updateCityVisitedStatus($cityId, $userId, $visited) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "UPDATE cities SET cit_visited = ? WHERE cit_id = ? AND cit_usr_id = ?";
        
        // Konwersja wartości boolean na integer (0 lub 1)
        $visitedValue = $visited ? 1 : 0;
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iii', $visitedValue, $cityId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Sprawdzenie czy update się powiódł
        $success = (mysqli_stmt_affected_rows($stmt) > 0);
        mysqli_stmt_close($stmt);
        
        return $success;
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji statusu odwiedzenia miasta: ' . $e->getMessage(), $userId);
        return false;
    }
}

/**
 * Sprawdza czy użytkownik ma jakiekolwiek miasta w bazie danych
 * 
 * @param int $userId ID użytkownika
 * @return bool Czy użytkownik ma jakiekolwiek miasta
 */
function userHasAnyCities($userId) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT COUNT(*) as count FROM cities WHERE cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        
        // Zwrócenie informacji czy liczba miast jest większa od 0
        return ($row['count'] > 0);
        
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas sprawdzania miast użytkownika: ' . $e->getMessage(), $userId);
        return false;
    }
} 