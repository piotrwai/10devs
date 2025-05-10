# Wprowadzenie do projektu: 10x-city - System rekomendacji miejsc turystycznych

## Powitanie

Witamy w projekcie 10x-city! Jest to webowy system zaprojektowany dla turystów, umożliwiający szybkie wyszukiwanie i zbieranie informacji o atrakcjach miejskich. Aplikacja wykorzystuje API GPT-4.1-mini do generowania rekomendacji atrakcji na podstawie kryteriów popularności i wzmianek w różnych źródłach.

## Przegląd projektu i struktura

Projekt 10x-city jest systemem PHP dla turystów, który automatycznie generuje rekomendacje miejsc wartych odwiedzenia w wybranych miastach. System działa jako aplikacja webowa, pozwalająca użytkownikom na wyszukiwanie miast, przeglądanie wygenerowanych rekomendacji, zarządzanie listą miast i oznaczenie rekomendacji jako odwiedzone.

### Główna struktura katalogów:
- `/api/` - Endpointy API REST
  - `/api/auth/` - Endpointy autoryzacji
  - `/api/cities/` - Zarządzanie miastami
  - `/api/recommendations/` - Zarządzanie rekomendacjami
  - `/api/users/` - Zarządzanie danymi użytkowników
- `/cities/` - Widoki związane z wyszukiwaniem miast
- `/city/` - Widoki szczegółów miasta i rekomendacji
- `/classes/` - Klasy PHP (AiService, GeoHelper, itp.)
- `/commonDB/` - Funkcje do operacji na bazie danych
- `/css/` - Pliki stylów
- `/js/` - Skrypty JavaScript
- `/templates/` - Pliki szablonów HTML
- `/smarty/` - System szablonów Smarty

## Główne moduły

### `Autoryzacja (Auth)`

- **Rola:** Zarządzanie procesem logowania, rejestracji i autoryzacji.
- **Kluczowe pliki:** 
  - `classes/Auth.php` - Klasa obsługi autoryzacji
  - `api/users/login.php` - Endpoint logowania
  - `api/users/register.php` - Endpoint rejestracji
  - `login.php` - Widok strony logowania
  - `register.php` - Widok strony rejestracji
- **Ostatnie zmiany:** Zaimplementowano system JWT do autoryzacji API.

### `Obsługa miast i rekomendacji`

- **Rola:** Zarządzanie informacjami o miastach i generowanie rekomendacji.
- **Kluczowe pliki:** 
  - `classes/AiService.php` - Klasa obsługi AI generująca rekomendacje
  - `classes/GeoHelper.php` - Integracja z API Google Maps
  - `api/cities/search.php` - Endpoint wyszukiwania miast
  - `api/cities/recommendations.php` - Zarządzanie rekomendacjami
  - `commonDB/cities.php` - Funkcje bazodanowe dla miast
  - `commonDB/recommendations.php` - Funkcje bazodanowe dla rekomendacji
- **Ostatnie zmiany:** Dodano automatyczne uzupełnianie rekomendacji, gdy poziom akceptacji spada poniżej 60%.

### `Integracja z AI`

- **Rola:** Komunikacja z API OpenAI i generowanie rekomendacji turystycznych.
- **Kluczowe pliki:** 
  - `classes/AiService.php` - Klasa do komunikacji z API OpenAI
  - `commonDB/aiLogs.php` - Logowanie działań AI
- **Ostatnie zmiany:** Implementacja modelu GPT-4.1-mini.

### `System bazodanowy`

- **Rola:** Obsługa operacji bazodanowych dla różnych modułów systemu.
- **Kluczowe pliki:** 
  - `commonDB/dbConnect.php` - Połączenie z bazą danych (wzorzec Singleton)
  - `commonDB/users.php` - Operacje bazodanowe na użytkownikach
  - `commonDB/cities.php` - Operacje bazodanowe na miastach
  - `commonDB/recommendations.php` - Operacje bazodanowe na rekomendacjach
  - `commonDB/errorLogs.php` - Zapisywanie logów błędów
- **Ostatnie zmiany:** Optymalizacja zapytań do bazy danych.

## Kluczowi współtwórcy

Projekt jest rozwijany przez zespół 10devs. Z analizy kodu wynika, że projekt jest nowy i aktywnie rozwijany przez zespół PHP.

## Ogólne wnioski i obecne kierunki

- System wykorzystuje AI do automatycznego generowania rekomendacji turystycznych.
- Integracja z Google Geocoding API do weryfikacji nazw miast.
- Integracja z Google Directions API do obliczania tras między miastami.
- System autoryzacji oparty o JWT.
- Rozwijany układ testów jednostkowych (PHPUnit) i end-to-end (Cypress).
- Interfejs użytkownika oparty o szablony HTML z Smarty.

## Potencjalne obszary złożoności

- **Integracja z OpenAI:** Moduł AiService zawiera złożoną logikę komunikacji z API OpenAI, walidacji odpowiedzi i przetwarzania rekomendacji.
- **System autoryzacji:** Implementacja JWT wymaga uwagi przy zarządzaniu tokeniami i ich weryfikacji.
- **Obsługa danych geolokalizacyjnych:** Integracja z Google API do weryfikacji miast i obliczania tras jest złożonym obszarem.
- **Zarządzanie rekomendacjami:** System uzupełniania rekomendacji gdy zbyt wiele zostanie odrzuconych.

## Pytania do zespołu

1. Jakie są plany rozwoju systemu po fazie MVP?
2. Czy planowane jest dodanie wymiany danych między użytkownikami?
3. Jaka jest strategia skalowania systemu przy dużej liczbie użytkowników?
4. Jak często aktualizowane są modele AI używane przez system?
5. Jakie są najczęstsze problemy zgłaszane przez użytkowników?
6. Czy planowana jest wersja mobilna aplikacji?
7. Jakie metryki są najważniejsze do monitorowania działania systemu?

## Następne kroki

1. Zapoznanie się z dokumentacją API OpenAI (model GPT-4.1-mini).
2. Konfiguracja lokalnego środowiska deweloperskiego zgodnie z instrukcjami.
3. Poznanie API Google Maps (Geocoding i Directions).
4. Przejrzenie testów jednostkowych i e2e w celu zrozumienia kluczowej funkcjonalności.
5. Przetestowanie lokalnie pełnego cyklu działania aplikacji.

## Konfiguracja środowiska deweloperskiego

1. **Wymagania wstępne:**
   - WAMP z PHP 8
   - MySQL 5
   - Dostęp do API OpenAI (klucz API)
   - Klucze API Google (Geocoding i Directions)
   - Node.js i npm (dla testów Cypress)
   - Composer (dla testów PHPUnit)

2. **Konfiguracja:**
   - Sklonuj repozytorium
   - Skopiuj `config.example.php` do `config.php`
   - Skonfiguruj dane dostępowe do bazy danych w pliku `config.php`
   - Dodaj klucze API (OpenAI i Google) w pliku `config.php`
   - Uruchom lokalny serwer i przejdź do adresu projektu

3. **Testowanie:**
   - Uruchom testy jednostkowe: `vendor/bin/phpunit`
   - Uruchom testy E2E: `npx cypress open` lub `npx cypress run`

## Przydatne zasoby

- Dokumentacja API OpenAI: [https://platform.openai.com/docs](https://platform.openai.com/docs)
- Dokumentacja Google Geocoding API: [https://developers.google.com/maps/documentation/geocoding](https://developers.google.com/maps/documentation/geocoding)
- Dokumentacja Google Directions API: [https://developers.google.com/maps/documentation/directions](https://developers.google.com/maps/documentation/directions)
- Dokumentacja JWT: [https://jwt.io/introduction](https://jwt.io/introduction)
- Dokumentacja PHPUnit: [https://phpunit.de/documentation.html](https://phpunit.de/documentation.html)
- Dokumentacja Cypress: [https://docs.cypress.io](https://docs.cypress.io) 