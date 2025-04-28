<?php
/**
 * Klasa middleware do obsługi autoryzacji
 */

// Dołączenie klasy Auth
require_once __DIR__ . '/Auth.php';

class AuthMiddleware {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    /**
     * Weryfikuje czy żądanie jest autoryzowane
     * 
     * @return int|null ID użytkownika lub null jeśli brak autoryzacji
     */
    public function authenticate() {
        return $this->auth->authenticateAndGetUserId();
    }
} 