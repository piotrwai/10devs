$(document).ready(function() {
    // Zmienne stanu
    let currentPage = 1;
    let currentVisitedFilter = null; // null = Wszystkie, false = Nieodwiedzone, true = Odwiedzone
    // Użyj wartości przekazanej z PHP lub domyślnej 10
    let perPage = typeof MAX_CITIES_PER_PAGE !== 'undefined' ? MAX_CITIES_PER_PAGE : 10; 
    let totalPages = 1;

    // Funkcja do pobierania i wyświetlania listy miast
    function loadCities(page, visitedFilter) {

        // Przygotowanie parametrów żądania
        const params = {
            page: page,
            per_page: perPage
        };
        if (visitedFilter !== null) {
            params.visited = visitedFilter;
        }

        // Pobranie tokena JWT (przykład: z localStorage) - USUNIĘTE
        // const jwtToken = localStorage.getItem('jwtToken'); // !!! Dostosuj sposób pobierania tokena !!!

        // Sprawdzanie tokena po stronie klienta - USUNIĘTE
        // if (!jwtToken) {
        //     // Jeśli nie ma tokena, można przekierować na stronę logowania
        //     console.error('Brak tokena JWT. Przekierowanie do logowania.');
        //     // window.location.href = '/login'; // Odkomentuj, aby włączyć przekierowanie
        //     $('#citiesTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Błąd autoryzacji. Proszę się zalogować.</td></tr>');
        //     $('#paginationControls').empty(); // Wyczyść paginację
        //     return;
        // }

        // Wyświetlenie informacji o ładowaniu
        $('#citiesTable tbody').html('<tr><td colspan="4" class="text-center">Ładowanie danych...</td></tr>');
        $('#paginationControls').empty(); // Wyczyść paginację na czas ładowania

        $.ajax({
            url: '/api/cities/',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                
                // Sprawdzenie, czy odpowiedź ma oczekiwaną strukturę
                if (response && response.data && response.data.pagination) {
                    currentPage = response.data.pagination.currentPage;
                    totalPages = response.data.pagination.totalPages;

                    // Aktualizacja tabeli (używamy response.data.data)
                    updateCitiesTable(response.data.data);

                    // Aktualizacja kontrolek paginacji
                    updatePaginationControls(currentPage, totalPages);
                } else {
                    // Obsługa nieoczekiwanej struktury odpowiedzi
                    showMessage('Otrzymano nieprawidłowe dane z serwera.', 'danger');
                    $('#citiesTable tbody').html(`<tr><td colspan="4" class="text-center text-muted">Błąd przetwarzania danych.</td></tr>`); 
                    $('#paginationControls').empty();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMessage = 'Wystąpił błąd podczas ładowania listy miast.';
                if (jqXHR.status === 401 || jqXHR.status === 403) {
                    errorMessage = 'Błąd autoryzacji. Sesja mogła wygasnąć lub nie masz uprawnień. Spróbuj zalogować się ponownie.';
                    // Opcjonalnie: przekieruj na stronę logowania
                    // window.location.href = '/login?error=session';
                } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                }
                // Zamiast w tabeli, pokaż błąd w kontenerze komunikatów
                showMessage(errorMessage, 'danger'); 
                // Opcjonalnie wyczyść tabelę
                $('#citiesTable tbody').html(`<tr><td colspan="4" class="text-center text-muted">Błąd ładowania danych.</td></tr>`); 
                $('#paginationControls').empty(); // Wyczyść paginację w razie błędu
            },
            // Dodajemy wyczyszczenie komunikatów przed nowym ładowaniem
            beforeSend: function() {
                clearMessages();
                 // Wyświetlenie informacji o ładowaniu
                $('#citiesTable tbody').html('<tr><td colspan="4" class="text-center">Ładowanie danych...</td></tr>');
                $('#paginationControls').empty(); // Wyczyść paginację na czas ładowania
            }
        });
    }

    // Funkcja do aktualizacji tabeli miast
    function updateCitiesTable(cities) {
        const $tbody = $('#citiesTable tbody');
        $tbody.empty(); // Wyczyść istniejące wiersze

        if (!cities || cities.length === 0) {
            // Sprawdź, czy filtr jest aktywny, aby dostosować komunikat
            const filterText = currentVisitedFilter === true ? ' odwiedzonych' : (currentVisitedFilter === false ? ' nieodwiedzonych' : '');
            $tbody.html(`<tr><td colspan="4" class="text-center">Nie znaleziono${filterText} miast. <a href="/cities/search">Dodaj nowe miasto</a>.</td></tr>`);
            return;
        }

        cities.forEach(city => {
            // Użyj sanitizeHTML do oczyszczenia danych przed wstawieniem do HTML
            const cityName = sanitizeHTML(city.name || 'Brak nazwy');
            const recommendationCount = Number.isInteger(city.recommendationCount) ? city.recommendationCount : 0;
            const visitedRecommendationsCount = Number.isInteger(city.visitedRecommendationsCount) ? city.visitedRecommendationsCount : 0;
            const cityId = parseInt(city.id);

            if (!cityId) {
                console.warn('Pominięto miasto bez ID:', city);
                return; // Pomiń miasta bez ID
            }

            // Przygotowanie elementu statusu jako klikalny przycisk
            const isVisited = city.visited === true;
            const statusClass = isVisited ? 'btn-success' : 'btn-secondary';
            const statusText = isVisited ? 'Odwiedzone' : 'Nieodwiedzone';
            const statusIcon = isVisited ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>'; // Wymaga FontAwesome
            const visitedStatusElement = `
                <button 
                    class="btn btn-sm ${statusClass} toggle-visited-btn" 
                    data-city-id="${cityId}" 
                    data-current-status="${isVisited ? 'true' : 'false'}" 
                    title="Kliknij, aby zmienić status"
                >
                    ${statusIcon} ${statusText}
                </button>`;
            
            // Link do szczegółów miasta (jeśli istnieje widok /city/{id})
            // Jeśli nie, można usunąć <a> lub zmienić href
            const cityLink = `<a href="/city/${cityId}">${cityName}</a>`;
            
            // Link do rekomendacji (zakładamy, że istnieje widok /city/{id}/recommendations)
            const recommendationsLink = `<a href="/city/${cityId}/recommendations" class="btn btn-info btn-sm" title="Zobacz Rekomendacje">Rekomendacje</a>`;
            
            // Przycisk usuwania
            const deleteButton = `<button class="btn btn-danger btn-sm delete-city-btn" data-city-id="${cityId}" data-city-name="${cityName}" title="Usuń Miasto">Usuń</button>`;

            const row = `
                <tr>
                    <td>${cityLink}</td>
                    <td>${recommendationCount} / ${visitedRecommendationsCount}</td>
                    <td>${visitedStatusElement}</td>
                    <td>
                        ${recommendationsLink}
                        ${deleteButton}
                    </td>
                </tr>`;
            $tbody.append(row);
        });
    }

    // --- Helper --- 
    // Prosta funkcja do oczyszczania HTML (zapobiega XSS)
    // W rzeczywistym projekcie warto rozważyć użycie biblioteki jak DOMPurify
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    // --- Komunikaty --- 
    function showMessage(message, type = 'info') {
        const $messageContainer = $('#messageContainer');
        // Dostępne typy: primary, secondary, success, danger, warning, info, light, dark
        const alertClass = `alert-${type}`;
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${sanitizeHTML(message)} 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        // Wyczyść poprzednie komunikaty i dodaj nowy
        $messageContainer.html(alertHtml);
    }

    function clearMessages() {
        $('#messageContainer').empty();
    }

    // Funkcja do aktualizacji kontrolek paginacji
    function updatePaginationControls(currentPage, totalPages) {
        const $paginationControls = $('#paginationControls');
        $paginationControls.empty(); // Wyczyść istniejące kontrolki

        if (totalPages <= 1) {
            return; // Nie pokazuj paginacji, jeśli jest tylko jedna strona lub mniej
        }

        let paginationHtml = '<nav aria-label="Nawigacja po stronach miast"><ul class="pagination justify-content-center">';

        // Przycisk "Poprzednia"
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        paginationHtml += `<li class="page-item ${prevDisabled}">
                           <a class="page-link prev-page" href="#" data-page="${currentPage - 1}" aria-label="Poprzednia">
                               <span aria-hidden="true">&laquo;</span>
                           </a>
                         </li>`;

        // TODO: Można dodać bardziej zaawansowaną logikę numeracji stron (np. z "...") dla dużej liczby stron
        // Prosta numeracja: pokazujemy tylko bieżącą stronę
        paginationHtml += `<li class="page-item active" aria-current="page">
                            <span class="page-link">${currentPage} / ${totalPages}</span>
                         </li>`;

        // Przycisk "Następna"
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        paginationHtml += `<li class="page-item ${nextDisabled}">
                           <a class="page-link next-page" href="#" data-page="${currentPage + 1}" aria-label="Następna">
                               <span aria-hidden="true">&raquo;</span>
                           </a>
                         </li>`;

        paginationHtml += '</ul></nav>';
        $paginationControls.html(paginationHtml);
    }

    // Inicjalne ładowanie miast
    loadCities(currentPage, currentVisitedFilter);

    // --- Obsługa zdarzeń ---

    // Zmiana filtra statusu "Odwiedzone"
    $('#visitedFilter').on('change', function() {
        const selectedValue = $(this).val();
        // Konwertuj pusty string na null, 'true'/'false' na boolean
        currentVisitedFilter = selectedValue === '' ? null : (selectedValue === 'true');
        currentPage = 1; // Resetuj do pierwszej strony po zmianie filtra
        loadCities(currentPage, currentVisitedFilter);
    });

    // Kliknięcie przycisków paginacji
    $('#paginationControls').on('click', 'a.page-link', function(e) {
        e.preventDefault(); // Zapobiegaj domyślnej akcji linku
        
        const $link = $(this);
        if ($link.parent().hasClass('disabled') || $link.parent().hasClass('active')) {
            return; // Ignoruj kliknięcia na nieaktywne lub aktywne linki
        }

        const targetPage = parseInt($link.data('page'));
        // Log 3: Na jaki przycisk strony kliknięto?
        if (!isNaN(targetPage)) {
            loadCities(targetPage, currentVisitedFilter);
        }
    });

    // Kliknięcie przycisku "Usuń" w tabeli
    $('#citiesTable tbody').on('click', '.delete-city-btn', function() {
        const $button = $(this);
        const cityId = $button.data('city-id');
        const cityName = $button.data('city-name') || 'to miasto'; // Użyj nazwy lub domyślnego tekstu

        if (!cityId) {
            // Można wyświetlić użytkownikowi komunikat o błędzie
            showMessage('Wystąpił błąd podczas próby usunięcia miasta. Brak ID.', 'warning');
            return;
        }

        // Wypełnij modal danymi i go pokaż
        const $modal = $('#deleteCityModal');
        $modal.find('.city-name-placeholder').text(cityName); // Wstaw nazwę miasta
        $modal.data('city-id', cityId); // Zapisz ID miasta w danych modala

        // Upewnij się, że przycisk potwierdzenia jest aktywny
        $modal.find('#confirmDeleteBtn').prop('disabled', false).text('Potwierdź');

        // Pokaż modal za pomocą API Bootstrapa (jeśli nie jest już zainicjalizowany globalnie)
        const modalInstance = bootstrap.Modal.getOrCreateInstance($modal[0]);
        modalInstance.show();
    });

    // Kliknięcie przycisku "Potwierdź" w modalu usuwania
    $('#confirmDeleteBtn').on('click', function() {
        const $modal = $('#deleteCityModal');
        const cityId = $modal.data('city-id');
        const $button = $(this);

        if (!cityId) {
            alert('Wystąpił błąd podczas usuwania miasta. Brak ID.');
            return;
        }

        // Pokaż wskaźnik ładowania na przycisku
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Usuwanie...');

        $.ajax({
            url: `/api/cities/${cityId}`, // Użyj endpointu DELETE
            method: 'DELETE',
            // Polegamy na ciasteczku dla autoryzacji, nie wysyłamy nagłówka ręcznie
            success: function() {
                // Zamknij modal
                const modalInstance = bootstrap.Modal.getInstance($modal[0]);
                modalInstance.hide();
                
                // Przeładuj dane dla bieżącej strony, aby odświeżyć widok
                // Sprawdź, czy po usunięciu ostatniego elementu na stronie,
                // nie trzeba cofnąć się do poprzedniej strony.
                // Prostsze rozwiązanie na teraz: zawsze przeładuj bieżącą stronę.
                loadCities(currentPage, currentVisitedFilter);

                // Pokaż komunikat sukcesu
                showMessage('Miasto zostało pomyślnie usunięte.', 'success');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMessage = 'Nie udało się usunąć miasta.';
                if (jqXHR.status === 404) {
                    errorMessage = 'Nie znaleziono miasta o podanym ID lub nie masz uprawnień do jego usunięcia.';
                } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                }
                // Pokaż komunikat błędu
                showMessage(`Błąd: ${errorMessage}`, 'danger');
            },
            complete: function() {
                // Przywróć przycisk w modalu niezależnie od wyniku
                $button.prop('disabled', false).text('Potwierdź');
            }
        });
    });

    // Kliknięcie przycisku zmiany statusu "Odwiedzone"
    $('#citiesTable tbody').on('click', '.toggle-visited-btn', function() {
        const $button = $(this);
        const cityId = $button.data('city-id');
        
        // Odczytaj atrybut data-current-status bezpośrednio
        const currentStatusAttr = $button.attr('data-current-status');
        
        // Konwertuj odczytany string na boolean
        const currentStatus = currentStatusAttr === 'true';
        
        const newStatus = !currentStatus; // Odwróć status

        if (!cityId) {
            showMessage('Wystąpił błąd podczas próby zmiany statusu miasta. Brak ID.', 'warning');
            return;
        }

        // Tymczasowo zablokuj przycisk i pokaż wskaźnik ładowania
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Zmieniam...');

        // Wywołaj funkcję aktualizującą status przez API
        updateCityStatus(cityId, newStatus, $button);
    });

    // Funkcja wysyłająca żądanie PUT do API w celu zmiany statusu miasta
    function updateCityStatus(cityId, newVisitedStatus, $buttonElement) {
        
        $.ajax({
            url: `/api/cities/${cityId}`, // Endpoint PUT
            method: 'PUT',
            contentType: 'application/json', // Ważne dla wysyłania JSON
            data: JSON.stringify({ visited: newVisitedStatus }), // Wyślij nowy status w ciele JSON
            // Autoryzacja przez ciasteczko
            success: function(response) {
                // Zaktualizuj wygląd przycisku bez przeładowania
                const statusClass = newVisitedStatus ? 'btn-success' : 'btn-secondary';
                const statusText = newVisitedStatus ? 'Odwiedzone' : 'Nieodwiedzone';
                const statusIcon = newVisitedStatus ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>';
                                
                $buttonElement
                    .removeClass('btn-success btn-secondary')
                    .addClass(statusClass)
                    .attr('data-current-status', String(newVisitedStatus)) // Używamy .attr() zamiast .data() i konwertujemy na string
                    .html(`${statusIcon} ${statusText}`) // Ustaw nową zawartość
                    .prop('disabled', false); // Odblokuj przycisk

                // Opcjonalnie: pokaż komunikat sukcesu
                showMessage('Status miasta został zaktualizowany.', 'success');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMessage = 'Nie udało się zaktualizować statusu miasta.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                } else if (jqXHR.status === 404) {
                     errorMessage = 'Nie znaleziono miasta o podanym ID.';
                }
                
                showMessage(`Błąd: ${errorMessage}`, 'danger');
                // Przywróć oryginalny wygląd przycisku w razie błędu
                const currentStatus = $buttonElement.attr('data-current-status') === 'true'; // Używamy .attr() zamiast .data()
                const originalClass = currentStatus ? 'btn-success' : 'btn-secondary';
                const originalText = currentStatus ? 'Odwiedzone' : 'Nieodwiedzone';
                const originalIcon = currentStatus ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>';
                $buttonElement
                    .removeClass('btn-success btn-secondary')
                    .addClass(originalClass)
                    .html(`${originalIcon} ${originalText}`)
                    .prop('disabled', false);
            }
        });
    }

}); 