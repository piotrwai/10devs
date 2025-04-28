/**
 * Główny plik JavaScript dla aplikacji 10x-city
 */

$(document).ready(function() {
    
    // Ustawienie domyślnego nagłówka dla AJAX - CSRF token (jeśli istnieje)
    if (typeof csrfToken !== 'undefined') {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }
    
    // Wspólna obsługa błędów AJAX
    $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
        // Ignoruj obsługę błędów dla formularza logowania
        if (settings.url === '/api/users/login') {
            return;
        }
        
        // Ogólna obsługa błędów HTTP
        if (jqXHR.status === 401) {
            // Nieuprawniony dostęp - przekierowanie do strony logowania
            console.error('Nieuprawniony dostęp - przekierowanie do strony logowania:', settings.url);
            window.location.href = '/login?error=auth';
        } else if (jqXHR.status === 403) {
            // Zabroniony dostęp
            console.error('Zabroniony dostęp do zasobu:', settings.url);
        } else if (jqXHR.status === 404) {
            // Nie znaleziono zasobu
            console.error('Nie znaleziono zasobu:', settings.url);
        } else if (jqXHR.status === 500) {
            // Błąd serwera
            console.error('Błąd serwera podczas przetwarzania żądania:', settings.url);
        }
    });
    
    // Animacje Bootstrap dla powiadomień
    $('.alert').each(function() {
        const alert = $(this);
        
        // Dodaj przycisk zamknięcia, jeśli nie istnieje
        if (!alert.find('.btn-close').length) {
            alert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
        }
        
        // Ustawienie automatycznego zamknięcia po czasie (dla alertów sukcesu)
        if (alert.hasClass('alert-success')) {
            setTimeout(function() {
                alert.fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });
    
    // Konfiguracja potwierdzenia dla akcji "niebezpiecznych"
    $('[data-confirm]').on('click', function(e) {
        const confirmMessage = $(this).data('confirm');
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
}); 