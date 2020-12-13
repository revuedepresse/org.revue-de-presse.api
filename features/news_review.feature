Feature: News review

  Scenario: Real-time access to news review
    Given there are publications on a given day
    And a news review is requested by an authenticated consumer
    When publications for this day have been sorted by popularity
    Then these publications are ready to be served