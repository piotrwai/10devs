<?php

// Zakładamy, że ErrorLogger jest autoloadowany lub załączony wcześniej
// require_once __DIR__ . '/ErrorLogger.php';

class GeoHelper
{
    /**
     * Sprawdza, czy podana nazwa jest miastem przy użyciu Google Geocoding API.
     *
     * @param string $cityName Nazwa do sprawdzenia.
     * @return bool Zwraca true, jeśli nazwa jest miastem (typ 'locality'), w przeciwnym razie false.
     */
    public static function isCity(string $cityName): bool
    {
        // Dołączenie potrzebnych plików (jeśli nie zostały wcześniej dołączone)
        require_once __DIR__ . '/../commonDB/errorLogs.php';
        
        // Wczytanie konfiguracji
        $config = require __DIR__ . '/../config.php';
        
        // Walidacja konfiguracji
        if (empty($config['geocoding']['apiKey'])) {
            ErrorLogger::logError('geocoding_error', 'Brak klucza API dla Google Geocoding w config.php lub klucz jest pusty.');
            return false;
        }

        $apiKey = $config['geocoding']['apiKey'];
        $language = $config['geocoding']['language'] ?? 'pl';
        $apiUrl = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s&language=%s',
            urlencode($cityName),
            $apiKey,
            $language
        );

        // Inicjalizacja cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10 sekund
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Wyłączenie weryfikacji SSL dla zgodności
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        // Wykonanie zapytania
        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorNo = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Obsługa błędów cURL
        if ($curlErrorNo !== 0) {
            ErrorLogger::logError('geocoding_curl_error', "Błąd cURL ({$curlErrorNo}): {$curlError} dla miasta: {$cityName}");
            return false;
        }

        // Obsługa błędów HTTP
        if ($httpCode !== 200) {
            ErrorLogger::logError('geocoding_http_error', "Błąd HTTP ({$httpCode}) dla miasta: {$cityName}");
            return false;
        }

        // Sprawdzenie czy odpowiedź nie jest pusta
        if (empty($responseJson)) {
            ErrorLogger::logError('geocoding_empty_response', "Pusta odpowiedź API dla miasta: {$cityName}");
            return false;
        }

        // Dekodowanie JSON
        $response = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ErrorLogger::logError('geocoding_json_error', "Błąd JSON dla miasta: {$cityName}");
            return false;
        }

        // Analiza odpowiedzi
        if (isset($response['status'])) {
            // Jeśli status to OK i mamy wyniki - sprawdzamy czy zawierają locality
            if ($response['status'] === 'OK' && !empty($response['results'])) {
                foreach ($response['results'] as $result) {
                    if (isset($result['types']) && in_array('locality', $result['types'])) {
                        return true; // Znaleziono miasto
                    }
                }
                // Przeszliśmy wszystkie wyniki i nie znaleźliśmy locality - to nie jest miasto według API
                return false;
            } 
            // Obsługa innych kodów statusu
            else if ($response['status'] === 'ZERO_RESULTS') {
                // Nic nie znaleziono - to nie jest miasto
                return false;
            } 
            else if ($response['status'] !== 'OK') {
                // Błąd API
                $errorMessage = isset($response['error_message']) ? $response['error_message'] : 'Brak szczegółów błędu';
                ErrorLogger::logError('geocoding_api_error', "Błąd API ({$response['status']}): {$errorMessage} dla miasta: {$cityName}");
                return false;
            }
        } else {
            // Nieprawidłowy format odpowiedzi
            ErrorLogger::logError('geocoding_invalid_response', "Brak statusu w odpowiedzi API dla miasta: {$cityName}");
            return false;
        }

        // Domyślnie zwracamy false - jeśli dojdziemy do tego miejsca, oznacza to, że nie znaleziono miasta
        return false;
    }
}

?> 