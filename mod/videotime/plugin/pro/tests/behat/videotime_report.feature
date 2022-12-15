@videotime @videotimeplugin_pro @videotimeplugin_pro_report
Feature: View report for Video time
  In order see Video Time user data
  As a teacher
  I need to view Video Time report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname    | email                | idnumber | middlename | alternatename | firstnamephonetic | lastnamephonetic |
      | teacher1 | Teacher   | 1           | teacher1@example.com | t1       |            | fred          |                   |                  |
      | student1 | Grainne   | Beauchamp   | student1@example.com | s1       | Ann        | Jill          | Gronya            | Beecham          |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity  | name   | intro      | course | vimeo_url                   | label_mode | section |
      | videotime | Video1 | VideoDesc1 | C1     | https://vimeo.com/253989945 | 0          | 1       |
    And the following config values are set as admin:
      | fullnamedisplay | firstname |
      | alternativefullnameformat | middlename, alternatename, firstname, lastname |

  @javascript
  Scenario: Go to the Video Time report
    Given I log in as "teacher1"
    And I am on the "Video1" "videotime activity" page
    When I navigate to "Report" in current page administration
    Then I should see "Nothing to display"
