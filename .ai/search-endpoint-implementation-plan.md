# API Endpoint Implementation Plan: /api/cities/search

## 1. Przegląd punktu końcowego
Punkt końcowy służy do wyszukiwania miasta i generowania rekomendacji atrakcji turystycznych przez AI. Umożliwia użytkownikowi otrzymanie informacji o mieście (wraz z opcjonalnym podsumowaniem generowanym przez AI) oraz listy rekomendacji atrakcji, które mogą być później zapisane.

## 2. Szczegóły żądania
- Metoda HTTP: POST
- URL: /api/cities/search
- Parametry:
  - Wymagane:
    - `cityName` (string) – nazwa miasta do wyszukania.
  - Opcjonalne: brak
- Treść żądania (Request Body):
  ```json
  {
    "cityName": "string"
  }
  ```

## 3. Szczegóły odpowiedzi
- Sukces (200 OK):
  ```json
  {
    "city": {
      "id": number,           // null, jeśli miasto nie zostało zapisane dla użytkownika
      "name": "string",
      "summary": "string"     // Podsumowanie wygenerowane przez AI (do 150 znaków)
    },
    "recommendations": [
      {
        "id": number,         // null, ponieważ rekomendacje nie są jeszcze zapisane
        "title": "string",
        "description": "string",
        "model": "string"     // Identyfikator modelu AI
      }
      // ... maksymalnie 10 rekomendacji
    ]
  }
  ```
- Błąd – 400 Bad Request: gdy nazwa miasta jest pusta lub nieprawidłowa.

## 4. Przepływ danych
1. Odbiór żądania POST z JSON zawierającym `cityName`.
2. Weryfikacja autentykacji poprzez token JWT.
3. Walidacja danych wejściowych – sprawdzenie, czy `cityName` nie jest pusty i mieści się w ustalonych granicach (do 150 znaków).
4. Sprawdzenie, czy miasto już istnieje w bazie danych dla danego użytkownika:
   - Jeśli miasto istnieje, pobranie jego danych (np. id, nazwa).
   - Jeśli nie, ustawienie `id` jako null.
5. Wywołanie serwisu AI, który generuje:
   - Podsumowanie miasta (summary).
   - Listę rekomendacji (do 10 elementów) zawierających tytuł, opis oraz identyfikator modelu AI.
6. Utworzenie obiektu odpowiedzi zgodnie z powyższą specyfikacją.
7. Zwrócenie odpowiedzi.

## 5. Względy bezpieczeństwa
- Uwierzytelnienie: Wymagane jest użycie tokena JWT przesyłanego w nagłówku `Authorization: Bearer <token>`.
- Autoryzacja: Logika powinna zapewniać, że użytkownik widzi tylko swoje dane.
- Walidacja danych wejściowych: Użycie filtrów i przygotowanych zapytań (prepared statements) w celu zapobiegania SQL Injection.
- Ograniczenie danych: Zapewnienie, że podsumowanie miasta nie przekracza 150 znaków oraz tytuły rekomendacji do 150 znaków (zgodnie z wymaganiami) oraz treść rekomendacji nie przekracza 64000 znaków.
- Sprawdzenie wersji PHP oraz zgodność z PHP 7 i PHP 8.4 – unikanie błędów i ostrzeżeń.

## 6. Obsługa błędów
- Błąd 400 Bad Request: W przypadku nieprawidłowych lub brakujących danych (pusty `cityName`).
- Błąd 500 Internal Server Error: W przypadku awarii po stronie serwera, np. błąd połączenia z bazą danych lub serwisem AI.
- Rejestracja błędów: Wszystkie błędy powinny być logowane do tabeli `error_logs` z odpowiednimi danymi (typ błędu, wiadomość, URL, ewentualny payload).

## 7. Rozważania dotyczące wydajności
- Optymalizacja zapytań do bazy danych poprzez wykorzystanie indeksów (np. `cit_usr_id`, `cit_name`).
- Ograniczenie liczby rekomendacji do maksymalnie 10, aby zmniejszyć obciążenie serwera i AI.
- Rozważenie cachowania wyników z serwisu AI w razie dużego obciążenia.
- Asynchroniczne wywołania do zewnętrznego serwisu AI, jeśli obsługa czasu odpowiedzi staje się krytyczna.
- 60 sekund czasu oczekiwania na odpowiedź AI. Po przekroczeniu czasu błąd AI timeout.

## 8. Kroki implementacji
1. Utworzenie kontrolera dla endpointa `/api/cities/search`.
   - Implementacja metody obsługującej żądanie POST.
2. Walidacja tokena JWT oraz autoryzacja użytkownika.
3. Walidacja danych wejściowych – sprawdzenie, czy `cityName` jest dostarczony i prawidłowy.
4. Sprawdzenie w bazie danych, czy miasto już istnieje dla użytkownika.
5. Integracja z zewnętrznym serwisem AI:
   - Wywołanie modelu AI do generowania summary dla miasta.
   - Odebranie listy rekomendacji.
6. Zbudowanie obiektu odpowiedzi zgodnie z dokumentacją.
7. Obsługa wyjątków:
   - Logowanie błędów w tabeli `error_logs`.
   - Odpowiadanie odpowiednimi kodami statusu.
9. Weryfikacja zgodności kodu z PHP 7 oraz PHP 8.4.