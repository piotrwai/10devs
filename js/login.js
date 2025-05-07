/**
 * Skrypt obsługujący formularz logowania
 */

$(document).ready(function() {
    const $form = $('#login-form');
    const $loginInput = $('#login');
    const $passwordInput = $('#password');
    const $submitButton = $('#login-btn');
    const $formMessages = $('#form-messages');
    const $spinner = $('.btn-spinner');
    
    // Ukryj komunikaty formularza jeśli są już komunikaty z serwera
    if ($('.alert-danger, .alert-success').length > 0) {
        $formMessages.hide();
    }

    // Obsługa wysyłania formularza
    $form.on('submit', function(e) {
        e.preventDefault();
        
        // Ukryj komunikaty z serwera, jeśli istnieją
        $('.alert-danger, .alert-success').hide();
        
        // Reset komunikatów
        $formMessages.empty().removeClass('alert alert-danger alert-success').show();
        $('.is-invalid').removeClass('is-invalid');
        
        // Walidacja pól
        let isValid = true;
        
        if (!$loginInput.val().trim()) {
            $loginInput.addClass('is-invalid');
            $('#login-error').text('Proszę podać login.');
            isValid = false;
        }
        
        if (!$passwordInput.val()) {
            $passwordInput.addClass('is-invalid');
            $('#password-error').text('Proszę podać hasło.');
            isValid = false;
        }
        
        if (!isValid) {
            return;
        }
        
        // Pokazanie spinnera
        $submitButton.prop('disabled', true);
        $spinner.removeClass('d-none');
        
        // Wysłanie żądania AJAX
        $.ajax({
            url: '/api/users/login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                login: $loginInput.val().trim(),
                password: $passwordInput.val()
            }),
            success: function(response) {
                // Wyświetlenie komunikatu sukcesu
                $formMessages
                    .addClass('alert alert-success')
                    .text('Logowanie pomyślne. Przekierowywanie...');

                // Przekierowanie w zależności od tego, czy użytkownik ma miasta
                setTimeout(function() {
                    if (response.data.user.hasCities) {
                        window.location.href = '/dashboard';
                    } else {
                        window.location.href = '/cities/search';
                    }
                }, 100);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Wystąpił błąd podczas logowania';
                
                // Szczegółowa obsługa błędów na podstawie kodów statusu HTTP
                if (xhr.status === 401) {
                    errorMessage = 'Nieprawidłowy login lub hasło.';
                } else if (xhr.status === 429) {
                    errorMessage = 'Zbyt wiele prób logowania. Spróbuj ponownie później.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
                }
                
                // Jeśli serwer zwrócił bardziej szczegółową wiadomość, użyj jej
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                
                // Wyświetlenie komunikatu błędu w formularzu
                $formMessages
                    .empty()
                    .removeClass('alert-success')
                    .addClass('alert alert-danger')
                    .text(errorMessage)
                    .show();
            },
            complete: function() {
                // Ukrycie spinnera
                $submitButton.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });
    
    // Czyszczenie tylko podświetlenia błędów przy wpisywaniu
    $('input').on('input', function() {
        $(this).removeClass('is-invalid');
    });
}); 