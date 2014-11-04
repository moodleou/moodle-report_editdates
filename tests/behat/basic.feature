@ou @ou_vle @report @report_editdates
Feature: Edit course plugin dates
    When a user view edit dates report
    They can change the plugn date settings

    Background: Setup course and sample plugins
        Given the following "users" exist:
            | username | firstname | lastname | email |
            | teacher1 | Teacher | 1 | teacher1@asd.com |
            | student1 | Student | 1 | student1@asd.com |
            | student2 | Student | 2 | student2@asd.com |
            | student3 | Student | 3 | student3@asd.com |
            | student4 | Student | 4 | student4@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1 | 0 |
        And the following "course enrolments" exist:
            | user | course | role |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student |
            | student2 | C1 | student |
            | student3 | C1 | student |
            | student4 | C1 | student |
        And I log in as "teacher1"
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Quiz" to section "1" and I fill the form with:
          | Name | Test quiz name 1 |
          | Description | Test forum description |
        And I add a "Quiz" to section "2" and I fill the form with:
          | Name | Test quiz name 2 |
          | Description | Test forum description |
        And I add a "Quiz" to section "3" and I fill the form with:
          | Name | Test quiz name 3 |
          | Description | Test forum description |
        Given I log out

    @javascript @_switch_iframe
    Scenario: Test edit dates report can be used to change plugin instance dates
        When I log in as "admin"
        And I follow "Course 1"
        And I navigate to "Dates" node in "Course administration > Reports"
        And I follow "Dates"
        Then I should see "Course 1"
        And I should see "Activity view filter "
        And I follow "Expand all"
        Then I should see "Course start date"
        And I should see "Test quiz name 1"
        And I should see "Test quiz name 2"
        And I should see "Test quiz name 3"
        # In ouvle, the three quizzes get cmids 2, 3, 4 (because one module creates
        # an instance of itself on install). In core Moodle they are 1, 2, 3.
        # So, to aviod problems we only test with cmids 2 and 3.
        When I set the following fields to these values:
            | id_date_mod_2_timeopen_enabled  | 1 |
            | id_date_mod_2_timeclose_enabled | 1 |
            | id_date_mod_3_timeopen_enabled  | 1 |
            | id_date_mod_3_timeclose_enabled | 1 |
        And I press "Save changes"
        Then I should see "Course 1"
        And I should see "Activity view filter "
        And I follow "Expand all"
        Then I should see "Course start date"
        And I should see "Test quiz name 1"
        And I should see "Test quiz name 2"
        And I should see "Test quiz name 3"
        And the field "id_date_mod_2_timeopen_enabled" matches value "1"
        And the field "id_date_mod_2_timeclose_enabled" matches value "1"
        And the field "id_date_mod_3_timeopen_enabled" matches value "1"
        And the field "id_date_mod_3_timeclose_enabled" matches value "1"
