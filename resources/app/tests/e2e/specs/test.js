// https://docs.cypress.io/api/introduction/api.html

describe('My First Test', () => {
  it('Visits the app root url', () => {
    cy.visit('http://localhost:8081/');
    cy.url().should('include', '/login')
    cy.contains('Login')
    cy.contains('Email:')
    cy.contains('Password:')
    cy.get('[name=email]')
      .type('jward3@email.unc.edu');
    cy.get('[name=password]').type('tester');
    cy.get('[name=login-button]').click();
  })
})
