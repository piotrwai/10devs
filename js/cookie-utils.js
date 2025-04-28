/**
 * Funkcje pomocnicze do zarządzania ciasteczkami JWT
 */

const CookieUtils = {
    // Usuwa ciasteczko JWT
    removeJwtCookie: function() {
        document.cookie = 'jwtToken=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    },

    // Pobiera wartość ciasteczka po nazwie
    getCookie: function(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
}; 