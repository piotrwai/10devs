# API Endpoint Implementation Plan: Cities List Endpoint

## 1. Przegląd punktu końcowego
Punkt końcowy służy do pobierania listy miast powiązanych z autoryzowanym użytkownikiem wraz z liczbą powiązanych rekomendacji. Endpoint umożliwia paginację oraz filtrowanie według flagi odwiedzenia (visited).

## 2. Szczegóły żądania
- Metoda HTTP: GET
- Struktura URL: /api/cities
- Parametry zapytania:
  - Wymagane: Brak
  - Opcjonalne:
    - page: numer strony (np. ?page=1)
    - per_page: liczba rekordów na stronę (np. ?per_page=10)
    - visited: filtr typu boolean (np. ?visited=true)
- Request Body: Brak

## 3. Szczegóły odpowiedzi
- Kod statusu: 200 OK przy pomyślnym pobraniu danych
- Struktura odpowiedzi (JSON):
  ```json
  [
    {
      "id": number,
      "name": "string",
      "recommendationCount": number,
      "visited": boolean
    },
    ...
  ]
  ```

## 4. Przepływ danych
1. Autoryzacja: Sprawdzenie poprawności tokenu JWT przy użyciu odpowiedniego middleware.
2. Pobranie identyfikatora użytkownika z tokenu.
3. Walidacja parametrów zapytania, tj. `page`, `per_page`, `visited`.
4. Wykonanie zapytania do bazy danych:
   - Pobranie rekordów z tabeli `cities` przypisanych do użytkownika.
   - Dołączenie funkcji agregującej (COUNT) z tabeli `recom` aby określić liczbę rekomendacji dla każdego miasta.
   - Zastosowanie paginacji oraz filtracji wg. wartości `visited`.
   - Sortowanie miast alfabetyczne
5. Sformatowanie danych do struktury JSON zgodnej ze specyfikacją.
6. Zwrócenie odpowiedzi do klienta.
7. Brak danych (miast) powoduje pojawienie się komunikatu: Nie wprowadziłeś żadnego miasta!

## 5. Względy bezpieczeństwa
- Endpoint dostępny tylko dla autoryzowanych użytkowników (walidacja tokenu JWT).
- Upewnienie się, że użytkownik widzi tylko dane przypisane do jego identyfikatora (stosowanie filtrów wg. `usr_id`).
- Walidacja i sanitizacja parametrów wejściowych.

## 6. Obsługa błędów
- 400 Bad Request: Nieprawidłowe dane wejściowe, np. błędny format parametrów zapytania.
- 401 Unauthorized: Brak lub niewłaściwy token JWT.
- 500 Internal Server Error: Błędy po stronie serwera i zapytania do bazy danych.
- Wpisywanie błędów do tabeli `error_logs` dla dalszej analizy.

## 7. Rozważania dotyczące wydajności
- Użycie paginacji do ograniczenia liczby zwracanych rekordów. Domyślna paginacja to 10.
- Wykorzystanie indeksów w bazie danych (np. indeks na `cit_usr_id`).
- Optymalizacja zapytań SQL, szczególnie przy zliczaniu rekomendacji (COUNT z grupowaniem).

## 8. Etapy wdrożenia
1. Utworzenie lub aktualizacja kontrolera obsługującego endpoint GET /api/cities.
2. Implementacja walidacji parametrów zapytania (page, per_page, visited).
3. Rozbudowa warstwy serwisowej do obsługi logiki biznesowej:
   - Zapytanie do bazy danych z odpowiednimi filtrami i paginacją.
   - Agregacja danych (liczba rekomendacji dla każdego miasta).
4. Integracja z mechanizmem autoryzacji (walidacja JWT).
5. Implementacja mechanizmu logowania błędów, w tym zapisu do tabeli `error_logs`.
6. Wdrożenie na środowisko testowe - dodaj jego testowe wywołanie do pliku test_api.php 