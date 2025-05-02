{* Szablon Smarty dla strony wyszukiwania miast *}

{include file="../header.tpl" title="Wyszukaj miasto"}

<style>
    [contenteditable="true"] {
        border: 1px dashed #ccc;
        padding: 5px;
        border-radius: 4px;
        min-height: 20px;
    }
    
    [contenteditable="true"]:focus {
        outline: none;
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    
    .recommendation.editing {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .recommendation.editing .card-body {
        background-color: #f8f9fa;
    }
    
    .card-title {
        margin-right: 10px;
        flex: 1;
    }
    
    .btn-group {
        flex-shrink: 0;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4">Wyszukaj miasto</h1>
            
            <!-- Formularz wyszukiwania -->
            <form id="city-search-form" class="mb-5">
                <div class="form-group mb-3">
                    <label for="cityName" class="form-label">Nazwa miasta</label>
                    <input type="text" class="form-control" id="cityName" name="cityName" required 
                           placeholder="Wprowadź nazwę miasta" maxlength="150">
                    <div class="invalid-feedback" id="cityName-error"></div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="search-btn">
                    <span class="spinner-border spinner-border-sm d-none btn-spinner" role="status" aria-hidden="true"></span>
                    Wyszukaj atrakcje
                </button>
            </form>
            
            <!-- Komunikaty -->
            <div id="form-messages" class="mb-4"></div>
            
            <!-- Wyniki wyszukiwania -->
            <div id="search-results" class="d-none">
                <!-- Podsumowanie miasta -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title city-name"></h2>
                        <p class="card-text city-summary"></p>
                    </div>
                </div>
                
                <!-- Lista rekomendacji -->
                <h3 class="mb-3">Rekomendowane atrakcje</h3>
                <div id="recommendations-list"></div>
                
                <!-- Przyciski akcji -->
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-success" id="accept-all-btn">
                        Akceptuj wszystkie
                    </button>
                    <button class="btn btn-primary" id="save-recommendations-btn">
                        Zapisz wybrane
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{* Dołączenie skryptu JS obsługującego wyszukiwanie *}
<script src="{'/js/cities/search.js'|add_js_version}"></script>

{include file="../footer.tpl"} 