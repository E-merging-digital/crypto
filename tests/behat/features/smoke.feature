Feature: Smoke test
  In order to verify the site is up
  As a visitor
  I want to see the front page

  Scenario: Front page loads
    Given I am on "/"
    Then the response status code should be 200
