{* Szablon Smarty dla strony edycji profilu użytkownika *}

{include file="header.tpl" title="Profil użytkownika"}

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2>Dane użytkownika</h2>
                </div>
                <div class="card-body">
                    <form id="profile-form">
                        {* Komunikaty formularza (sukces, błąd ogólny) *}
                        <div id="form-messages" class="mb-3"></div>
                        
                        {* Pole: Login użytkownika *}
                        <div class="form-group mb-3">
                            <label for="login">Login:</label>
                            <input type="text" class="form-control" id="login" name="login" value="{$currentUser.login|escape}" required>
                            <div class="invalid-feedback" id="login-error"></div>
                        </div>
                        
                        {* Pole: Miasto bazowe *}
                        <div class="form-group mb-3">
                            <label for="cityBase">Miasto bazowe:</label>
                            <input type="text" class="form-control" id="cityBase" name="cityBase" value="{$currentUser.cityBase|escape}" required>
                            <div class="invalid-feedback" id="cityBase-error"></div>
                        </div>
                        
                        {* Pole: Nowe hasło (opcjonalne) *}
                        <div class="form-group mb-3">
                            <label for="password">Nowe hasło (minimum 5 znaków):</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="invalid-feedback" id="password-error"></div>
                            <small class="form-text text-muted">Pozostaw puste, jeśli nie chcesz zmieniać hasła.</small>
                        </div>
                        
                        {* Pole: Potwierdzenie nowego hasła *}
                        <div class="form-group mb-3">
                            <label for="confirmPassword">Potwierdź nowe hasło:</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                            <div class="invalid-feedback" id="confirmPassword-error"></div>
                        </div>
                        
                        {* Informacja o statusie administratora (tylko informacyjnie) *}
                        <div class="form-group mb-3 is-admin">
                            <label>Status administratora:</label>
                            <div id="admin-status">{if $currentUser.isAdmin}Tak{else}Nie{/if}</div>
                        </div>
                        
                        {* Przycisk zapisania zmian *}
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="save-profile-btn">Zapisz zmiany</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{* Dołączenie skryptu JS obsługującego formularz profilu *}
<script src="{'/js/profile.js'|add_js_version}"></script>

{include file="footer.tpl"} 