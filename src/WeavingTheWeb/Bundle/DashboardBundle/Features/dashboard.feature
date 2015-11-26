Feature: Default perspective
  I can access the default perspective from the dashboard

  Scenario: Display Edit query form
    Given I am on "/documents"
    Then I should see "Show administration panel" in the "#sql" element
    And the response should contain "Save your query"
    And the response should contain "Execute your query"
