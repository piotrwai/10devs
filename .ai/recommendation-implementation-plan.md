# API Endpoint Implementation Plan

Poniższy dokument zawiera kompleksowy plan wdrożenia wszystkich wskazanych punktów końcowych REST API w projekcie PHP 7/8, z uwzględnieniem architektury usługowej, walidacji, bezpieczeństwa i obsługi błędów.

---

## 1. POST /api/recommendations/save

### 1. Przegląd punktu końcowego
Zapisuje miasto wygenerowane przez AI (jeśli jeszcze nie istnieje dla użytkownika) oraz rekomendacje z przypisanymi statusami (accepted/edited/rejected). Zwraca szczegóły zapisanego miasta i rekomendacji.

### 2. Szczegóły żądania
- Metoda HTTP: POST
- URL: `/api/recommendations/save`
- Nagłówek:
  - `Authorization: Bearer <token>`
  - `Content-Type: application/json`
- Body (JSON):
  ```json
  {
    "city": { "name": "string", "summary": "string" },
    "recommendations": [
      { "title": "string", "description": "string", "model": "string", "status": "string" }
    ]
  }
  ```
- Parametry:
  - Wymagane:
    - `city.name` (string, 1–150 znaków)
    - `city.summary` (string, 1–150 znaków)
    - `recommendations` (tablica obiektów)
    - Dla każdego obiektu:
      - `title` (string, 1–150 znaków)
      - `description` (string)
      - `model` (string, np. 'manual' lub identyfikator AI)
      - `status` (string: 'accepted', 'edited', 'rejected')
  - Opcjonalne: brak

### 3. Szczegóły odpowiedzi
- **201 Created**
  ```json
  {
    "city": { "id": number, "name": "string", "summary": "string" },
    "savedRecommendations": number,
    "recommendations": [ { "id": number, "title": "string", "description": "string", "model": "string", "status": "string", "dateCreated": "timestamp" } ]
  }
  ```
- **400 Bad Request** – błędne dane wejściowe lub walidacja
- **401 Unauthorized** – brak lub nieprawidłowy token
- **500 Internal Server Error** – błąd po stronie serwera

### 4. Przepływ danych
1. **Autoryzacja**: middleware JWT dekoduje `Authorization`, ustawia `currentUserId`.
2. **Walidacja**: `RequestValidator` sprawdza długości, obecność wymaganych pól.
3. **Service**: `CityService::getOrCreateCity($userId, $name, $summary)`:
   - szuka miasta w `commonDB/getCityByNameAndUser`;
   - jeśli nie istnieje: `commonDB/setCity($userId, $name, $summary)`;
4. **Rekomendacje**: `RecommendationService::saveRecommendations($userId, $cityId, $recommendationDtos)`:
   - w transakcji DB:
     - filtruje obiekty wg statusu (zapisuje wszystkie, w tym rejected z odnotowaniem statusu);
     - batch INSERT do tabeli `recom`;
     - dla każdej utworzonej rekomendacji: logowanie w `ai_logs` przez `AILogsService`.
5. **Zwrócenie wyniku**.

### 5. Względy bezpieczeństwa
- **Autoryzacja**: tylko zalogowani użytkownicy (JWT + RLS w PostgreSQL).
- **SQL Injection**: przygotowane zapytania (`PDO` z placeholderami).
- **Ochrona przed masowymi atakami**: limit rozmiaru request body; rate limiting na poziomie serwera.

### 6. Obsługa błędów
- **Walidacja**: zwraca szczegółowy komunikat (JSON z listą błędów).
- **DatabaseException**: rollback transakcji, logowanie do `error_logs`, 500.
- **DuplicateEntry**: jeśli wystąpi (unikalność tytułu), 409 Conflict.

### 7. Wydajność
- Bulk-insert dla wielu rekomendacji.
- Użycie indeksów na `rec_usr_id`, `rec_cit_id`.
- Transakcja DB minimalizuje koszt powtarzania połączeń.

### 8. Kroki implementacji
1. Utworzyć w `commonDB` funkcje:
   - `getCityByNameAndUser(int $usrId, string $name)` (SELECT)... (nazwa GET zgodnie z regułami)
   - `setCity(int $usrId, string $name, string $desc)` (INSERT)
2. Utworzyć serwisy w `classes/Service`:
   - `CityService` z metodą `getOrCreateCity(...)`
   - `RecommendationService` z metodą `saveRecommendations(...)`
3. Dodać endpoint w `api/recommendations/save.php`:
   - wywołanie `RequestValidator`, `CityService`, `RecommendationService`
   - obsługa wyjątków i zwrot odpowiedzi
4. Dodać logowanie błędów poprzez `ErrorLogService::setError(...)` w catch
5. Napisać testy jednostkowe dla serwisów i integracyjne dla endpointu

---

## 2. POST /api/cities/{cityId}/recommendations/supplement

### 1. Przegląd punktu końcowego
Generuje dodatkowe rekomendacje AI, gdy wskaźnik akceptacji spadł poniżej 60%. Można wykonać raz na miasto.

### 2. Szczegóły żądania
- Metoda HTTP: POST
- URL: `/api/cities/{cityId}/recommendations/supplement`
- Parametry URL:
  - `cityId` (integer, wymagane)
- Nagłówek: JWT
- Body: brak

### 3. Szczegóły odpowiedzi
- **200 OK**
  ```json
  { "message": "Supplementary recommendations added successfully.", "newRecommendations": [ /* ... */ ] }
  ```
- **400 Bad Request** – próba ponownego uzupełnienia
- **401 Unauthorized**, **404 Not Found**, **500 Internal Server Error**

### 4. Przepływ danych
1. Sprawdzić, czy istnieje miasto (`CityService::getCityById`)
2. Obliczyć wskaźnik akceptacji (`RecommendationService::getAcceptanceRate`)
3. Jeśli <60% i brak wcześniejszego uzupełnienia (`cit_desc` flag?), wywołać AI, zapisać nowe rek.
4. Zwrócić listę nowych.

### 5. Względy bezpieczeństwa
- Autoryzacja JWT + RLS
- Walidacja `cityId`

### 6. Obsługa błędów
- `CityNotFoundException` → 404
- `AlreadySupplementedException` → 400
- Błędy AI/DB → log → 500

### 7. Wydajność
- Cache wskaźnika akceptacji

### 8. Kroki implementacji
1. Dodać metodę w `CityService`
2. Rozszerzyć `RecommendationService`
3. Endpoint w `api/cities/{cityId}/recommendations/supplement.php`
4. Testy

---

## 3. GET /api/cities/{cityId}/recommendations

### 1. Przegląd punktu końcowego
Pobiera strony rekomendacji dla danego miasta.

### 2. Szczegóły żądania
- Metoda: GET
- URL: `/api/cities/{cityId}/recommendations` + query `page`, `per_page`
- Authorization: JWT

### 3. Szczegóły odpowiedzi
- **200 OK** – tablica obiektów `[{ id, title, description, model, dateCreated, dateModified, status }]`
- **401**, **404**, **500**

### 4. Przepływ danych
- `RecommendationService::getByCity($userId, $cityId, $page, $perPage)`

### 5. Względy bezpieczeństwa
- RLS, walidacja parametry

### 6. Obsługa błędów
- Invalid params → 400
- Not found → 404

### 7. Wydajność
- OFFSET+LIMIT, indeks `rec_cit_id`, paginacja

### 8. Kroki implementacji
1. Serwisowe `getByCity`
2. Endpoint w `api/cities/.../recommendations/index.php`
3. Walidacja query, testy

---

## 4. POST /api/cities/{cityId}/recommendations

### 1. Przegląd punktu końcowego
Dodaje nowe rekomendacje do istniejącego miasta.

### 2. Szczegóły żądania
- Metoda: POST
- URL: `/api/cities/{cityId}/recommendations`
- Body: `{ "recommendations": [ { title, description, model, status } ] }`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **201 Created** – `{ savedRecommendations, recommendations: [...] }`
- **400**, **401**, **404**, **409**, **500**

### 4. Przepływ danych
- `CityService::assertCityExists(...)`
- `RecommendationService::saveRecommendations(...)`

### 5. Względy bezpieczeństwa
- Identyfikacja właściciela miasta w RLS

### 6. Obsługa błędów
- Brak miasta → 404
- Duplikat tytułu → 409

### 7. Wydajność
- Batch insert

### 8. Kroki implementacji
1. Walidacja miasta
2. Endpoit w `api/cities/.../recommendations/create.php`

---

## 5. GET /api/recommendations/{id}

### 1. Przegląd punktu końcowego
Pobiera szczegóły pojedynczej rekomendacji.

### 2. Szczegóły żądania
- Metoda: GET
- URL: `/api/recommendations/{id}`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **200 OK** – `{ id, cityId, title, description, model, dateCreated, dateModified, status }`
- **401**, **404**, **500**

### 4. Przepływ danych
- `RecommendationService::getById(...)`

### 5. Względy bezpieczeństwa
- RLS

### 6. Obsługa błędów
- Nieznalezione → 404

### 7. Wydajność
- Indeks PK

### 8. Kroki implementacji
1. Endpoint w `api/recommendations/show.php`

---

## 6. POST /api/recommendations

### 1. Przegląd punktu końcowego
Tworzy manualną rekomendację z unikanym tytułem per user+city.

### 2. Szczegóły żądania
- Metoda: POST
- URL: `/api/recommendations`
- Body: `{ cityId, title, description, model: 'manual' }`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **201 Created** – utworzona rekomendacja
- **400**, **401**, **409**, **500**

### 4. Przepływ danych
- `RecommendationService::createManual(...)`

### 5. Względy bezpieczeństwa
- RLS, walidacja cityId

### 6. Obsługa błędów
- Duplikat tytułu → 409

### 7. Wydajność
- Pojedynczy INSERT

### 8. Kroki implementacji
1. Endpoint w `api/recommendations/create.php`

---

## 7. PUT /api/recommendations/{id}

### 1. Przegląd punktu końcowego
Aktualizuje pola rekomendacji (tytuł, opis, status, done).

### 2. Szczegóły żądania
- Metoda: PUT
- URL: `/api/recommendations/{id}`
- Body: opcjonalnie `title`, `description`, `status`, `done`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **200 OK** – zaktualizowana encja
- **400**, **401**, **404**, **500**

### 4. Przepływ danych
- `RecommendationService::update(...)`

### 5. Względy bezpieczeństwa
- RLS

### 6. Obsługa błędów
- Nieznalezione → 404

### 7. Wydajność
- UPDATE z SELECT przed

### 8. Kroki implementacji
1. Endpoint w `api/recommendations/update.php`

---

## 8. PUT /api/recommendations/update-done

### 1. Przegląd punktu końcowego
Zmienia flagę `done` dla wielu rekomendacji.

### 2. Szczegóły żądania
- Metoda: PUT
- URL: `/api/recommendations/update-done`
- Body: `{ recommendationIds: [number], done: boolean }`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **200 OK** – `{ message: "Recommendations updated successfully.", updatedCount: number }`
- **400**, **401**, **500**

### 4. Przepływ danych
- `RecommendationService::bulkUpdateDone(...)`

### 5. Względy bezpieczeństwa
- RLS

### 6. Obsługa błędów
- Brak IDs lub pusta tablica → 400

### 7. Wydajność
- Batch UPDATE

### 8. Kroki implementacji
1. Endpoint w `api/recommendations/update-done.php`

---

## 9. DELETE /api/recommendations/{id}

### 1. Przegląd punktu końcowego
Usuwa pojedynczą rekomendację.

### 2. Szczegóły żądania
- Metoda: DELETE
- URL: `/api/recommendations/{id}`
- Auth: JWT

### 3. Szczegóły odpowiedzi
- **204 No Content**
- **401**, **404**, **500**

### 4. Przepływ danych
- `RecommendationService::deleteById(...)`

### 5. Względy bezpieczeństwa
- RLS, weryfikacja właściciela

### 6. Obsługa błędów
- Nieznalezione → 404

### 7. Wydajność
- DELETE z WHERE PK

### 8. Kroki implementacji
1. Endpoint w `api/recommendations/delete.php`

---

*Dokument zapisano w `.ai/recommendation-implementation-plan.md` dla zespołu deweloperskiego.* 