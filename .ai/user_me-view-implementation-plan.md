# Plan implementacji widoku Dane Użytkownika (/profile)

## 1. Przegląd
Widok "Dane Użytkownika" umożliwia zalogowanemu użytkownikowi przeglądanie i modyfikację swoich danych profilowych: loginu, hasła oraz miasta bazowego. Zapewnia interfejs do aktualizacji tych informacji zgodnie z wymaganiami aplikacji.

## 2. Routing widoku
Widok powinien być dostępny pod ścieżką `/profile`. Dostęp do tej ścieżki powinien wymagać uwierzytelnienia użytkownika.

## 3. Struktura komponentów
Ze względu na stack technologiczny (PHP, Smarty, jQuery), nie będziemy tworzyć komponentów w rozumieniu frameworków JS (React/Vue/Angular). Zamiast tego, widok będzie składał się z:
1.  Głównego szablonu Smarty (`profile.tpl`), który renderuje strukturę HTML.
2.  Pliku JavaScript (`profile.js`) wykorzystującego jQuery do obsługi logiki frontendu (pobieranie danych, walidacja, wysyłanie formularza, obsługa odpowiedzi API).

Struktura HTML w `profile.tpl` będzie zawierać:
- Główny kontener widoku.
- Formularz (`<form id="profile-form">`) do edycji danych.
- Pola formularza dla loginu, miasta bazowego, nowego hasła i potwierdzenia hasła.
- Przycisk zapisu zmian.
- Obszar do wyświetlania komunikatów o sukcesie lub błędach.

## 4. Szczegóły komponentu (Formularz Profilu w `profile.tpl` i `profile.js`)

### Formularz Edycji Profilu (`#profile-form`)
- **Opis komponentu:** Formularz HTML umożliwiający użytkownikowi edycję loginu, hasła i miasta bazowego. Logika jest zarządzana przez `profile.js`.
- **Główne elementy HTML:**
    - `<form id="profile-form">`
    - `<div class="form-group">` dla każdego pola:
        - `<label>`
        - `<input type="text" id="login" name="login" required>`
        - `<input type="text" id="cityBase" name="cityBase" required>`
        - `<input type="password" id="password" name="password">` (dla nowego hasła)
        - `<input type="password" id="confirmPassword" name="confirmPassword">` (dla potwierdzenia nowego hasła)
        - Elementy do wyświetlania błędów walidacji per pole (np. `<div class="invalid-feedback">`)
    - `<button type="submit" id="save-profile-btn">Zapisz zmiany</button>`
    - `<div class="is-admin">` (zawiera informację o użytkowniku: Administrator - Tak/Nie)
    - `<div id="form-messages">` (do ogólnych komunikatów formularza - sukces, błąd API)
- **Obsługiwane interakcje:**
    - Wypełnianie pól formularza.
    - Utrata focusu z pól (do ewentualnej walidacji "on blur").
    - Kliknięcie przycisku "Zapisz zmiany" (submit formularza).
- **Obsługiwana walidacja (w `profile.js` przed wysłaniem):**
    - **Login:**
        - Wymagany: Nie może być pusty. Komunikat: "Login jest wymagany."
        - Unikalność: Sprawdzana po stronie serwera. Komunikat (z API): "Login jest już zajęty."
    - **Miasto bazowe:**
        - Wymagane: Nie może być puste. Komunikat: "Miasto bazowe jest wymagane."
    - **Nowe hasło:**
        - Wymagane tylko jeśli wypełnione jest pole "Potwierdź nowe hasło".
        - Minimalna długość: 5 znaków (jeśli niepuste). Komunikat: "Hasło musi mieć minimum 5 znaków."
    - **Potwierdź nowe hasło:**
        - Wymagane tylko jeśli wypełnione jest pole "Nowe hasło".
        - Dopasowanie: Musi być identyczne jak "Nowe hasło". Komunikat: "Hasła nie pasują do siebie."
- **Propsy (Dane z API):** Dane użytkownika (`id`, `login`, `cityBase`) pobrane z `GET /api/users/me` są używane do wstępnego wypełnienia pól formularza.

## 6. Zarządzanie stanem
Zarządzanie stanem odbywa się głównie za pomocą jQuery w pliku `profile.js`. Kluczowe elementy stanu:
- **Dane użytkownika:** Przechowywane w zmiennych JS po pobraniu z API, używane do wypełnienia formularza.
- **Stan ładowania:** Zmienna (np. `isLoading`) do śledzenia, czy trwa komunikacja z API (np. podczas zapisywania). Może być użyta do deaktywacji przycisku zapisu.
- **Komunikaty błędów/sukcesu:** Przechowywane w zmiennych lub bezpośrednio wstrzykiwane do odpowiednich elementów DOM (`#form-messages`, `.invalid-feedback`).

Nie jest wymagany dedykowany customowy hook, stan jest zarządzany lokalnie w skrypcie `profile.js`.

## 7. Integracja API
Integracja z API odbywa się za pomocą asynchronicznych żądań AJAX (jQuery `$.ajax`).

- **Pobieranie danych użytkownika:**
    - **Endpoint:** `GET /api/users/me`
    - **Typ żądania:** `GET`
    - **Ciało żądania:** Brak
    - **Odpowiedź (Sukces 200 OK):**
      ```json
      {
        "id": number,
        "login": "string",
        "cityBase": "string",
        "isAdmin": boolean
      }
      ```
    - **Odpowiedź (Błąd):** 401 (Unauthorized), 500 (Server Error)
    - **Akcja frontendowa:** Po załadowaniu widoku, wywołaj ten endpoint. W przypadku sukcesu, wypełnij pola formularza (`#login`, `#cityBase`) i informację w klasie `is-admin`. W przypadku błędu, wyświetl komunikat.

- **Aktualizacja danych użytkownika:**
    - **Endpoint:** `PUT /api/users/me` (Uwaga: Ten endpoint musi zostać zaimplementowany w backendzie)
    - **Typ żądania:** `PUT`
    - **Ciało żądania:**
      ```json
      {
        "login": "string",        // Aktualny login z formularza
        "cityBase": "string",     // Aktualne miasto z formularza
        "password": "string"      // Nowe hasło z formularza (tylko jeśli zmieniane, opcjonalne)
      }
      ```
    - **Odpowiedź (Sukces 200 OK):**
      ```json
      {
        "id": number,
        "login": "string",
        "cityBase": "string",
        "isAdmin": boolean
      }
      ```
    - **Odpowiedź (Błąd):**
        - 400 (Bad Request): Błędy walidacji (np. login zajęty, hasło za krótkie, brakujące pola). Oczekiwany format: `{ "error": "Komunikat błędu", "field": "nazwa_pola" }`
        - 401 (Unauthorized)
        - 500 (Server Error)
    - **Akcja frontendowa:** Po walidacji po stronie klienta i kliknięciu "Zapisz", wywołaj ten endpoint. W przypadku sukcesu, wyświetl komunikat o powodzeniu (np. "Dane zostały zaktualizowane."). Opcjonalnie zaktualizuj pola formularza, jeśli odpowiedź zawiera zaktualizowane dane. W przypadku błędu 400, wyświetl odpowiedni komunikat błędu (najlepiej przy konkretnym polu, jeśli `field` jest dostępne w odpowiedzi, lub jako ogólny komunikat). W przypadku innych błędów, wyświetl ogólny komunikat błędu.

## 8. Interakcje użytkownika
- **Ładowanie strony:** Użytkownik wchodzi na `/profile`. Skrypt `profile.js` wysyła żądanie `GET /api/users/me`. Formularz jest wypełniany danymi użytkownika. Przycisk "Zapisz" jest aktywny.
- **Edycja pól:** Użytkownik modyfikuje wartości w polach `login`, `cityBase`.
- **Zmiana hasła:** Użytkownik wpisuje nowe hasło w polu `password` i powtarza je w `confirmPassword`.
- **Próba zapisu:** Użytkownik klika "Zapisz zmiany".
    - Skrypt `profile.js` uruchamia walidację po stronie klienta.
    - **Jeśli walidacja nie przejdzie:** Wyświetlane są komunikaty błędów przy odpowiednich polach. Przycisk "Zapisz" pozostaje aktywny. Wysyłanie żądania do API jest blokowane.
    - **Jeśli walidacja przejdzie:** Przycisk "Zapisz" jest deaktywowany (opcjonalnie, aby zapobiec podwójnemu kliknięciu), wyświetlany jest wskaźnik ładowania (opcjonalnie). Skrypt wysyła żądanie `PUT /api/users/me` z danymi z formularza.
- **Odpowiedź API (Sukces):** Wskaźnik ładowania znika. Wyświetlany jest komunikat o sukcesie. Przycisk "Zapisz" staje się ponownie aktywny. Pola hasła są czyszczone.
- **Odpowiedź API (Błąd 400 - Walidacja serwera):** Wskaźnik ładowania znika. Wyświetlany jest komunikat błędu zwrócony przez API (jeśli jest `field`, przy odpowiednim polu, w przeciwnym razie jako ogólny komunikat). Przycisk "Zapisz" staje się ponownie aktywny.
- **Odpowiedź API (Inny błąd):** Wskaźnik ładowania znika. Wyświetlany jest ogólny komunikat o błędzie. Przycisk "Zapisz" staje się ponownie aktywny.

## 9. Warunki i walidacja
Walidacja odbywa się dwuetapowo: po stronie klienta (w `profile.js`) i po stronie serwera (w implementacji endpointu `PUT /api/users/me`).

- **Walidacja po stronie klienta (przed wysłaniem żądania PUT):**
    - **Login:** Sprawdzenie, czy pole nie jest puste (`required`).
    - **Miasto bazowe:** Sprawdzenie, czy pole nie jest puste (`required`).
    - **Nowe hasło:** Sprawdzenie minimalnej długości (5 znaków), jeśli pole jest wypełnione.
    - **Potwierdź nowe hasło:** Sprawdzenie, czy jest identyczne z nowym hasłem, jeśli oba pola są wypełnione.
    - **Wpływ na interfejs:** Jeśli walidacja nie przejdzie, wyświetlane są komunikaty błędów (`.invalid-feedback`) przy odpowiednich polach (np. poprzez dodanie klasy `is-invalid` do inputa w Bootstrapie), a wysłanie żądania jest blokowane.

- **Walidacja po stronie serwera (w `PUT /api/users/me`):**
    - **Login:** Sprawdzenie unikalności w bazie danych. Sprawdzenie, czy pole nie jest puste.
    - **Miasto bazowe:** Sprawdzenie, czy pole nie jest puste.
    - **Nowe hasło:** Sprawdzenie minimalnej długości (5 znaków), jeśli zostało przesłane.
    - **Wpływ na interfejs:** Jeśli walidacja serwera nie przejdzie (odpowiedź 400), komunikat błędu z API jest wyświetlany użytkownikowi (w `#form-messages` lub przy konkretnym polu, jeśli API dostarczy informacji o polu).

## 10. Obsługa błędów
- **Błędy walidacji klienta:** Wyświetlanie komunikatów bezpośrednio przy polach formularza. Zapobieganie wysłaniu żądania do API.
- **Błędy walidacji serwera (400 Bad Request):** Wyświetlanie komunikatu błędu zwróconego przez API. Jeśli API zwraca informację o polu (`field`), błąd jest wyświetlany przy tym polu. W przeciwnym razie, jako ogólny komunikat formularza.
- **Brak autoryzacji (401 Unauthorized):** Przekierowanie użytkownika do strony logowania lub wyświetlenie odpowiedniego komunikatu.
- **Błędy serwera (500 Internal Server Error):** Wyświetlanie ogólnego komunikatu o błędzie, np. "Wystąpił nieoczekiwany błąd. Spróbuj ponownie później."
- **Błędy sieciowe (AJAX error):** Wyświetlanie ogólnego komunikatu o problemie z połączeniem, np. "Błąd połączenia z serwerem. Sprawdź swoje połączenie internetowe."
- **Stan ładowania:** Użycie wskaźnika ładowania i/lub deaktywacja przycisku zapisu podczas komunikacji z API zapobiega frustracji użytkownika i podwójnym żądaniom.

## 11. Kroki implementacji
1.  **Backend:**
    *   Zdefiniować i zaimplementować endpoint `PUT /api/users/me` w PHP.
    *   Dodać nową funkcję w `commonDB/users.php` do aktualizacji danych użytkownika (np. `setUserProfile`), obsługującą aktualizację loginu, miasta bazowego i opcjonalnie hasła (wraz z jego hashowaniem). Funkcja powinna zawierać walidację (unikalność loginu, minimalna długość hasła).
    *   Upewnić się, że endpoint `GET /api/users/me` działa poprawnie i zwraca wymagane dane.
    *   Zabezpieczyć oba endpointy, aby wymagały uwierzytelnienia użytkownika.
2.  **Frontend (Smarty):**
    *   Utworzyć szablon Smarty `profile.tpl` w katalogu /templates.
    *   Zaimplementować strukturę HTML formularza (`#profile-form`) z polami: login, miasto bazowe, nowe hasło, potwierdź nowe hasło, przyciskiem zapisu i miejscem na komunikaty.
    *   Dołączyć plik `profile.js` do szablonu.
    *   Dodać odpowiednie klasy CSS (np. Bootstrap) do stylizacji formularza i komunikatów.
3.  **Frontend (jQuery - `profile.js`):**
    *   Po załadowaniu dokumentu (`$(document).ready(...)`):
        *   Wykonać żądanie AJAX `GET /api/users/me`.
        *   W przypadku sukcesu, wypełnić pola `#login` i `#cityBase` otrzymanymi danymi.
        *   W przypadku błędu, wyświetlić komunikat w `#form-messages`.
    *   Dodać obsługę zdarzenia `submit` dla formularza `#profile-form`:
        *   Zablokować domyślną akcję przesyłania formularza (`event.preventDefault()`).
        *   Zaimplementować logikę walidacji po stronie klienta (sprawdzanie pustych pól, długości hasła, zgodności haseł).
        *   Wyświetlić błędy walidacji przy polach lub wyczyścić poprzednie błędy.
        *   Jeśli walidacja przejdzie:
            *    Zebrać dane z formularza.
            *    Opcjonalnie: Deaktywować przycisk zapisu / pokazać wskaźnik ładowania.
            *    Wysłać żądanie AJAX `PUT /api/users/me` z zebranymi danymi.
            *    W `success` callback: Wyświetlić komunikat sukcesu, wyczyścić pola hasła, aktywować przycisk.
            *    W `error` callback: Wyświetlić odpowiedni komunikat błędu (zależnie od statusu odpowiedzi), aktywować przycisk.
4.  **Routing aplikacji:**
    *   Dodać regułę routingu w głównej aplikacji PHP, która dla ścieżki `/profile` będzie renderować szablon `profile.tpl` (upewniając się, że użytkownik jest zalogowany).
5.  **Testowanie:**
    *   Przetestować pobieranie danych.
    *   Przetestować walidację po stronie klienta dla wszystkich pól i warunków.
    *   Przetestować zapisywanie zmian (zmiana loginu, zmiana miasta, zmiana hasła, zmiana kilku pól naraz).
    *   Przetestować obsługę błędów API (login zajęty, hasło za krótkie, błędy serwera, brak autoryzacji).
    *   Przetestować responsywność widoku. 