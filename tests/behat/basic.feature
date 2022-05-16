@ou @ou_vle @report @report_editdates
Feature: Edit course plugin dates
  When a user view edit dates report
  They can change the plugin date settings

  Background: Setup course and sample plugins
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I am on the "Course 1" "course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz name 1       |
      | Description | Test quiz description  |
    Given I log out

  @javascript @_switch_iframe
  Scenario: Test edit dates report can be used to change plugin instance dates
    When I am on the "Course 1" "course" page logged in as "admin"
    And I navigate to "Reports > Dates" in current page administration
    Then I should see "Course 1"
    And I should see "Activity view filter "
    And I follow "Expand all"
    Then I should see "Course start date"
    And I should see "Test quiz name 1"

    # Enable the quiz open and close time settings.
    And I click on "Enable" "checkbox" in the "Open the quiz" "fieldset"
    And I click on "Enable" "checkbox" in the "Close the quiz" "fieldset"
    And I press "Save changes"
    Then I should see "Course 1"
    And I should see "Activity view filter "
    And I follow "Expand all"
    Then I should see "Course start date"
    And I should see "Test quiz name 1"
    And I should see "1" in the "Open the quiz" "fieldset"
    And I should see "1" in the "Close the quiz" "fieldset"
