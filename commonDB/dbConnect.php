<?php
// Plik obsługujący połączenie z bazą danych

/**
 * Zwraca obiekt połączenia do bazy danych
 * 
 * @throws Exception W przypadku problemów z połączeniem
 * @return resource Połączenie do bazy danych
 */
function getDbConnection() {
    static $connection = null;
    
    // Jeśli połączenie już istnieje, zwróć je (singleton)
    if ($connection !== null) {
        return $connection;
    }
    
    // Wczytanie konfiguracji z pliku
    $config = require __DIR__ . '/../config.php';
    
    // Pobranie danych z konfiguracji
    $dbHost = $config['database']['host'];
    $dbUser = $config['database']['user'];
    $dbPass = $config['database']['password'];
    $dbName = $config['database']['name'];
    
    // Utworzenie nowego połączenia
    $connection = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
    
    // Sprawdzenie czy połączenie się powiodło
    if (mysqli_connect_error()) {
        throw new Exception('Błąd połączenia z bazą danych: ' . mysqli_connect_error());
    }
    
    // Ustawienie kodowania znaków
    mysqli_set_charset($connection, $config['database']['charset'] ?? 'utf8mb4');
    
    return $connection;
} 