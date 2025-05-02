/**
 * Skrypt obsługujący formularz rejestracji
 */

$(document).ready(function() {
    const $form = $('#register-form');
    const $loginInput = $('#login');
    const $passwordInput = $('#password');
    const $confirmPasswordInput = $('#confirm-password');
    const $cityBaseInput = $('#cityBase');
    const $submitButton = $('#register-btn');
    const $formMessages = $('#form-messages');
    const $spinner = $('.btn-spinner');

    // Obsługa wysyłania formularza
    $form.on('submit', function(e) {
        e.preventDefault();
        
        // Reset komunikatów
        $formMessages.empty().removeClass('alert alert-danger alert-success');
        $('.is-invalid').removeClass('is-invalid');
        
        // Walidacja pól
        let isValid = true;
        const login = $loginInput.val().trim();
        const password = $passwordInput.val();
        const confirmPassword = $confirmPasswordInput.val();
        const cityBase = $cityBaseInput.val().trim();
        
        // Walidacja loginu
        if (!login || login.length < 2 || login.length > 50) {
            $loginInput.addClass('is-invalid');
            $('#login-error').text('Login musi mieć od 2 do 50 znaków');
            isValid = false;
        }
        
        // Walidacja hasła
        if (!password || password.length < 5) {
            $passwordInput.addClass('is-invalid');
            $('#password-error').text('Hasło musi mieć minimum 5 znaków');
            isValid = false;
        }
        
        // Walidacja potwierdzenia hasła
        if (password !== confirmPassword) {
            $confirmPasswordInput.addClass('is-invalid');
            $('#confirm-password-error').text('Hasła nie są identyczne');
            isValid = false;
        }
        
        // Walidacja miasta bazowego
        if (!cityBase || cityBase.length < 3 || cityBase.length > 150) {
            $cityBaseInput.addClass('is-invalid');
            $('#cityBase-error').text('Miasto bazowe musi mieć od 3 do 150 znaków');
            isValid = false;
        }
        
        // Dodatkowa walidacja loginu
        if (!/^[a-zA-Z0-9_-]+$/.test(login)) {
            $loginInput.addClass('is-invalid');
            $('#login-error').text('Login może zawierać tylko litery, cyfry, podkreślenia i myślniki');
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
            url: '/api/users/register',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                login: login,
                password: password,
                cityBase: cityBase
            }),
            success: function(response) {
                // Wyświetlenie komunikatu sukcesu
                $formMessages
                    .addClass('alert alert-success')
                    .text('Rejestracja pomyślna. Za chwilę zostaniesz przekierowany do strony logowania...');
                
                // Wyczyszczenie formularza
                $form[0].reset();
                
                // Przekierowanie do strony logowania
                setTimeout(function() {
                    window.location.href = '/login?registered=1';
                }, 2000);
            },
            error: function(xhr) {console.log();
                let errorMessage = 'Wystąpił błąd podczas rejestracji.';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = xhr.responseJSON.errors.join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $formMessages
                    .addClass('alert alert-danger')
                    .html(errorMessage);
            },
            complete: function() {
                // Ukrycie spinnera
                $submitButton.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });
    
    // Czyszczenie komunikatów błędów przy wpisywaniu
    $('input').on('input', function() {
        $(this).removeClass('is-invalid');
        $formMessages.empty().removeClass('alert alert-danger alert-success');
    });
}); 