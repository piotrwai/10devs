describe('Proces logowania użytkownika', () => {
  // Dane testowego użytkownika - najlepiej byłoby je przenieść do fixtures lub komendy niestandardowej
  // Na potrzeby tego przykładu, zakładamy, że taki użytkownik istnieje
  // Możesz go utworzyć ręcznie w bazie lub przez test rejestracji
  const testUser = {
    login: `testuser_login_${Date.now()}`,
    password: 'TestoweHaslo123',
    cityBase: 'Miasto Logowania'
  };

  before(() => {
    // Rejestrujemy użytkownika raz przed wszystkimi testami w tej grupie
    cy.request({
      method: 'POST',
      url: '/api/users/register',
      body: {
        login: testUser.login,
        password: testUser.password,
        cityBase: testUser.cityBase
      },
      failOnStatusCode: false // Na wypadek, gdyby użytkownik już istniał z poprzedniego przebiegu
    }).then((response) => {
      if (response.status === 201) {
        cy.log(`Użytkownik ${testUser.login} zarejestrowany pomyślnie.`);
      } else if (response.status === 409) {
        cy.log(`Użytkownik ${testUser.login} już istniał.`);
      } else {
        cy.log(`Problem z rejestracją użytkownika ${testUser.login}: Status ${response.status}`);
      }
    });
  });

  beforeEach(() => {
    // Odwiedź stronę logowania przed każdym testem
    cy.visit('/login');
  });

  it('powinien pozwolić zarejestrowanemu użytkownikowi na zalogowanie się', () => {
    cy.get('input#login').type(testUser.login);
    cy.get('input#password').type(testUser.password);
    cy.get('button#login-btn').click();
    cy.url().should('include', '/search');
    // cy.get('a[href="/profile"]').should('be.visible'); // Przykład dodatkowej asercji
  });

  it('powinien wyświetlić błąd dla nieprawidłowego hasła', () => {
    cy.get('input#login').type(testUser.login);
    cy.get('input#password').type('ZleHaslo123');
    cy.get('button#login-btn').click();

    // Sprawdzenie ogólnego komunikatu błędu z serwera
    cy.get('.alert.alert-danger').should('be.visible').and('contain', 'Nieprawidłowy login lub hasło.'); // Dostosuj do faktycznego komunikatu
    cy.url().should('include', '/login'); // Upewnij się, że pozostał na stronie logowania
  });

  it('powinien wyświetlić błąd dla nieistniejącego loginu', () => {
    cy.get('input#login').type('nieistniejacy_user_123xyz');
    cy.get('input#password').type('JakiesHaslo');
    cy.get('button#login-btn').click();

    cy.get('.alert.alert-danger').should('be.visible').and('contain', 'Nieprawidłowy login lub hasło.'); // Dostosuj do faktycznego komunikatu
    cy.url().should('include', '/login');
  });

});