# Prompt dla generatora Proof of Concept: 10x-city

## Kontekst projektu
Potrzebuję proof of concept dla aplikacji turystycznej "10x-city", która pomoże użytkownikom szybko znaleźć i zapisać atrakcje w miastach. Aplikacja ma wykorzystywać API GPT-4.1-mini do generowania rekomendacji na podstawie nazwy miasta.

## Zakres PoC
Skoncentrujmy się WYŁĄCZNIE na podstawowej funkcjonalności: generowaniu i prezentacji rekomendacji atrakcji turystycznych dla wybranego miasta. 

### Funkcjonalności objęte PoC:
1. Prosty formularz do wprowadzania nazwy miasta
2. Wygenerowanie przez AI:
   - Krótkiej charakterystyki miasta (do 150 znaków)
   - Listy 10 rekomendowanych atrakcji (każda z tytułem i opisem)
3. Wyświetlenie wygenerowanych rekomendacji w czytelnej formie
4. Podstawowa obsługa błędów (np. brak wprowadzonej nazwy miasta)

### Funkcjonalności wykluczone z PoC:
- Rejestracja/logowanie użytkowników
- Edycja/zapisywanie/odrzucanie propozycji
- Lista miast i atrakcji
- Oznaczanie miast jako odwiedzone
- Uzupełnianie rekomendacji
- Logowanie działań AI do bazy danych

## Technologie do wykorzystania
- Frontend: HTML, CSS, jQuery, JavaScript
- Backend: PHP 8
- Komunikacja z AI: API GPT-4.1-mini
- Baza danych: MySQL

## Instrukcje
1. Zanim zaczniesz tworzyć kod, przedstaw plan pracy w punktach wraz z szacunkami czasowymi.
2. Poczekaj na moją akceptację planu, zanim przejdziesz do tworzenia kodu.
3. Kod powinien być prosty, dobrze skomentowany i gotowy do uruchomienia na serwerze WAMP.
4. Struktura katalogów powinna być minimalistyczna i logiczna.
5. Pamiętaj o obsłudze błędów (zwłaszcza w komunikacji z API).

## Oczekiwany rezultat
Prosty, działający PoC, który pozwoli użytkownikowi wpisać nazwę miasta i otrzymać wygenerowane przez AI rekomendacje atrakcji. Kod powinien być łatwy do rozbudowy w przyszłości.