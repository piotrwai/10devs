------------------------------------------------
Wynik z `Google AI Studio`
------------------------------------------------

```markdown
# Plan Testów dla Projektu 10x-city

**Wersja:** 1.0
**Data utworzenia:** 2024-08-01
**Autor:** [Twoje Imię i Nazwisko], Starszy Inżynier QA

## 1. Wprowadzenie i Cele Testowania

### 1.1 Wprowadzenie
Niniejszy dokument opisuje plan testów dla aplikacji webowej "10x-city", systemu rekomendacji miejsc turystycznych. Aplikacja umożliwia użytkownikom wyszukiwanie informacji o atrakcjach w miastach, generowanie rekomendacji za pomocą AI (GPT-4.1-mini), zarządzanie listami miast i rekomendacji. Projekt jest obecnie w fazie rozwoju MVP (Minimum Viable Product).

### 1.2 Cele Testowania
Główne cele procesu testowania to:
*   Weryfikacja zgodności zaimplementowanych funkcjonalności z wymaganiami opisanymi w pliku `README.md` i wywnioskowanymi z analizy kodu.
*   Zapewnienie stabilności i niezawodności kluczowych komponentów aplikacji (backend API, frontend, baza danych).
*   Wykrycie i zaraportowanie błędów funkcjonalnych, bezpieczeństwa, użyteczności i wydajnościowych.
*   Ocena poprawności integracji z zewnętrznymi serwisami (OpenAI API, Google Geocoding API, Google Directions API).
*   Zapewnienie bezpieczeństwa danych użytkownika oraz kluczy API.
*   Weryfikacja poprawności działania mechanizmów autoryzacji (JWT).
*   Potwierdzenie gotowości aplikacji do wdrożenia wersji MVP.

## 2. Zakres Testów

### 2.1 Funkcjonalności w Zakresie Testów
*   **Zarządzanie Użytkownikami:**
    *   Rejestracja nowego użytkownika (walidacja danych, unikalność loginu).
    *   Logowanie użytkownika (poprawne dane, błędne dane, obsługa błędów).
    *   Wylogowanie użytkownika (usunięcie tokena/ciasteczka).
    *   Zarządzanie profilem użytkownika (pobieranie danych, aktualizacja loginu, miasta bazowego, hasła).
    *   Autoryzacja dostępu do zasobów na podstawie tokena JWT.
*   **Wyszukiwanie Miast i Generowanie Rekomendacji:**
    *   Wyszukiwanie miasta przez użytkownika.
    *   Walidacja nazwy miasta (Google Geocoding API).
    *   Generowanie podsumowania miasta i rekomendacji przez AI (OpenAI API).
    *   Generowanie informacji o trasie z miasta bazowego (Google Directions API).
    *   Obsługa błędów związanych z zewnętrznymi API (AI, Google).
    *   Wyświetlanie wyników wyszukiwania (podsumowanie, lista rekomendacji).
    *   Testowanie trybu deweloperskiego bez wywołań OpenAI (mock data w `AiService.php`).
*   **Zarządzanie Rekomendacjami:**
    *   Akceptacja / Edycja / Odrzucenie rekomendacji na interfejsie wyszukiwania.
    *   Zapisywanie zaakceptowanych/edytowanych rekomendacji i danych miasta (`POST /api/recommendations/save`).
    *   Wyświetlanie listy rekomendacji dla zapisanego miasta (`/city/{id}/recommendations`).
    *   Dodawanie nowej, ręcznej rekomendacji do istniejącego miasta.
    *   Edycja treści istniejącej rekomendacji.
    *   Zmiana statusu rekomendacji (accepted, edited, rejected).
    *   Oznaczanie rekomendacji jako "Odwiedzona" (`done`).
    *   Usuwanie pojedynczej rekomendacji.
*   **Panel Miast (Dashboard):**
    *   Wyświetlanie listy zapisanych miast użytkownika.
    *   Wyświetlanie liczby rekomendacji (całkowitej i odwiedzonych) dla każdego miasta.
    *   Paginacja listy miast.
    *   Filtrowanie listy miast po statusie "Odwiedzone".
    *   Zmiana statusu "Odwiedzone" dla miasta (`PUT /api/cities/{id}`).
    *   Edycja nazwy miasta.
    *   Usuwanie miasta wraz z powiązanymi rekomendacjami (`DELETE /api/cities/{id}`).
*   **Uzupełnianie Rekomendacji:**
    *   Weryfikacja logiki wyzwalania uzupełniania (poniżej 60% akceptacji rekomendacji AI).
    *   Poprawność wywołania AI z listą istniejących tytułów.
    *   Wyświetlanie nowych, unikalnych rekomendacji.
*   **Bezpieczeństwo:**
    *   Ochrona przed nieautoryzowanym dostępem do API i stron.
    *   Walidacja danych wejściowych (frontend i backend) pod kątem XSS, SQL Injection.
    *   Poprawność implementacji JWT (generowanie, walidacja, wygasanie, bezpieczne przechowywanie/przesyłanie).
    *   Zabezpieczenie kluczy API i danych konfiguracyjnych (sprawdzenie `.gitignore` i braku `config.php` w repozytorium).
*   **Logowanie Zdarzeń:**
    *   Poprawność zapisywania logów błędów (`error_logs`).
    *   Poprawność zapisywania logów interakcji z AI (`ai_logs`, `ai_inputs`).
*   **Interfejs Użytkownika (UI/UX):**
    *   Poprawność wyświetlania elementów na stronach (`index`, `login`, `register`, `profile`, `dashboard`, `search`, `recommendations`).
    *   Responsywność interfejsu (podstawowa weryfikacja).
    *   Intuicyjność nawigacji i przepływu pracy użytkownika.
    *   Obsługa formularzy (walidacja po stronie klienta, komunikaty błędów/sukcesu).
    *   Poprawność działania skryptów JavaScript (AJAX, dynamiczne aktualizacje UI).

### 2.2 Funkcjonalności Poza Zakresem Testów (zgodnie z `README.md` - Limitations)
*   Wymiana danych między użytkownikami.
*   Obsługa formatów innych niż tekstowe (obrazów, PDF, DOCX).
*   Integracja z innymi systemami zewnętrznymi (poza OpenAI i Google Maps API).
*   Aplikacja mobilna.
*   Dodatkowe rozszerzenia funkcjonalne poza zakresem MVP.

## 3. Typy Testów do Przeprowadzenia

*   **Testy Jednostkowe (Unit Tests):** (Zalecane, choć brak śladów w kodzie) Weryfikacja poprawności działania poszczególnych klas i funkcji, szczególnie w `classes/` i `commonDB/`. Należy rozważyć ich dodanie, np. przy użyciu PHPUnit.
*   **Testy Integracyjne (Integration Tests):**
    *   Testowanie współpracy między modułami (np. API <-> Baza Danych, API <-> `AiService`, `AiService` <-> Zewnętrzne API).
    *   Testowanie poprawności działania funkcji bazodanowych (`commonDB/`) w kontekście rzeczywistej bazy danych.
    *   Testowanie integracji `GeoHelper` z Google APIs.
*   **Testy API (API Tests):** Kluczowy typ testów. Weryfikacja wszystkich endpointów API (`./api/`) pod kątem:
    *   Poprawności metod HTTP (GET, POST, PUT, DELETE).
    *   Struktury żądań i odpowiedzi (JSON).
    *   Kodów statusu HTTP.
    *   Autoryzacji JWT (poprawny token, brak tokenu, nieważny token, token wygasły).
    *   Walidacji danych wejściowych.
    *   Poprawności logiki biznesowej.
    *   Obsługi błędów.
    *   Nagłówków odpowiedzi (CORS, Content-Type).
    *   Testowanie reguł `.htaccess` dla API.
*   **Testy End-to-End (E2E Tests):** Symulacja działań użytkownika w przeglądarce w celu weryfikacji kompletnych przepływów (np. rejestracja -> logowanie -> wyszukanie miasta -> zapis rekomendacji -> sprawdzenie w dashboardzie).
*   **Testy Interfejsu Użytkownika (UI Tests):** Weryfikacja poprawności renderowania elementów HTML/CSS, działania skryptów JavaScript (walidacja formularzy, AJAX, dynamiczne zmiany), spójności wizualnej.
*   **Testy Bezpieczeństwa (Security Tests):**
    *   Testy penetracyjne (podstawowe) pod kątem OWASP Top 10 (SQLi, XSS, Broken Authentication, Sensitive Data Exposure).
    *   Weryfikacja mechanizmów autoryzacji (czy użytkownik może modyfikować tylko swoje dane).
    *   Sprawdzenie konfiguracji bezpieczeństwa serwera i aplikacji (np. nagłówki HTTP, obsługa sesji/tokenów).
    *   Sprawdzenie braku wrażliwych danych w kodzie źródłowym i repozytorium.
*   **Testy Wydajnościowe (Performance Tests):** (Podstawowe dla MVP)
    *   Pomiar czasu odpowiedzi API, zwłaszcza dla endpointów integrujących się z AI i Google.
    *   Obserwacja zużycia zasobów serwera pod obciążeniem (jeśli możliwe).
    *   Ocena szybkości ładowania stron i odpowiedzi interfejsu.
*   **Testy Użyteczności (Usability Tests):** Ocena intuicyjności interfejsu, łatwości nawigacji i ogólnego doświadczenia użytkownika (User Experience).
*   **Testy Kompatybilności (Compatibility Tests):** Sprawdzenie działania aplikacji na różnych przeglądarkach (Chrome, Firefox, Edge - najnowsze wersje) oraz potencjalnie różnych wersjach PHP (7.x vs 8.x), jeśli wymagane.

## 4. Scenariusze Testowe dla Kluczowych Funkcjonalności

*Poniżej przedstawiono przykładowe, wysokopoziomowe scenariusze. Szczegółowe przypadki testowe zostaną opracowane w osobnym dokumencie lub systemie do zarządzania testami.*

### 4.1 Rejestracja i Logowanie
*   **TC001:** Rejestracja nowego użytkownika z poprawnymi danymi.
*   **TC002:** Próba rejestracji z zajętym loginem.
*   **TC003:** Próba rejestracji z niepoprawnymi danymi (za krótkie hasło, niezgodne hasła, nieprawidłowy format loginu, puste pola).
*   **TC004:** Logowanie zarejestrowanego użytkownika z poprawnymi danymi.
*   **TC005:** Próba logowania z błędnym hasłem.
*   **TC006:** Próba logowania z nieistniejącym loginem.
*   **TC007:** Wylogowanie użytkownika (weryfikacja usunięcia sesji/tokena i przekierowania).
*   **TC008:** Próba dostępu do chronionych stron (np. /dashboard) bez zalogowania (oczekiwane przekierowanie do /login).
*   **TC009:** Próba dostępu do chronionych endpointów API bez/z nieważnym tokenem JWT (oczekiwany błąd 401).
*   **TC010:** Weryfikacja wygasania tokena JWT i konieczności ponownego logowania.

### 4.2 Zarządzanie Profilem Użytkownika
*   **TC011:** Poprawne wyświetlanie danych zalogowanego użytkownika na stronie profilu.
*   **TC012:** Aktualizacja loginu i miasta bazowego użytkownika (bez zmiany hasła).
*   **TC013:** Aktualizacja loginu, miasta bazowego i hasła użytkownika.
*   **TC014:** Próba aktualizacji profilu z nowym loginem, który jest już zajęty.
*   **TC015:** Próba aktualizacji profilu z niepoprawnym nowym hasłem (za krótkie, niezgodne potwierdzenie).
*   **TC016:** Weryfikacja, czy zmiany w profilu są poprawnie zapisane i widoczne po ponownym załadowaniu strony/API.

### 4.3 Wyszukiwanie Miasta i Generowanie Rekomendacji
*   **TC017:** Wyszukanie istniejącego miasta - poprawne wygenerowanie podsumowania, rekomendacji i trasy.
*   **TC018:** Wyszukanie nieistniejącego miasta lub nazwy niebędącej miastem (oczekiwany błąd walidacji z `GeoHelper`).
*   **TC019:** Wyszukanie miasta z użyciem znaków specjalnych/diakrytycznych.
*   **TC020:** Weryfikacja generowania trasy z miasta bazowego (poprawność danych, obsługa braku trasy).
*   **TC021:** Testowanie reakcji systemu na błędy API OpenAI (np. timeout, błąd klucza API - przy użyciu trybu mock).
*   **TC022:** Testowanie reakcji systemu na błędy Google APIs (Geocoding, Directions).
*   **TC023:** Weryfikacja formatu i liczby generowanych rekomendacji (min. 3, max. 10).
*   **TC024:** Weryfikacja działania wyszukiwania w trybie deweloperskim (zakomentowany kod w `AiService.php`).
*   **TC025:** Weryfikacja poprawności wyświetlania wyników wyszukiwania na stronie `/cities/search`.

### 4.4 Zarządzanie Rekomendacjami (Interfejs Wyszukiwania i Zapis)
*   **TC026:** Akceptacja pojedynczej rekomendacji w wynikach wyszukiwania.
*   **TC027:** Odrzucenie pojedynczej rekomendacji w wynikach wyszukiwania.
*   **TC028:** Edycja tytułu i opisu rekomendacji w wynikach wyszukiwania.
*   **TC029:** Akceptacja wszystkich rekomendacji za pomocą przycisku "Akceptuj wszystkie".
*   **TC030:** Zapisanie wybranych (zaakceptowanych/edytowanych) rekomendacji dla nowego miasta.
*   **TC031:** Zapisanie wybranych rekomendacji dla miasta, które już istnieje w bazie użytkownika.
*   **TC032:** Próba zapisania bez zaakceptowania/edycji żadnej rekomendacji (oczekiwany błąd).
*   **TC033:** Weryfikacja przekierowania/komunikatu po zapisaniu rekomendacji.
*   **TC034:** Weryfikacja, czy odrzucone rekomendacje nie są zapisywane.

### 4.5 Zarządzanie Rekomendacjami (Widok Miasta)
*   **TC035:** Poprawne wyświetlanie listy zapisanych rekomendacji dla miasta (`/city/{id}/recommendations`).
*   **TC036:** Dodanie nowej, ręcznej rekomendacji.
*   **TC037:** Próba dodania ręcznej rekomendacji o tytule, który już istnieje dla tego miasta.
*   **TC038:** Edycja tytułu i opisu istniejącej rekomendacji.
*   **TC039:** Oznaczenie rekomendacji jako "Odwiedzona" i cofnięcie oznaczenia.
*   **TC040:** Zmiana statusu istniejącej rekomendacji (np. z 'accepted' na 'rejected').
*   **TC041:** Usunięcie pojedynczej rekomendacji.
*   **TC042:** Weryfikacja sortowania rekomendacji (np. alfabetycznie).
*   **TC043:** Weryfikacja działania przycisków Akceptuj/Odrzuć/Edytuj/Usuń w widoku listy.
*   **TC044:** Weryfikacja drukowania listy rekomendacji (ukrywanie elementów `.no-print`, pokazywanie `.print-only`, formatowanie).

### 4.6 Panel Miast (Dashboard)
*   **TC045:** Poprawne wyświetlanie listy zapisanych miast z liczbą rekomendacji (całkowitą/odwiedzonych).
*   **TC046:** Testowanie paginacji listy miast (przechodzenie między stronami).
*   **TC047:** Testowanie filtrowania listy miast (Wszystkie / Odwiedzone / Nieodwiedzone).
*   **TC048:** Zmiana statusu miasta na "Odwiedzone".
*   **TC049:** Zmiana statusu miasta na "Nieodwiedzone".
*   **TC050:** Edycja nazwy miasta.
*   **TC051:** Próba edycji nazwy miasta na pustą lub zbyt długą.
*   **TC052:** Usunięcie miasta (weryfikacja usunięcia miasta i powiązanych rekomendacji).
*   **TC053:** Wyświetlanie komunikatu o braku miast, jeśli użytkownik żadnych nie dodał.
*   **TC054:** Weryfikacja linków do widoku rekomendacji dla każdego miasta.

### 4.7 Uzupełnianie Rekomendacji
*   **TC055:** Weryfikacja pojawienia się przycisku "Uzupełnij rekomendacje", gdy wskaźnik akceptacji AI spadnie poniżej 60% (np. po odrzuceniu kilku rekomendacji).
*   **TC056:** Wywołanie funkcji uzupełniania przez kliknięcie przycisku.
*   **TC057:** Weryfikacja, czy żądanie API (`POST /api/cities/search` z `supplement: true`) zawiera listę istniejących tytułów.
*   **TC058:** Poprawne wyświetlenie nowych, unikalnych rekomendacji na liście (bez duplikatów).
*   **TC059:** Obsługa sytuacji, gdy AI nie zwróci żadnych nowych, unikalnych rekomendacji.
*   **TC060:** Weryfikacja, czy przycisk "Uzupełnij" znika/jest deaktywowany po udanym uzupełnieniu.

### 4.8 Bezpieczeństwo
*   **TC061:** Próba wykonania akcji API (np. PUT /api/cities/{id}) na zasobie innego użytkownika (oczekiwany błąd 403/404).
*   **TC062:** Weryfikacja, czy plik `config.php` znajduje się w `.gitignore`.
*   **TC063:** Próba wstrzyknięcia kodu JavaScript (XSS) w polach formularzy (login, miasto, tytuł/opis rekomendacji) i weryfikacja kodowania danych wyjściowych w HTML/JS.
*   **TC064:** Próba wstrzyknięcia SQL w parametrach API (np. `id`, `cityName`, `page`).
*   **TC065:** Weryfikacja nagłówków bezpieczeństwa HTTP (jeśli skonfigurowane).
*   **TC066:** Testowanie siły sekretu JWT (jeśli możliwe).
*   **TC067:** Weryfikacja obsługi błędów - czy nie ujawniają zbyt wielu informacji technicznych.

## 5. Środowisko Testowe

*   **Serwer WWW:** Serwer zgodny z wymaganiami (np. Apache lub Nginx) z obsługą PHP.
*   **PHP:** Wersja 7.x lub 8.x (zgodnie z wymaganiami projektu, warto przetestować na obu, jeśli wspierane).
*   **Baza Danych:** MySQL (wersja 5+) lub MariaDB. Zalecana osobna instancja bazy danych dla celów testowych, wypełniona danymi testowymi.
*   **Przeglądarki:** Najnowsze wersje Google Chrome, Mozilla Firefox, Microsoft Edge.
*   **Systemy Operacyjne:** Windows, macOS, Linux (do testów kompatybilności przeglądarek).
*   **Klucze API:** Dedykowane klucze API dla środowiska testowego (OpenAI, Google Geocoding, Google Directions) - jeśli możliwe, w przeciwnym razie należy ostrożnie korzystać z kluczy deweloperskich lub intensywnie wykorzystywać tryb mock.
*   **Dane Testowe:** Przygotowany zestaw danych użytkowników, miast i rekomendacji o różnych statusach, obejmujący przypadki brzegowe.

## 6. Narzędzia do Testowania

*   **Przeglądarka WWW z narzędziami deweloperskimi:** Do inspekcji DOM, analizy ruchu sieciowego, debugowania JS.
*   **Narzędzie do Testowania API:** Postman lub Insomnia (do manualnych i półautomatycznych testów API).
*   **Framework do Testów E2E/UI:** Selenium IDE, Cypress, lub Playwright (do automatyzacji testów interfejsu użytkownika).
*   **Framework do Testów Jednostkowych PHP:** PHPUnit (do testów jednostkowych i integracyjnych backendu - zalecane wprowadzenie).
*   **Klient Bazy Danych:** HeidiSQL, phpMyAdmin (do weryfikacji danych w bazie).
*   **Narzędzie do Zarządzania Testami / Bug Tracking:** Jira, Trello, TestRail (do zarządzania przypadkami testowymi i raportowania błędów).
*   **Narzędzia do Testów Wydajności:** Apache JMeter, k6 (opcjonalnie, do podstawowych testów wydajności API).
*   **Narzędzia do Testów Bezpieczeństwa:** OWASP ZAP, Burp Suite (opcjonalnie, do podstawowych skanów bezpieczeństwa).
*   **Edytor Tekstu / IDE:** Do analizy kodu i skryptów testowych.

## 7. Harmonogram Testów

*Harmonogram testów zostanie dostosowany do ogólnego harmonogramu projektu i poszczególnych sprintów/iteracji. Zakłada się ciągłe testowanie w miarę dostarczania nowych funkcjonalności.*

*   **Faza 1: Testy Komponentów i Integracji:** Równolegle z rozwojem poszczególnych modułów (backend API, klasy, commonDB).
*   **Faza 2: Testy Systemowe / E2E:** Po zintegrowaniu głównych komponentów i udostępnieniu działającej wersji aplikacji.
*   **Faza 3: Testy Akceptacyjne Użytkownika (UAT):** Przed wdrożeniem MVP (jeśli dotyczy).
*   **Faza 4: Testy Regresji:** Przed każdym wdrożeniem nowej wersji oraz po wprowadzeniu poprawek błędów.
*   **Faza 5: Testy Bezpieczeństwa i Wydajności:** Przed finalnym wdrożeniem MVP.

*Szczegółowy harmonogram zostanie ustalony z zespołem deweloperskim i kierownictwem projektu.*

## 8. Kryteria Akceptacji Testów

### 8.1 Kryteria Wejścia (Rozpoczęcia Testów)
*   Dostępna stabilna wersja aplikacji na środowisku testowym.
*   Ukończony rozwój funkcjonalności przewidzianych do testowania w danej fazie.
*   Dostępna dokumentacja techniczna i wymagania (np. `README.md`, specyfikacja API).
*   Przygotowane środowisko testowe (baza danych, konfiguracja, klucze API).
*   Dostępne narzędzia do testowania.

### 8.2 Kryteria Wyjścia (Zakończenia Testów)
*   Wykonano wszystkie zaplanowane przypadki testowe dla danej fazy.
*   Osiągnięto założony poziom pokrycia testami (jeśli mierzony).
*   Wszystkie krytyczne (`Critical`) i Wysokie (`High`) błędy zostały naprawione i zweryfikowane (re-test).
*   Liczba otwartych błędów o niższym priorytecie (Medium, Low) mieści się w akceptowalnych ramach ustalonych z zespołem.
*   Wyniki testów zostały udokumentowane i zaraportowane.
*   Aplikacja spełnia kluczowe wymagania funkcjonalne i niefunkcjonalne.
*   Uzyskano akceptację od Product Ownera / klienta (jeśli dotyczy).

## 9. Role i Odpowiedzialności

*   **Inżynier QA (Tester):**
    *   Projektowanie i tworzenie przypadków testowych.
    *   Wykonywanie testów manualnych i automatycznych.
    *   Raportowanie i śledzenie błędów.
    *   Weryfikacja poprawek błędów (re-test).
    *   Przygotowanie i utrzymanie środowiska testowego (w koordynacji z deweloperami).
    *   Raportowanie statusu testów.
    *   Współpraca z deweloperami w celu diagnozowania problemów.
*   **Deweloperzy:**
    *   Naprawa zgłoszonych błędów.
    *   Dostarczanie stabilnych wersji aplikacji do testów.
    *   Wsparcie w konfiguracji środowiska testowego.
    *   (Opcjonalnie) Tworzenie testów jednostkowych.
*   **Product Owner / Kierownik Projektu:**
    *   Dostarczanie wymagań i kryteriów akceptacji.
    *   Priorytetyzacja błędów.
    *   Podejmowanie decyzji o wdrożeniu na podstawie wyników testów.

## 10. Procedury Raportowania Błędów

*   **Narzędzie:** Wszystkie znalezione błędy będą raportowane i śledzone przy użyciu [Nazwa Narzędzia, np. Jira, Trello].
*   **Format Zgłoszenia Błędu:** Każde zgłoszenie powinno zawierać co najmniej:
    *   **Tytuł:** Krótki, zwięzły opis problemu.
    *   **Środowisko:** Wersja aplikacji, przeglądarka, system operacyjny, środowisko testowe.
    *   **Kroki do Reprodukcji:** Szczegółowa lista kroków pozwalająca na odtworzenie błędu.
    *   **Wynik Oczekiwany:** Jak system powinien się zachować.
    *   **Wynik Rzeczywisty:** Jak system faktycznie się zachował.
    *   **Priorytet/Waga:** (np. Krytyczny, Wysoki, Średni, Niski) - ustalany wstępnie przez testera, weryfikowany przez zespół.
    *   **Załączniki:** Zrzuty ekranu, nagrania wideo, logi (jeśli relevantne).
    *   **Dodatkowe Informacje:** Wszelkie inne pomocne informacje.
*   **Cykl Życia Błędu:**
    1.  **Nowy (New):** Zgłoszony przez testera.
    2.  **Otwarty (Open/Assigned):** Potwierdzony i przypisany do dewelopera.
    3.  **W Trakcie (In Progress):** Deweloper pracuje nad poprawką.
    4.  **Rozwiązany (Resolved/Fixed):** Poprawka zaimplementowana przez dewelopera.
    5.  **Gotowy do Testów (Ready for Test):** Wersja z poprawką dostępna na środowisku testowym.
    6.  **Weryfikacja (Testing/Verifying):** Tester weryfikuje poprawkę.
    7.  **Zamknięty (Closed):** Błąd został poprawnie naprawiony i zweryfikowany.
    8.  **Odrzucony (Rejected):** Zgłoszenie nie jest błędem, jest duplikatem lub nie da się go odtworzyć.
    9.  **Ponownie Otwarty (Reopened):** Poprawka nie zadziałała lub wprowadziła regresję.
```


