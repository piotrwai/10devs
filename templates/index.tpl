{include file="header.tpl"}

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-4">Witaj w 10xCities</h1>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title h4 mb-4">Twój osobisty przewodnik po miastach świata</h2>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-map-marked-alt fa-3x mb-3 text-primary"></i>
                                <h3 class="h5">Inteligentne Rekomendacje</h3>
                                <p>Otrzymuj spersonalizowane propozycje atrakcji dopasowane do Twoich preferencji, wykorzystujące zaawansowaną sztuczną inteligencję.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-list-alt fa-3x mb-3 text-primary"></i>
                                <h3 class="h5">Zarządzanie Podróżami</h3>
                                <p>Twórz, edytuj i organizuj swoje listy miejsc do odwiedzenia. Oznaczaj odwiedzone miasta i śledź swoje podróżnicze osiągnięcia.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-lightbulb fa-3x mb-3 text-primary"></i>
                                <h3 class="h5">Personalizacja</h3>
                                <p>Dostosowuj rekomendacje do swoich potrzeb, dodawaj własne miejsca i dziel się swoimi doświadczeniami.</p>
                            </div>
                        </div>
                    </div>

                    <div class="about-section mb-4">
                        <h3 class="h4 mb-3">Dlaczego 10xCities?</h3>
                        <p>10xCities to innowacyjne narzędzie stworzone z myślą o podróżnikach, którzy cenią swój czas i chcą maksymalnie wykorzystać każdą podróż. Nasza platforma łączy w sobie zaawansowaną technologię sztucznej inteligencji z praktycznym podejściem do planowania podróży.</p>
                        
                        <p>Dzięki naszemu systemowi:</p>
                        <ul>
                            <li>Oszczędzasz czas na researchu - otrzymujesz gotowe, spersonalizowane rekomendacje</li>
                            <li>Masz pewność, że nie przeoczysz najważniejszych atrakcji w mieście</li>
                            <li>Możesz elastycznie planować swoje wycieczki, dodając własne miejsca i modyfikując propozycje</li>
                            <li>Śledzisz swoje podróże i masz dostęp do historii odwiedzonych miejsc</li>
                        </ul>
                    </div>

                    <div class="how-it-works mb-4">
                        <h3 class="h4 mb-3">Jak to działa?</h3>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="step text-center">
                                    <div class="step-number">1</div>
                                    <p>Zarejestruj się i podaj swoje miasto bazowe</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step text-center">
                                    <div class="step-number">2</div>
                                    <p>Wyszukaj interesujące Cię miasto</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step text-center">
                                    <div class="step-number">3</div>
                                    <p>Otrzymaj spersonalizowane rekomendacje</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step text-center">
                                    <div class="step-number">4</div>
                                    <p>Zarządzaj swoimi listami miejsc</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {if !$isLogged}
                    <div class="cta-section text-center mt-4">
                        <h3 class="h4 mb-3">Rozpocznij swoją podróż już dziś!</h3>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="/register" class="btn btn-primary btn-lg">Zarejestruj się</a>
                            <a href="/login" class="btn btn-outline-primary btn-lg">Zaloguj się</a>
                        </div>
                    </div>
                    {else}
                    <div class="cta-section text-center mt-4">
                        <h3 class="h4 mb-3">Kontynuuj swoją podróż!</h3>
                        <a href="/dashboard" class="btn btn-primary btn-lg">Przejdź do panelu</a>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

{include file="footer.tpl"} 