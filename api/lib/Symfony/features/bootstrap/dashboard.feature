Feature: Access to perspectives

Scenario: Display administration perspective
Given I am on "/sf2/app_dev.php/documents"
Then I should see "Update administration panel" in the "#sql" element
