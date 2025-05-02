/**
 * Skrypt obsługujący wyszukiwanie miast i wyświetlanie rekomendacji
 */

$(document).ready(function() {
    const $form = $('#city-search-form');
    const $cityInput = $('#cityName');
    const $submitButton = $('#search-btn');
    const $formMessages = $('#form-messages');
    const $spinner = $('.btn-spinner');
    const $searchResults = $('#search-results');
    const $recommendationsList = $('#recommendations-list');
    const $acceptAllBtn = $('#accept-all-btn');
    const $saveRecommendationsBtn = $('#save-recommendations-btn');
    let currentCityId = null;
    let supplementRequested = false;

    // Obsługa formularza wyszukiwania
    $form.on('submit', function(e) {
        e.preventDefault();
        
        // Reset komunikatów i stanu
        $formMessages.empty().removeClass('alert alert-danger alert-success');
        $('.is-invalid').removeClass('is-invalid');
        $searchResults.addClass('d-none');
        
        // Walidacja
        const cityName = $cityInput.val().trim();
        if (!cityName) {
            $cityInput.addClass('is-invalid');
            $('#cityName-error').text('Proszę podać nazwę miasta');
            return;
        }
        
        if (cityName.length > 150) {
            $cityInput.addClass('is-invalid');
            $('#cityName-error').text('Nazwa miasta nie może przekraczać 150 znaków');
            return;
        }
        
        // Pokazanie spinnera i blokada przycisku
        $submitButton.prop('disabled', true);
        $spinner.removeClass('d-none');
        
        // Wywołanie API
        searchCity(cityName);
    });

    // Funkcja do wyszukiwania miasta
    function searchCity(cityName, supplement = false) {
        const requestData = supplement ? 
            { cityId: currentCityId, supplement: true } : 
            { cityName: cityName };

        $.ajax({
            url: '/api/cities/search',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                // Debug: logowanie surowej odpowiedzi
                console.log('Surowa odpowiedź z serwera:', response);
                
                // Parsowanie odpowiedzi JSON na obiekt JavaScript
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Błąd parsowania odpowiedzi JSON:', e);
                    throw new Error('Nieprawidłowy format odpowiedzi z serwera');
                }
                
                // Debug: logowanie sparsowanej odpowiedzi
                console.log('Sparsowana odpowiedź:', response);
                
                // Sprawdzenie czy odpowiedź zawiera wymagane dane
                if (!response || !response.data || !response.data.city || !response.data.recommendations) {
                    throw new Error('Nieprawidłowa odpowiedź z serwera');
                }
                
                // Debug: logowanie danych
                console.log('Dane miasta:', response.data.city);
                console.log('Rekomendacje:', response.data.recommendations);
                
                // Zapisanie ID miasta
                if (response.data.city && response.data.city.id) {
                    currentCityId = response.data.city.id;
                }
                
                // Wyświetlenie wyników
                displaySearchResults(response.data);
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas wyszukiwania miasta';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $formMessages
                    .addClass('alert alert-danger')
                    .text(errorMessage);
            },
            complete: function() {
                // Ukrycie spinnera i odblokowanie przycisku
                $submitButton.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    }

    // Funkcja wyświetlająca wyniki wyszukiwania
    function displaySearchResults(data) {
        // Wyświetlenie sekcji wyników
        $searchResults.removeClass('d-none');
        
        // Wyświetlenie informacji o mieście
        $('.city-name').text(data.city.name);
        $('.city-summary').text(data.city.summary);
        
        // Wyczyszczenie i wypełnienie listy rekomendacji
        $recommendationsList.empty();
        
        data.recommendations.forEach((rec, index) => {
            const $recommendation = $(`
                <div class="card mb-3 recommendation" data-id="${rec.id || ''}" data-index="${index}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title" contenteditable="true">${rec.title}</h5>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary edit-btn">
                                    <i class="bi bi-pencil"></i> Edytuj
                                </button>
                                <button class="btn btn-sm btn-outline-success accept-btn">
                                    <i class="bi bi-check-lg"></i> Akceptuj
                                </button>
                                <button class="btn btn-sm btn-outline-danger reject-btn">
                                    <i class="bi bi-x-lg"></i> Odrzuć
                                </button>
                            </div>
                        </div>
                        <p class="card-text" contenteditable="true">${rec.description}</p>
                        <div class="text-muted small">
                            <em>Źródło: ${rec.model}</em>
                        </div>
                        <div class="edit-controls d-none mt-2">
                            <button class="btn btn-sm btn-success save-edit-btn">
                                <i class="bi bi-check"></i> Zapisz zmiany
                            </button>
                            <button class="btn btn-sm btn-secondary cancel-edit-btn">
                                <i class="bi bi-x"></i> Anuluj
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            // Zapisanie oryginalnych wartości
            $recommendation.data('original-title', rec.title);
            $recommendation.data('original-description', rec.description);
            
            $recommendationsList.append($recommendation);
        });
    }

    // Obsługa przycisku "Akceptuj wszystkie"
    $acceptAllBtn.on('click', function() {
        $('.recommendation').each(function() {
            const $rec = $(this);
            if (!$rec.find('.reject-btn').hasClass('active')) {
                $rec.find('.accept-btn').addClass('active btn-success').removeClass('btn-outline-success');
                $rec.find('.reject-btn').removeClass('active btn-danger').addClass('btn-outline-danger');
                $rec.data('status', 'accepted');
            }
        });
    });

    // Obsługa przycisków akceptacji/odrzucenia dla pojedynczych rekomendacji
    $recommendationsList.on('click', '.accept-btn', function() {
        const $btn = $(this);
        const $rec = $btn.closest('.recommendation');
        
        if ($btn.hasClass('active')) {
            // Jeśli przycisk jest już aktywny, dezaktywujemy go
            $btn.removeClass('active btn-success').addClass('btn-outline-success');
            $rec.data('status', 'pending');
        } else {
            // Aktywujemy przycisk akceptacji i dezaktywujemy odrzucenie
            $btn.addClass('active btn-success').removeClass('btn-outline-success');
            $rec.find('.reject-btn').removeClass('active btn-danger').addClass('btn-outline-danger');
            $rec.data('status', 'accepted');
        }
    });

    $recommendationsList.on('click', '.reject-btn', function() {
        const $btn = $(this);
        const $rec = $btn.closest('.recommendation');
        
        if ($btn.hasClass('active')) {
            // Jeśli przycisk jest już aktywny, dezaktywujemy go
            $btn.removeClass('active btn-danger').addClass('btn-outline-danger');
            $rec.data('status', 'pending');
        } else {
            // Aktywujemy przycisk odrzucenia i dezaktywujemy akceptację
            $btn.addClass('active btn-danger').removeClass('btn-outline-danger');
            $rec.find('.accept-btn').removeClass('active btn-success').addClass('btn-outline-success');
            $rec.data('status', 'rejected');
        }
    });

    // Obsługa edycji rekomendacji
    $recommendationsList.on('click', '.edit-btn', function() {
        const $rec = $(this).closest('.recommendation');
        const $editControls = $rec.find('.edit-controls');
        const $title = $rec.find('.card-title');
        const $description = $rec.find('.card-text');
        
        // Pokazanie przycisków edycji
        $editControls.removeClass('d-none');
        
        // Włączenie edycji
        $title.attr('contenteditable', 'true').focus();
        $description.attr('contenteditable', 'true');
        
        // Dodanie klasy wskazującej na tryb edycji
        $rec.addClass('editing');
    });

    // Zapisanie zmian w edycji
    $recommendationsList.on('click', '.save-edit-btn', function() {
        const $rec = $(this).closest('.recommendation');
        const $editControls = $rec.find('.edit-controls');
        const $title = $rec.find('.card-title');
        const $description = $rec.find('.card-text');
        
        // Wyłączenie edycji
        $title.attr('contenteditable', 'false');
        $description.attr('contenteditable', 'false');
        
        // Ukrycie przycisków edycji
        $editControls.addClass('d-none');
        
        // Usunięcie klasy edycji
        $rec.removeClass('editing');
        
        // Oznaczenie rekomendacji jako edytowanej
        $rec.data('status', 'edited');
        $rec.find('.accept-btn').addClass('active btn-success').removeClass('btn-outline-success');
        $rec.find('.reject-btn').removeClass('active btn-danger').addClass('btn-outline-danger');
    });

    // Anulowanie zmian w edycji
    $recommendationsList.on('click', '.cancel-edit-btn', function() {
        const $rec = $(this).closest('.recommendation');
        const $editControls = $rec.find('.edit-controls');
        const $title = $rec.find('.card-title');
        const $description = $rec.find('.card-text');
        
        // Przywrócenie oryginalnych wartości
        $title.text($rec.data('original-title'));
        $description.text($rec.data('original-description'));
        
        // Wyłączenie edycji
        $title.attr('contenteditable', 'false');
        $description.attr('contenteditable', 'false');
        
        // Ukrycie przycisków edycji
        $editControls.addClass('d-none');
        
        // Usunięcie klasy edycji
        $rec.removeClass('editing');
    });

    // Obsługa przycisku "Zapisz wybrane"
    $saveRecommendationsBtn.on('click', function() {
        const cityData = {
            name: $('.city-name').text(),
            summary: $('.city-summary').text()
        };
        
        const recommendations = [];
        $('.recommendation').each(function() {
            const $rec = $(this);
            const status = $rec.data('status') || 'pending';
            
            // Zbieramy tylko zaakceptowane i edytowane rekomendacje
            if (status === 'accepted' || status === 'edited') {
                recommendations.push({
                    title: $rec.find('.card-title').text(),
                    description: $rec.find('.card-text').text(),
                    model: $rec.find('.text-muted em').text().replace('Źródło: ', ''),
                    status: status
                });
            }
        });

        // Sprawdzenie czy są jakieś rekomendacje do zapisania
        if (recommendations.length === 0) {
            $formMessages
                .addClass('alert alert-warning')
                .text('Proszę wybrać przynajmniej jedną rekomendację do zapisania');
            return;
        }
        
        // Wywołanie API do zapisania rekomendacji
        $.ajax({
            url: '/api/recommendations/save',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                cityId: currentCityId,
                recommendations: recommendations
            }),
            success: function(response) {
                // Wyświetlenie komunikatu sukcesu
                $formMessages
                    .addClass('alert alert-success')
                    .text('Rekomendacje zostały zapisane pomyślnie');
                
                // Przekierowanie do listy miast po 2 sekundach
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 1000);
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas zapisywania rekomendacji';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $formMessages
                    .addClass('alert alert-danger')
                    .text(errorMessage);
            }
        });
    });
}); 