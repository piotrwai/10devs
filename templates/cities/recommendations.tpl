{* Szablon Smarty dla strony listy rekomendacji miasta *}
{include file="../header.tpl" title="Rekomendacje dla `{$city.name}`"}

<div class="container mt-4" id="recommendationsContainer" data-city-id="{$city.id}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Rekomendacje: {$city.name|escape}</h1>
        <div class="no-print">
            <a href="/dashboard" class="btn btn-secondary" id="returnToCitiesBtn">Lista</a>
            <button class="btn btn-info ms-2 {if !$recommendations}disabled{/if}" onclick="{if $recommendations}window.print();{/if}" {if !$recommendations}disabled{/if}>Drukuj</button>
            <button class="btn btn-primary ms-2" id="addRecBtn" title="Dodaj rekomendację">Dodaj</button>
        </div>
    </div>

    {* Kontener na komunikaty *}
    <div id="messageContainer" class="mb-3 no-print"></div>

    {* Układ kartowy dla rekomendacji *}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        {foreach from=$recommendations item=rec}
            <div class="col">
                <div class="card h-100 recommendation-card" data-rec-id="{$rec.id}" data-title="{$rec.title|escape}" data-description="{$rec.description|escape}" data-status="{$rec.status}">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <h5 class="card-title mb-0">{$rec.title|escape}</h5>
                        <div class="status-badges">
                            <span class="badge bg-secondary me-1">{$rec.model|escape}</span>
                            <span class="badge {if $rec.status == 'accepted'}bg-success
                                {elseif $rec.status == 'edited'}bg-warning
                                {elseif $rec.status == 'rejected'}bg-danger
                                {elseif $rec.status == 'saved'}bg-info
                                {elseif $rec.status == 'done'}bg-primary
                                {else}bg-secondary{/if}">
                                {if $rec.status == 'accepted'}Zaakceptowana
                                {elseif $rec.status == 'edited'}Edytowana
                                {elseif $rec.status == 'rejected'}Odrzucona
                                {elseif $rec.status == 'saved'}Zapisana (nowa)
                                {elseif $rec.status == 'done'}Odwiedzona
                                {else}{$rec.status|escape}{/if}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="description-container">
                            {$rec.description|nl2br nofilter}
                        </div>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center no-print">
                        <div class="visited-status">
                            <span class="toggle-done-btn" data-rec-id="{$rec.id}" data-current-status="{$rec.done|default:false}" style="cursor: pointer;" title="Zmień status odwiedzenia">
                                {if $rec.done}
                                    <i class="fas fa-check-circle text-success"></i> Odwiedzone
                                {else}
                                    <i class="far fa-circle text-muted"></i> Do odwiedzenia
                                {/if}
                            </span>
                        </div>
                        <div class="btn-group" role="group" aria-label="Akcje dla rekomendacji">
                            <button class="btn btn-sm btn-success accept-btn" data-id="{$rec.id}" title="Akceptuj"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="{$rec.id}" title="Odrzuć"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-warning edit-btn" data-id="{$rec.id}" title="Edytuj"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-secondary delete-btn" data-id="{$rec.id}" title="Usuń"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    {* Informacja o odwiedzeniu widoczna tylko na wydruku *}
                    <div class="print-only visited-status mt-2">
                        Odwiedzona: {if $rec.done}Tak{else}Nie{/if}
                    </div>
                </div>
            </div>
        {foreachelse}
            <div class="col-12 text-center">
                <div class="alert alert-info">Brak rekomendacji dla tego miasta.</div>
            </div>
        {/foreach}
    </div>
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

{* Modal tworzenia rekomendacji - ukryty na wydruku *}
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