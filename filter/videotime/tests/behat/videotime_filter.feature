@mod @filter_videotime
Feature: Use video time filter
  In order to use a vimeo in course text, I need to embed videotime with filter
  As an teacher
  I need embed Video Time activity

  Background:
    Given the following "courses" exist:
      | shortname | fullname   |
      | C1        | Course 1 |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
      | student  | Student   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "activities" exist:
      | activity  | name   | intro      | course | vimeo_url                   | section |
      | videotime | Video1 | VideoDesc1 | C1     | https://vimeo.com/253989945 | 1       |
    And the following "filter_videotime > label" exist:
      | name   |
      | Video1 |
    And the "videotime" filter is "on"
    And the following config values are set as admin:
      | disableifediting | 1 | filter_videotime |

  @javascript
  Scenario: Message displayed by filter when editing
    Given I am logged in as "teacher"
    And I am on "Course 1" course homepage
    When I turn editing mode on
    Then I should see "Video Time activity 'Video1' will be displayed when editing is complete"
    And I should not see "Vimeo URL is not set"

  @javascript
  Scenario: Validate Videotime course module id
    Given the following "activities" exist:
      | activity | name | intro                 | course | section |
      | label    | name | [videotime cmid="-1"] | C1     | 1       |
    When I am logged in as "teacher"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    Then I should see "Vimeo URL is not set"

  @javascript
  Scenario: Message not displayed by filter when not editing
    Given I am logged in as "teacher"
    When I am on "Course 1" course homepage
    Then I should not see "Video Time activity 'Video1' will be displayed when editing is complete"
    And I should not see "videotime cmid"
