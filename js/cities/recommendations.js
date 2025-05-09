// Plik JS do obsługi interakcji na stronie rekomendacji miasta
$(document).ready(function() {
    const $messageContainer = $('#messageContainer');
    // Pobierz ID miasta z atrybutu data
    const CITY_ID = $('#recommendationsContainer').data('city-id');

    // --- Helper --- 
    // Prosta funkcja do oczyszczania HTML (zapobiega XSS)
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = String(str); // Upewnij się, że to string
        return temp.innerHTML;
    }

    // Funkcja do wyświetlania komunikatów (info, success, danger)
    function showMessage(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>`;
        $messageContainer.html(alertHtml);
    }

    // Funkcja do czyszczenia komunikatów
    function clearMessages() {
        $messageContainer.empty();
    }

    // Funkcja do generowania HTML dla ikony/przycisku 'done'
    function createDoneElementHtml(recId, isDone) {
        const iconClass = isDone ? 'fas fa-check-circle text-success' : 'far fa-circle text-muted';
        const currentStatus = isDone ? 'true' : 'false';
        const text = isDone ? 'Odwiedzone' : 'Do odwiedzenia';
        return `
            <span class="toggle-done-btn" data-rec-id="${recId}" data-current-status="${currentStatus}" style="cursor: pointer;" title="Zmień status odwiedzenia">
                <i class="${iconClass}"></i> ${text}
            </span>`;
    }

    // Funkcja do aktualizacji statusu na karcie
    function updateCardStatus($card, status) {
        const statusMap = {
            'accepted': { text: 'Zaakceptowana', class: 'bg-success' },
            'edited': { text: 'Edytowana', class: 'bg-warning' },
            'rejected': { text: 'Odrzucona', class: 'bg-danger' },
            'saved': { text: 'Zapisana (nowa)', class: 'bg-info' },
            'done': { text: 'Odwiedzona', class: 'bg-primary' }
        };

        const statusInfo = statusMap[status] || { text: status, class: 'bg-secondary' };
        const $statusBadge = $card.find('.status-badges .badge:last-child');
        
        // Usuń wszystkie możliwe klasy tła
        $statusBadge.removeClass('bg-success bg-warning bg-danger bg-info bg-primary bg-secondary');
        // Dodaj odpowiednią klasę
        $statusBadge.addClass(statusInfo.class);
        // Zaktualizuj tekst
        $statusBadge.text(statusInfo.text);
        
        // Aktualizuj atrybut data-status
        $card.attr('data-status', status);
    }

    // Funkcja do aktualizacji stanu przycisku drukowania
    function updatePrintButton() {
        const hasRecommendations = $('.recommendation-card').length > 0;
        const $printBtn = $('.btn-info[onclick*="print"]');
        if (hasRecommendations) {
            $printBtn.removeClass('disabled').prop('disabled', false);
        } else {
            $printBtn.addClass('disabled').prop('disabled', true);
        }
    }

    // Funkcja do ładowania rekomendacji dla miasta
    function loadRecommendations() {
        clearMessages();
        if (typeof CITY_ID === 'undefined' || !CITY_ID) {
            showMessage('Nie można załadować rekomendacji: brak identyfikatora miasta.', 'danger');
            return;
        }
        
        const $container = $('.row');
        $container.html('<div class="col-12 text-center">Ładowanie rekomendacji...</div>');
        
        $.ajax({
            url: `/api/cities/${CITY_ID}/recommendations`,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!Array.isArray(response.data)) {
                    showMessage('Niepoprawna odpowiedź serwera.', 'danger');
                    return;
                }
                const recs = response.data;
                $container.empty();
                
                if (recs.length === 0) {
                    $container.html('<div class="col-12 text-center"><div class="alert alert-info">Brak rekomendacji dla tego miasta.</div></div>');
                } else {
                    recs.forEach(rec => {
                        const doneElementHtml = createDoneElementHtml(rec.id, rec.done);
                        const cardHtml = `
                            <div class="col">
                                <div class="card h-100 recommendation-card" data-rec-id="${rec.id}" 
                                    data-title="${sanitizeHTML(rec.title)}" 
                                    data-description="${sanitizeHTML(rec.description)}"
                                    data-status="${rec.status}">
                                    <div class="card-header d-flex justify-content-between align-items-start">
                                        <h5 class="card-title mb-0">${sanitizeHTML(rec.title)}</h5>
                                        <div class="status-badges">
                                            <span class="badge bg-secondary me-1">${sanitizeHTML(rec.model)}</span>
                                            <span class="badge bg-secondary"></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="description-container">
                                            ${rec.description.replace(/\n/g, '<br>')}
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center no-print">
                                        <div class="visited-status">
                                            ${doneElementHtml}
                                        </div>
                                        <div class="btn-group" role="group" aria-label="Akcje dla rekomendacji">
                                            <button class="btn btn-sm btn-success accept-btn" data-id="${rec.id}" title="Akceptuj"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-danger reject-btn" data-id="${rec.id}" title="Odrzuć"><i class="fas fa-times"></i></button>
                                            <button class="btn btn-sm btn-warning edit-btn" data-id="${rec.id}" title="Edytuj"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-secondary delete-btn" data-id="${rec.id}" title="Usuń"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="print-only visited-status mt-2">
                                        Odwiedzona: ${rec.done ? 'Tak' : 'Nie'}
                                    </div>
                                </div>
                            </div>`;
                        $container.append(cardHtml);
                        // Aktualizuj status po dodaniu karty
                        updateCardStatus($(`[data-rec-id="${rec.id}"]`), rec.status);
                    });
                }
                // Aktualizuj stan przycisku drukowania
                updatePrintButton();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || xhr.statusText || 'błąd sieci';
                showMessage(`Nie można załadować rekomendacji: ${msg}`, 'danger');
                $container.empty();
                updatePrintButton();
            }
        });
    }

    // Na start załaduj rekomendacje
    loadRecommendations();

    // Obsługa akceptacji rekomendacji
    $(document).on('click', '.accept-btn', function() {
        clearMessages();
        const recId = $(this).data('id');
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ status: 'accepted' }),
            success: function(response) {
                showMessage('Rekomendacja została zaakceptowana.', 'success');
                const $card = $(`.card[data-rec-id='${recId}']`);
                updateCardStatus($card, 'accepted');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd akceptacji rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa odrzucenia rekomendacji
    $(document).on('click', '.reject-btn', function() {
        clearMessages();
        const recId = $(this).data('id');
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ status: 'rejected' }),
            success: function() {
                showMessage('Rekomendacja została odrzucona.', 'warning');
                const $card = $(`.card[data-rec-id='${recId}']`);
                updateCardStatus($card, 'rejected');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd odrzucenia rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa otwarcia modala edycji
    $(document).on('click', '.edit-btn', function() {
        clearMessages();
        const $card = $(this).closest('.card');
        const recId = $card.data('rec-id');
        const title = $card.data('title');
        const description = $card.data('description');
        
        $('#editRecForm #recTitle').val(title);
        $('#editRecForm #recDesc').val(description);
        $('#editRecModal').data('rec-id', recId);
        
        bootstrap.Modal.getOrCreateInstance($('#editRecModal')).show();
    });

    // Zapis zmian po edycji
    $('#saveRecBtn').on('click', function() {
        clearMessages();
        const recId = $('#editRecModal').data('rec-id');
        const newTitle = $('#recTitle').val().trim();
        const newDesc = $('#recDesc').val().trim();
        
        if (!newTitle || !newDesc) {
            showMessage('Tytuł i opis są wymagane.', 'warning');
            return;
        }
        
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ title: newTitle, description: newDesc, status: 'edited' }),
            success: function(response) {
                showMessage('Rekomendacja została zaktualizowana.', 'success');
                const $card = $(`.card[data-rec-id='${recId}']`);
                
                // Aktualizuj dane karty
                $card.data('title', newTitle).data('description', newDesc);
                $card.find('.card-title').text(newTitle);
                $card.find('.description-container').html(newDesc.replace(/\n/g, '<br>'));
                updateCardStatus($card, 'edited');
                
                // Zamknij modal
                bootstrap.Modal.getInstance($('#editRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd aktualizacji rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa przycisku usuwania
    $(document).on('click', '.delete-btn', function() {
        const recId = $(this).data('id');
        $('#deleteRecModal').data('rec-id', recId);
        bootstrap.Modal.getOrCreateInstance($('#deleteRecModal')).show();
    });

    // Potwierdzenie usunięcia
    $('#confirmDeleteRecBtn').on('click', function() {
        const recId = $('#deleteRecModal').data('rec-id');
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'DELETE',
            success: function() {
                showMessage('Rekomendacja została usunięta.', 'success');
                $(`.card[data-rec-id='${recId}']`).closest('.col').remove();
                
                // Sprawdź czy to była ostatnia rekomendacja
                if ($('.recommendation-card').length === 0) {
                    $('.row').html('<div class="col-12 text-center"><div class="alert alert-info">Brak rekomendacji dla tego miasta.</div></div>');
                }
                
                // Aktualizuj stan przycisku drukowania
                updatePrintButton();
                bootstrap.Modal.getInstance($('#deleteRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd usuwania rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa zmiany statusu odwiedzenia
    $(document).on('click', '.toggle-done-btn', function() {
        const $btn = $(this);
        const recId = $btn.data('rec-id');
        const currentStatus = $btn.data('current-status') === true;
        const newStatus = !currentStatus;
        
        $.ajax({
            url: `/api/recommendations/${recId}/done`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ done: newStatus }),
            success: function() {
                const $visitedStatus = $btn.closest('.visited-status');
                $visitedStatus.html(createDoneElementHtml(recId, newStatus));
                
                // Aktualizuj status w sekcji do druku
                const $card = $btn.closest('.card');
                $card.find('.print-only.visited-status').text(`Odwiedzona: ${newStatus ? 'Tak' : 'Nie'}`);
                
                showMessage(`Rekomendacja została oznaczona jako ${newStatus ? 'odwiedzona' : 'nieodwiedzona'}.`, 'success');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd aktualizacji statusu odwiedzenia.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa dodawania nowej rekomendacji
    $('#addRecBtn').on('click', function() {
        $('#newRecTitle').val('');
        $('#newRecDesc').val('');
        bootstrap.Modal.getOrCreateInstance($('#addRecModal')).show();
    });

    // Zapisywanie nowej rekomendacji
    $('#createRecBtn').on('click', function() {
        const title = $('#newRecTitle').val().trim();
        const description = $('#newRecDesc').val().trim();
        
        if (!title || !description) {
            showMessage('Tytuł i opis są wymagane.', 'warning');
            return;
        }
        
        $.ajax({
            url: `/api/cities/${CITY_ID}/recommendations`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ title, description }),
            success: function(response) {
                showMessage('Nowa rekomendacja została dodana.', 'success');
                loadRecommendations(); // Przeładuj wszystkie rekomendacje
                bootstrap.Modal.getInstance($('#addRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd dodawania rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa przycisku powrotu do listy miast
    $('#returnToCitiesBtn').on('click', function(e) {
        e.preventDefault();
        
        // Pobierz parametry z localStorage (jeśli istnieją)
        const page = localStorage.getItem('citiesDashboardPage') || 1;
        const visitedFilter = localStorage.getItem('citiesDashboardVisitedFilter') || '';
        
        // Zbuduj URL z parametrami
        let returnUrl = '/dashboard';
        const params = new URLSearchParams();
        
        if (page !== 1) {
            params.append('page', page);
        }
        if (visitedFilter !== '') {
            params.append('visited', visitedFilter);
        }
        
        // Dodaj parametry do URL jeśli istnieją
        const queryString = params.toString();
        if (queryString) {
            returnUrl += '?' + queryString;
        }
        
        // Przekieruj do dashboardu z parametrami
        window.location.href = returnUrl;
    });
}); 