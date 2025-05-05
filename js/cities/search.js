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
    let supplementUsed = false;
    let currentCityName = '';

    // Obsługa formularza wyszukiwania
    $form.on('submit', function(e) {
        e.preventDefault();
        
        const currentMode = $form.data('mode') || 'search';

        // Reset komunikatów i stanu
        $formMessages.empty().removeClass('alert alert-danger alert-success alert-info');
        $('.is-invalid').removeClass('is-invalid');
        if (currentMode === 'search') {
            $searchResults.addClass('d-none');
        }
        
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
        
        // Zapisanie/aktualizacja nazwy miasta
        currentCityName = cityName;

        // Reset flagi użycia suplementu przy nowym wyszukiwaniu
        supplementUsed = false;

        // Przywrócenie domyślnego tekstu i stanu przycisku
        $submitButton.html('Wyszukaj atrakcje').removeClass('btn-warning').addClass('btn-primary');
        
        // Pokazanie spinnera i blokada przycisku
        $submitButton.prop('disabled', true);
        $spinner.removeClass('d-none');
        
        // Ustawienie komunikatu w zależności od trybu
        if (currentMode === 'supplement') {
            setLoadingMessage('Generuję dodatkowe rekomendacje...', 'bi-stars');
            requestSupplement();
        } else {
            setLoadingMessage('Sprawdzam poprawność miasta...', 'bi-geo-alt');
            searchCity(cityName);
        }
    });

    // Funkcja do ustawiania komunikatu ładowania
    function setLoadingMessage(message, iconClass = 'bi-search') {
         $formMessages
            .removeClass('alert-danger alert-success')
            .addClass('alert alert-info')
            .html(`<i class="bi ${iconClass} me-2"></i> ${message}`);
    }

    // Funkcja do wyszukiwania miasta
    function searchCity(cityName) {
        const requestData = { cityName: cityName };
        
        // Set initial loading message for the whole process
        setLoadingMessage('Sprawdzam miasto, szukam trasy i generuję rekomendacje...', 'bi-compass');

        $.ajax({
            url: '/api/cities/search',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                // Parsowanie odpowiedzi JSON na obiekt JavaScript
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    throw new Error('Nieprawidłowy format odpowiedzi z serwera');
                }
                
                // Sprawdzenie czy odpowiedź zawiera wymagane dane
                if (!response || !response.data || !response.data.city || !response.data.recommendations) {
                    throw new Error('Nieprawidłowa odpowiedź z serwera');
                }
                
                // Zapisanie ID miasta
                if (response.data.city && response.data.city.id) {
                    currentCityId = response.data.city.id;
                }
                
                // Wyświetlenie wyników
                displaySearchResults(response.data);
                // Usunięcie komunikatu ładowania po sukcesie
                $formMessages.empty().removeClass('alert alert-info');
                // Sprawdź od razu, czy nie trzeba pokazać przycisku suplementu
                checkSupplementButton();
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas wyszukiwania miasta';
                
                // Próba pobrania komunikatu błędu z odpowiedzi
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                
                showErrorMessage(errorMessage, xhr.status === 400);
            },
            complete: function() {
                // Ukrycie spinnera i odblokowanie przycisku
                $submitButton.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    }

    // Funkcja do wysyłania zapytania o uzupełnienie rekomendacji
    function requestSupplement() {
        if (!currentCityName) {
            showErrorMessage('Brak nazwy miasta do uzupełnienia rekomendacji.');
            return;
        }

        const existingTitles = $('.recommendation').map(function() {
            // Pobierz tytuł, usuwając ewentualną ikonę trasy
            const titleElement = $(this).find('.card-title');
            const titleText = titleElement.clone().find('i').remove().end().text().trim();
            return titleText;
        }).get();

        // console.log('Requesting supplement for:', currentCityName, 'Existing titles:', existingTitles);

        const requestData = {
            cityName: currentCityName,
            supplement: true,
            existingTitles: existingTitles
        };

        $submitButton.prop('disabled', true);
        $spinner.removeClass('d-none');
        setLoadingMessage('Generuję dodatkowe rekomendacje...', 'bi-stars');

        $.ajax({
            url: '/api/cities/search', // Używamy tego samego endpointu
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    throw new Error('Nieprawidłowy format odpowiedzi z serwera przy uzupełnianiu');
                }

                if (response && response.data && Array.isArray(response.data.newRecommendations)) {
                    const newRecs = response.data.newRecommendations;
                    if (newRecs.length > 0) {
                        displayNewRecommendations(newRecs);
                        showSuccessMessage(`Znalezionych rekomendacji: ${newRecs.length}`);
                        supplementUsed = true; // Oznacz jako użyte
                        $submitButton.prop('disabled', true).hide(); // Deaktywuj i ukryj przycisk na stałe
                        $form.data('mode', 'search'); // Wróć do trybu search
                    } else {
                        showInfoMessage('Nie znaleziono dodatkowych unikalnych rekomendacji dla tego miasta.');
                        supplementUsed = true; // Oznacz jako użyte, nawet jeśli nic nie znaleziono
                        $submitButton.prop('disabled', true).hide(); // Deaktywuj i ukryj
                        $form.data('mode', 'search'); // Wróć do trybu search
                    }
                } else {
                    throw new Error('Nieprawidłowa odpowiedź serwera przy uzupełnianiu');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Wystąpił błąd podczas generowania dodatkowych rekomendacji';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showErrorMessage(errorMessage);
                // Nie ustawiamy supplementUsed = true w przypadku błędu, aby umożliwić ponowienie
                $submitButton.prop('disabled', false); // Odblokuj przycisk w razie błędu
            },
            complete: function() {
                $spinner.addClass('d-none');
                // Nie odblokowuj przycisku tutaj, zrobiono to w success/error
            }
        });
    }

    // Funkcja do obliczania procentu akceptacji rekomendacji AI
    function calculateAcceptanceRate() {
        let totalAiRecommendations = 0;
        let acceptedOrEditedCount = 0;

        $('.recommendation').each(function() {
            const $rec = $(this);
            const model = $rec.data('model');

            // Pomijamy rekomendacje trasy
            if (model === 'route_planner' || model === 'route_planner_error') {
                return; // continue
            }

            totalAiRecommendations++;

            const status = $rec.data('status');
            // Liczymy wszystko co NIE jest 'rejected' jako pozytywne
            if (status !== 'rejected') {
                acceptedOrEditedCount++;
            }
        });

        if (totalAiRecommendations === 0) {
            return 100; // Jeśli nie ma rekomendacji AI, uznajemy 100%
        }

        const rate = (acceptedOrEditedCount / totalAiRecommendations) * 100;
        console.log(`Acceptance Rate: ${rate.toFixed(2)}% (${acceptedOrEditedCount}/${totalAiRecommendations})`);
        return rate;
    }

    // Funkcja sprawdzająca, czy pokazać przycisk uzupełnienia
    function checkSupplementButton() {
        if (supplementUsed) return; // Jeśli już użyto, nie pokazuj ponownie

        const acceptanceRate = calculateAcceptanceRate();

        if (acceptanceRate <= 60) {
            // console.log('Acceptance rate <= 60%, showing supplement button.');
            $submitButton.html('Uzupełnij rekomendacje')
                         .removeClass('btn-primary')
                         .addClass('btn-warning')
                         .prop('disabled', false); // Upewnij się, że jest odblokowany
            // Zmieniamy zachowanie formularza/przycisku na tryb suplementu
            // Można to zrobić zmieniając flagę lub dodając klasę
            $form.data('mode', 'supplement');
        } else {
             // Jeśli procent wzrośnie powyżej 60, wróć do standardowego przycisku
             // (choć w tym flow to mało prawdopodobne bez odświeżenia)
             if ($form.data('mode') === 'supplement') {
                  $submitButton.html('Wyszukaj atrakcje')
                               .removeClass('btn-warning')
                               .addClass('btn-primary');
                  $form.data('mode', 'search'); 
             }
         }
    }

    // Funkcja do wyświetlania komunikatów o błędach
    function showErrorMessage(message, isValidationError = false) {
        $formMessages
            .removeClass('alert-info alert-success')
            .addClass('alert alert-danger')
            .html(`<i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}`);
            
        if (isValidationError && message.includes('nie jest rozpoznawana jako miasto')) {
            $cityInput.addClass('is-invalid');
        }
    }

    // Funkcja do wyświetlania komunikatów sukcesu
    function showSuccessMessage(message) {
        $formMessages
            .removeClass('alert-info alert-danger alert-warning')
            .addClass('alert alert-success')
            .html(`<i class="bi bi-check-circle-fill me-2"></i> ${message}`);
    }

    // Funkcja do wyświetlania komunikatów informacyjnych
    function showInfoMessage(message) {
        $formMessages
            .removeClass('alert-success alert-danger alert-warning')
            .addClass('alert alert-info')
            .html(`<i class="bi bi-info-circle-fill me-2"></i> ${message}`);
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
            // Zamiana \n na <br> w opisie dla poprawnego wyświetlania kroków trasy
            const descriptionHtml = rec.description.replace(/\n/g, '<br>'); 
            const isRoute = rec.model === 'route_planner' || rec.model === 'route_planner_error';
            const cardClass = isRoute ? 'border-primary' : ''; // Dodatkowa klasa dla karty trasy
            const titleIcon = isRoute ? '<i class="bi bi-signpost-split me-2"></i>' : ''; // Ikona dla trasy

            const $recommendation = $(`
                <div class="card mb-3 recommendation ${cardClass}" data-id="${rec.id || ''}" data-index="${index}" data-model="${rec.model}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title" contenteditable="true">${titleIcon}${rec.title}</h5>
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
                        <p class="card-text recommendation-description" contenteditable="true">${descriptionHtml}</p>
                        <div class="text-muted small">
                            <em>Źródło: ${rec.model}</em>
                        </div>
                        <div class="recommendation-status mt-2" style="display: none;"></div>
                    </div>
                </div>
            `);
            
            // Zapisanie oryginalnych wartości
            $recommendation.data('original-title', rec.title);
            $recommendation.data('original-description', rec.description);
            
            $recommendationsList.append($recommendation);
        });
        
        // Wyczyszczenie starych komunikatów
        $formMessages.empty().removeClass('alert alert-danger alert-success alert-info alert-warning');

        // Pokazanie przycisków akcji (jeśli są jakieś rekomendacje poza trasą)
        if (data.recommendations.some(r => r.model !== 'route_planner' && r.model !== 'route_planner_error')) {
            $('#accept-all-btn, #save-recommendations-btn').removeClass('d-none');
        } else {
            $('#accept-all-btn, #save-recommendations-btn').addClass('d-none');
        }
    }

    // Funkcja do wyświetlania NOWYCH rekomendacji (dodaje na górze)
    function displayNewRecommendations(newRecs) {
        newRecs.reverse().forEach((rec, index) => {
            const descriptionHtml = rec.description.replace(/\n/g, '<br>');
            const isRoute = false; // Nowe rekomendacje nigdy nie są trasą
            const cardClass = 'border-warning'; // Oznaczamy nowe rekomendacje
            const titleIcon = '<i class="bi bi-star-fill text-warning me-2"></i>'; // Ikona dla nowych

            const $recommendation = $(`
                <div class="card mb-3 recommendation ${cardClass}" data-id="" data-index="new-${index}" data-model="${rec.model}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title" contenteditable="true">${titleIcon}${rec.title}</h5>
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
                        <p class="card-text recommendation-description" contenteditable="true">${descriptionHtml}</p>
                        <div class="text-muted small">
                            <em>Źródło: ${rec.model}</em>
                        </div>
                        <div class="recommendation-status mt-2" style="display: none;"></div>
                    </div>
                </div>
            `);

            // Zapisanie oryginalnych wartości
            $recommendation.data('original-title', rec.title);
            $recommendation.data('original-description', rec.description);

            // Dodanie na początku listy
            $recommendationsList.prepend($recommendation);
        });
        // Po dodaniu nowych, przeliczmy procent akceptacji (choć przycisk i tak jest już ukryty)
        calculateAcceptanceRate();
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

    // Nasłuchiwanie na zmianę statusu rekomendacji, aby przeliczyć procent
    $recommendationsList.on('click', '.accept-btn, .reject-btn', function() {
        // Poczekaj chwilę na aktualizację danych i przelicz
        setTimeout(checkSupplementButton, 100);
    });

    // Obsługa zapisu edycji również powinna wywołać przeliczenie
    $recommendationsList.on('click', '.save-edit-btn', function() {
        setTimeout(checkSupplementButton, 100);
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
                city: cityData,
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