# Specyfikacja modułu rejestracji i logowania

## 1. ARCHITEKTURA INTERFEJSU UŻYTKOWNIKA

### 1.1 Widoki dla użytkowników niezalogowanych
- **Strona logowania**:
  - Formularz logowania zawiera pola: _Login_ oraz _Hasło_.
  - Walidacja po stronie klienta (przy użyciu jQuery) sprawdza, czy pola nie są puste oraz czy hasło spełnia minimalną liczbę znaków.
  - Komunikaty błędów: np. "Niepoprawne dane logowania", "Pole nie może być puste".
  - Akcja logowania wysyła żądanie AJAX do endpointu `/api/users/login`.
  - Dostęp przez URL: `/login` (przekierowanie do `login.php` zgodnie z regułami w `.htaccess`).

- **Strona rejestracji**:
  - Formularz rejestracji zawiera pola: _Login_, _Hasło_, _Potwierdź hasło_, _Miasto bazowe_.
  - Walidacja:
    - Login: od 2 do 50 znaków, sprawdzenie unikalności (na poziomie serwera oraz przez komunikację AJAX).
    - Hasło: minimum 5 znaków (zgodnie z walidacją w funkcji `setUserProfile`).
    - Miasto bazowe: obowiązkowe, od 3 do 150 znaków.
  - Komunikaty błędów: np. "Login już istnieje", "Hasło za krótkie", "Pole nie może być puste".
  - Akcja rejestracji wysyła żądanie AJAX do endpointu `/api/users/register`.
  - Dostęp przez URL: `/register` (należy dodać odpowiednią regułę w `.htaccess` na wzór istniejących).

### 1.2 Nawigacja i layout
- Aplikacja posiada wspólne elementy interfejsu takie jak nagłówek (header), stopka (footer) oraz paski nawigacyjne. Kieruj się już wdrożonymi szablonami z `./templates`.
- Dla użytkowników niezalogowanych widoczne są widoki logowania i rejestracji.
- Po poprawnym logowaniu interfejs przełącza się w tryb autoryzowany, umożliwiając dostęp do funkcji wyszukiwania atrakcji oraz innych usług.
- Po poprawnej rejestracji użytkownik musi się prawidłowo zalogować. Konieczny jest, po prawidłowej rejestracji, komunikat o prawidłowej rejestracji oraz o tym, że konieczne jest logowanie.
- Zgodnie z `.htaccess`, aplikacja obsługuje przekierowania z ładnych URL (np. `/login`, `/profile`, `/logout`) na odpowiednie pliki PHP.

### 1.3 Integracja komponentów i obsługa sesji
- Formularze logowania i rejestracji są implementowane jako osobne szablony w folderze `./templates`.
- Komunikacja z backendem odbywa się poprzez AJAX, a odpowiedzi z API (np. błędy walidacji, token autoryzacyjny) są przetwarzane dynamicznie.
- Sesja użytkownika oparta jest na tokenach JWT, które są przechowywane po stronie klienta (np. w localStorage) i wysyłane z każdym żądaniem wymagającym autoryzacji, co już jest obsługiwane przez klasę `Auth`.

### 1.4 Przebieg procesu
- **Rejestracja**:
  1. Użytkownik wypełnia formularz rejestracji.
  2. Dane są walidowane po stronie klienta i wysyłane do endpointu `/api/users/register`.
  3. W przypadku sukcesu, użytkownik zostaje przekierowany do strony logowania z komunikatem o prawidłowej rejestracji.
  4. W przypadku błędów wyświetlany jest odpowiedni komunikat.

- **Logowanie**:
  1. Użytkownik wprowadza dane do formularza logowania.
  2. Dane są wysyłane do endpointu `/api/users/login`.
  3. W przypadku poprawnych danych zwracany jest token JWT oraz dane użytkownika, które inicjują przejście na widok autoryzowany.
  4. W przypadku niepowodzenia wyświetlany jest komunikat o błędzie.

- **Wylogowanie**:
  - Użytkownik wylogowuje się poprzez kliknięcie przycisku, co powoduje usunięcie tokena z przeglądarki i przekierowanie do strony logowania z komunikatem o prawidłowym wylogowaniu.
  - Dostęp przez URL: `/logout` (przekierowanie do `logout.php` zgodnie z regułami w `.htaccess`).

---

## 2. LOGIKA BACKENDOWA

### 2.1 Struktura endpointów API
- **POST /api/users/register**
  - Odpowiedzialny za rejestrację nowego użytkownika.
  - Walidacja danych: sprawdzenie unikalności loginu, długości hasła (minimum 5 znaków), poprawności pól.
  - Logika: hashowanie hasła (za pomocą `password_hash`), zapisanie danych do tabeli `users` poprzez nową funkcję `setNewUser` w `./commonDB/users.php`.

- **POST /api/users/login**
  - Służy do logowania użytkownika.
  - Walidacja: weryfikacja danych logowania, porównanie hashu hasła (za pomocą `password_verify`).
  - W przypadku sukcesu generowany jest token JWT i zwracane są dane użytkownika.
  - Wykorzystuje istniejącą klasę `Auth` do generowania JWT.

- **GET /api/users/me**
  - Pobiera profil użytkownika na podstawie przesłanego tokena JWT.
  - Wykorzystuje istniejącą klasę `Auth` do autoryzacji i funkcję `getUserProfile` z `./commonDB/users.php` do pobrania danych użytkownika.

### 2.2 Modele danych i walidacja wejścia
- Model użytkownika zawiera pola: `usr_id`, `usr_login`, `usr_password`, `usr_city`, `usr_admin`, `usr_date_registration`.
- Walidacja odbywa się na dwóch poziomach:
  - Po stronie klienta z wykorzystaniem jQuery (podstawowa walidacja formularzy).
  - Po stronie serwera przy użyciu dedykowanych funkcji walidacyjnych.
- Obsługa wyjątków:
  - W przypadku niepoprawnych danych zwracane są odpowiednie kody błędów (np. 400 Bad Request, 409 Conflict).
  - Błędy są logowane w tabeli `error_logs` w bazie danych przy użyciu klasy `ErrorLogger`.

### 2.3 Aktualizacja renderowania widoków
- Po autentykacji, system korzysta z szablonów Smarty do renderowania widoków użytkownika.
- Dynamiczne ładowanie danych (np. informacje o użytkowniku) odbywa się na podstawie odpowiedzi z API.
- System wykorzystuje przekierowania URL zdefiniowane w `.htaccess` dla zapewnienia przyjaznych użytkownikowi adresów.

---

## 3. SYSTEM AUTENTYKACJI

### 3.1 Mechanizm JWT
- Po poprawnym logowaniu, serwer generuje token JWT zawierający kluczowe informacje o użytkowniku (np. `usr_id`, `usr_login`, `usr_admin`).
- Token jest zwracany w odpowiedzi na żądanie logowania i powinien być przechowywany po stronie klienta.
- Każde żądanie do chronionych endpointów musi zawierać token w nagłówku `Authorization: Bearer <token>`.
- Wykorzystuje istniejącą klasę `Auth`, która obsługuje generowanie, weryfikację i dekodowanie tokenów JWT.

### 3.2 Middleware do weryfikacji tokena
- Na backendzie już istnieje funkcjonalność `Auth::authenticateAndGetUserId()`, która:
  - Parsuje i weryfikuje poprawność tokena JWT.
  - Sprawdza ważność tokena oraz zgodność z danymi użytkownika w bazie.
  - W przypadku niepoprawnego lub przeterminowanego tokena, zwraca null.
- Funkcja ta jest wykorzystywana we wszystkich chronionych endpointach (np. `/api/users/me`, `/api/users/update`).

### 3.3 Integracja z bazą danych i bezpieczeństwo
- Baza danych MySQL przechowuje dane użytkowników w tabeli `users`, a hasła są bezpiecznie przechowywane w postaci hashów (z użyciem funkcji `password_hash` i `password_verify`).
- Wszystkie operacje na danych użytkowników (rejestracja, logowanie) realizowane są przez dedykowane funkcje w module `./commonDB/users.php`.
- System autentykacji jest zgodny z wymaganiami PHP 7 oraz PHP 8.4.
- Należy dodać funkcję `setNewUser` w `./commonDB/users.php` do obsługi rejestracji nowych użytkowników, wzorując się na istniejącej funkcji `setUserProfile`.

### 3.4 Komponenty i usługi autentykacyjne
- **Auth** (umieszczona w folderze `./classes`):
  - Już istniejąca klasa obsługująca generację, walidację i dekodowanie tokenów JWT.
  - Wymaga rozszerzenia o metodę do generowania nowego tokena JWT po udanym logowaniu.
- **Response** (umieszczona w folderze `./classes`):
  - Już istniejąca klasa do formatowania odpowiedzi API.
  - Wykorzystywana do wysyłania ujednoliconych odpowiedzi JSON z odpowiednimi kodami HTTP.
- **ErrorLogger** (umieszczona w folderze `./classes`):
  - Już istniejąca klasa do logowania błędów.
  - Wykorzystywana do zapisywania informacji o błędach w tabeli `error_logs`.

---

## Podsumowanie
Dokument ten przedstawia szczegółową specyfikację modułu rejestracji i logowania, uwzględniając architekturę interfejsu użytkownika, logikę backendową oraz system autentykacji oparty na technologii MySQL i JWT. Implementacja musi być zgodna z istniejącą strukturą aplikacji, wymaganiami projektu (w tym regułami nazewnictwa i standardami PHP) oraz dokumentacją projektu. Wszystkie zmiany powinny być kompatybilne z PHP 7 oraz PHP 8.4, przy zachowaniu bezpieczeństwa i wydajności systemu. 

Należy dodać nowe pliki:
- `./login.php` - strona logowania (URL: `/login`) - już uwzględnione w `.htaccess`
- `./register.php` - strona rejestracji (URL: `/register`) - należy dodać regułę w `.htaccess`
- `./logout.php` - obsługa wylogowania (URL: `/logout`) - już uwzględnione w `.htaccess`
- `./api/users/login.php` - endpoint logowania
- `./api/users/register.php` - endpoint rejestracji
- `./templates/login.tpl` - szablon strony logowania
- `./templates/register.tpl` - szablon strony rejestracji

Należy rozszerzyć funkcjonalność:
1. Dodać w `commonDB/users.php` funkcję `setNewUser` obsługującą rejestrację
2. Dodać w `classes/Auth.php` metodę generującą nowy token JWT
3. Rozszerzyć `config.php` o parametry JWT, jeśli jeszcze nie istnieją
4. Dodać regułę w `.htaccess` dla przekierowania `/register` na `register.php`: 