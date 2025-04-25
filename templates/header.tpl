<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title|default:"10x-city"}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dodanie tokenu JWT -->
    <script>sessionStorage.setItem('jwtToken', '{$jwtToken}');</script>
    
    <!-- Własne style CSS -->
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Główna nawigacja -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">10x-city</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/search">Wyszukiwanie rekomendacji</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {if $smarty.server.REQUEST_URI == '/profile'}active{/if}" href="/profile">Profil</a>
                    </li>
                    {if isset($currentUser) && $currentUser.isAdmin}
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/logs">Logi</a>
                    </li>
                    {/if}
                </ul>
                <div class="d-flex">
                    {if isset($currentUser)}
                        <span class="navbar-text me-3">Witaj, {$currentUser.login}!</span>
                        <a href="/logout" class="btn btn-outline-light">Wyloguj</a>
                    {else}
                        <a href="/login" class="btn btn-outline-light">Logowanie</a>
                    {/if}
                </div>
            </div>
        </div>
    </nav>

    <!-- Główna zawartość strony -->
    <main class="py-4"> 