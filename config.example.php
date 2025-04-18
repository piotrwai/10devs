<?php
/**
 * Przykładowy plik konfiguracyjny
 * Skopiuj ten plik do config.php i wprowadź odpowiednie dane
 * Ten plik można bezpiecznie commitować do repozytorium.
 */

return [
    // Konfiguracja bazy danych
    'database' => [
        'host' => 'localhost',         // Adres hosta bazy danych
        'name' => 'nazwa_bazy',        // Nazwa bazy danych
        'user' => 'uzytkownik_bazy',   // Nazwa użytkownika bazy danych
        'password' => 'haslo_bazy',    // Hasło do bazy danych
        'charset' => 'utf8mb4'         // Kodowanie znaków
    ],
    
    // Konfiguracja API OpenAI
    'openai' => [
        'api_key' => 'sk-your-openai-api-key-here',  // Klucz API OpenAI
        'model' => 'gpt-4.1-mini',                   // Model AI do użycia
        'timeout' => 60,                             // Timeout w sekundach
        'max_tokens' => 1500                         // Maksymalna długość odpowiedzi
    ],
    
    // Konfiguracja aplikacji
    'app' => [
        'debug' => true,                      // Tryb debugowania (zmień na false w produkcji)
        'log_errors' => true,                 // Czy logować błędy
        'max_recommendations' => 10,          // Maksymalna liczba rekomendacji
        'max_cities_per_page' => 20,          // Maksymalna liczba miast na stronie
        'default_summary_length' => 150,      // Domyślna długość opisu miasta
        'default_title_length' => 200         // Domyślna długość tytułu rekomendacji
    ],
    
    // Konfiguracja JWT (do autoryzacji)
    'jwt' => [
        'secret_key' => 'your-jwt-secret-key-here',   // Klucz do podpisywania tokenów JWT (zmień na silny klucz w produkcji)
        'expiration' => 3600,                         // Czas ważności tokenu w sekundach
        'issuer' => '10devs-api'                      // Wydawca tokenu
    ]
]; 