<?php
/**
 * Klasa pomocnicza do generowania odpowiedzi API
 */
class Response {
    /**
     * Generuje odpowiedź błędu
     * 
     * @param int $code Kod HTTP odpowiedzi
     * @param string $message Komunikat błędu
     * @param array $data Dodatkowe dane do zwrócenia
     */
    public static function error($code, $message, $data = []) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'errors' => isset($data['errors']) ? $data['errors'] : null,
            'data' => $data
        ]);
        exit();
    }

    /**
     * Generuje odpowiedź sukcesu
     * 
     * @param int $code Kod HTTP odpowiedzi
     * @param string $message Komunikat sukcesu
     * @param array $data Dane do zwrócenia
     */
    public static function success($code, $message, $data = []) {
        http_response_code($code);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
} 