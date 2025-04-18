<?php
// Klasa obsługująca formatowanie i wysyłanie odpowiedzi API

class Response {
    /**
     * Wysyła odpowiedź sukcesu (HTTP 200) z danymi w formacie JSON
     * 
     * @param mixed $data Dane do wysłania w odpowiedzi
     * @param int $statusCode Kod statusu HTTP (domyślnie 200)
     * @return void
     */
    public static function sendSuccess($data, $statusCode = 200) {
        self::sendResponse($statusCode, [
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Wysyła odpowiedź błędu z kodem HTTP i komunikatem
     * 
     * @param int $statusCode Kod statusu HTTP błędu
     * @param string $message Komunikat błędu
     * @param array $details Dodatkowe szczegóły błędu (opcjonalne)
     * @return void
     */
    public static function sendError($statusCode, $message, $details = null) {
        $response = [
            'success' => false,
            'error' => [
                'code' => $statusCode,
                'message' => $message
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        self::sendResponse($statusCode, $response);
    }
    
    /**
     * Wysyła odpowiedź HTTP w formacie JSON
     * 
     * @param int $statusCode Kod statusu HTTP
     * @param mixed $data Dane do wysłania w formacie JSON
     * @return void
     */
    private static function sendResponse($statusCode, $data) {
        // Ustawienie nagłówków HTTP
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        
        // Konwersja danych do formatu JSON
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Wysłanie odpowiedzi
        echo $jsonData;
        exit;
    }
} 