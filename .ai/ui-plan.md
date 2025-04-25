/*
Plik: .ai/ui-plan.md
Opis: Szczegółowa architektura interfejsu użytkownika dla produktu 10x-city
*/

# Architektura UI dla 10x-city

## 1. Przegląd struktury UI
- Interfejs jest podzielony na kilka kluczowych widoków:
  - Ekran logowania/rejestracji
  - Dashboard miast użytkownika
  - Widok wyszukiwania rekomendacji
  - Widok szczegółowy miasta
  - Ekran edycji rekomendacji
  - Dane użytkownika
  - (Opcjonalnie) Widok logów administracyjnych
- Główna nawigacja jest realizowana poprzez górne menu, bez użycia breadcrumbs.
- Całość opiera się na responsywnym designie z użyciem Bootstrap, jQuery oraz szablonów Smarty, zgodnych z istniejącym kodem.
- Komunikacja z backendem odbywa się asynchronicznie (Ajax) bez strategii cache, a stan aplikacji zarządzany jest przez centralny store.
- Bezpieczeństwo jest zachowane poprzez dotychczasowe mechanizmy przechowywania tokena JWT oraz obsługi błędów uprawnień.

## 2. Lista widoków

### 2.1. Ekran logowania/rejestracji
- **Ścieżka widoku:** /login
- **Główny cel:** Umożliwienie użytkownikowi logowania lub rejestracji
- **Kluczowe informacje:** Formularz logowania, formularz rejestracji, komunikaty o błędach
- **Kluczowe komponenty:** Formularze, przyciski, alerty
- **UX, dostępność, bezpieczeństwo:** Prosty, intuicyjny interfejs; walidacja po stronie klienta; zabezpieczenia zgodne z dotychczasową implementacją

### 2.2. Dashboard miast użytkownika
- **Ścieżka widoku:** /dashboard
- **Główny cel:** Prezentacja zapisanych miast użytkownika
- **Kluczowe informacje:** Lista miast, liczba rekomendacji, przyciski do przejścia do szczegółów miasta oraz usunięcia miasta z potwierdzeniem
- **Kluczowe komponenty:** Listy, przyciski, alerty (Tak/Nie), przycisk dodania nowego miasta
- **UX, dostępność, bezpieczeństwo:** Intuicyjny interfejs umożliwiający szybki dostęp; responsywny design; operacje krytyczne potwierdzane alertami

### 2.3. Widok wyszukiwania rekomendacji
- **Ścieżka widoku:** /search
- **Główny cel:** Umożliwienie wyszukiwania rekomendacji dla zadanego miasta
- **Kluczowe informacje:** Pole do wpisania nazwy miasta, przycisk "Znajdź rekomendacje", komunikat "Trwa wyszukiwanie rekomendacji..."
- **Kluczowe komponenty:** Formularz wyszukiwania, wskaźnik ładowania, lista wyników, sekcja do ręcznego dodawania rekomendacji
- **UX, dostępność, bezpieczeństwo:** Wyraźne komunikaty informujące o procesie wyszukiwania; możliwość ponownego wyszukiwania rekomendacji, jeżeli akceptacje spadają poniżej 60% (sprawdzane po każdym odrzuceniu rekomendacji)

### 2.4. Widok szczegółowy miasta
- **Ścieżka widoku:** /city/{id}
- **Główny cel:** Zarządzanie danym miastem i jego rekomendacjami
- **Kluczowe informacje:** Wyróżniona nazwa miasta, status "Odwiedzone" (jeśli dotyczy), opis miasta w textarea, lista rekomendacji
- **Kluczowe komponenty:** Tekstowe wyświetlanie informacji, przyciski (usunięcia, zmiany statusu, edycji), tekstarea z przyciskiem "Zapisz"
- **UX, dostępność, bezpieczeństwo:** Intuicyjne rozmieszczenie elementów; możliwość edycji inline lub przez modal; wyraźna wizualizacja statusu (np. niebieski tekst dla "Odwiedzone"); alerty przed operacjami nieodwracalnymi

### 2.5. Ekran edycji rekomendacji
- **Ścieżka widoku:** Może być zintegrowany z widokiem szczegółowym miasta (inline) lub w formie modal
- **Główny cel:** Umożliwienie edycji pojedynczej rekomendacji
- **Kluczowe informacje:** Formularz edycji rekomendacji, możliwości akceptacji, odrzucenia lub usunięcia
- **Kluczowe komponenty:** Formularz, przyciski, alerty
- **UX, dostępność, bezpieczeństwo:** Spójność z innymi operacjami; możliwość szybkiej edycji; potwierdzenia operacji krytycznych

### 2.6. Dane użytkownika
- **Ścieżka widoku:** /profile
- **Główny cel:** Przegląd i edycja danych użytkownika (zmiana nazwy, hasła, miasta)
- **Kluczowe informacje:** Formularz danych, walidacja unikalności nazwy użytkownika
- **Kluczowe komponenty:** Formularze, przyciski, komunikaty błędów
- **UX, dostępność, bezpieczeństwo:** Jasny układ formularzy; natychmiastowa walidacja; zabezpieczenia zgodne z dotychczasową implementacją

### 2.7. Widok logów administracyjnych (dla admina)
- **Ścieżka widoku:** /admin/logs
- **Główny cel:** Przegląd logów AI i błędów
- **Kluczowe informacje:** Kategorie logów, możliwość filtrowania według typu, daty i użytkownika
- **Kluczowe komponenty:** Tabele, filtry, paginacja
- **UX, dostępność, bezpieczeństwo:** Widoczność tylko dla użytkowników z uprawnieniami admina; intuicyjny interfejs do analizy logów

## 3. Mapa podróży użytkownika
- **Krok 1:** Użytkownik wchodzi na stronę i trafia do ekranu logowania/rejestracji (/login).
- **Krok 2:** Po zalogowaniu użytkownik zostaje przekierowany do dashboardu miast (/dashboard). Jeśli nie posiada żadnego zapisanego miasta, domyślnie jest przekierowywany do widoku wyszukiwania rekomendacji (/search).
- **Krok 3:** W widoku wyszukiwania rekomendacji użytkownik wpisuje nazwę miasta i klika "Znajdź rekomendacje". Wyświetlany jest komunikat "Trwa wyszukiwanie rekomendacji...".
- **Krok 4:** Po zakończeniu wyszukiwania wyświetlane są rekomendacje wraz z możliwością ich edycji, akceptacji, odrzucenia oraz ręcznego dodania kolejnej rekomendacji. Zapis miasta i rekomendacji do bazy danych następuje dopiero po kliknięciu 'Zapisz do bazy'. Kliknięcie tego przycisku jest możliwe tylko jeśli wszystkie rekomendacje zostaną zaakceptone lub odrzucone. Jeśli nie są pojawia się komunikat - "Oceń wszystkie rekomendacje". Rekomendacje wprowadzone ręcznie są automatycznie zaakceptowane.
- **Krok 5:** Użytkownik może przejść do szczegółowego widoku miasta (/city/{id}), klikając wybrane miasto z dashboardu. Tam zarządza opisem miasta, listą rekomendacji, zmienia status miasta (odwiedzone/odwiedź ponownie) oraz usuwa miasto wraz z rekomendacjami.
- **Krok 6:** W dowolnym momencie użytkownik może wejść do swojego profilu (/profile) w celu edycji danych lub, jeśli posiada uprawnienia admina, do widoku logów administracyjnych (/admin/logs).

## 4. Układ i struktura nawigacji
- Główna nawigacja realizowana jest przez górne menu, które jest dostępne na wszystkich stronach.
- Menu zawiera linki do: Dashboard, Wyszukiwania rekomendacji, Profilu oraz (dla admina) do widoku logów.
- Nawigacja jest prosta i intuicyjna, bez dodatkowych elementów jak breadcrumbs, co zapewnia czysty i responsywny design.

## 5. Kluczowe komponenty
- **Formularz logowania/rejestracji:** Umożliwia autoryzację z wykorzystaniem walidacji i zabezpieczeń (JWT).
- **Lista miast:** Wyświetla zapisane miasta wraz z akcjami (przejście do szczegółów, usunięcie).
- **Formularz wyszukiwania rekomendacji:** Z polem tekstowym, przyciskiem "Znajdź rekomendacje" i wskaźnikiem ładowania.
- **Komponent rekomendacji:** Lista rekomendacji z opcjami edycji, akceptacji (kolor czarny), odrzucenia (kolor szary) oraz możliwością masowej akceptacji.
- **Modal/inline edycji rekomendacji:** Umożliwia modyfikację wybranej rekomendacji wraz z alertami typu Tak/Nie dla operacji krytycznych.
- **Textarea do edycji opisu miasta:** Z przyciskiem "Zapisz" do aktualizacji opisu miasta.
- **Przyciski zmiany statusu miasta:** Dynamicznie wyświetlają opcję "Odwiedzone" lub "Odwiedź ponownie" w zależności od stanu miasta.
- **Górne menu:** Globalna nawigacja umożliwiająca dostęp do wszystkich głównych widoków. 