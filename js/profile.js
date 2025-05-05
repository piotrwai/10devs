/**
 * Skrypt obsługujący formularz edycji profilu użytkownika
 */

$(document).ready(function() {
    // Zmienne
    const profileForm = $('#profile-form');
    const formMessages = $('#form-messages');
    const loginInput = $('#login');
    const cityBaseInput = $('#cityBase');
    const passwordInput = $('#password');
    const confirmPasswordInput = $('#confirmPassword');
    const adminStatusElem = $('#admin-status');
    const saveButton = $('#save-profile-btn');
    
    // Stan - czy trwa zapisywanie
    let isSaving = false;
    
    // Funkcja wyświetlająca komunikat sukcesu
    function showSuccessMessage(message) {
        formMessages.removeClass('alert alert-danger').addClass('alert alert-success').html(message);
    }
    
    // Funkcja wyświetlająca ogólny komunikat błędu
    function showErrorMessage(message) {
        formMessages.removeClass('alert alert-success').addClass('alert alert-danger').html(message);
    }
    
    // Funkcja wyświetlająca błąd przy konkretnym polu
    function showFieldError(field, message) {
        const fieldInput = $('#' + field);
        const errorElement = $('#' + field + '-error');
        
        fieldInput.addClass('is-invalid');
        errorElement.text(message);
    }
    
    // Funkcja czyszcząca błędy
    function clearErrors() {
        // Wyczyść ogólny komunikat błędu
        formMessages.removeClass('alert alert-danger alert-success').html('');
        
        // Wyczyść błędy pól
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
    
    // Funkcja ładująca dane użytkownika
    function loadUserProfile() {
        // Pobierz aktualne wartości
        const currentLogin = loginInput.val();
        const currentCityBase = cityBaseInput.val();
        
        $.ajax({
            url: '/api/users/me',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + sessionStorage.getItem('jwtToken')
            },
            success: function(response) {
                // Sprawdź czy odpowiedź ma prawidłową strukturę
                if (!response || response.status !== 'success' || !response.data) {
                    console.error('Nieprawidłowa odpowiedź z serwera:', response);
                    showErrorMessage('Nieprawidłowy format danych z serwera');
                    return;
                }
                
                const userData = response.data;
                // console.log('Pobrane dane użytkownika:', userData);
                
                // Aktualizuj pola tylko jeśli dane się zmieniły
                if (userData.login && userData.login !== currentLogin) {
                    loginInput.val(userData.login);
                }
                if (userData.cityBase && userData.cityBase !== currentCityBase) {
                    cityBaseInput.val(userData.cityBase);
                }
                
                // Aktualizuj status administratora tylko jeśli się zmienił
                const newAdminStatus = userData.isAdmin ? 'Tak' : 'Nie';
                if (adminStatusElem.text() !== newAdminStatus) {
                    adminStatusElem.text(newAdminStatus);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas pobierania danych profilu.';
                
                if (xhr.status === 401) {
                    // Brak autoryzacji - przekieruj do strony logowania
                    window.location.href = '/login?error=access';
                    return;
                }
                
                console.error('Błąd pobierania profilu:', xhr.responseText);
                
                // Wyświetl komunikat błędu
                showErrorMessage(errorMessage);
            }
        });
    }
    
    // Załaduj dane użytkownika po załadowaniu strony
    loadUserProfile();
    
    // Obsługa zdarzenia submit formularza
    profileForm.on('submit', function(event) {
        // Zapobiegaj domyślnej akcji przesyłania formularza
        event.preventDefault();
        
        // Jeśli trwa zapisywanie, ignoruj kolejne kliknięcia
        if (isSaving) return;
        
        // Wyczyść poprzednie komunikaty błędów
        clearErrors();
        
        // Pobierz wartości z pól
        const login = loginInput.val().trim();
        const cityBase = cityBaseInput.val().trim();
        const password = passwordInput.val();
        const confirmPassword = confirmPasswordInput.val();
        
        // Flaga do śledzenia czy walidacja przeszła
        let isValid = true;
        
        // Walidacja: Login
        if (!login) {
            showFieldError('login', 'Login jest wymagany.');
            isValid = false;
        }
        
        // Walidacja: Miasto bazowe
        if (!cityBase) {
            showFieldError('cityBase', 'Miasto bazowe jest wymagane.');
            isValid = false;
        }
        
        // Walidacja: Hasła, tylko jeśli którekolwiek pole jest wypełnione
        if (password || confirmPassword) {
            // Sprawdź długość hasła
            if (password.length < 5) {
                showFieldError('password', 'Hasło musi mieć minimum 5 znaków.');
                isValid = false;
            }
            
            // Sprawdź zgodność haseł
            if (password !== confirmPassword) {
                showFieldError('confirmPassword', 'Hasła nie pasują do siebie.');
                isValid = false;
            }
        }
        
        // Jeśli walidacja nie przeszła, przerwij
        if (!isValid) return;
        
        // Ustaw flagę zapisywania
        isSaving = true;
        
        // Deaktywuj przycisk zapisu
        saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Zapisywanie...');
        
        // Przygotuj dane do wysłania
        const userData = {
            login: login,
            cityBase: cityBase
        };
        
        // Dodaj hasło do danych tylko jeśli zostało podane
        if (password) {
            userData.password = password;
        }
        
        // Wyślij żądanie AJAX
        $.ajax({
            url: '/api/users/update',
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(userData),
            headers: {
                'Authorization': 'Bearer ' + sessionStorage.getItem('jwtToken')
            },
            success: function(response) {
                // Wyświetl komunikat sukcesu
                showSuccessMessage('Dane zostały zaktualizowane.');
                
                // Wyczyść pola hasła
                passwordInput.val('');
                confirmPasswordInput.val('');
                
                // Odśwież status administratora
                const adminStatus = response.data.isAdmin ? 'Tak' : 'Nie';
                adminStatusElem.text(adminStatus);
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas aktualizacji profilu.';
                
                if (xhr.status === 400) {
                    try {
                        // Spróbuj sparsować błędy walidacji
                        const response = JSON.parse(xhr.responseText);
                        
                        if (Array.isArray(response)) {
                            // Obsługa tablicy błędów
                            response.forEach(function(error) {
                                if (error.field && error.error) {
                                    showFieldError(error.field, error.error);
                                }
                            });
                        } else if (response.field && response.error) {
                            // Obsługa pojedynczego błędu
                            showFieldError(response.field, response.error);
                        } else if (response.error) {
                            // Ogólny błąd
                            showErrorMessage(response.error);
                        }
                    } catch (e) {
                        // Błąd parsowania odpowiedzi
                        showErrorMessage(errorMessage);
                    }
                } else if (xhr.status === 401) {
                    // Brak autoryzacji - przekieruj do strony logowania
                    window.location.href = '/login?error=access';
                    return;
                } else {
                    // Inny błąd
                    showErrorMessage(errorMessage);
                }
            },
            complete: function() {
                // Resetuj flagę zapisywania
                isSaving = false;
                
                // Aktywuj przycisk zapisu
                saveButton.prop('disabled', false).text('Zapisz zmiany');
            }
        });
    });
    
    // Dodatkowa obsługa zdarzeń dla lepszego UX (opcjonalnie)
    
    // Wyczyszczenie błędu pola po wprowadzeniu zmiany
    $('.form-control').on('input', function() {
        const field = $(this);
        const fieldId = field.attr('id');
        const errorElement = $('#' + fieldId + '-error');
        
        field.removeClass('is-invalid');
        errorElement.text('');
    });
}); 