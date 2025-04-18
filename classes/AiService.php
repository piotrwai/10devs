<?php
// Klasa obsługująca interakcje z serwisami AI

class AiService {
    private $apiKey;
    private $apiEndpoint;
    private $model;
    private $timeout;
    private $maxTokens;
    
    /**
     * Konstruktor inicjujący serwis AI
     */
    public function __construct() {
        // Wczytanie konfiguracji z pliku
        $config = require_once __DIR__ . '/../config.php';
        
        // Pobranie danych konfiguracyjnych
        $this->apiKey = $config['openai']['api_key'];
        $this->model = $config['openai']['model'] ?? 'gpt-4.1-mini';
        $this->timeout = $config['openai']['timeout'] ?? 60;
        $this->maxTokens = $config['openai']['max_tokens'] ?? 1500;
        $this->apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    }
    
    /**
     * Generuje podsumowanie miasta i rekomendacje
     * 
     * @param string $cityName Nazwa miasta
     * @param int $userId ID użytkownika
     * @return array|null Dane wygenerowane przez AI (city summary i recommendations)
     */
    public function generateCityRecommendations($cityName, $userId) {
        try {
            // Ustawienie limitu czasu wykonania skryptu
            set_time_limit($this->timeout + 10);
            
            // Przygotowanie danych do wysłania
            $payload = [
                'city' => $cityName,
                'user_id' => $userId,
                'max_recommendations' => 10
            ];
            
            // Wywołanie serwisu AI
            $response = $this->callAiService($payload);
            
            if (!$response) {
                throw new Exception("Nie udało się uzyskać odpowiedzi od serwisu AI");
            }
            
            // Walidacja odpowiedzi
            if (!isset($response['city_summary']) || !isset($response['recommendations'])) {
                throw new Exception("Niepoprawny format odpowiedzi z serwisu AI");
            }
            
            // Ograniczenie długości summarys
            $citySummary = mb_substr($response['city_summary'], 0, 150);
            
            // Przygotowanie listy rekomendacji
            $recommendations = [];
            foreach ($response['recommendations'] as $rec) {
                // Sprawdzenie czy rekomendacja zawiera wymagane pola
                if (!isset($rec['title']) || !isset($rec['description'])) {
                    continue;
                }
                
                // Ograniczenie długości pól
                $title = mb_substr($rec['title'], 0, 200);
                $description = mb_substr($rec['description'], 0, 64000);
                
                $recommendations[] = [
                    'id' => null, // Rekomendacje nie są jeszcze zapisane
                    'title' => $title,
                    'description' => $description,
                    'model' => $this->model
                ];
                
                // Ograniczenie do maksymalnie 10 rekomendacji
                if (count($recommendations) >= 10) {
                    break;
                }
            }
            
            // Logowanie udanego wywołania
            $this->logAiCall($userId, null, 'success', $cityName);
            
            return [
                'summary' => $citySummary,
                'recommendations' => $recommendations
            ];
            
        } catch (Exception $e) {
            // Logowanie błędu
            require_once __DIR__ . '/ErrorLogger.php';
            ErrorLogger::logError('ai_error', $e->getMessage(), $userId, null, $cityName);
            $this->logAiCall($userId, null, 'error', $cityName);
            return null;
        }
    }
    
    /**
     * Wywołuje serwis AI OpenAI z modelem gpt-4.1-mini
     * 
     * @param array $payload Dane do wysłania
     * @return array|null Odpowiedź z serwisu AI
     * @throws Exception W przypadku błędu komunikacji z API
     */
    private function callAiService($payload) {
        // Przygotowanie promptu dla modelu OpenAI
        $prompt = $this->preparePrompt($payload['city']);
        
        /* 
         * ZAKOMENTOWANY KOD PRODUKCYJNY - TO JEST WŁAŚCIWE WYWOŁANIE API OpenAI
         * Ten kod będzie używany w produkcji do rzeczywistego wywołania API OpenAI.
         */
        /*
        // Przygotowanie danych do wysłania do API OpenAI
        $requestData = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Jesteś pomocnym asystentem specjalizującym się w turystyce i rekomendacjach miejsc wartych odwiedzenia. Odpowiadasz w strukturyzowanym formacie JSON, który będzie później przetwarzany przez system.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => $this->maxTokens,
            'response_format' => ['type' => 'json_object']
        ];
        
        // Inicjalizacja cURL
        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Wyłączenie weryfikacji certyfikatu SSL, zgodnie z wymaganiem
        
        // Wykonanie żądania
        $response = curl_exec($ch);
        
        // Sprawdzenie błędów cURL
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception('Błąd cURL: ' . curl_error($ch));
        }
        
        // Pobranie kodu odpowiedzi HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Sprawdzenie czy kod HTTP jest prawidłowy
        if ($httpCode !== 200) {
            throw new Exception('Błąd API OpenAI: kod HTTP ' . $httpCode . ', odpowiedź: ' . $response);
        }
        
        // Dekodowanie odpowiedzi JSON
        $responseData = json_decode($response, true);
        
        // Sprawdzenie czy odpowiedź jest poprawna
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Nieprawidłowy format odpowiedzi z API OpenAI');
        }
        
        // Ekstrakcja i dekodowanie zawartości JSON z odpowiedzi
        $content = $responseData['choices'][0]['message']['content'];
        $result = json_decode($content, true);
        
        // Sprawdzenie czy dekodowanie się powiodło
        if ($result === null) {
            throw new Exception('Nie można zdekodować zawartości JSON z odpowiedzi OpenAI');
        }
        
        return $result;
        */
        
        /* 
         * TYMCZASOWA ODPOWIEDŹ DO TESTÓW - PRZYKŁAD POPRAWNEJ ODPOWIEDZI
         * Ten kod zwraca przykładową odpowiedź dla celów testowych.
         * W produkcji zostanie zastąpiony rzeczywistym wywołaniem API powyżej.
         */
        return [
            'city_summary' => 'Miasto ' . $payload['city'] . ' to urokliwe miejsce położone w centralnej części kraju, znane z bogatej historii, pięknej architektury i licznych atrakcji turystycznych.',
            'recommendations' => [
                [
                    'title' => 'Rynek Główny',
                    'description' => 'Największy średniowieczny rynek w Europie, otoczony zabytkowymi kamienicami i kościołami. Warto odwiedzić Sukiennice, które znajdują się w centralnej części rynku, gdzie można zakupić pamiątki i rękodzieło.'
                ],
                [
                    'title' => 'Zamek Królewski',
                    'description' => 'Imponująca budowla z XIV wieku położona na wzgórzu, oferująca piękny widok na miasto i rzekę. Wnętrza zamku kryją cenne kolekcje sztuki, meble z epoki oraz interesujące wystawy historyczne.'
                ],
                [
                    'title' => 'Stare Miasto',
                    'description' => 'Zabytkowa dzielnica pełna urokliwych uliczek, kamienic i kościołów. Idealne miejsce na spacer, z licznymi kawiarniami, restauracjami i sklepami z pamiątkami.'
                ],
                [
                    'title' => 'Muzeum Narodowe',
                    'description' => 'Jedno z najważniejszych muzeów w kraju, prezentujące bogaty zbiór dzieł sztuki od średniowiecza po współczesność, w tym obrazy, rzeźby, rzemiosło artystyczne i artefakty historyczne.'
                ],
                [
                    'title' => 'Park Miejski',
                    'description' => 'Rozległy park w centrum miasta, idealny na relaks i aktywny wypoczynek. Oferuje piękne alejki, jeziorka, ogrody kwiatowe i place zabaw dla dzieci.'
                ]
            ]
        ];
        
        /* 
         * PRZYKŁAD BŁĘDNEJ ODPOWIEDZI (DO TESTOWANIA OBSŁUGI BŁĘDÓW)
         * Ten kod NIE jest używany w bieżącej implementacji, ale może być pomocny do testowania obsługi błędów.
         * Aby przetestować scenariusz błędu, można zamienić powyższą odpowiedź na tę poniżej.
         *
         * // Przykład 1: Brak wymaganego pola city_summary
         * return [
         *    'recommendations' => [
         *       [
         *          'title' => 'Miejsce X',
         *          'description' => 'Opis miejsca X'
         *       ]
         *    ]
         * ];
         *
         * // Przykład 2: Brak wymaganego pola recommendations
         * return [
         *    'city_summary' => 'Opis miasta'
         * ];
         *
         * // Przykład 3: Nieprawidłowa struktura rekomendacji (brak wymaganego pola description)
         * return [
         *    'city_summary' => 'Opis miasta',
         *    'recommendations' => [
         *       [
         *          'title' => 'Miejsce bez opisu'
         *       ]
         *    ]
         * ];
         */
    }
    
    /**
     * Przygotowuje prompt dla modelu AI
     * 
     * @param string $cityName Nazwa miasta
     * @return string Przygotowany prompt
     */
    private function preparePrompt($cityName) {
        // Przygotowanie promptu dla modelu OpenAI
        return <<<PROMPT
Stwórz informacje turystyczne o mieście $cityName, które zawierają:
1. Krótkie podsumowanie (maksymalnie 150 znaków) opisujące najważniejsze cechy miasta
2. Listę maksymalnie 10 rekomendacji miejsc lub atrakcji wartych odwiedzenia

Odpowiedź sformatuj jako obiekt JSON w następującym formacie:
```json
{
  "city_summary": "Krótkie podsumowanie miasta (maksymalnie 150 znaków)",
  "recommendations": [
    {
      "title": "Nazwa atrakcji lub miejsca",
      "description": "Szczegółowy opis atrakcji lub miejsca (co najmniej 100 znaków)"
    },
    // więcej rekomendacji...
  ]
}
```

Każda rekomendacja powinna zawierać unikalny tytuł (maksymalnie 200 znaków) oraz szczegółowy opis (maksymalnie 64000 znaków).
Upewnij się, że odpowiedź jest w poprawnym formacie JSON i zawiera co najmniej 3 rekomendacje.
PROMPT;
    }
    
    /**
     * Zapisuje informacje o wywołaniu AI do bazy danych
     * 
     * @param int $userId ID użytkownika
     * @param int|null $recommendationId ID rekomendacji (jeśli już istnieje)
     * @param string $status Status wywołania ('success', 'error', itp.)
     * @param string $content Dodatkowe informacje (np. nazwa miasta)
     * @return bool Czy operacja się powiodła
     */
    private function logAiCall($userId, $recommendationId = null, $status, $content = null) {
        try {
            // Dodanie wpisu do AI inputs
            require_once __DIR__ . '/../commonDB/aiLogs.php';
            
            if ($recommendationId) {
                // Jeśli mamy ID rekomendacji, zapisz do ai_logs
                return setAiLog($userId, $recommendationId, $status);
            } else {
                // W przeciwnym razie zapisz do ai_inputs
                return setAiInput($userId, $content, 'city_search');
            }
        } catch (Exception $e) {
            // Logowanie błędu
            require_once __DIR__ . '/ErrorLogger.php';
            ErrorLogger::logError('ai_log_error', $e->getMessage(), $userId);
            return false;
        }
    }
} 