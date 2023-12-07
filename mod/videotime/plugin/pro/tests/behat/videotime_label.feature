@mod @mod_videotime @videotimeplugin_pro
Feature: Configure videotime label mode
  In order to use a video assignment I need to place them on the course page
  As an teacher
  I need view use video and tabs on course page

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
      | activity  | name   | intro      | course | vimeo_url                   | label_mode | section |
      | videotime | Video1 | VideoDesc1 | C1     | https://vimeo.com/253989945 | 1          | 1       |
      | videotime | Video2 | VideoDesc2 | C1     | https://vimeo.com/253989945 | 1          | 2       |
      | videotime | Video3 | VideoDesc2 | C1     | https://vimeo.com/253989945 | 0          | 3       |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I am on the "Video1" "videotime activity editing" page
    And I set the following fields to these values:
      | Enable tab                 | 1               |
      | Video Time Information tab | 1               |
      | Information tab content    | A big rabbit    |
    And I press "Save and return to course"

  Scenario: See activity in normal mode but not label on course page
    Given I am on "Course 1" course homepage
    Then I should see "Video3" in the "Topic 3" "section"
    And I should not see "Video1" in the "Topic 1" "section"

  Scenario: Force label mode
    Given the following config values are set as admin:
    | label_mode_force | 1 | videotime |
    | label_mode       | 1 | videotime |
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    Then I should not see "Video3" in the "Topic 3" "section"

  Scenario: Force normal mode
    Given the following config values are set as admin:
    | label_mode_force | 1 | videotime |
    | label_mode       | 0 | videotime |
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    Then I should see "Video1" in the "Topic 1" "section"

  @javascript
  Scenario: See information on information tab
    Given I am on "Course 1" course homepage
    When I follow "Information"
    Then I should see "A big rabbit" in the "region-main" "region"

  @javascript
  Scenario: Do not see information on watch tab
    Given I am on "Course 1" course homepage
    When I follow "Watch"
    Then I should not see "A big rabbit" in the "region-main" "region"

  @javascript
  Scenario: Separate labes operate independently
    Given I am on "Course 1" course homepage
    And I am on the "Video2" "videotime activity editing" page
    And I set the following fields to these values:
      | Enable tab                 | 1               |
      | Video Time Information tab | 1               |
      | Information tab content    | Another rabbit  |
    And I press "Save and return to course"
    When I click on "Watch" "link" in the "Topic 1" "section"
    And I click on "Information" "link" in the "Topic 2" "section"
    Then I should not see "A big rabbit" in the "region-main" "region"
    And I should see "Another rabbit" in the "region-main" "region"
