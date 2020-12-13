Feature: Daily press review

  Scenario: I can see a daily press review
    Given publications have been collected from French press media accounts
    When I access the daily press review
    Then I can see highlights for today