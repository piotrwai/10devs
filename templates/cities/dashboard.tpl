{* Szablon Smarty dla strony dashboardu *}

{include file="../header.tpl" title="Moje Miasta"}

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Moje Miasta</h1>
        <a href="/cities/search" class="btn btn-primary">Dodaj Nowe Miasto</a>
    </div>

    <div class="mb-3">
        <label for="visitedFilter" class="form-label">Filtr statusu:</label>
        <select id="visitedFilter" class="form-select" style="width: auto; display: inline-block;">
            <option value="" selected>Wszystkie</option>
            <option value="false">Nieodwiedzone</option>
            <option value="true">Odwiedzone</option>
        </select>
    </div>

    {* Kontener na komunikaty *}
    <div id="messageContainer" class="mb-3"></div>

    <table id="citiesTable" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Nazwa Miasta</th>
                <th>Liczba Rekomendacji / Odwiedzone</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            {* Wiersze tabeli będą dodawane dynamicznie przez JavaScript *}
            <tr>
                <td colspan="4" class="text-center">Ładowanie danych...</td>
            </tr>
        </tbody>
    </table>

    <div id="paginationControls" class="d-flex justify-content-center">
        {* Kontrolki paginacji będą generowane przez JavaScript *}
    </div>
</div>

    <!-- Modal Potwierdzenia Usunięcia -->
    <div class="modal fade" id="deleteCityModal" tabindex="-1" aria-labelledby="deleteCityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCityModalLabel">Potwierdź Usunięcie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Czy na pewno chcesz usunąć miasto <strong class="city-name-placeholder">[Nazwa Miasta]</strong>? Tej operacji nie można cofnąć.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Potwierdź</button>
                </div>
            </div>
        </div>
    </div>

<script>
    // Przekazanie zmiennej z PHP (Smarty) do JavaScriptu
    const MAX_CITIES_PER_PAGE = {$maxCitiesPerPage|default:10};
</script>

{* Dołączenie skryptu JS obsługującego dashboard *}
<script src="{'/js/cities/dashboard.js'|add_js_version}"></script>

{include file="../footer.tpl"}