<?php
/**
 * Klasa ErrorLogger służy do logowania błędów systemu 10devs.
 * 
 * ErrorLogger zapewnia statyczny interfejs do jednolitego logowania błędów
 * w bazie danych. Logowane błędy mogą być powiązane z użytkownikiem, 
 * zawierać szczegółowe informacje o miejscu wystąpienia błędu oraz dodatkowe dane.
 * 
 * @package 10devs
 * @author 10devs Team
 * @version 1.0
 */
class ErrorLogger {
    
    /**
     * Loguje błąd aplikacji w bazie danych.
     * 
     * @param string $errorType Typ błędu (np. login_error, validation_error, ai_fetch_error)
     * @param string $errorMessage Wiadomość z opisem błędu
     * @param int|null $userId ID użytkownika (opcjonalne)
     * @param string|null $url URL na którym wystąpił błąd (opcjonalne)
     * @param string|null $payload Dodatkowe dane związane z błędem (opcjonalne)
     * @return bool Czy operacja zapisu do bazy się powiodła
     */
    public static function logError($errorType, $errorMessage, $userId = null, $url = null, $payload = null) {
        try {
            // Importowanie funkcji z commonDB
            if (!function_exists('setErrorLog')) {
                require_once __DIR__ . '/../commonDB/errorLogs.php';
            }
            
            // Wywołanie funkcji z commonDB do zapisania błędu
            return setErrorLog($errorType, $errorMessage, $userId, $url, $payload);
        } catch (Exception $e) {
            // W przypadku błędu podczas logowania, wypisz do logu systemowego
            // Jest to ostateczność, gdyż nie możemy zalogować błędu logowania błędu do bazy
            error_log('Błąd podczas zapisywania do error_logs: ' . $e->getMessage());
            return false;
        }
    }
} 