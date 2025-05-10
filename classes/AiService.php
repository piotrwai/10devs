<?php
/**
 * Klasa AiService odpowiada za komunikację z serwisem AI, generowanie rekomendacji 
 * i zarządzanie odpowiedziami AI dla aplikacji turystycznej 10devs.
 * 
 * Klasa wykorzystuje zewnętrzne API AI do generowania rekomendacji turystycznych
 * dla wybranych miast. Obsługuje również formatowanie zapytań, przetwarzanie odpowiedzi
 * i zarządzanie błędami podczas komunikacji z API AI.
 * 
 * @package 10devs
 * @author 10devs Team
 * @version 1.0
 */

// Dołączenie potrzebnych plików
require_once __DIR__ . '/../commonDB/aiLogs.php';
require_once __DIR__ . '/ErrorLogger.php';

class AiService {
    /**
     * Klucz API dla serwisu AI.
     * 
     * @var string
     */
    private $apiKey;
    
    /**
     * URL bazowy dla API AI.
     * 
     * @var string
     */
    private $apiEndpoint;
    
    /**
     * Model AI używany do generowania treści.
     * 
     * @var string
     */
    private $model;
    
    /**
     * Temperatura używana przy generowaniu odpowiedzi (większa wartość = większa kreatywność).
     * 
     * @var float
     */
    private $timeout;
    
    /**
     * Maksymalna liczba tokenów (jednostek tekstu) w odpowiedzi.
     * 
     * @var int
     */
    private $maxTokens;
    
    /**
     * Inicjalizuje nową instancję klasy AiService.
     * 
     * Wczytuje konfigurację z pliku config.php i inicjalizuje wszystkie
     * niezbędne parametry potrzebne do komunikacji z API AI.
     * 
     * @throws Exception Jeśli nie można załadować konfiguracji lub brakuje wymaganych parametrów.
     */
    public function __construct() {
        // Wczytanie konfiguracji z pliku
        $config = require __DIR__ . '/../config.php';
        
        // Pobranie danych konfiguracyjnych
        $this->apiKey = $config['openai']['api_key'];
        $this->model = $config['openai']['model'] ?? 'gpt-4o-mini';
        $this->timeout = $config['openai']['timeout'] ?? 60;
        $this->maxTokens = $config['openai']['max_tokens'] ?? 1500;
        $this->apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    }
    
    /**
     * Generuje podsumowanie miasta i rekomendacje
     * 
     * @param string $cityName Nazwa miasta
     * @param int $userId ID użytkownika
     * @param int|null $cityId ID miasta, jeśli jest już znane
     * @return array|null Dane wygenerowane przez AI (city summary i recommendations)
     */
    public function generateCityRecommendations($cityName, $userId, $cityId = null) {
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
            
            // Szczegółowa walidacja odpowiedzi
            if (!isset($response['city_summary']) || !is_string($response['city_summary'])) {
                throw new Exception("Brak lub nieprawidłowy format podsumowania miasta");
            }
            
            if (!isset($response['recommendations']) || !is_array($response['recommendations']) || empty($response['recommendations'])) {
                throw new Exception("Brak lub nieprawidłowy format rekomendacji");
            }
            
            // Sprawdzenie minimalnej liczby rekomendacji
            if (count($response['recommendations']) < 3) {
                throw new Exception("Zbyt mało rekomendacji w odpowiedzi AI (minimum 3)");
            }
            
            // Ograniczenie długości summary
            $citySummary = mb_substr($response['city_summary'], 0, 150);
            
            // Przygotowanie listy rekomendacji
            $recommendations = [];
            $invalidRecommendations = 0;
            
            foreach ($response['recommendations'] as $rec) {
                // Szczegółowa walidacja rekomendacji
                if (!isset($rec['title']) || !is_string($rec['title']) || empty(trim($rec['title']))) {
                    $invalidRecommendations++;
                    continue;
                }
                
                if (!isset($rec['description']) || !is_string($rec['description']) || strlen(trim($rec['description'])) < 100) {
                    $invalidRecommendations++;
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
            
            // Sprawdzenie czy mamy wystarczającą liczbę prawidłowych rekomendacji
            if (count($recommendations) < 3) {
                throw new Exception("Zbyt mało prawidłowych rekomendacji (minimum 3, otrzymano " . count($recommendations) . ")");
            }
            
            // Logowanie statystyk
            if ($invalidRecommendations > 0) {
                ErrorLogger::logError('add_city_info', "Pominięto $invalidRecommendations nieprawidłowych rekomendacji dla miasta $cityName", $userId, null, $cityId);
            }
            
            // Logowanie udanego wywołania
            $this->logAiCall($userId, null, 'success', $cityName);
            
            // Zwracamy dane w formacie zgodnym z API
            return [
                'city' => [
                    'id' => $cityId, // Używamy przekazanego ID miasta, jeśli jest dostępne
                    'name' => $cityName,
                    'summary' => $citySummary
                ],
                'recommendations' => $recommendations
            ];
            
        } catch (Exception $e) {
            // Logowanie błędu
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
                    'title' => 'Rynek Główny sześciokątny',
                    'description' => 'Największy średniowieczny rynek w Europie, otoczony zabytkowymi kamienicami i kościołami. Warto odwiedzić Sukiennice, które znajdują się w centralnej części rynku, gdzie można zakupić pamiątki i rękodzieło.',
                    'model' => $this->model
                ],
                [
                    'title' => 'Zamek Królewski',
                    'description' => 'Imponująca budowla z XIV wieku położona na wzgórzu, oferująca piękny widok na miasto i rzekę. Wnętrza zamku kryją cenne kolekcje sztuki, meble z epoki oraz interesujące wystawy historyczne.',
                    'model' => $this->model
                ],
                [
                    'title' => 'Stare Miasto',
                    'description' => 'Zabytkowa dzielnica pełna urokliwych uliczek, kamienic i kościołów. Idealne miejsce na spacer, z licznymi kawiarniami, restauracjami i sklepami z pamiątkami.',
                    'model' => $this->model
                ],
                [
                    'title' => 'Muzeum Narodowe',
                    'description' => 'Jedno z najważniejszych muzeów w kraju, prezentujące bogaty zbiór dzieł sztuki od średniowiecza po współczesność, w tym obrazy, rzeźby, rzemiosło artystyczne i artefakty historyczne.',
                    'model' => $this->model
                ],
                [
                    'title' => 'Park Miejski',
                    'description' => 'Rozległy park w centrum miasta, idealny na relaks i aktywny wypoczynek. Oferuje piękne alejki, jeziorka, ogrody kwiatowe i place zabaw dla dzieci.',
                    'model' => $this->model
                ]
            ]
        ];
        
        /* 
         * PRZYKŁAD BŁĘDNEJ ODPOWIEDZI (DO TESTOWANIA OBSŁUGI BŁĘDÓW)
         * Ten kod NIE jest używany w bieżącej implementacji, ale może być pomocny do testowania obsługi błędów.
         * Aby przetestować scenariusz błędu, można zamienić powyższą odpowiedź na tę poniżej.
         *
         * // Przykład 1: Brak wymaganego pola city_summary
         * 
          return [
             'recommendations' => [
                [
                   'title' => 'Miejsce X',
                   'description' => 'Opis miejsca X',
                   'model' => $this->model
                ]
             ]
          ];
         */
        /*
          // Przykład 2: Brak wymaganego pola recommendations
          return [
             'city_summary' => 'Opis miasta'
          ];
         
          // Przykład 3: Nieprawidłowa struktura rekomendacji (brak wymaganego pola description)
          return [
             'city_summary' => 'Opis miasta',
             'recommendations' => [
                [
                   'title' => 'Miejsce bez opisu',
                   'model' => $this->model
                ]
             ]
          ];
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
Upewnij się, że odpowiedź jest w poprawnym formacie JSON i zawiera od 3 do 10 rekomendacji.
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
            if ($recommendationId) {
                // Jeśli mamy ID rekomendacji, zapisz do ai_logs
                return setAiLog($userId, $recommendationId, $status);
            } else {
                // W przeciwnym razie zapisz do ai_inputs
                return setAiInput($userId, $content, 'city_search');
            }
        } catch (Exception $e) {
            // Logowanie błędu
            ErrorLogger::logError('ai_log_error', $e->getMessage(), $userId);
            return false;
        }
    }

    /**
     * Generuje dodatkowe rekomendacje dla miasta, unikając istniejących
     *
     * @param string $cityName Nazwa miasta
     * @param int $userId ID użytkownika
     * @param array $existingTitles Lista tytułów istniejących rekomendacji
     * @return array|null Nowe rekomendacje wygenerowane przez AI
     */
    public function generateSupplementaryRecommendations($cityName, $userId, $existingTitles)
    {
        try {
            // Ustawienie limitu czasu wykonania skryptu
            set_time_limit($this->timeout + 10);

            // Nazwa miasta jest teraz przekazywana bezpośrednio
            if (empty(trim($cityName))) {
                 throw new Exception("Nazwa miasta nie może być pusta.");
            }

            // Przygotowanie danych do wysłania
            $payload = [
                'city' => $cityName,
                'user_id' => $userId,
                'existing_titles' => $existingTitles,
                'max_recommendations' => 5 // Możemy chcieć mniej rekomendacji uzupełniających
            ];

            // Wywołanie serwisu AI (użyjemy dedykowanej metody do promptu uzupełniającego)
            $response = $this->callSupplementaryAiService($payload);

            if (!$response) {
                throw new Exception("Nie udało się uzyskać odpowiedzi od serwisu AI dla rekomendacji uzupełniających");
            }

            // Walidacja odpowiedzi (podobna jak w generateCityRecommendations, ale bez city_summary)
            if (!isset($response['recommendations']) || !is_array($response['recommendations'])) {
                 // Dopuszczamy pustą tablicę, jeśli AI nie znajdzie nic nowego
                if (empty($response['recommendations'])) {
                   return []; // Zwracamy pustą tablicę, jeśli nie ma nowych rekomendacji
                }
                throw new Exception("Brak lub nieprawidłowy format rekomendacji uzupełniających");
            }

            // Przygotowanie listy nowych rekomendacji
            $newRecommendations = [];
            $invalidRecommendations = 0;

            foreach ($response['recommendations'] as $rec) {
                // Walidacja pojedynczej rekomendacji
                if (!isset($rec['title']) || !is_string($rec['title']) || empty(trim($rec['title']))) {
                    $invalidRecommendations++;
                    continue;
                }

                if (!isset($rec['description']) || !is_string($rec['description']) || strlen(trim($rec['description'])) < 100) {
                    $invalidRecommendations++;
                    continue;
                }

                 // Sprawdzenie czy tytuł już istnieje (dodatkowa ochrona)
                 if (in_array(trim($rec['title']), $existingTitles)) {
                    $invalidRecommendations++;
                    continue;
                 }

                // Ograniczenie długości pól
                $title = mb_substr(trim($rec['title']), 0, 200);
                $description = mb_substr($rec['description'], 0, 64000);

                $newRecommendations[] = [
                    'id' => null, // Nowe rekomendacje nie mają jeszcze ID
                    'title' => $title,
                    'description' => $description,
                    'model' => $this->model // Model użyty do generowania
                ];

                // Ograniczenie do maksymalnej liczby nowych rekomendacji
                if (count($newRecommendations) >= $payload['max_recommendations']) {
                    break;
                }
            }
            
            // Logowanie statystyk
            if ($invalidRecommendations > 0) {
                ErrorLogger::logError('supplement_info', "Pominięto $invalidRecommendations nieprawidłowych lub zduplikowanych rekomendacji uzupełniających dla miasta $cityName", $userId);
            }

            // Logowanie udanego wywołania (można dodać specyficzny status/content)
            $this->logAiCall($userId, null, 'supplement_success', $cityName);

            return $newRecommendations;

        } catch (Exception $e) {
            ErrorLogger::logError('supplement_ai_error', "Błąd przy generowaniu uzupełnienia dla '$cityName': " . $e->getMessage(), $userId);
            $this->logAiCall($userId, null, 'supplement_error', $cityName . ' - ' . $e->getMessage());
            return null; // Zwracamy null w przypadku błędu
        }
    }

    /**
     * Wywołuje serwis AI OpenAI dla rekomendacji uzupełniających
     *
     * @param array $payload Dane do wysłania
     * @return array|null Odpowiedź z serwisu AI
     * @throws Exception W przypadku błędu komunikacji z API
     */
    private function callSupplementaryAiService($payload) {
        // Przygotowanie promptu dla modelu OpenAI, uwzględniając istniejące tytuły
        $prompt = $this->prepareSupplementaryPrompt($payload['city'], $payload['existing_titles']);

        /*
         * ZAKOMENTOWANY KOD PRODUKCYJNY - WYWOŁANIE API OpenAI DLA UZUPEŁNIENIA
         */
        /*
        $requestData = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Jesteś pomocnym asystentem specjalizującym się w turystyce i rekomendacjach miejsc wartych odwiedzenia. Odpowiadasz w strukturyzowanym formacie JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.75, // Można lekko zwiększyć temperaturę dla większej różnorodności
            'max_tokens' => $this->maxTokens,
            'response_format' => ['type' => 'json_object']
        ];

        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception('Błąd cURL przy uzupełnianiu: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Błąd API OpenAI przy uzupełnianiu: kod HTTP ' . $httpCode . ', odpowiedź: ' . $response);
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Nieprawidłowy format odpowiedzi z API OpenAI przy uzupełnianiu');
        }

        $content = $responseData['choices'][0]['message']['content'];
        $result = json_decode($content, true);

        if ($result === null) {
            throw new Exception('Nie można zdekodować zawartości JSON z odpowiedzi OpenAI przy uzupełnianiu');
        }

        return $result;
        */

        /*
         * TYMCZASOWA ODPOWIEDŹ DO TESTÓW UZUPEŁNIENIA - PRZYKŁAD POPRAWNEJ ODPOWIEDZI
         * Ten kod zwraca przykładową odpowiedź dla celów testowych uzupełniania.
         */
        return [
            'recommendations' => [
                [
                    'title' => 'Nowa Atrakcja 1',
                    'description' => 'To jest zupełnie nowa atrakcja, której nie było wcześniej. Opis musi być wystarczająco długi, aby przejść walidację (minimum 100 znaków). Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'model' => $this->model
                ],
                [
                    'title' => 'Inny punkt widokowy',
                    'description' => 'Kolejna unikalna propozycja wygenerowana w ramach uzupełnienia. Zapewnia inne spojrzenie na miasto niż poprzednie rekomendacje. Pamiętajmy o minimalnej długości opisu.',
                    'model' => $this->model
                ]
            ]
        ];
    }

    /**
     * Przygotowuje prompt dla modelu AI uzupełniającego
     *
     * @param string $cityName Nazwa miasta
     * @param array $existingTitles Lista tytułów istniejących rekomendacji
     * @return string Przygotowany prompt
     */
    private function prepareSupplementaryPrompt($cityName, $existingTitles) {
        // Przygotowanie promptu dla modelu OpenAI, uwzględniając istniejące tytuły
        // Upewnijmy się, że tytuły są bezpieczne do wstawienia w prompt
        $escapedTitles = array_map(function($title) {
            return str_replace(["\n", "\r", '"', '`'], ' ', $title); // Podstawowe czyszczenie
        }, $existingTitles);
        $existingTitlesStr = '"' . implode('", "', $escapedTitles) . '"';

        return <<<PROMPT
Znajdź dodatkowe, interesujące miejsca lub atrakcje turystyczne w mieście $cityName.

Potrzebuję listy nowych rekomendacji, które są **zupełnie inne** niż te, które już mam. Oto lista tytułów istniejących rekomendacji, których **nie należy powtarzać ani proponować niczego bardzo podobnego**: [$existingTitlesStr].

Wygeneruj od 1 do 5 nowych rekomendacji.

Odpowiedź sformatuj jako obiekt JSON w następującym formacie:
```json
{
  "recommendations": [
    {
      "title": "Unikalna nazwa nowej atrakcji",
      "description": "Szczegółowy opis nowej atrakcji (co najmniej 100 znaków, maksymalnie 64000 znaków)"
    }
    // ...ewentualnie więcej nowych rekomendacji...
  ]
}
```

Każda nowa rekomendacja musi mieć:
- `title`: Unikalny tytuł (maksymalnie 200 znaków), różniący się od podanych powyżej.
- `description`: Szczegółowy opis (minimum 100 znaków, maksimum 64000 znaków).

Upewnij się, że odpowiedź jest poprawnym JSONem i zawiera tylko klucz `recommendations` z listą nowych propozycji.
PROMPT;
    }
} 