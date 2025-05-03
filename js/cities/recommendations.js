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
        return `
            <span class="toggle-done-btn" data-rec-id="${recId}" data-current-status="${currentStatus}" style="cursor: pointer;" title="Zmień status odwiedzenia">
                <i class="${iconClass}"></i>
            </span>`;
    }

    // Funkcja do ładowania rekomendacji dla miasta
    function loadRecommendations() {
        clearMessages();
        if (typeof CITY_ID === 'undefined' || !CITY_ID) {
            showMessage('Nie można załadować rekomendacji: brak identyfikatora miasta.', 'danger');
            return;
        }
        // Wyczyść tabelę i pokaż informację o ładowaniu
        $('#recommendationsTable tbody').html('<tr><td colspan="6" class="text-center">Ładowanie rekomendacji...</td></tr>');
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
                const $tbody = $('#recommendationsTable tbody');
                $tbody.empty();
                if (recs.length === 0) {
                    $tbody.html('<tr><td colspan="6" class="text-center">Brak rekomendacji dla tego miasta.</td></tr>');
                    // Dezaktywuj przycisk drukowania
                    $('.btn-info[onclick*="print"]').addClass('disabled').prop('disabled', true);
                } else {
                    // Aktywuj przycisk drukowania
                    $('.btn-info[onclick*="print"]').removeClass('disabled').prop('disabled', false);
                    
                    recs.forEach(rec => {
                        const doneElementHtml = createDoneElementHtml(rec.id, rec.done);
                        // Mapowanie statusu na polski przy ładowaniu
                        let polishStatus = rec.status;
                        switch (rec.status) {
                            case 'accepted': polishStatus = 'Zaakceptowana'; break;
                            case 'edited': polishStatus = 'Edytowana'; break;
                            case 'rejected': polishStatus = 'Odrzucona'; break;
                            case 'saved': polishStatus = 'Zapisana (nowa)'; break;
                            case 'done': polishStatus = 'Odwiedzona'; break;
                            default: polishStatus = sanitizeHTML(rec.status);
                        }
                        const rowHtml = `
                            <tr data-rec-id="${rec.id}" 
                                data-title="${sanitizeHTML(rec.title)}" 
                                data-description="${sanitizeHTML(rec.description)}"
                                data-status="${rec.status}"
                                class="recommendation-row">
                                <td>${sanitizeHTML(rec.title)}</td>
                                <td>${rec.description.replace(/\n/g, '<br>')}</td>
                                <td class="text-center no-print" style="white-space: nowrap;">${sanitizeHTML(rec.model)}</td>
                                <td class="text-center no-print">${polishStatus}</td>
                                <td class="text-center no-print">${doneElementHtml}</td>
                                <td class="no-print">
                                    <div class="btn-group" role="group" aria-label="Akcje dla rekomendacji">
                                        <button class="btn btn-sm btn-success accept-btn" data-id="${rec.id}" title="Akceptuj"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-danger reject-btn" data-id="${rec.id}" title="Odrzuć"><i class="fas fa-times"></i></button>
                                        <button class="btn btn-sm btn-warning edit-btn" data-id="${rec.id}" title="Edytuj"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-secondary delete-btn" data-id="${rec.id}" title="Usuń"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                                <td class="print-only visited-status">
                                    Odwiedzona: ${rec.done ? 'Tak' : 'Nie'}
                                </td>
                            </tr>`;
                        let inserted = false;
                        $('#recommendationsTable tbody tr').each(function() {
                            const currentTitle = $(this).find('td:first').text();
                            if (sanitizeHTML(rec.title).localeCompare(currentTitle) < 0) {
                                $(this).before(rowHtml);
                                inserted = true;
                                return false; // zakończ pętlę
                            }
                        });
                        if (!inserted) {
                            $('#recommendationsTable tbody').append(rowHtml);
                        }
                    });
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || xhr.statusText || 'błąd sieci';
                showMessage(`Nie można załadować rekomendacji: ${msg}`, 'danger');
                $('#recommendationsTable tbody').empty();
                // Dezaktywuj przycisk drukowania w przypadku błędu
                $('.btn-info[onclick*="print"]').addClass('disabled').prop('disabled', true);
            }
        });
    }

    // Na start załaduj rekomendacje
    loadRecommendations();

    // Obsługa akceptacji rekomendacji
    $(document).on('click', '.accept-btn', function() {
        clearMessages();
        const recId = $(this).data('id');
        console.log('Accepting recommendation ID:', recId);
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ status: 'accepted' }),
            success: function(response) {
                showMessage('Rekomendacja została zaakceptowana.', 'success');
                const $row = $(`tr[data-rec-id='${recId}']`);
                $row.attr('data-status', 'accepted');
                $row.find('td:nth-child(4)').text('Zaakceptowana');
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
        console.log('Rejecting recommendation ID:', recId);
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ status: 'rejected' }),
            success: function() {
                showMessage('Rekomendacja została odrzucona.', 'warning');
                const $row = $(`tr[data-rec-id='${recId}']`);
                $row.attr('data-status', 'rejected');
                $row.find('td:nth-child(4)').text('Odrzucona');
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
        const $tr = $(this).closest('tr');
        const recId = $tr.data('rec-id');
        const title = $tr.data('title');
        const description = $tr.data('description');
        // Wypełnij pola formularza
        $('#editRecForm #recTitle').val(title);
        $('#editRecForm #recDesc').val(description);
        $('#editRecModal').data('rec-id', recId);
        // Pokaż modal
        bootstrap.Modal.getOrCreateInstance($('#editRecModal')).show();
    });

    // Zapis zmian po edycji
    $('#saveRecBtn').on('click', function() {
        clearMessages();
        const recId = $('#editRecModal').data('rec-id');
        console.log('Saving recommendation ID:', recId);
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
                const $tr = $(`tr[data-rec-id='${recId}']`);
                $tr.attr('data-status', 'edited');
                $tr.find('td:eq(0)').text(response.data.title);
                $tr.find('td:eq(1)').html(response.data.description.replace(/\n/g, '<br>'));
                $tr.find('td:eq(3)').text('Edytowana');
                $tr.data('title', response.data.title);
                $tr.data('description', response.data.description);
                bootstrap.Modal.getInstance($('#editRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd aktualizacji rekomendacji.';
                showMessage(msg, 'danger');
            }
        });
    });

    // Obsługa usuwania rekomendacji
    $(document).on('click', '.delete-btn', function() {
        clearMessages();
        const recId = $(this).data('id');
        console.log('Opening delete modal for recommendation ID:', recId);
        $('#deleteRecModal').data('rec-id', recId);
        bootstrap.Modal.getOrCreateInstance($('#deleteRecModal')).show();
    });
    $('#confirmDeleteRecBtn').on('click', function() {
        clearMessages();
        const $btn = $(this);
        const recId = $('#deleteRecModal').data('rec-id');
        console.log('Deleting recommendation ID:', recId);
        $btn.prop('disabled', true).text('Usuwanie...');
        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'DELETE',
            success: function() {
                showMessage('Rekomendacja została usunięta.', 'success');
                $(`tr[data-rec-id='${recId}']`).remove();
                bootstrap.Modal.getInstance($('#deleteRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd usuwania rekomendacji.';
                showMessage(msg, 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Tak, usuń');
            }
        });
    });

    // Obsługa otwarcia modala dodawania
    $('#addRecBtn').on('click', function() {
        clearMessages();
        // Wyczyść formularz
        $('#addRecForm')[0].reset();
        bootstrap.Modal.getOrCreateInstance($('#addRecModal')).show();
    });

    // Obsługa tworzenia nowej rekomendacji
    $('#createRecBtn').on('click', function() {
        clearMessages();
        console.log('Creating new recommendation for city ID:', CITY_ID);
        const newTitle = $('#newRecTitle').val().trim();
        const newDesc = $('#newRecDesc').val().trim();
        if (!newTitle || !newDesc) {
            showMessage('Tytuł i opis są wymagane.', 'warning');
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Dodawanie...');

        $.ajax({
            url: `/api/cities/${CITY_ID}/recommendations`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ title: newTitle, description: newDesc, model: 'manual', status: 'accepted' }),
            success: function(response) {
                showMessage('Rekomendacja została dodana.', 'success');
                const rec = response.data;
                // Dodaj nowy wiersz do tabeli
                const doneElementHtml = createDoneElementHtml(rec.id, rec.done);
                // Mapowanie statusu na polski
                let polishStatus = rec.status;
                switch (rec.status) {
                    case 'accepted': polishStatus = 'Zaakceptowana'; break;
                    case 'edited': polishStatus = 'Edytowana'; break;
                    case 'rejected': polishStatus = 'Odrzucona'; break;
                    case 'saved': polishStatus = 'Zapisana (nowa)'; break;
                    case 'done': polishStatus = 'Odwiedzona'; break;
                }
                const rowHtml = `
                    <tr data-rec-id="${rec.id}" data-title="${sanitizeHTML(rec.title)}" data-description="${sanitizeHTML(rec.description)}">
                        <td>${sanitizeHTML(rec.title)}</td>
                        <td>${rec.description.replace(/\n/g, '<br>')}</td>
                        <td class="text-center" style="white-space: nowrap;">${sanitizeHTML(rec.model)}</td>
                        <td class="text-center">${polishStatus}</td>
                        <td class="text-center">${doneElementHtml}</td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Akcje dla rekomendacji">
                                <button class="btn btn-sm btn-success accept-btn" data-id="${rec.id}" title="Akceptuj"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-danger reject-btn" data-id="${rec.id}" title="Odrzuć"><i class="fas fa-times"></i></button>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="${rec.id}" title="Edytuj"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-secondary delete-btn" data-id="${rec.id}" title="Usuń"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
                // Wstaw wiersz alfabetycznie
                let inserted = false;
                $('#recommendationsTable tbody tr').each(function() {
                    const currentTitle = $(this).find('td:first').text();
                    if (sanitizeHTML(rec.title).localeCompare(currentTitle) < 0) {
                        $(this).before(rowHtml);
                        inserted = true;
                        return false; // zakończ pętlę
                    }
                });
                if (!inserted) {
                    $('#recommendationsTable tbody').append(rowHtml); // Dodaj na końcu, jeśli większy od wszystkich
                }
                // Zamknij modal i przywróć przycisk
                bootstrap.Modal.getInstance($('#addRecModal')).hide();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd dodawania rekomendacji.';
                showMessage(msg, 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Dodaj');
            }
        });
    });

    // Obsługa kliknięcia na ikonę/przycisk "Odwiedzone"
    $(document).on('click', '.toggle-done-btn', function() {
        clearMessages();
        const $element = $(this);
        const recId = $element.data('rec-id');
        const currentStatus = $element.data('current-status') === true || $element.data('current-status') === 'true'; // Konwersja na boolean
        const newStatus = !currentStatus;

        console.log(`Toggling done status for recId: ${recId} from ${currentStatus} to ${newStatus}`);

        // Tymczasowa zmiana ikony na spinner
        $element.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.ajax({
            url: `/api/recommendations/${recId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ done: newStatus }),
            success: function(response) {
                showMessage('Status odwiedzenia został zaktualizowany.', 'success');
                // Aktualizuj ikonę i atrybut data
                const doneElementHtml = createDoneElementHtml(recId, newStatus);
                $element.replaceWith(doneElementHtml); // Zastąp cały element, aby poprawnie ustawić data
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Błąd aktualizacji statusu odwiedzenia.';
                showMessage(msg, 'danger');
                // Przywróć oryginalną ikonę w razie błędu
                const originalDoneHtml = createDoneElementHtml(recId, currentStatus);
                $element.replaceWith(originalDoneHtml);
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