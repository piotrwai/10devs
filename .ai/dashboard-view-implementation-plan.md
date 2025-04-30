# Plan implementacji widoku Dashboard Miast Użytkownika

## 1. Przegląd
Widok Dashboard (`/dashboard`) jest głównym ekranem po zalogowaniu użytkownika (chyba że nie ma żadnych miast, wtedy przekierowanie na `/cities/search`). Prezentuje listę zapisanych przez użytkownika miast wraz z liczbą rekomendacji dla każdego z nich. Umożliwia nawigację do szczegółowego widoku miasta, usunięcie miasta (po potwierdzeniu) oraz przejście do widoku wyszukiwania w celu dodania nowego miasta. Widok wyświetla również status "Odwiedzone" dla odpowiednich miast. Implementuje filtrowanie po statusie "Odwiedzone" oraz paginację po stronie serwera.

## 2. Routing widoku
Widok powinien być dostępny pod ścieżką `/dashboard`. Dostęp do tej ścieżki wymaga autoryzacji użytkownika (posiadania ważnego tokena JWT).

## 3. Struktura komponentów
Struktura oparta będzie na szablonie Smarty (`.tpl`), stylach Bootstrap i logice obsługiwanej przez jQuery.

```
dashboard.tpl
├── Nagłówek (np. <h1>Moje Miasta</h1>)
├── Przycisk "Dodaj Nowe Miasto" (link/przycisk do /cities/search)
├── Filtry (select do filtrowania wg statusu "Wszystkie/Nieodwiedzone/Odwiedzone")
└── Lista Miast (implementowana jako standardowa tabela HTML `<table>`, domyślne sortowanie po stronie serwera)
│   └── Wiersze Miast (`<tr>`) - generowane dynamicznie (przez Smarty lub jQuery)
│       ├── Komórka Nazwy Miasta (`<td>`) - zawiera link do `/city/{id}`
│       ├── Komórka Liczby Rekomendacji (`<td>`)
│       ├── Komórka Statusu "Odwiedzone" (`<td>`) - wskaźnik wizualny
│       └── Komórka Akcji (`<td>`)
│           ├── Przycisk "Usuń" (`<button class="btn btn-danger btn-sm delete-city-btn">`) - wywołuje modal potwierdzający
│           └── Przycisk "Rekomendacje" (`<a href="/city/{id}/recommendations" class="btn btn-info btn-sm">`) - przejście do widoku rekomendacji dla miasta
└── Kontrolki Paginacji (np. Przyciski "Poprzednia", "Następna", wskaźnik strony)

(Poza główną strukturą, inicjalizowany przez JS)
└── Modal Potwierdzenia Usunięcia (np. Bootstrap Modal lub jQuery Modal)
    ├── Tekst potwierdzenia
    ├── Przycisk "Potwierdź"
    └── Przycisk "Anuluj"
```

## 4. Szczegóły komponentów

### `DashboardView` (Główny szablon `dashboard.tpl` i powiązany JS)
-   **Opis komponentu:** Główny kontener strony `/dashboard`. Odpowiada za ogólny układ, wyświetlenie tytułu, przycisku dodawania miasta, filtrów, listy miast oraz kontrolek paginacji. Inicjalizuje pobieranie danych i obsługę zdarzeń.
-   **Główne elementy:** Kontener Bootstrap (`div.container`), nagłówek (`h1`), przycisk (`a.btn` lub `button.btn`), filtr (`select`), tabela (`table.table`), kontrolki paginacji (`div.pagination-controls`).
-   **Obsługiwane interakcje:** Inicjalizacja pobrania danych przy ładowaniu strony.
-   **Obsługiwana walidacja:** Brak bezpośredniej walidacji; polega na danych z API.
-   **Props:** Dane miast przekazywane z backendu (PHP) do Smarty lub ładowane przez Ajax. Informacje o paginacji (aktualna strona, łączna liczba stron/elementów).

### `CityList` (Tabela HTML)
-   **Opis komponentu:** Tabela (`<table class="table table-striped table-bordered">`) wyświetlająca listę miast użytkownika dla bieżącej strony.
-   **Główne elementy:** `<thead>` z nagłówkami kolumn (Nazwa, Rekomendacje, Status, Akcje), `<tbody>` wypełniany dynamicznie danymi miast. Nagłówki mogą mieć event listenery do sortowania (jeśli implementowane po stronie klienta, inaczej sortowanie serwerowe).
-   **Obsługiwane interakcje:** Kliknięcie przycisku "Usuń" w wierszu (delegacja zdarzeń), kliknięcie linku "Rekomendacje".
-   **Obsługiwana walidacja:** Brak.
-   **Props:** Tablica obiektów miast z API (`[{id, name, recommendationCount, visited}, ...]`).

### `CityListItem` (Wiersz tabeli `<tr>`)
-   **Opis komponentu:** Reprezentuje pojedynczy wiersz w tabeli `CityList`, wyświetlając dane jednego miasta i przyciski akcji.
-   **Główne elementy:** Komórki tabeli (`<td>`). Jedna zawiera link (`<a>`) z nazwą miasta do `/city/{id}`, inne wyświetlają `recommendationCount` i status `visited`. Ostatnia zawiera przyciski "Usuń" i "Rekomendacje". Atrybut `data-city-id` na wierszu `<tr>` lub przycisku "Usuń" przechowuje ID miasta.
-   **Obsługiwane interakcje:** Kliknięcie nazwy miasta (nawigacja), kliknięcie przycisku "Usuń" (wywołuje modal), kliknięcie przycisku "Rekomendacje" (nawigacja).
-   **Obsługiwana walidacja:** Brak.
-   **Props:** Obiekt `city` (`{id, name, recommendationCount, visited}`).

### `AddCityButton`
-   **Opis komponentu:** Przycisk (`<a href="/cities/search" class="btn btn-primary">`) lub (`<button>`), który przekierowuje użytkownika do widoku wyszukiwania (`/cities/search`), aby mógł znaleźć i dodać nowe miasto.
-   **Główne elementy:** Element `<a>` lub `<button>` z odpowiednimi klasami Bootstrap.
-   **Obsługiwane interakcje:** Kliknięcie przycisku.
-   **Obsługiwana walidacja:** Brak.
-   **Props:** Brak.

### `DeleteConfirmationModal` (Bootstrap Modal / jQuery Modal)
-   **Opis komponentu:** Modal dialogowy wyświetlany po kliknięciu przycisku "Usuń" przy mieście. Prosi użytkownika o potwierdzenie operacji usunięcia. Przechowuje `cityId` miasta do usunięcia (np. w `data` atrybucie modala).
-   **Główne elementy:** Tytuł modala, tekst potwierdzenia (np. "Czy na pewno chcesz usunąć miasto [Nazwa Miasta]?"), przycisk "Potwierdź" (`button.btn-danger`), przycisk "Anuluj" (`button.btn-secondary`).
-   **Obsługiwane interakcje:** Kliknięcie "Potwierdź" (wywołuje API DELETE), kliknięcie "Anuluj" (zamyka modal).
-   **Obsługiwana walidacja:** Brak.
-   **Props:** `cityId`, `cityName` (do wyświetlenia w komunikacie).

### `PaginationControls`
-   **Opis komponentu:** Zestaw przycisków i informacji umożliwiających nawigację między stronami listy miast.
-   **Główne elementy:** Przyciski "Poprzednia", "Następna", wskaźnik obecnej strony (np. "Strona X z Y"). Przyciski mogą być wyłączone (`disabled`), jeśli nie ma poprzedniej/następnej strony.
-   **Obsługiwane interakcje:** Kliknięcie przycisku "Poprzednia" lub "Następna" (wywołuje przeładowanie danych dla nowej strony).
-   **Obsługiwana walidacja:** Logika włączania/wyłączania przycisków na podstawie bieżącej strony i całkowitej liczby stron.
-   **Props:** `currentPage`, `totalPages` (lub `totalItems` i `perPage`).

### `VisitedFilter`
-   **Opis komponentu:** Element `select` pozwalający użytkownikowi filtrować listę miast według statusu odwiedzenia.
-   **Główne elementy:** `<select>` z opcjami: "Wszystkie", "Nieodwiedzone", "Odwiedzone".
-   **Obsługiwane interakcje:** Zmiana wybranej opcji (`change` event) - wywołuje przeładowanie danych z odpowiednim parametrem `visited`.
-   **Obsługiwana walidacja:** Brak.
-   **Props:** Aktualnie wybrany filtr (do ustawienia wartości początkowej).

## 6. Zarządzanie stanem
Ze względu na użycie jQuery i Smarty, zarządzanie stanem będzie proste i oparte głównie na DOM oraz zmiennych JavaScript:
-   Lista miast dla bieżącej strony będzie przechowywana w zmiennej JavaScript po pobraniu z API (jeśli ładowana przez Ajax) lub bezpośrednio renderowana przez Smarty.
-   Aktualna strona (`currentPage`), wybrany filtr (`currentFilter`) i ewentualnie liczba elementów na stronę (`perPage`) będą przechowywane w zmiennych JavaScript.
-   ID miasta do usunięcia będzie tymczasowo przechowywane (np. w `data` atrybucie modala lub zmiennej JS) podczas procesu potwierdzania usunięcia.
-   Nie przewiduje się potrzeby tworzenia customowych hooków ani złożonych mechanizmów zarządzania stanem.

## 7. Integracja API

-   **Pobieranie listy miast:**
    -   Endpoint: `GET /api/cities`
    -   Typ żądania: `GET`
    -   Parametry:
        -   `page` (wymagane dla paginacji, np. `1`): Numer strony.
        -   `per_page` (opcjonalne, np. `10`): Liczba miast na stronę. Backend powinien mieć domyślną wartość.
        -   `visited` (opcjonalne): `true` (odwiedzone), `false` (nieodwiedzone), brak parametru (wszystkie).
    -   Typ odpowiedzi (Success 200 OK): `application/json` - obiekt zawierający listę miast dla strony i informacje o paginacji, np.:
        ```json
        {
          "data": [
            { "id": number, "name": string, "recommendationCount": number, "visited": boolean },
            ...
          ],
          "pagination": {
            "currentPage": number,
            "totalPages": number,
            "totalItems": number,
            "perPage": number
          }
        }
        ```
    -   Wywołanie: Przy ładowaniu strony (`$(document).ready()`), po zmianie strony lub zmianie filtra, za pomocą `$.ajax()` lub `fetch()`. Dane z `data` zostaną użyte do wypełnienia tabeli (`<tbody>`). Dane z `pagination` do aktualizacji kontrolek paginacji. Wymaga nagłówka `Authorization: Bearer <token>`.
-   **Usuwanie miasta:**
    -   Endpoint: `DELETE /api/cities/{cityId}` (**Uwaga:** Ten endpoint musi zostać dodany do API Planu i zaimplementowany w backendzie).
    -   Typ żądania: `DELETE`
    -   Parametry: `cityId` w ścieżce URL.
    -   Typ odpowiedzi (Success 204 No Content): Brak zawartości.
    -   Typ odpowiedzi (Error 404 Not Found): Miasto nie istnieje.
    -   Wywołanie: Po kliknięciu "Potwierdź" w modalu, używając `$.ajax()` lub `fetch()`. Wymaga nagłówka `Authorization: Bearer <token>`. Po sukcesie, wiersz miasta zostanie usunięty z tabeli (np. przez bezpośrednią manipulację DOM).

## 8. Interakcje użytkownika
-   **Ładowanie widoku:** Użytkownik przechodzi na `/dashboard`. Skrypt JS wysyła żądanie `GET /api/cities` (dla strony 1, bez filtra `visited`). Po otrzymaniu odpowiedzi, lista miast jest renderowana w tabeli, aktualizowane są kontrolki paginacji.
-   **Kliknięcie nazwy miasta:** Użytkownik klika link z nazwą miasta. Następuje przekierowanie na stronę `/city/{id}` danego miasta.
-   **Kliknięcie przycisku "Rekomendacje":** Użytkownik klika przycisk. Następuje przekierowanie na stronę `/city/{id}`.
-   **Kliknięcie "Dodaj Nowe Miasto":** Użytkownik klika przycisk. Następuje przekierowanie na stronę `/cities/search`.
-   **Zmiana filtra "Odwiedzone":** Użytkownik wybiera nową opcję w `select`. Skrypt JS odczytuje nową wartość filtra, ustawia `currentPage` na 1 i wysyła nowe żądanie `GET /api/cities` z odpowiednim parametrem `visited` i `page=1`. Tabela i paginacja są aktualizowane.
-   **Zmiana strony (Paginacja):** Użytkownik klika przycisk "Następna" lub "Poprzednia". Skrypt JS aktualizuje `currentPage` i wysyła nowe żądanie `GET /api/cities` z nowym numerem strony i bieżącym filtrem `visited`. Tabela i paginacja są aktualizowane.
-   **Kliknięcie przycisku "Usuń":**
    1.  Skrypt JS odczytuje `data-city-id` i `data-city-name` z klikniętego przycisku lub jego wiersza nadrzędnego.
    2.  Wyświetlany jest `DeleteConfirmationModal`, przekazując `cityId` i `cityName`.
-   **Kliknięcie "Anuluj" w modalu:** Modal jest zamykany, brak dalszych akcji.
-   **Kliknięcie "Potwierdź" w modalu:**
    1.  Skrypt JS wysyła żądanie `DELETE /api/cities/{cityId}` używając zapisanego `cityId`.
    3.  Po otrzymaniu odpowiedzi 204: Modal jest zamykany, **dane dla bieżącej strony są ponownie ładowane** (wysyłane jest żądanie `GET /api/cities` z `currentPage` i `currentFilter`), aby odświeżyć listę i paginację, wyświetlany jest komunikat o sukcesie (np. toast/alert).
    4.  Po otrzymaniu błędu (np. 404, 500): Modal jest zamykany, wyświetlany jest komunikat o błędzie.
-   **Sortowanie (jeśli implementowane po stronie klienta):** Użytkownik klika nagłówek kolumny. Skrypt JS sortuje istniejące wiersze w `<tbody>` i aktualizuje widok tabeli.

## 9. Warunki i walidacja
-   **Dostęp:** Widok dostępny tylko dla zalogowanych użytkowników (backend powinien weryfikować token JWT przy każdym żądaniu API). Jeśli użytkownik nie jest zalogowany, powinien zostać przekierowany na stronę logowania.
-   **Usuwanie:** Operacja usunięcia miasta wymaga potwierdzenia przez użytkownika w modalu, aby zapobiec przypadkowemu usunięciu danych.
-   **Dane wejściowe API:** Nie ma bezpośrednich pól formularzy w tym widoku. Walidacja dotyczy głównie poprawności `cityId` przekazywanego do API (co jest zapewnione przez pobranie go z listy istniejących miast).
-   **Paginacja:** Przyciski paginacji ("Poprzednia", "Następna") powinny być wyłączone (`disabled`), jeśli użytkownik jest odpowiednio na pierwszej lub ostatniej stronie.

## 10. Obsługa błędów
-   **Błąd ładowania listy miast (`GET /api/cities`):** Jeśli żądanie API zawiedzie (np. błąd serwera 500, brak autoryzacji 401/403), zamiast tabeli należy wyświetlić komunikat błędu, np. "Wystąpił błąd podczas ładowania listy miast. Prosimy spróbować ponownie później." W przypadku błędu 401/403, przekierować użytkownika do strony logowania.
-   **Błąd usuwania miasta (`DELETE /api/cities/{cityId}`):** Jeśli żądanie API zawiedzie (np. 404 - miasto nie znalezione, 500 - błąd serwera), należy wyświetlić stosowny komunikat błędu użytkownikowi i nie usuwać wiersza z tabeli. Np. "Nie udało się usunąć miasta. Spróbuj ponownie."
-   **Pusta lista miast (dla strony/filtra):** Jeśli API zwróci pustą tablicę `data` (np. dla danego filtra lub strony nie ma miast), należy wyczyścić `<tbody>` tabeli i wyświetlić komunikat informacyjny, np. "Nie znaleziono miast spełniających kryteria." lub "Nie masz jeszcze żadnych zapisanych miast. Kliknij 'Dodaj Nowe Miasto', aby rozpocząć!" (jeśli ogólnie brak miast). Należy również odpowiednio zaktualizować/ukryć kontrolki paginacji.
-   **Komunikaty:** Wszystkie komunikaty dla użytkownika (błędy, sukcesy, potwierdzenia) powinny być w języku polskim i wyświetlane w sposób nieinwazyjny (np. Bootstrap Alerts, Toastr.js).

## 11. Kroki implementacji
1.  **Utworzenie szablonu Smarty (`dashboard.tpl`):**
    -   Zdefiniuj podstawową strukturę HTML strony z użyciem kontenerów Bootstrap.
    -   Dodaj nagłówek `<h1>Moje Miasta</h1>`.
    -   Dodaj przycisk/link "Dodaj Nowe Miasto" (`<a href="/cities/search" class="btn btn-primary">Dodaj Nowe Miasto</a>`).
    -   Dodaj element `select` dla filtra `VisitedFilter`.
    -   Dodaj pustą strukturę tabeli HTML (`<table id="citiesTable" class="table table-striped table-bordered" style="width:100%">`) z `<thead>` zawierającym odpowiednie nagłówki (Nazwa, Rekomendacje, Status, Akcje) i pustym `<tbody>`.
    -   Dodaj kontener dla kontrolek paginacji (`<div id="paginationControls">`).
    -   Dodaj strukturę HTML dla modala potwierdzenia usunięcia (np. Bootstrap Modal) z ID (np. `deleteCityModal`), miejscem na nazwę miasta, oraz przyciskami "Potwierdź" i "Anuluj".
2.  **Backend (PHP - Kontroler/Logika):**
    -   Zapewnij routing dla ścieżki `/dashboard`.
    -   Implementuj logikę sprawdzającą autoryzację użytkownika.
    -   *Opcja 2 (Renderowanie po stronie klienta):* Przygotuj pustą stronę (szablon) i pozwól JS załadować dane.
3.  **Frontend (JavaScript - `dashboard.js`):**
    -   Zdefiniuj zmienne stanu: `let currentPage = 1;`, `let currentVisitedFilter = null;` (`null` dla 'Wszystkie', `true` dla 'Odwiedzone', `false` dla 'Nieodwiedzone'), `let perPage = 10;` (lub pobierz z konfiguracji).
    -   Utwórz funkcję `loadCities(page, visitedFilter)`:
        -   Wykonuje żądanie Ajax (`$.ajax`) do `GET /api/cities` z parametrami `page`, `per_page` (zmienna globalna lub stała) i `visited` (jeśli `visitedFilter !== null`).
        -   W callbacku sukcesu:
            -   Odczytaj `data` i `pagination` z odpowiedzi.
            -   Wyczyść `<tbody>` tabeli `#citiesTable`.
            -   Jeśli `data` jest pusta, pokaż komunikat "Brak miast...".
            -   Dla każdego miasta w `data`, dodaj wiersz (`<tr>`) do `<tbody>`, wypełniając komórki (`<td>`) danymi (`name` jako link do `/city/{id}`, `recommendationCount`, `visited`, przyciski "Usuń" z `data-city-id`, `data-city-name` i "Rekomendacje" jako link do `/city/{id}/recommendations`).
            -   Zaktualizuj `currentPage` (na podstawie odpowiedzi `pagination.currentPage`).
            -   Wywołaj funkcję `updatePaginationControls(pagination.currentPage, pagination.totalPages)`.
        -   W callbacku błędu: Pokaż komunikat o błędzie ładowania.
    -   Utwórz funkcję `updatePaginationControls(currentPage, totalPages)`:
        -   Wyczyść kontener `#paginationControls`.
        -   Dodaj HTML dla przycisków "Poprzednia", "Następna" i wskaźnika strony (np. "Strona X z Y").
        -   Wyłącz przycisk "Poprzednia", jeśli `currentPage <= 1`.
        -   Wyłącz przycisk "Następna", jeśli `currentPage >= totalPages`.
        -   Dodaj event listenery do przycisków paginacji, które wywołują `loadCities()` z odpowiednio `currentPage - 1` lub `currentPage + 1` i `currentVisitedFilter`.
    -   Użyj `$(document).ready()` do inicjalizacji:
        -   Wywołaj `loadCities(currentPage, currentVisitedFilter)` po raz pierwszy.
        -   Dodaj event listener `change` do filtra `#visitedFilter`:
            -   Odczytaj nową wartość filtra.
            -   Zaktualizuj `currentVisitedFilter`.
            -   Wywołaj `loadCities(1, currentVisitedFilter)` (resetuj do strony 1 po zmianie filtra).
    -   **Obsługa kliknięcia "Usuń":** Użyj delegacji zdarzeń jQuery do obsługi kliknięć na przyciskach `.delete-city-btn` wewnątrz tabeli.
        ```javascript
        $('#citiesTable tbody').on('click', '.delete-city-btn', function() {
            const cityId = $(this).data('city-id');
            const cityName = $(this).data('city-name'); // Pobierz nazwę miasta
            // Wypełnij i pokaż modal #deleteCityModal
            $('#deleteCityModal').data('city-id', cityId); // Zapisz ID w modalu
            $('#deleteCityModal .city-name-placeholder').text(cityName); // Wstaw nazwę miasta
            $('#deleteCityModal').modal('show');
        });
        ```
    -   **Obsługa potwierdzenia usunięcia:** Dodaj obsługę kliknięcia przycisku "Potwierdź" w modalu.
        ```javascript
        $('#confirmDeleteBtn').on('click', function() {
            const cityId = $('#deleteCityModal').data('city-id');
            // Pokaż wskaźnik ładowania
            $(this).prop('disabled', true).text('Usuwanie...');

            $.ajax({
                url: `/api/cities/${cityId}`, // Użyj poprawnego endpointu
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + your_jwt_token }, // Dodaj token JWT
                success: function() {
                    $('#deleteCityModal').modal('hide');
                    // Przeładuj bieżącą stronę
                    loadCities(currentPage, currentVisitedFilter);
                    // Pokaż komunikat sukcesu
                    showSuccessMessage('Miasto zostało pomyślnie usunięte.');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Pokaż komunikat błędu
                    showErrorMessage('Nie udało się usunąć miasta. Spróbuj ponownie.');
                    console.error("Delete error:", textStatus, errorThrown);
                },
                complete: function() {
                    // Przywróć przycisk w modalu
                    $('#confirmDeleteBtn').prop('disabled', false).text('Potwierdź');
                }
            });
        });
        ```
    -   **Implementacja funkcji pomocniczych:** `showSuccessMessage`, `showErrorMessage` (np. używając Bootstrap Alerts).
    -   **Obsługa tokena JWT:** Upewnij się, że token JWT jest poprawnie pobierany (np. z `localStorage` lub `sessionStorage`) i dołączany do wszystkich żądań Ajax w nagłówku `Authorization`.
4.  **Styling (CSS/SCSS):**
    -   Dodaj ewentualne niestandardowe style CSS do poprawy wyglądu tabeli, przycisków, filtra, kontrolek paginacji lub modala, jeśli standardowe style Bootstrap są niewystarczające.
    -   Zdefiniuj styl dla wskaźnika statusu "Odwiedzone" (np. kolor tekstu, ikona).
5.  **Testowanie:**
    -   Przetestuj ładowanie listy miast (również pustej).
    -   Przetestuj działanie paginacji (przyciski, wskaźnik strony, wyłączanie przycisków).
    -   Przetestuj działanie filtrowania (zmiana opcji, aktualizacja listy, reset paginacji do strony 1).
    -   Przetestuj proces usuwania miasta (wyświetlanie modala, potwierdzenie, anulowanie, odświeżenie listy po sukcesie, obsługa błędów API).
    -   Sprawdź responsywność widoku na różnych rozmiarach ekranu.
    -   Przetestuj nawigację do widoku szczegółów miasta, widoku rekomendacji i do widoku dodawania miasta.
    -   Sprawdź poprawność wyświetlania statusu "Odwiedzone".

</rewritten_file> 