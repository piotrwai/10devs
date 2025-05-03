{* Szablon Smarty dla strony listy rekomendacji miasta *}
{include file="../header.tpl" title="Rekomendacje dla `{$city.name}`"}

<div class="container mt-4" id="recommendationsContainer" data-city-id="{$city.id}">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h1>Rekomendacje: {$city.name|escape}</h1>
        <div>
            <a href="/dashboard" class="btn btn-secondary" id="returnToCitiesBtn">Lista</a>
            <button class="btn btn-info ms-2 {if !$recommendations}disabled{/if}" onclick="{if $recommendations}window.print();{/if}" {if !$recommendations}disabled{/if}>Drukuj</button>
            <button class="btn btn-primary ms-2" id="addRecBtn" title="Dodaj rekomendację">Dodaj</button>
        </div>
    </div>

    {* Kontener na komunikaty *}
    <div id="messageContainer" class="mb-3 no-print"></div>

    <table id="recommendationsTable" class="table table-striped">
        <thead>
            <tr>
                <th>Tytuł</th>
                <th>Opis</th>
                <th class="no-print">Model</th>
                <th class="no-print">Status</th>
                <th class="no-print">Odwiedzone</th>
                <th class="no-print">Akcje</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$recommendations item=rec}
                <tr data-rec-id="{$rec.id}" data-title="{$rec.title|escape}" data-description="{$rec.description|escape}" data-status="{$rec.status}" class="recommendation-row">
                    <td>{$rec.title|escape}</td>
                    <td>{$rec.description|nl2br nofilter}</td>
                    <td class="text-center no-print" style="white-space: nowrap;">{$rec.model|escape}</td>
                    <td class="text-center no-print">
                        {if $rec.status == 'accepted'}Zaakceptowana
                        {elseif $rec.status == 'edited'}Edytowana
                        {elseif $rec.status == 'rejected'}Odrzucona
                        {elseif $rec.status == 'saved'}Zapisana (nowa)
                        {elseif $rec.status == 'done'}Odwiedzona
                        {else}{$rec.status|escape}{/if}
                    </td>
                    <td class="text-center no-print">
                        <span class="toggle-done-btn" data-rec-id="{$rec.id}" data-current-status="{$rec.done|default:false}" style="cursor: pointer;" title="Zmień status odwiedzenia">
                            {if $rec.done}
                                <i class="fas fa-check-circle text-success"></i>
                            {else}
                                <i class="far fa-circle text-muted"></i>
                            {/if}
                        </span>
                    </td>
                    <td class="no-print">
                        <div class="btn-group" role="group" aria-label="Akcje dla rekomendacji">
                            <button class="btn btn-sm btn-success accept-btn" data-id="{$rec.id}" title="Akceptuj"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="{$rec.id}" title="Odrzuć"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-warning edit-btn" data-id="{$rec.id}" title="Edytuj"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-secondary delete-btn" data-id="{$rec.id}" title="Usuń"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                    {* Dodajemy ukryty wiersz ze statusem odwiedzenia, który będzie widoczny tylko na wydruku *}
                    <td class="print-only visited-status">
                        Odwiedzona: {if $rec.done}Tak{else}Nie{/if}
                    </td>
                </tr>
            {/foreach}
            {if !$recommendations}
                <tr><td colspan="6" class="text-center">Brak rekomendacji dla tego miasta.</td></tr>
            {/if}
        </tbody>
    </table>
</div>

{* Modal edycji rekomendacji - ukryty na wydruku *}
<div class="modal fade no-print" id="editRecModal" tabindex="-1" aria-labelledby="editRecModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRecModalLabel">Edytuj rekomendację</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <form id="editRecForm">
                    <div class="mb-3">
                        <label for="recTitle" class="form-label">Tytuł</label>
                        <input type="text" class="form-control" id="recTitle" name="title" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label for="recDesc" class="form-label">Opis</label>
                        <textarea class="form-control" id="recDesc" name="description" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" id="saveRecBtn">Zapisz zmiany</button>
            </div>
        </div>
    </div>
</div>

{* Modal potwierdzenia usunięcia rekomendacji - ukryty na wydruku *}
<div class="modal fade no-print" id="deleteRecModal" tabindex="-1" aria-labelledby="deleteRecModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRecModalLabel">Usuń rekomendację</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                Czy na pewno chcesz usunąć tę rekomendację?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Nie</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRecBtn">Tak, usuń</button>
            </div>
        </div>
    </div>
</div>

{* Dodaj modal tworzenia rekomendacji - ukryty na wydruku *}
<div class="modal fade no-print" id="addRecModal" tabindex="-1" aria-labelledby="addRecModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRecModalLabel">Nowa rekomendacja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <form id="addRecForm">
                    <div class="mb-3">
                        <label for="newRecTitle" class="form-label">Tytuł</label>
                        <input type="text" class="form-control" id="newRecTitle" name="title" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label for="newRecDesc" class="form-label">Opis</label>
                        <textarea class="form-control" id="newRecDesc" name="description" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-success" id="createRecBtn">Dodaj</button>
            </div>
        </div>
    </div>
</div>

<script src="{'/js/cities/recommendations.js'|add_js_version}"></script>
{include file="../footer.tpl"} 