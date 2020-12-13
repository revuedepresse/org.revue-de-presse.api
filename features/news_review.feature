Feature: Daily press review

  Scenario: I can see a daily press review
    Given there are new publications
    When I access the daily press review
    Then I can see highlights for today