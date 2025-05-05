<?php
/**
 * Przykładowy plik konfiguracyjny
 * Skopiuj ten plik do config.php i wprowadź odpowiednie dane
 * Ten plik można bezpiecznie commitować do repozytorium.
 */

return [
    // Konfiguracja bazy danych - zgodna z config.php
    'database' => [
        'host' => 'localhost',
        'name' => '10devs', // Zmieniono z 'dbname' na 'name' dla zgodności
        'user' => 'root',
        'password' => '', // Przykładowe puste hasło jak w config.php
        'charset' => 'utf8mb4'
    ],
    
    // Konfiguracja API OpenAI - zgodna z config.php
    'openai' => [
        'api_key' => 'sk-YOUR_OPENAI_API_KEY_HERE', // Zmieniono 'apiKey' na 'api_key'
        'model' => 'gpt-4.1-mini',
        'timeout' => 60,
        'max_tokens' => 10240
    ],
    
    // Konfiguracja aplikacji - zgodna z config.php (bez js_version)
    'app' => [
        'debug' => true, // Przykładowo true, jak w config.php
        'log_errors' => true,
        'max_recommendations' => 10,
        'max_cities_per_page' => 3, // Przykładowo 3, jak w config.php
        'default_summary_length' => 150,
        'default_title_length' => 200,
        'js_version' => date('YmdHis')         // Wersja JavaScriptu
    ],
    
    // Konfiguracja Google Geocoding API - zgodna z config.php
    'geocoding' => [
        'apiKey' => 'YOUR_GOOGLE_GEOCODING_API_KEY_HERE',
        'language' => 'pl'
    ],
    
    // Konfiguracja JWT - zgodna z config.php
    'jwt' => [
        'secret_key' => 'tajny-klucz-do-jwt-zmien-na-produkcji', // Zgodne z config.php
        'expiration' => 3600,
        'issuer' => '10devs-api' // Zgodne z config.php
    ],
    
    // Konfiguracja Google Maps Directions API
    'googleapis' => [
        'apiKey' => 'TWOJ_NOWY_KLUCZ_API_GOOGLE_DIRECTIONS',
        'mode' => 'driving',
        'units' => 'metric',
        'language' => 'pl'
    ]
]; 