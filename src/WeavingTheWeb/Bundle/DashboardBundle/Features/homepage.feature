Feature: Registration and Login
  Registration and login forms are available from the homepage

  Scenario: Display Register form
    Given I am on the homepage
    Then I should see "Register" in the "#register" element

  Scenario: Display Login form
    Given I am on the homepage
    Then I should see "Log in" in the "#login" element

  Scenario: Display Login form
    Given I am on the homepage
    Then I should not see "404" in the "body" element
