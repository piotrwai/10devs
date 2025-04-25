# API Endpoint Implementation Plan: GET /api/users/me

## 1. Przegląd punktu końcowego
Endpoint umożliwia pobranie profilu zalogowanego użytkownika. Po weryfikacji tokenu JWT zwracane są następujące dane: identyfikator użytkownika, login, miasto bazowe oraz flaga informująca o uprawnieniach administracyjnych.

## 2. Szczegóły żądania
- **Metoda HTTP**: GET
- **Struktura URL**: /api/users/me
- **Parametry**:
  - **Wymagane**:
    - Nagłówek `Authorization` zawierający token JWT (format: Bearer <token>).
  - **Opcjonalne**: Brak
- **Request Body**: Brak

## 3. Szczegóły odpowiedzi
- **Pomyślna odpowiedź (200 OK)**:
  ```json
  {
    "id": number,
    "login": "string",
    "cityBase": "string",
    "isAdmin": boolean
  }
  ```
- **Błędy**:
  - 401 Unauthorized – Brak lub niepoprawny token JWT.
  - 404 Not Found – Użytkownik nie został znaleziony.
  - 500 Internal Server Error – Wewnętrzny błąd serwera.

## 4. Przepływ danych
1. Otrzymanie żądania na endpoint /api/users/me.
2. Przekazanie żądania do middleware odpowiedzialnego za weryfikację tokenu JWT.
3. Weryfikacja poprawności tokenu i wyodrębnienie identyfikatora użytkownika.
4. Wywołanie funkcji serwisowej `getUserProfile`, która pobiera dane użytkownika z bazy na podstawie `usr_id`.
5. Wykonanie zapytania SQL z wykorzystaniem prepared statements, wyszukanie rekordu w tabeli `users` wg klucza głównego.
6. Mapowanie danych z bazy do odpowiedzi JSON:
   - `id` ← `usr_id`
   - `login` ← `usr_login`
   - `cityBase` ← `usr_city`
   - `isAdmin` ← `usr_admin` (przekonwertowane na typ logiczny)
7. Zwrócenie odpowiedzi w formacie JSON.

## 5. Względy bezpieczeństwa
- Weryfikacja tokenu JWT przez middleware'a przed wykonaniem operacji na danych.
- Wykorzystanie przygotowanych zapytań SQL (prepared statements) w celu zabezpieczenia przed SQL Injection.
- Ograniczenie przekazywanych informacji tylko do niezbędnych danych użytkownika.
- Sprawdzenie uprawnień użytkownika przed wykonaniem operacji.

## 6. Obsługa błędów
- **401 Unauthorized**: W przypadku braku tokenu JWT lub niepoprawnego tokenu – zwrócenie komunikatu o błędzie autoryzacji.
- **404 Not Found**: Gdy użytkownik nie zostanie znaleziony w bazie danych – zwrócenie odpowiedniego komunikatu.
- **500 Internal Server Error**: W przypadku nieoczekiwanych błędów wewnętrznych lub problemów z bazą danych.
- Logowanie krytycznych błędów do tabeli `error_logs` dla późniejszej analizy.

## 7. Rozważania dotyczące wydajności
- Endpoint wykonuje proste zapytanie o pojedynczy rekord, co wspierane jest przez indeks na kolumnie `usr_id`, zapewniając wysoką wydajność.
- Możliwość wdrożenia mechanizmu cache'owania danych użytkownika w przyszłości, gdy profil nie zmienia się dynamicznie.
- Minimalizacja narzutów dzięki lekkiej logice w warstwie serwisowej.

## 8. Kroki implementacji
1. Dodanie lub weryfikacja middleware'a odpowiedzialnego za weryfikację tokenu JWT.
2. Utworzenie kontrolera, np. `UsersController`, z metodą `getMe` obsługującą żądanie GET /api/users/me.
3. Implementacja funkcji serwisowej `getUserProfile` w warstwie serwisowej, która pobiera dane użytkownika na podstawie identyfikatora.
4. Utworzenie zapytania SQL przy użyciu prepared statements do pobrania użytkownika z tabeli `users`.
5. Mapowanie danych z bazy do struktury odpowiedzi JSON zgodnie z wymaganiami endpointu.
6. Implementacja obsługi błędów z odpowiednimi kodami statusu (401, 404, 500) oraz logowanie błędów w tabeli `error_logs`.
7. Wdrożenie endpointu w środowisku testowym - dodaj jego testowe wywołanie do pliku test_api.php