// cypress/e2e/registration_spec.cy.js

describe('Proces rejestracji użytkownika', () => {
  const testPassword = 'TestoweHaslo123';

  beforeEach(() => {
    // Odwiedź stronę rejestracji przed każdym testem w tej grupie
    cy.visit('/register');
  });

  it('powinien pozwolić nowemu użytkownikowi na rejestrację z poprawnymi danymi', () => {
    const uniqueLogin = `testuser_valid_${Date.now()}`;

    cy.get('input#login').type(uniqueLogin);
    cy.get('input#password').type(testPassword);
    cy.get('input#confirm-password').type(testPassword);
    cy.get('input#cityBase').type('Testowe Miasto Poprawne');
    cy.get('button#register-btn').click();
    cy.url().should('include', '/login');
    // Dodatkowo można sprawdzić komunikat o sukcesie na stronie logowania, jeśli jest
    // Np. cy.get('.alert-success').should('contain', 'Rejestracja zakończona pomyślnie');
  });

  it('powinien wyświetlić błąd, gdy hasła się nie zgadzają', () => {
    const uniqueLogin = `testuser_pwd_mismatch_${Date.now()}`;

    cy.get('input#login').type(uniqueLogin);
    cy.get('input#password').type(testPassword);
    cy.get('input#confirm-password').type('InneHaslo123'); // Różne hasła
    cy.get('input#cityBase').type('Miasto Testowe Niezgodne Hasla');
    cy.get('button#register-btn').click();

    // Sprawdzenie komunikatu błędu przy polu potwierdzenia hasła
    // Zakładamy, że walidacja po stronie klienta/serwera doda klasę is-invalid i wyświetli komunikat
    cy.get('input#confirm-password').should('have.class', 'is-invalid'); // Jeśli Bootstrap dodaje tę klasę
    cy.get('div#confirm-password-error').should('be.visible').and('contain', 'Hasła nie są identyczne');
    
    // Upewnij się, że nie nastąpiło przekierowanie
    cy.url().should('not.include', '/login');
    cy.url().should('include', '/register');
  });

  it('powinien wyświetlić błąd, gdy login jest za krótki (walidacja HTML5/JS)', () => {
    // Ten test sprawdza głównie walidację HTML5 lub JS po stronie klienta
    cy.get('input#login').type('aa'); // Login za krótki
    cy.get('input#password').type(testPassword);
    cy.get('input#confirm-password').type(testPassword);
    cy.get('input#cityBase').type('Miasto Krotki Login');
    cy.get('button#register-btn').click();
    
      // Sprawdzenie komunikatu błędu przy polu login, generowanego przez register.js
      cy.get('div#login-error')
        .should('be.visible')
        .and('contain', 'Login musi mieć od 2 do 50 znaków'); // Upewnij się, że tekst jest DOKŁADNIE taki sam
  
      // Dodatkowo sprawdź, czy pole ma klasę 'is-invalid'
      cy.get('input#login').should('have.class', 'is-invalid');
  
      // Upewnij się, że nie nastąpiło przekierowanie
      cy.url().should('not.include', '/login');
      cy.url().should('include', '/register');
  });

  it('powinien wyświetlić błąd serwera, gdy login jest już zajęty', () => {
    const existingLogin = `test`;
    // Krok 1: Zarejestruj użytkownika, aby upewnić się, że login istnieje
    cy.request({
      method: 'POST',
      url: '/api/users/register', // Bezpośrednie wywołanie API do rejestracji
      body: {
        login: existingLogin,
        password: testPassword,
        cityBase: 'Miasto Istniejace'
      },
      failOnStatusCode: false // Nie przerywaj testu, jeśli API zwróci błąd (np. 409)
    }).then((response) => {
      // Można sprawdzić, czy pierwsza rejestracja się powiodła lub zwróciła oczekiwany status
      // np. expect(response.status).to.be.oneOf([201, 409]); // 201 jeśli nie istniał, 409 jeśli test był już uruchamiany
    });

    // Krok 2: Spróbuj zarejestrować tego samego użytkownika przez UI
    cy.visit('/register'); // Ponowne odwiedzenie, bo poprzedni test mógł zmienić stronę
    cy.get('input#login').type(existingLogin);
    cy.get('input#password').type(testPassword);
    cy.get('input#confirm-password').type(testPassword);
    cy.get('input#cityBase').type('Miasto Istniejace Ponownie');
    cy.get('button#register-btn').click();

    // Sprawdzenie ogólnego komunikatu błędu z serwera
    cy.get('.alert.alert-danger').should('be.visible').and('contain', 'Login jest już zajęty'); // Dostosuj do faktycznego komunikatu
    cy.url().should('include', '/register'); // Upewnij się, że pozostał na stronie rejestracji
  });

  // Można dodać więcej testów dla innych pól, np. zbyt krótkie hasło, puste miasto bazowe etc.
})