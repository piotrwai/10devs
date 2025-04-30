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
 * Pobiera informacje o mieście po ID, sprawdzając czy należy do użytkownika
 * 
 * @param int $cityId ID miasta
 * @param int $userId ID użytkownika
 * @return array|null Dane miasta lub null jeśli nie znaleziono
 */
function getCityById($cityId, $userId) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT cit_id as id, cit_name as name, cit_desc as description, 
                        CASE WHEN cit_visited = 1 THEN TRUE ELSE FALSE END as visited
                 FROM cities 
                 WHERE cit_id = ? AND cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $cityId, $userId);
        mysqli_stmt_execute($stmt);
        
        // Pobranie wyniku
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        
        return null;
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
 * @return array Tablica asocjacyjna zawierająca klucze 'data' (tablica miast) i 'totalItems' (całkowita liczba miast pasujących do kryteriów)
 */
function getUserCitiesWithRecommendationCount($userId, $page = 1, $perPage = 10, $visited = null) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Obliczenie offsetu dla paginacji
        $offset = ($page - 1) * $perPage;
        
        // Budowanie zapytania bazowego z SQL_CALC_FOUND_ROWS
        $query = "SELECT SQL_CALC_FOUND_ROWS
                    c.cit_id AS id, 
                    c.cit_name AS name,
                    COUNT(DISTINCT r.rec_id) AS recommendationCount,
                    SUM(CASE WHEN r.rec_done = 1 THEN 1 ELSE 0 END) AS visitedRecommendationsCount,
                    CASE WHEN c.cit_visited = 1 THEN TRUE ELSE FALSE END AS visited
                  FROM 
                    cities c
                  LEFT JOIN 
                    recom r ON c.cit_id = r.rec_cit_id AND r.rec_usr_id = ?
                  WHERE 
                    c.cit_usr_id = ?";
        
        // Dodanie warunku filtrowania po statusie odwiedzenia
        $params = [$userId, $userId]; // Dodajemy userId dwa razy - dla JOIN i WHERE
        $types = 'ii';
        
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
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Błąd wykonania zapytania: " . mysqli_stmt_error($stmt));
        }
        
        // Pobranie wyników
        $result = mysqli_stmt_get_result($stmt);
        $cities = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Upewnijmy się, że visited jest booleanem i liczby są liczbami
            $row['visited'] = (bool)$row['visited'];
            $row['recommendationCount'] = (int)$row['recommendationCount'];
            $row['visitedRecommendationsCount'] = (int)$row['visitedRecommendationsCount'];
            $cities[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        // Pobranie całkowitej liczby znalezionych wierszy (przed LIMIT)
        $totalItemsResult = mysqli_query($db, "SELECT FOUND_ROWS() as total");
        if (!$totalItemsResult) {
            // Logowanie błędu, ale nie przerywamy - zwrócimy 0
            ErrorLogger::logError('db_warning', 'Błąd podczas pobierania FOUND_ROWS(): ' . mysqli_error($db), $userId);
            $totalItems = 0; 
        } else {
            $totalItemsRow = mysqli_fetch_assoc($totalItemsResult);
            $totalItems = (int)$totalItemsRow['total'];
            mysqli_free_result($totalItemsResult);
        }

        // Zwrócenie wyników w oczekiwanym formacie
        return [
            'data' => $cities,
            'totalItems' => $totalItems
        ];
        
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania listy miast: ' . $e->getMessage(), $userId);
        // Zwracamy pusty wynik w przypadku błędu
        return [
            'data' => [],
            'totalItems' => 0
        ];
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
 * Sprawdza, czy użytkownik ma jakiekolwiek miasta
 * 
 * @param int $userId ID użytkownika
 * @return bool True jeśli użytkownik ma co najmniej jedno miasto, false w przeciwnym razie
 */
function userHasAnyCities($userId) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania
        $query = "SELECT 1 FROM cities WHERE cit_usr_id = ? LIMIT 1";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        // Sprawdzenie czy znaleziono jakikolwiek rekord
        return (mysqli_stmt_num_rows($stmt) > 0);
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas sprawdzania czy użytkownik ma miasta: ' . $e->getMessage(), $userId);
        return false;
    }
}

/**
 * Usuwa miasto na podstawie ID, sprawdzając czy należy do użytkownika.
 * UWAGA: Rekomendacje powiązane z tym miastem są usuwane automatycznie przez mechanizm bazy danych (ON DELETE CASCADE).
 * 
 * @param int $cityId ID miasta do usunięcia
 * @param int $userId ID użytkownika, do którego należy miasto
 * @return bool True jeśli miasto zostało usunięte, false w przeciwnym razie (np. nie znaleziono, błąd)
 */
function delCityByIdAndUserId($cityId, $userId) {
    try {
        // Pobranie połączenia do bazy
        $db = getDbConnection();
        
        // Przygotowanie zapytania DELETE
        // Sprawdzamy również usr_id, aby upewnić się, że użytkownik usuwa swoje miasto
        $query = "DELETE FROM cities WHERE cit_id = ? AND cit_usr_id = ?";
        
        // Przygotowanie i wykonanie zapytania
        $stmt = mysqli_prepare($db, $query);
        if (!$stmt) {
            ErrorLogger::logError('db_error', 'Błąd przygotowania zapytania DELETE dla miasta: ' . mysqli_error($db), $userId);
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, 'ii', $cityId, $userId);
        $executeResult = mysqli_stmt_execute($stmt);
        
        if (!$executeResult) {
            ErrorLogger::logError('db_error', 'Błąd wykonania zapytania DELETE dla miasta: ' . mysqli_stmt_error($stmt), $userId);
            mysqli_stmt_close($stmt);
            return false;
        }
        
        // Sprawdzenie czy rekord został usunięty
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        // Zwracamy true tylko jeśli dokładnie jeden rekord został usunięty
        return ($affectedRows === 1);
        
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Wyjątek podczas usuwania miasta: ' . $e->getMessage(), $userId);
        return false;
    }
} 