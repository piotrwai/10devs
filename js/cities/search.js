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
                // Zapisanie ID miasta
                if (response.data.city && response.data.city.id) {
                    currentCityId = response.data.city.id;
                }
                
                // Wyświetlenie wyników
                displaySearchResults(response.data);
                
                // Jeśli to było pierwsze wyszukiwanie i procent akceptacji jest niski,
                // automatycznie pobierz dodatkowe rekomendacje
                if (!supplement && !supplementRequested && response.data.acceptanceRate < 60) {
                    supplementRequested = true;
                    if (confirm('Procent akceptacji rekomendacji jest niski (poniżej 60%). Czy chcesz otrzymać dodatkowe rekomendacje?')) {
                        searchCity(null, true);
                    }
                }
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
                            <h5 class="card-title">${rec.title}</h5>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-success accept-btn">
                                    <i class="bi bi-check-lg"></i> Akceptuj
                                </button>
                                <button class="btn btn-sm btn-outline-danger reject-btn">
                                    <i class="bi bi-x-lg"></i> Odrzuć
                                </button>
                            </div>
                        </div>
                        <p class="card-text">${rec.description}</p>
                        <div class="text-muted small">
                            <em>Źródło: ${rec.model}</em>
                        </div>
                    </div>
                </div>
            `);
            
            $recommendationsList.append($recommendation);
        });
    }

    // Obsługa przycisku "Akceptuj wszystkie"
    $acceptAllBtn.on('click', function() {
        $('.recommendation').each(function() {
            const $rec = $(this);
            $rec.find('.accept-btn').addClass('active btn-success').removeClass('btn-outline-success');
            $rec.find('.reject-btn').removeClass('active btn-danger').addClass('btn-outline-danger');
            $rec.data('status', 'accepted');
        });
    });

    // Obsługa przycisków akceptacji/odrzucenia dla pojedynczych rekomendacji
    $recommendationsList.on('click', '.accept-btn', function() {
        const $btn = $(this);
        const $rec = $btn.closest('.recommendation');
        
        $btn.addClass('active btn-success').removeClass('btn-outline-success');
        $rec.find('.reject-btn').removeClass('active btn-danger').addClass('btn-outline-danger');
        $rec.data('status', 'accepted');
    });

    $recommendationsList.on('click', '.reject-btn', function() {
        const $btn = $(this);
        const $rec = $btn.closest('.recommendation');
        
        $btn.addClass('active btn-danger').removeClass('btn-outline-danger');
        $rec.find('.accept-btn').removeClass('active btn-success').addClass('btn-outline-success');
        $rec.data('status', 'rejected');
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
            if (status !== 'rejected') {
                recommendations.push({
                    title: $rec.find('.card-title').text(),
                    description: $rec.find('.card-text').text(),
                    model: $rec.find('.text-muted em').text().replace('Źródło: ', ''),
                    status: status
                });
            }
        });
        
        // Wywołanie API do zapisania rekomendacji
        $.ajax({
            url: '/api/cities/save-recommendations',
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
                    window.location.href = '/cities';
                }, 2000);
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