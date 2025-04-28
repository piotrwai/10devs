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

/**
 * Aktualizuje dane użytkownika w bazie danych
 * 
 * @param int $userId Identyfikator użytkownika
 * @param string $login Nowy login użytkownika
 * @param string $cityBase Nowe miasto bazowe użytkownika
 * @param string|null $password Nowe hasło użytkownika (opcjonalne)
 * @return array|bool Sukces: true, Błąd: tablica z informacją o błędzie
 */
function setUserProfile($userId, $login, $cityBase, $password = null) {
    // Walidacja parametrów wejściowych
    $userId = (int)$userId;
    $login = trim($login);
    $cityBase = trim($cityBase);
    
    if ($userId <= 0 || empty($login) || empty($cityBase)) {
        return ['error' => 'Nieprawidłowe dane wejściowe', 'field' => 'general'];
    }
    
    try {
        // Pobranie połączenia z bazą danych
        $db = getDbConnection();
        
        // Sprawdzenie unikalności loginu
        $query = "SELECT usr_id FROM users WHERE usr_login = ? AND usr_id != ?";
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $login, $userId);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_store_result($stmt);
        
        // Jeśli znaleziono innego użytkownika z takim samym loginem
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            return ['error' => 'Login jest już zajęty', 'field' => 'login'];
        }
        
        mysqli_stmt_close($stmt);
        
        // Przygotowanie zapytania UPDATE - różne dla przypadku z hasłem i bez
        if ($password !== null) {
            // Walidacja hasła
            if (strlen($password) < 5) {
                return ['error' => 'Hasło musi mieć minimum 5 znaków', 'field' => 'password'];
            }
            
            // Hashowanie hasła
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Aktualizacja z hasłem
            $query = "UPDATE users SET usr_login = ?, usr_city = ?, usr_password = ? WHERE usr_id = ?";
            $stmt = mysqli_prepare($db, $query);
            
            if (!$stmt) {
                throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
            }
            
            mysqli_stmt_bind_param($stmt, 'sssi', $login, $cityBase, $hashedPassword, $userId);
        } else {
            // Aktualizacja bez hasła
            $query = "UPDATE users SET usr_login = ?, usr_city = ? WHERE usr_id = ?";
            $stmt = mysqli_prepare($db, $query);
            
            if (!$stmt) {
                throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
            }
            
            mysqli_stmt_bind_param($stmt, 'ssi', $login, $cityBase, $userId);
        }
        
        // Wykonanie zapytania
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        // Sprawdzenie czy coś zostało zaktualizowane
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        if ($affectedRows === 0) {
            // Brak zmian lub użytkownik nie istnieje
            return ['error' => 'Nie znaleziono użytkownika lub brak zmian', 'field' => 'general'];
        }
        
        // Sukces
        return true;
        
    } catch (Exception $e) {
        // Logowanie błędu
        ErrorLogger::logError('db_error', 'Błąd podczas aktualizacji profilu użytkownika: ' . $e->getMessage(), $userId);
        
        // Zwrócenie informacji o błędzie
        return ['error' => 'Wystąpił błąd podczas aktualizacji profilu', 'field' => 'general'];
    }
} 

/**
 * Sprawdza czy podany login jest już zajęty
 * 
 * @param string $login Login do sprawdzenia
 * @return bool True jeśli login jest zajęty, false w przeciwnym razie
 */
function isLoginTaken($login) {
    try {
        $db = getDbConnection();
        
        $query = "SELECT 1 FROM users WHERE usr_login = ?";
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
        }
        
        mysqli_stmt_bind_param($stmt, 's', $login);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        
        return $exists;
        
    } catch (Exception $e) {
        ErrorLogger::logError('db_error', 'Błąd podczas sprawdzania unikalności loginu: ' . $e->getMessage());
        return true; // W razie błędu zakładamy, że login jest zajęty (bezpieczniejsza opcja)
    }
}

/**
 * Tworzy nowego użytkownika w bazie danych
 * 
 * @param array $userData Dane użytkownika (login, password, cityBase)
 * @return int|false ID utworzonego użytkownika lub false w przypadku błędu
 */
function setNewUser($userData) {
    try {
        $db = getDbConnection();
        
        $query = "INSERT INTO users (usr_login, usr_password, usr_city, usr_admin, usr_date_registration) 
                  VALUES (?, ?, ?, 0, NOW())";
        
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
        }
        
        mysqli_stmt_bind_param($stmt, 'sss', 
            $userData['login'],
            $userData['password'],
            $userData['cityBase']
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        $userId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);
        
        if ($userId <= 0) {
            throw new Exception('Nie udało się pobrać ID utworzonego użytkownika');
        }
        
        return $userId;
        
    } catch (Exception $e) {
        ErrorLogger::logError('db_error', 'Błąd podczas tworzenia nowego użytkownika: ' . $e->getMessage());
        return false;
    }
} 

/**
 * Pobiera dane użytkownika na podstawie loginu
 * 
 * @param string $login Login użytkownika
 * @return array|null Dane użytkownika lub null jeśli nie znaleziono
 */
function getUserByLogin($login) {
    try {
        $db = getDbConnection();
        
        $query = "SELECT usr_id, usr_login, usr_password, usr_city, usr_admin 
                  FROM users 
                  WHERE usr_login = ?";
        
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            throw new Exception('Błąd przygotowania zapytania: ' . mysqli_error($db));
        }
        
        mysqli_stmt_bind_param($stmt, 's', $login);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Błąd wykonania zapytania: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        
        return $user;
        
    } catch (Exception $e) {
        ErrorLogger::logError('db_error', 'Błąd podczas pobierania użytkownika po loginie: ' . $e->getMessage());
        return null;
    }
} 