@report @report_editdates @ou @ou_vle
Feature: Edit dates report navigation
  In order to navigate through report page
  As an admin
  Go to course administration -> Reports -> Dates

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | admin | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Selector should be available in the Activities and resources page
    Given I am on the "Course 1" "course" page logged in as "admin"
    When I navigate to "Reports > Dates" in current page administration
    Then "Report" "field" should exist
    And the "Report" select box should contain "Dates"
    And the field "Report" matches value "Dates"
