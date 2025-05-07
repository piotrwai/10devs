<?php

// Zakładamy, że ErrorLogger jest autoloadowany lub załączony wcześniej
// require_once __DIR__ . '/ErrorLogger.php';

class GeoHelper
{
    /**
     * Sprawdza, czy podana nazwa jest miastem przy użyciu Google Geocoding API.
     *
     * @param string $cityName Nazwa do sprawdzenia.
     * @return array|false Zwraca tablicę [isCity => bool, properName => string] lub false w przypadku błędu
     */
    public function isCity(string $cityName)
    {
        // Dołączenie potrzebnych plików (jeśli nie zostały wcześniej dołączone)
        require_once __DIR__ . '/../commonDB/errorLogs.php';
        
        // Wczytanie konfiguracji
        $configPath = __DIR__ . '/../config.php';
        if (!file_exists($configPath)) {
            ErrorLogger::logError('config_error', 'Plik konfiguracyjny nie został znaleziony: ' . $configPath);
            return false;
        }
        $config = require($configPath);
        
        // Walidacja konfiguracji dla Geocoding
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

        // Wywołanie cURL (prywatna metoda pomocnicza)
        $response = $this->performCurlRequest($apiUrl, 'Geocoding');

        if ($response === null) {
            // Błąd został już zalogowany w performCurlRequest
            return false;
        }

        // Analiza odpowiedzi Geocoding
        if (isset($response['status'])) {
            if ($response['status'] === 'OK' && !empty($response['results'])) {
                foreach ($response['results'] as $result) {
                    if (isset($result['types']) && in_array('locality', $result['types'])) {
                        // Szukamy długiej nazwy miasta w komponentach adresu
                        $properName = $cityName; // domyślnie używamy oryginalnej nazwy
                        if (isset($result['address_components'])) {
                            foreach ($result['address_components'] as $component) {
                                if (in_array('locality', $component['types'])) {
                                    $properName = $component['long_name'];
                                    break;
                                }
                            }
                        }
                        return [
                            'isCity' => true,
                            'properName' => $properName
                        ];
                    }
                }
                return ['isCity' => false, 'properName' => $cityName];
            } elseif ($response['status'] === 'ZERO_RESULTS') {
                return ['isCity' => false, 'properName' => $cityName];
            } else {
                $errorMessage = isset($response['error_message']) ? $response['error_message'] : 'Brak szczegółów błędu';
                ErrorLogger::logError('geocoding_api_error', "Błąd API Geocoding ({$response['status']}): {$errorMessage} dla miasta: {$cityName}");
                return false;
            }
        } else {
            ErrorLogger::logError('geocoding_invalid_response', "Brak statusu w odpowiedzi API Geocoding dla miasta: {$cityName}");
            return false;
        }
    }

    /**
     * Pobiera trasę między dwoma miastami używając Google Directions API.
     *
     * @param string $originCity Miasto początkowe.
     * @param string $destinationCity Miasto docelowe.
     * @return array|null Dane trasy [distance_km, steps, summary] lub null w przypadku błędu.
     */
    public function getDirections(string $originCity, string $destinationCity): ?array
    {
        require_once __DIR__ . '/../commonDB/errorLogs.php';

        $configPath = __DIR__ . '/../config.php';
        if (!file_exists($configPath)) {
            ErrorLogger::logError('config_error', 'Plik konfiguracyjny nie został znaleziony: ' . $configPath);
            return null;
        }
        $config = require($configPath);

        // Walidacja konfiguracji dla Directions API (googleapis)
        if (empty($config['googleapis']['apiKey'])) {
            ErrorLogger::logError('directions_error', 'Brak klucza API dla Google Directions w config.php (googleapis.apiKey) lub klucz jest pusty.');
            return null;
        }

        $apiKey = $config['googleapis']['apiKey'];
        $options = [
            'mode' => $config['googleapis']['mode'] ?? 'driving',
            'units' => $config['googleapis']['units'] ?? 'metric',
            'language' => $config['googleapis']['language'] ?? 'pl'
        ];

        $params = array_merge(
            $options,
            [
                'origin' => $originCity,
                'destination' => $destinationCity,
                'key' => $apiKey
            ]
        );

        $apiUrl = 'https://maps.googleapis.com/maps/api/directions/json?' . http_build_query($params);

        $response = $this->performCurlRequest($apiUrl, 'Directions');

        if ($response === null) {
            // Błąd zalogowany w performCurlRequest
            return null;
        }

        // Analiza odpowiedzi Directions
        if (isset($response['status'])) {
            if ($response['status'] === 'OK' && !empty($response['routes'])) {
                $route = $response['routes'][0]; // Bierzemy pierwszą trasę
                if (empty($route['legs'])) {
                    ErrorLogger::logError('directions_warning', "Trasa OK, ale brak odcinków (legs) dla: {$originCity} -> {$destinationCity}");
                    return null;
                }
                $leg = $route['legs'][0]; // Bierzemy pierwszy odcinek

                $directionsData = [
                    'distance_km' => isset($leg['distance']['value']) ? round($leg['distance']['value'] / 1000) : 0,
                    'steps' => [],
                    'summary' => $route['summary'] ?? ''
                ];

                if (!empty($leg['steps'])) {
                    foreach ($leg['steps'] as $step) {
                        $instruction = isset($step['html_instructions']) ? strip_tags($step['html_instructions']) : 'brak opisu';
                        $distanceText = $step['distance']['text'] ?? '--';
                        $directionsData['steps'][] = sprintf('%s (%s)', $instruction, $distanceText);
                    }
                }
                return $directionsData;

            } elseif (in_array($response['status'], ['NOT_FOUND', 'ZERO_RESULTS'])){
                // Statusy oznaczające brak trasy - nie logujemy jako błąd API, ale zwracamy null
                 ErrorLogger::logError('directions_info', "Nie znaleziono trasy ({$response['status']}) dla: {$originCity} -> {$destinationCity}");
                 return null;
            } else {
                // Inne statusy błędu API
                $errorMessage = isset($response['error_message']) ? $response['error_message'] : 'Brak szczegółów błędu';
                ErrorLogger::logError('directions_api_error', "Błąd API Directions ({$response['status']}): {$errorMessage} dla trasy: {$originCity} -> {$destinationCity}");
                return null;
            }
        } else {
            ErrorLogger::logError('directions_invalid_response', "Brak statusu w odpowiedzi API Directions dla trasy: {$originCity} -> {$destinationCity}");
            return null;
        }
    }

    /**
     * Wykonuje zapytanie cURL i zwraca zdekodowaną odpowiedź JSON.
     *
     * @param string $url URL do zapytania.
     * @param string $apiName Nazwa API dla logowania.
     * @return array|null Tablica z odpowiedzią lub null w przypadku błędu.
     */
    protected function performCurlRequest(string $url, string $apiName): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 15, // Zwiększony timeout do 15s
            CURLOPT_SSL_VERIFYPEER => false, // Dla środowiska deweloperskiego
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorNo = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlErrorNo !== 0) {
            ErrorLogger::logError("{$apiName}_curl_error", "Błąd cURL ({$curlErrorNo}): {$curlError} dla URL: {$url}");
            return null;
        }

        if ($httpCode !== 200) {
            ErrorLogger::logError("{$apiName}_http_error", "Błąd HTTP ({$httpCode}) dla URL: {$url} Odpowiedź: " . substr($responseJson, 0, 500)); // Logujemy początek odpowiedzi
            return null;
        }

        if (empty($responseJson)) {
            ErrorLogger::logError("{$apiName}_empty_response", "Pusta odpowiedź API dla URL: {$url}");
            return null;
        }

        $response = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ErrorLogger::logError("{$apiName}_json_error", "Błąd JSON dla URL: {$url}. Błąd: " . json_last_error_msg() . " Odpowiedź: " . substr($responseJson, 0, 500));
            return null;
        }

        return $response;
    }
}

?> 