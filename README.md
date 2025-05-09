# 10x-city - System rekomendacji miejsc turystycznych

## Instalacja i konfiguracja

### Wymagania
- PHP 7.x lub 8.x
- MySQL/MariaDB
- Dostęp do API OpenAI (klucz API)
- Dostęp do Google Geocoding API oraz Google Directions API

### Instalacja
1. Sklonuj repozytorium:
   ```
   git clone <adres-repozytorium>
   cd 10devs
   ```

2. Konfiguracja:
   - Skopiuj `config.example.php` do `config.php`:
     ```
     cp config.example.php config.php
     ```
   - Edytuj `config.php` i wprowadź odpowiednie dane:
     - Dane połączenia do bazy danych
     - Klucz API OpenAI
     - Klucze Google API (Geocoding i Directions)
     - Sekret JWT do podpisywania tokenów
     - Inne ustawienia specyficzne dla środowiska

### Struktura projektu
- `/api/` - Endpointy API REST
  - `/api/auth/` - Endpointy autoryzacji (logowanie, rejestracja)
  - `/api/cities/` - Zarządzanie miastami
  - `/api/recommendations/` - Zarządzanie rekomendacjami
  - `/api/users/` - Zarządzanie danymi użytkowników
- `/cities/` - Widoki związane z wyszukiwaniem miast
- `/city/` - Widoki szczegółów miasta i rekomendacji
- `/classes/` - Klasy PHP (AiService, GeoHelper, itp.)
- `/commonDB/` - Funkcje do operacji na bazie danych
- `/css/` - Pliki stylów
- `/js/` - Skrypty JavaScript
  - `/js/cities/` - Skrypty specyficzne dla miast
- `/templates/` - Pliki szablonów HTML
  - `/templates/cities/` - Szablony związane z miastami

## Endpointy API

### Użytkownicy
- `POST /api/users/register` - Rejestracja nowego użytkownika
- `POST /api/users/login` - Logowanie użytkownika i wydanie tokenu JWT
- `POST /api/users/logout` - Wylogowanie użytkownika
- `PUT /api/users/me` - Aktualizacja danych zalogowanego użytkownika
- `GET /api/users/me` - Pobranie profilu zalogowanego użytkownika

### Miasta
- `GET /api/cities` - Lista miast użytkownika z liczbą rekomendacji
- `POST /api/cities/search` - Wyszukiwanie miasta i generowanie rekomendacji
- `GET /api/cities/{cityId}` - Szczegóły miasta
- `PUT /api/cities/{cityId}` - Aktualizacja informacji o mieście (np. oznaczenie jako odwiedzone)
- `DELETE /api/cities/{cityId}` - Usunięcie miasta i powiązanych rekomendacji

### Rekomendacje
- `GET /api/cities/{cityId}/recommendations` - Lista rekomendacji dla miasta
- `POST /api/cities/{cityId}/recommendations` - Dodanie nowych rekomendacji do miasta
- `GET /api/recommendations/{id}` - Szczegóły konkretnej rekomendacji
- `POST /api/recommendations` - Ręczne dodanie rekomendacji
- `PUT /api/recommendations/{id}` - Aktualizacja rekomendacji (edycja, akceptacja, odrzucenie)
- `PUT /api/recommendations/update-done` - Oznaczenie wielu rekomendacji jako odwiedzonych
- `DELETE /api/recommendations/{id}` - Usunięcie rekomendacji

### Logi
- `GET /api/ai-logs` - Pobranie logów działań AI (tylko dla administratorów)
- `POST /api/ai-logs` - Rejestracja działania AI
- `POST /api/ai-inputs` - Rejestracja zapytania do AI
- `POST /api/error-logs` - Zapis błędów systemu
- `GET /api/error-logs` - Pobranie logów błędów (tylko dla administratorów)

## Bezpieczeństwo

### Mechanizmy autoryzacji
System wykorzystuje tokeny JWT (JSON Web Token) do autoryzacji użytkowników. Po zalogowaniu, token JWT jest generowany i musi być dołączany w nagłówku `Authorization` (format: `Bearer <token>`) przy kolejnych żądaniach do API.

### Wrażliwe dane
W projekcie zarządzamy wrażliwymi danymi przy użyciu pliku `config.php`, który nigdy nie powinien być commitowany do repozytorium. W pliku tym przechowujemy:

- Dane dostępowe do bazy danych
- Klucze API (OpenAI, Google)
- Klucze JWT do podpisywania tokenów
- Inne wrażliwe ustawienia konfiguracyjne

Zawsze używaj pliku `.gitignore` aby zapobiec przypadkowemu commitowaniu tych danych.

## Developement

### Testowanie API bez OpenAI
Podczas developmentu możesz testować API bez konieczności wywoływania rzeczywistego API OpenAI. W pliku `AiService.php` znajdziesz zakomentowany kod produkcyjny oraz przykładową odpowiedź dla celów testowych. Możesz również użyć przykładów błędnych odpowiedzi, aby testować obsługę błędów.

### Używane narzędzia
- PHP 7.x (kompatybilne z PHP 8.x)
- MySQL/MariaDB jako baza danych
- JWT do autoryzacji
- OpenAI API (model gpt-4.1-mini) do generowania rekomendacji
- Google Geocoding API do weryfikacji nazwy miasta
- Google Directions API do określania trasy dojazdu

## Testy
W projekcie zastosowano dwa rodzaje testów: jednostkowe oparte o PHPUnit oraz testy End-to-End (E2E) wykorzystujące Cypress.

### Testy jednostkowe (PHPUnit)
Testy jednostkowe sprawdzają poprawność działania poszczególnych komponentów i klas systemu w izolacji.

#### Struktura testów jednostkowych
- `/tests/unit/` - katalog zawierający testy jednostkowe
  - `GeoHelperTest.php` - testy klasy GeoHelper odpowiadającej za weryfikację miast i obliczanie tras
  - `DashboardTest.php` - testy funkcjonalności dashboardu

#### Uruchamianie testów jednostkowych
```
vendor/bin/phpunit
```

### Testy End-to-End (Cypress)
Testy E2E weryfikują działanie aplikacji z perspektywy użytkownika końcowego, symulując interakcje z interfejsem.

#### Struktura testów E2E
- `/cypress/e2e/` - katalog zawierający testy E2E
  - `login_spec.cy.js` - testy procesu logowania
  - `registration_spec.cy.js` - testy procesu rejestracji
  - `homepage_spec.cy.js` - testy strony głównej

#### Uruchamianie testów E2E
```
npx cypress open
```
lub w trybie headless:
```
npx cypress run
```

### Narzędzia do testowania
- **Testy jednostkowe:** PHPUnit - framework do testowania kodu PHP
- **Testy End-to-End (E2E):** Cypress - framework do automatyzacji testów interfejsu użytkownika
- **Narzędzia pomocnicze:** Postman/Insomnia - do testowania API REST

## Table of Contents
- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [License](#license)

## Project Description
10x-city is a web-based system designed for tourists, enabling quick searching and gathering of information about city attractions. The application uses GPT-4.1-mini API to generate attraction recommendations based on popularity criteria and mentions in various sources.

**Problem Statement:** Tourists struggle with finding and cataloging information about interesting places, events, or buildings in cities, which is time-consuming and requires manual data collection.

**Solution:** 10x-city streamlines this process by automatically generating recommendations for city attractions, allowing users to review, edit, and save these recommendations for future reference.

## Tech Stack
- **Frontend:** HTML, CSS, jQuery, JavaScript
- **Backend:** PHP 8
- **Database:** MySQL 5
- **AI Integration:** GPT-4.1-mini API
- **Maps Integration:** Google Geocoding API, Google Directions API
- **Testing:** PHPUnit (Unit Testing), Cypress (E2E Testing), Postman/Insomnia (API Testing)

## Getting Started Locally

### Prerequisites
- WAMP with PHP 8 or higher
- MySQL 5
- Access to GPT-4.1-mini API
- Google API keys (Geocoding and Directions)
- Node.js i npm (dla testów Cypress)
- Composer (dla testów PHPUnit)

### Installation Steps
1. Clone the repository
   ```
   git clone https://github.com/piotrwai/10devs.git
   ```

2. Configure database
   - Create a new MySQL database
   - Import the database schema from `db.sql`
   - Update database credentials in configuration file

3. Configure API access
   - Add your GPT-4.1-mini API key to the configuration file
   - Add your Google API keys to the configuration file

4. Start your local server and navigate to the project URL

5. Set up testing environment
   - Install PHP dependencies:
     ```
     composer install
     ```
   - Install JavaScript dependencies:
     ```
     npm install
     ```
   - Update the `cypress.config.js` file with your local server URL if different from `http://10devs.local/`

## Available Scripts
- `setup.php` - Initialize database and configuration
- `test.php` - Test the connection to the API
- `vendor/bin/phpunit` - Run PHPUnit tests
- `npx cypress open` - Open Cypress Test Runner
- `npx cypress run` - Run Cypress tests in headless mode

## Project Scope

### Features Included
- User registration and login (username, password, base city)
- Searching for attractions in target cities
- City name verification using Google Geocoding API
- Calculating routes from base city using Google Directions API
- Presentation of recommendations (city description and up to 10 attractions)
- Editing and managing recommendations (accept, edit, reject)
- City list management with attraction counts
- Marking cities as "Visited"
- Marking recommendations as "Visited"
- Recommendation replenishment if acceptance rate falls below 60%
- Printing recommendation lists
- AI action logging

### Limitations
- No data exchange between users
- Text-only format (no images, PDFs, DOCXs)
- No integration with other systems
- No mobile application
- No additional functional extensions in MVP phase

## Project Status
MVP in development

## License
Proprietary - All rights reserved

---

© 2025 10x-city 