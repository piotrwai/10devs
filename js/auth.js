/**
 * Skrypt konfigurujący globalne ustawienia AJAX dla autoryzacji
 */

$(document).ready(function() {
    // Dodanie tokena do wszystkich żądań AJAX
    $.ajaxSetup({
        beforeSend: function(xhr) {
            // Pobierz token z ciasteczka
            const token = document.cookie.split('; ').find(row => row.startsWith('jwtToken='))?.split('=')[1];
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }
        }
    });
    
    // Obsługa błędów autoryzacji
    $(document).ajaxError(function(event, xhr) {
        if (xhr.status === 401) {
            // Usunięcie lokalnych danych i przekierowanie do strony logowania
            localStorage.removeItem('user_data');
            window.location.href = '/login?error=auth';
        }
    });

    // Obsługa wylogowania
    $('#logout-btn').click(function(e) {
        e.preventDefault();
        console.log('Rozpoczęcie procesu wylogowania...');
        
        // Wywołanie endpointu wylogowania
        $.ajax({
            url: '/api/users/logout',
            method: 'POST',
            dataType: 'json',
            complete: function(xhr, status) {
                console.log('Odpowiedź serwera:', {
                    status: status,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON,
                    headers: xhr.getAllResponseHeaders()
                });
            },
            success: function(response) {
                console.log('Sukces wylogowania:', response);
                if (response && response.status === 'success') {
                    // Po pomyślnym wylogowaniu na serwerze, wyczyść lokalne dane
                    localStorage.removeItem('user_data');
                    sessionStorage.removeItem('jwtToken');
                    
                    // Przekierowanie na stronę logowania
                    window.location.href = '/login?message=logged_out';
                } else {
                    console.error('Nieprawidłowa odpowiedź:', response);
                    window.location.href = '/login?error=logout_failed';
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd wylogowania:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                // W przypadku błędu, spróbuj wylogować lokalnie
                localStorage.removeItem('user_data');
                sessionStorage.removeItem('jwtToken');
                window.location.href = '/login?error=logout_failed';
            }
        });
    });
}); 