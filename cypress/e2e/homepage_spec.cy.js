    // homepage_spec.cy.js

    // Opis zestawu testów (suite)
    describe('Strona główna aplikacji 10x-city', () => {
      // Opis pojedynczego przypadku testowego
      it('powinna się poprawnie załadować i wyświetlić tytuł', () => {
        // Odwiedź stronę główną (Cypress automatycznie użyje baseUrl)
        cy.visit('/') // Odwiedza baseUrl + '/'

        // Sprawdź, czy tytuł strony zawiera oczekiwany tekst
        // Zmień 'Tytuł Twojej Aplikacji' na rzeczywisty tytuł strony głównej
        cy.title().should('include', '10x-city')

        // Przykład dodatkowej asercji: sprawdź, czy jakiś element jest widoczny
        // np. jeśli masz <header> na stronie:
        // cy.get('header').should('be.visible')
      })
    })