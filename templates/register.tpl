{* Szablon Smarty dla strony rejestracji *}

{include file="header.tpl" title="Rejestracja"}

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2>Rejestracja</h2>
                </div>
                <div class="card-body">
                    {if isset($errorMessage)}
                        <div class="alert alert-danger" role="alert">
                            {$errorMessage}
                        </div>
                    {/if}
                    
                    <form id="register-form">
                        {* Komunikaty formularza (sukces, błąd ogólny) *}
                        <div id="form-messages" class="mb-3"></div>
                        
                        {* Pole: Login użytkownika *}
                        <div class="form-group mb-3">
                            <label for="login">Login (2-50 znaków):</label>
                            <input type="text" class="form-control" id="login" name="login" required minlength="2" maxlength="50">
                            <div class="invalid-feedback" id="login-error">Login musi mieć od 2 do 50 znaków.</div>
                            <small class="form-text text-muted">Login musi być unikalny w systemie.</small>
                        </div>
                        
                        {* Pole: Hasło użytkownika *}
                        <div class="form-group mb-3">
                            <label for="password">Hasło (minimum 5 znaków):</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="5">
                            <div class="invalid-feedback" id="password-error">Hasło musi mieć minimum 5 znaków.</div>
                        </div>
                        
                        {* Pole: Potwierdzenie hasła *}
                        <div class="form-group mb-3">
                            <label for="confirm-password">Potwierdź hasło:</label>
                            <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
                            <div class="invalid-feedback" id="confirm-password-error">Hasła muszą być identyczne.</div>
                        </div>
                        
                        {* Pole: Miasto bazowe *}
                        <div class="form-group mb-3">
                            <label for="cityBase">Miasto bazowe (3-150 znaków):</label>
                            <input type="text" class="form-control" id="cityBase" name="cityBase" required minlength="3" maxlength="150">
                            <div class="invalid-feedback" id="cityBase-error">Miasto bazowe musi mieć od 3 do 150 znaków.</div>
                            <small class="form-text text-muted">Podaj miasto, które będzie Twoim punktem startowym.</small>
                        </div>
                        
                        {* Przycisk rejestracji *}
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="register-btn">
                                <span class="spinner-border spinner-border-sm d-none btn-spinner" role="status" aria-hidden="true"></span>
                                Zarejestruj się
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <p>Masz już konto? <a href="/login">Zaloguj się</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Dołączenie skryptu JS obsługującego formularz rejestracji *}
<script src="{'/js/register.js'|add_js_version}"></script>

{include file="footer.tpl"} 