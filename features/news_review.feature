Feature: News review

  Scenario: Real-time access to news review
    Given there are publications on a given day
    When a news review is requested by an authenticated consumer
    Then these publications are ready to be served