# Dokument wymagań produktu (PRD) - 10x-city

## 1. Przegląd produktu
System przeznaczony dla turystów, umożliwiający szybkie wyszukiwanie i gromadzenie informacji o atrakcjach w miastach. Aplikacja działa jako strona WWW i korzysta z API GPT-4.1-mini do generowania rekomendacji atrakcji na podstawie kryteriów popularności oraz liczby wzmiankowań w różnych źródłach.

## 2. Problem użytkownika
Turyści mają trudności ze znalezieniem i katalogowaniem informacji o ciekawych miejscach, wydarzeniach czy budynkach w miastach, co jest zadaniem czasochłonnym i wymaga ręcznego gromadzenia danych.

## 3. Wymagania funkcjonalne
- Rejestracja i logowanie:
  - Użytkownik rejestruje się podając login, hasło (szyfrowane w bazie danych) oraz miasto bazowe.
  - Uwierzytelnianie odbywa się przy użyciu loginu i hasła.
  - Bez prawidłowego logowania można tylko dokonać rejestracji i logowania.
- Bezpieczny dostęp:
 - Użytkownik ma mieć możliwość rejestracji i logowania się do systemu w sposób zapewniający bezpieczeństwo moich danych.
  - Kryteria akceptacji:
  - Logowanie i rejestracja odbywają się na dedykowanych stronach.
  - Logowanie wymaga podania nazwy użytkownika i hasła.
  - Rejestracja wymaga podania nazwy użytkownika, miasta bazowego, hasła i potwierdzenia hasła.
  - Użytkownik musi się zalogować by mieć dostęp do systemu.
  - Użytkownik może się wylogować z systemu poprzez przycisk w prawym górnym rogu.
  - Nie korzystamy z zewnętrznych serwisów logowania (np. Google, GitHub).
- Wyszukiwanie atrakcji:
  - Po zalogowaniu użytkownik wprowadza nazwę miasta, które chce odwiedzić.
  - System prezentuje krótką charakterystykę miasta (do 150 znaków) oraz do 10 rekomendowanych propozycji atrakcji.
- Prezentacja propozycji:
  - Każda propozycja zawiera tytuł (do 150 znaków) oraz rozwinięcie wyjaśniające, dlaczego warto zobaczyć dane miejsce.
- Edycja i zarządzanie rekomendacjami:
  - Użytkownik może edytować, akceptować lub odrzucać proponowane atrakcje.
  - Zmiana edytowanej propozycji jest zapisywana natychmiast po kliknięciu przycisku "zapisz" (bez dodatkowego potwierdzenia).
  - Użytkownik może dodać nową pozycję ręcznie, a system waliduje, aby nie dopuścić duplikatów.
- Lista miast:
  - System gromadzi listę miast, dla których użytkownik posiada zapisane atrakcje wraz z liczbą propozycji.
  - Po kliknięciu na miasto użytkownik uzyskuje dostęp do szczegółowej listy atrakcji z możliwością ich edycji lub usuwania.
- Usuwanie propozycji:
  - Usunięcie pozycji wymaga potwierdzenia poprzez wyświetlenie komunikatu: "Czy na pewno chcesz usunąć tę pozycję?".
- Oznaczanie miasta jako "Odwiedzone":
  - Użytkownik ma możliwość oznaczenia miasta jako odwiedzone.
- Uzupełnienie rekomendacji:
  - Jeżeli procent akceptacji propozycji spada poniżej 60%, system wyświetla pytanie o uzupełnienie rekomendacji.
  - Użytkownik, jeśli chce dodatkowych rekomendacji, musi to potwierdzić aktywując przycisk "Uzupełnij rekomendacje" (akcja możliwa tylko raz dla danego miasta).
  - Po uzupełnieniu system prezentuje nowe propozycje na podstawie analizy poprzednich wyników. Prezentuje nowe propozycje nad starymi propozycjami.
- Logowanie działań AI:
  - System rejestruje działania AI, zapisując datę, informacje o użytkowniku, proponowane miejsca oraz statusy (zaakceptowana, edytowana, odrzucona).

## 4. Granice produktu
- Brak wymiany danych między użytkownikami.
- Brak dodawania formatów innych niż tekst (np. zdjęcia, PDF, DOCX).
- Brak integracji z innymi systemami.
- Brak aplikacji mobilnej.
- Nie przewiduje się dodatkowych rozszerzeń funkcjonalnych na etapie MVP.
- Wyniki generowane są przy użyciu technologii PHP, HTML, CSS, jQuery, JavaScript oraz API GPT-4.1-mini.

## 5. Historyjki użytkowników

US-001  
Tytuł: Rejestracja i logowanie  
Opis: Jako turysta chcę się zarejestrować i zalogować, podając wyłącznie login, hasło (szyfrowane) oraz miasto bazowe, aby uzyskać dostęp do systemu.  
Kryteria akceptacji:  
- Użytkownik rejestruje się poprzez formularz rejestracyjny z polami: login, hasło i miasto bazowe.  
- Hasło jest szyfrowane.  
- Użytkownik może się zalogować, używając loginu i hasła.
- Po prawidłowej rejestracji i prawidłowym logowaniu system przechodzi do funkcji wyszukiwania atrakcji.

US-002  
Tytuł: Wyszukiwanie atrakcji w docelowym mieście  
Opis: Jako turysta chcę wprowadzić nazwę miasta, które chcę odwiedzić, aby otrzymać krótką charakterystykę miasta (do 150 znaków) oraz listę do 10 rekomendowanych atrakcji.  
Kryteria akceptacji:  
- Użytkownik wprowadza miasto docelowe w formularzu.  
- System prezentuje charakterystykę miasta (do 150 znaków) oraz listę do 10 propozycji.

US-003  
Tytuł: Przeglądanie i edycja rekomendacji  
Opis: Jako turysta chcę przeglądać, edytować, akceptować lub odrzucać propozycje atrakcji w danym mieście, aby dostosować je do moich potrzeb.  
Kryteria akceptacji:  
- Użytkownik widzi listę propozycji z tytułem i rozwinięciem.  
- Użytkownik może edytować propozycje; zmiany zapisują się po kliknięciu przycisku "zapisz".  
- Użytkownik może oznaczyć propozycję jako zaakceptowaną lub odrzuconą.

US-004  
Tytuł: Dodawanie nowych propozycji  
Opis: Jako turysta chcę móc ręcznie dodawać propozycje atrakcji, aby uzupełnić listę rekomendacji.  
Kryteria akceptacji:  
- Użytkownik ma dostęp do formularza umożliwiającego dodanie nowej pozycji.  
- System weryfikuje, że dodana pozycja nie jest duplikatem już istniejących propozycji.  
- Nowa propozycja pojawia się na liście atrakcji.

US-005  
Tytuł: Zarządzanie listą miast i atrakcji  
Opis: Jako turysta chcę mieć listę miast z liczbą zapisanych atrakcji, aby móc szybko odnaleźć i zarządzać moimi propozycjami.  
Kryteria akceptacji:  
- System wyświetla listę miast z przypisaną liczbą propozycji.  
- Po wybraniu miasta użytkownik widzi szczegółową listę atrakcji z opcjami edycji i usuwania, gdzie usuwanie wymaga potwierdzenia.

US-006  
Tytuł: Oznaczanie miasta jako odwiedzone  
Opis: Jako turysta chcę móc oznaczyć miasto jako "Odwiedzone", aby móc śledzić miejsca, które już odwiedziłem.  
Kryteria akceptacji:  
- Użytkownik ma możliwość oznaczenia miasta jako "Odwiedzone".  
- Status odwiedzenia jest widoczny w systemie i na liście miast.

US-007  
Tytuł: Uzupełnienie rekomendacji  
Opis: Jako turysta chcę uzupełnić rekomendacje, gdy poziom akceptacji propozycji AI spadnie poniżej 60%, aby otrzymać dodatkowe propozycje atrakcji.  
Kryteria akceptacji:  
- System monitoruje poziom akceptacji rekomendacji.  
- Gdy akceptacja spadnie poniżej 60%, użytkownik otrzymuje pytanie o uzupełnienie rekomendacji.  
- Użytkownik aktywuje funkcję poprzez kliknięcie przycisku "Uzupełnij rekomendacje", który może być użyty tylko raz dla danego miasta.  
- System prezentuje nowe propozycje po uzupełnieniu.

US-008  
Tytuł: Logowanie działań AI  
Opis: Jako system chcę rejestrować działania AI związane z prezentacją rekomendacji, aby umożliwić późniejszą analizę.  
Kryteria akceptacji:  
- System zapisuje logi zawierające datę, tożsamość użytkownika, wygenerowane propozycje oraz statusy (zaakceptowana, edytowana, odrzucona).

US-009
Tytuł: Przeglądanie i modyfikacja danych swojego profilu
Opis: Jako turysta chcę przejrzeć wszystkie swoje dane w systemie oraz móc zmienić: nazwę/login użytkownika, hasło, miasto bazowe.
Kryteria akceptacji:
- Nazwa/login użytkownika musi być unikalny.
- Miasto bazowe musi być podane.
- Hasło musi być podane i mieć minimum 5 znaków.

## 6. Metryki sukcesu
- Minimum 75% propozycji musi być zaakceptowanych lub edytowanych i zaakceptowanych.
- Monitorowanie poziomu akceptacji: jeśli akceptacje spadają poniżej 60%, wywoływana jest funkcja uzupełnienia rekomendacji.
- Rejestracja logów AI: system zapisuje operacje na propozycjach (data, użytkownik, status), co pozwala na analizę trafności rekomendacji.
- Liczba wykonanych operacji (edycja, akceptacja, odrzucenie) służy jako wskaźnik użyteczności systemu.

