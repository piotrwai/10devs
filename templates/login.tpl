{* Szablon Smarty dla strony logowania *}

{include file="header.tpl" title="Logowanie"}

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h2>Logowanie</h2>
                </div>
                <div class="card-body">
                    {if isset($successMessage)}
                        <div class="alert alert-success" role="alert">
                            {$successMessage}
                        </div>
                    {/if}
                    
                    {if isset($errorMessage)}
                        <div class="alert alert-danger" role="alert">
                            {$errorMessage}
                        </div>
                    {/if}
                    
                    <form id="login-form">
                        {* Komunikaty formularza (sukces, błąd ogólny) *}
                        <div id="form-messages" class="mb-3"></div>
                        
                        {* Pole: Login użytkownika *}
                        <div class="form-group mb-3">
                            <label for="login">Login:</label>
                            <input type="text" class="form-control" id="login" name="login" required>
                            <div class="invalid-feedback" id="login-error">Proszę podać login.</div>
                        </div>
                        
                        {* Pole: Hasło użytkownika *}
                        <div class="form-group mb-3">
                            <label for="password">Hasło:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback" id="password-error">Proszę podać hasło.</div>
                        </div>
                        
                        {* Przycisk logowania *}
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="login-btn">
                                <span class="spinner-border spinner-border-sm d-none btn-spinner" role="status" aria-hidden="true"></span>
                                Zaloguj się
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <p>Nie masz jeszcze konta? <a href="/register">Zarejestruj się</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Dołączenie skryptu JS obsługującego formularz logowania *}
<script src="{'/js/login.js'|add_js_version}"></script>

{include file="footer.tpl"} 