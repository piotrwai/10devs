<?php
/**
 * Plik obsługujący ścieżkę /logout - wylogowanie użytkownika
 */

// Dołączenie pliku konfiguracyjnego, jeśli to konieczne
require_once 'config.php';

// Usunięcie tokena JWT z localStorage po stronie klienta poprzez przekierowanie do strony z krótkim skryptem JS
?><!DOCTYPE html>
<html>
<head>
    <title>Wylogowywanie...</title>
    <script>
        // Usunięcie tokena JWT z localStorage
        localStorage.removeItem('jwtToken');
        sessionStorage.removeItem('jwtToken');
        
        // Usunięcie ciasteczka, jeśli było używane
        document.cookie = "jwtToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        // Przekierowanie do strony logowania z parametrem informującym o wylogowaniu
        window.location.href = '/login?logout=1';
    </script>
</head>
<body>
    <p>Trwa wylogowywanie...</p>
</body>
</html> 