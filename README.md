# 10x-city - System rekomendacji miejsc turystycznych

## Instalacja i konfiguracja

### Wymagania
- PHP 7.x lub 8.x
- MySQL/MariaDB
- Dostęp do API OpenAI (klucz API)

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
     - Inne ustawienia specyficzne dla środowiska

### Struktura projektu
- `./api/` - Endpointy API REST
- `./classes/` - Klasy PHP
- `./commonDB/` - Funkcje do operacji na bazie danych
- `./templates/` - Pliki szablonów HTML
- `./img/` - Pliki graficzne

## Endpointy API

### Miasta
- `POST /api/cities/search` - Wyszukiwanie miasta i generowanie rekomendacji
  
### Rekomendacje
- `POST /api/recommendation/save` - Zapisywanie rekomendacji dla miasta

## Bezpieczeństwo

### Wrażliwe dane
W projekcie zarządzamy wrażliwymi danymi przy użyciu pliku `config.php`, który nigdy nie powinien być commitowany do repozytorium. W pliku tym przechowujemy:

- Dane dostępowe do bazy danych
- Klucze API (np. OpenAI)
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

## Getting Started Locally

### Prerequisites
- WAMP with PHP 8 or higher
- MySQL 5
- Access to GPT-4.1-mini API

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

4. Start your local server and navigate to the project URL

## Available Scripts
- `setup.php` - Initialize database and configuration
- `test.php` - Test the connection to the API

## Project Scope

### Features Included
- User registration and login (username, password, base city)
- Searching for attractions in target cities
- Presentation of recommendations (city description and up to 10 attractions)
- Editing and managing recommendations (accept, edit, reject)
- City list management with attraction counts
- Marking cities as "Visited"
- Recommendation replenishment if acceptance rate falls below 60%
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