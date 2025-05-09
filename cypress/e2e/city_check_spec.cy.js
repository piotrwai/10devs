/// <reference types="cypress" />

/**
 * Test end-to-end do sprawdzania duplikatów miast
 */
describe('Sprawdzanie duplikatów miast', () => {
    
    // Dane testowe
    const testUser = {
        login: `test_user_${Date.now()}`,
        password: 'testPassword123',
        cityBase: 'Warszawa'
    };
    
    const testCity = 'Kraków';
    
    beforeEach(() => {
        // Czyszczenie cookies i storage przed każdym testem
        cy.clearCookies();
        cy.clearLocalStorage();
    });
    
    it('Powinien utworzyć konto, dodać miasto i wykryć jego duplikat', () => {
        // 1. Rejestracja użytkownika
        cy.visit('/register');
        cy.get('#login').type(testUser.login);
        cy.get('#password').type(testUser.password);
        cy.get('#confirm-password').type(testUser.password);
        cy.get('#cityBase').type(testUser.cityBase);
        cy.get('#register-form button[type="submit"]').click();
        
        // Weryfikacja poprawnej rejestracji - przekierowanie do logowania
        cy.url().should('include', '/login');
        cy.get('.alert-success').should('be.visible')
            .and('contain', 'Rejestracja zakończona pomyślnie');
        
        // 2. Logowanie użytkownika
        cy.get('#login').type(testUser.login);
        cy.get('#password').type(testUser.password);
        cy.get('#login-form button[type="submit"]').click();
        
        // Weryfikacja poprawnego logowania - przekierowanie do dashboardu
        cy.url().should('include', '/search');
        
        // 3. Wyszukiwanie nowego miasta
        cy.visit('/cities/search');
        cy.get('#cityName').type(testCity);
        cy.get('#city-search-form button[type="submit"]').click();
        
        // Weryfikacja, że rekomendacje się pojawiły
        cy.get('#search-results').should('be.visible');
        cy.get('#recommendations-list .recommendation').should('have.length.greaterThan', 0);
        
        // 4. Zapisanie rekomendacji miasta
        cy.get('#accept-all-btn').click();
        cy.get('#save-recommendations-btn').click();
        cy.get('#saveConfirmationModal').should('be.visible');
        cy.get('#confirmSave').click();
        
        // Weryfikacja zapisania i przekierowania
        cy.url().should('include', '/dashboard');
        
        // 5. Ponowne wyszukiwanie tego samego miasta aby sprawdzić duplikat
        cy.visit('/cities/search');
        cy.get('#cityName').type(testCity);
        cy.get('#city-search-form button[type="submit"]').click();
        
        // Weryfikacja wykrycia duplikatu
        cy.wait(1000); // Czekamy na odpowiedź API
        cy.get('#search-btn').should('not.be.visible'); // Przycisk wyszukiwania powinien zniknąć
        cy.get('#form-messages').should('be.visible')
            .and('have.class', 'alert-success')
            .and('contain', 'Istnieją już rekomendacje dla tego miasta');
        
        // Weryfikacja, że przycisk "Przejdź" jest widoczny i linkuje do właściwej strony
        cy.get('#form-messages a.btn')
            .should('be.visible')
            .and('contain', 'Przejdź')
            .and('have.attr', 'href')
            .and('include', '/city/')
            .and('include', '/recommendations');
        
        // 6. Kliknięcie przycisku Przejdź
        cy.get('#form-messages a.btn').click();
        
        // Weryfikacja przekierowania do rekomendacji miasta
        cy.url().should('include', '/city/');
        cy.url().should('include', '/recommendations');
    });
    
    it('Powinien umożliwić wyszukanie nowego miasta po zmianie nazwy', () => {
        // Logowanie
        cy.visit('/login');
        cy.get('#login').type(testUser.login);
        cy.get('#password').type(testUser.password);
        cy.get('#login-form button[type="submit"]').click();
        
        // Przejście do wyszukiwania
        cy.visit('/cities/search');
        
        // Wyszukiwanie istniejącego miasta z modyfikacją nazwy
        const modifiedCityName = testCity + ' 2023';
        cy.get('#cityName').type(modifiedCityName);
        cy.get('#city-search-form button[type="submit"]').click();
        
        // Weryfikacja, że proces wyszukiwania działa normalnie (nie wykryto duplikatu)
        cy.get('#search-results').should('be.visible');
        cy.get('#recommendations-list .recommendation').should('have.length.greaterThan', 0);
    });
}); 