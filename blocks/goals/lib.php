<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for goals.
 *
 * @package   block_goals
 * @copyright 2022 David Aylmer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/badgeslib.php');

use block_goals\models;

/**
 * Constants for the user preferences grouping options
 */
const BLOCK_GOALS_REMINDER_NEVER = 0;
const BLOCK_GOALS_REMINDER_MONTHLY = 1;
const BLOCK_GOALS_REMINDER_WEEKLY = 2;
const BLOCK_GOALS_REMINDER_TESTING = 3;


/**
 * Allows selection of all courses without a value for the custom field.
 */
define('BLOCK_GOALS_CUSTOMFIELD_EMPTY', -1);

/**
 * Constants for the user preferences sorting options
 * timeline
 */
define('BLOCK_GOALS_SORTING_TITLE', 'title');
define('BLOCK_GOALS_SORTING_LASTACCESSED', 'lastaccessed');

/**
 * Constants for the user preferences view options
 */
define('BLOCK_GOALS_VIEW_CARD', 'card');
define('BLOCK_GOALS_VIEW_LIST', 'list');
define('BLOCK_GOALS_VIEW_SUMMARY', 'summary');

/**
 * Constants for the user paging preferences
 */
define('BLOCK_GOALS_PAGING_12', 12);
define('BLOCK_GOALS_PAGING_24', 24);
define('BLOCK_GOALS_PAGING_48', 48);
define('BLOCK_GOALS_PAGING_96', 96);
define('BLOCK_GOALS_PAGING_ALL', 0);

/**
 * Constants for the admin category display setting
 */
define('BLOCK_GOALS_DISPLAY_CATEGORIES_ON', 'on');
define('BLOCK_GOALS_DISPLAY_CATEGORIES_OFF', 'off');

/**
 * Get the current user preferences that are available
 *
 * @return mixed Array representing current options along with defaults
 */
function block_goals_user_preferences() {
    $preferences['block_goals_user_grouping_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_GOALS_GROUPING_ALL,
        'type' => PARAM_ALPHA,
        'choices' => array(
            BLOCK_GOALS_GROUPING_ALLINCLUDINGHIDDEN,
            BLOCK_GOALS_GROUPING_ALL,
            BLOCK_GOALS_GROUPING_INPROGRESS,
            BLOCK_GOALS_GROUPING_FUTURE,
            BLOCK_GOALS_GROUPING_PAST,
            BLOCK_GOALS_GROUPING_FAVOURITES,
            BLOCK_GOALS_GROUPING_HIDDEN,
            BLOCK_GOALS_GROUPING_CUSTOMFIELD,
        )
    );

    $preferences['block_goals_user_grouping_customfieldvalue_preference'] = [
        'null' => NULL_ALLOWED,
        'default' => null,
        'type' => PARAM_RAW,
    ];

    $preferences['block_goals_user_sort_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_GOALS_SORTING_TITLE,
        'type' => PARAM_ALPHA,
        'choices' => array(
            BLOCK_GOALS_SORTING_TITLE,
            BLOCK_GOALS_SORTING_LASTACCESSED
        )
    );
    $preferences['block_goals_user_view_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_GOALS_VIEW_CARD,
        'type' => PARAM_ALPHA,
        'choices' => array(
            BLOCK_GOALS_VIEW_CARD,
            BLOCK_GOALS_VIEW_LIST,
            BLOCK_GOALS_VIEW_SUMMARY
        )
    );

    $preferences['/^block_goals_hidden_course_(\d)+$/'] = array(
        'isregex' => true,
        'choices' => array(0, 1),
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 'none'
    );

    $preferences['block_goals_user_paging_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_GOALS_PAGING_12,
        'type' => PARAM_INT,
        'choices' => array(
            BLOCK_GOALS_PAGING_12,
            BLOCK_GOALS_PAGING_24,
            BLOCK_GOALS_PAGING_48,
            BLOCK_GOALS_PAGING_96,
            BLOCK_GOALS_PAGING_ALL
        )
    );

    return $preferences;
}


/**
 * Serve the files from the MYPLUGIN file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function block_goals_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'content') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true);
/*
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/MYPLUGIN:view', $context)) {
        return false;
    }
*/
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_goals', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function get_reminderfrequencies() {
    $frequencies = [
        BLOCK_GOALS_REMINDER_NEVER => 'Never',
        BLOCK_GOALS_REMINDER_MONTHLY => 'Monthly',
        BLOCK_GOALS_REMINDER_WEEKLY => 'Weekly',
    ];

    $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
    if ($managegoals) {
        $frequencies[BLOCK_GOALS_REMINDER_TESTING] = 'Testing';
    }

    return $frequencies;
}

function send_reminder_emails($reminderfrequency = 0) {
    try {
        $time = time();
        $success = 0;
        $failure = 0;

        global $CFG, $DB;

        // Get all users that have future goals and add them to container array.
        $goalusers = [];

        // Get all users that have future individual goals.
        $query = '
            SELECT DISTINCT(u.*)
            FROM {goal_goals} g
            INNER JOIN {user} u ON u.id = g.userid
            WHERE g.type = :type AND duedate >= :duedate
            AND u.id != :siteguestid
        ';
        $params = [
            'type' => models\goal::TYPE_INDIVIDUAL,
            'duedate' => $time,
            'siteguestid' => $CFG->siteguest,
        ];
        $users = $DB->get_records_sql($query, $params);

        foreach ($users as $user) {
            // Duplicates overriden.
            $goalusers[$user->id] = $user;
        }

        // Get all users that have future team goals.
        $query = '
            SELECT DISTINCT(u.*)
            FROM {goal_goals} g
            INNER JOIN {goal_team} gt ON gt.id = g.teamid AND g.type = :type
            INNER JOIN {goal_teammembers} gtm ON gtm.teamid = gt.id
            INNER JOIN {user} u ON u.id = gtm.userid 
            WHERE duedate >= :duedate
            AND u.id <> :siteguestid
        ';
        $params = [
            'type' => models\goal::TYPE_TEAM,
            'duedate' => $time,
            'siteguestid' => $CFG->siteguest,
        ];

        $users = $DB->get_records_sql($query, $params);

        foreach ($users as $user) {
            // Duplicates overriden.
            $goalusers[$user->id] = $user;
        }
        mtrace('Total users with goals: ' . count($goalusers));

        // For all users with future goals:
        foreach ($goalusers as $userid => $goaluser) {

            $goaltext = '';
            $goalhtml = '<br>';

            $userreminderfrequency = get_user_preferences('goalreminderfrequencysetting', 0, $goaluser);
            $reminderfrequencies = get_reminderfrequencies();

            $reminderfrequenytext = 'empty';
            if (array_key_exists($userreminderfrequency, $reminderfrequencies)) {
                $reminderfrequenytext = $reminderfrequencies[$userreminderfrequency];
            }

            if ($userreminderfrequency != $reminderfrequency) {
                // 'Never', skip this user:
                mtrace('Skipping user: ' . fullname($goaluser));
                continue;
            }

            $individualgoals = models\goal::get_records_select(
                'userid = :userid AND type = :type AND duedate >= :duedate AND hidden = :hidden', [
                'userid' => $goaluser->id,
                'type' => models\goal::TYPE_INDIVIDUAL,
                'duedate' => $time,
                'hidden' => models\goal::HIDDEN_FALSE
            ], 'duedate ASC');
            if (!empty($individualgoals)) {
                $goaltext .= get_string('mysmartgoals', 'block_goals') . PHP_EOL;
                $goalhtml .= '<b>' . get_string('mysmartgoals', 'block_goals') . '</b><br/>';
            }

            foreach ($individualgoals as $individualgoal) {

                $duedate = userdate($individualgoal->get('duedate'),get_string('strftimedatefullshort'));
                $link = (new \moodle_url('/blocks/goals/view.php', ['action' => 'trackgoal', 'id' => $individualgoal->get('id')]))->out(false);

                $goaltext .= get_string('emailgoaltext', 'block_goals', (object)[
                        'goal' => $individualgoal->get('goaltext'),
                        'progress' => $individualgoal->get('progress'),
                        'duedate' => $duedate
                    ]) . PHP_EOL;
                $goalhtml .= get_string('emailgoalhtml', 'block_goals', (object)[
                        'goal' => $individualgoal->get('goaltext'),
                        'progress' => $individualgoal->get('progress'),
                        'link' => $link,
                        'duedate' => $duedate
                    ]) . '<br/>' . PHP_EOL;
            }

            $myteammembers = models\teammember::get_records(['userid' => $goaluser->id], 'timemodified', 'DESC');
            foreach ($myteammembers as $myteammember) {
                $team = new models\team($myteammember->get('teamid'));

                $teamgoals = models\goal::get_records_select(
                    'teamid = :teamid AND type = :type AND duedate >= :duedate AND hidden = :hidden', [
                    'teamid' => $team->get('id'),
                    'type' => models\goal::TYPE_TEAM,
                    'duedate' => $time,
                    'hidden' => models\goal::HIDDEN_FALSE
                ], 'duedate ASC');

                if (!empty($teamgoals)) {
                    $goaltext .= PHP_EOL . 'Team: ' . $myteammember->get_team()->get('name') . ' SMART Goals';
                    $goalhtml .= '<br/>' . PHP_EOL . '<b>Team: ' . $myteammember->get_team()->get('name') . ' SMART goals</b><br/>' . PHP_EOL;
                }

                //mtrace('Team ' . $myteammember->get_team()->get('name') . ' goals count: ' . count($teamgoals));
                foreach ($teamgoals as $teamgoal) {

                    $duedate = userdate($teamgoal->get('duedate'), get_string('strftimedatefullshort'));
                    $link = (new \moodle_url('/blocks/goals/view.php', ['action' => 'trackgoal', 'id' => $teamgoal->get('id')]))->out(false);
                    //mtrace('Team ' . $teamgoal->get('name') . ' linking 1');

                    $goaltext .= get_string('emailgoaltext', 'block_goals', (object)[
                            'goal' => $teamgoal->get('goaltext'),
                            'progress' => $teamgoal->get('progress'),
                            'duedate' => $duedate
                        ]) . PHP_EOL;
                    //mtrace('Team ' . $teamgoal->get('name') . ' linking 2');
                    $goalhtml .= get_string('emailgoalhtml', 'block_goals', (object)[
                            'goal' => $teamgoal->get('goaltext'),
                            'progress' => $teamgoal->get('progress'),
                            'link' => $link,
                            'duedate' => $duedate
                        ]) . '<br/>' . PHP_EOL;
                    //mtrace('Team ' . $teamgoal->get('name') . ' linking 3');
                }
            }

            $dashboardlink = (new \moodle_url('/blocks/goals/view.php'))->out(false);
            $preferenceslink = (new \moodle_url('/blocks/goals/preferences.php'))->out(false);

            $paramstext = [
                'fullname' => fullname($goaluser),
                'goals' => $goaltext,
                'viewyourdashboard' => get_string('viewyourdashboardtext', 'block_goals', $dashboardlink),
                'reminderfrequency' => $reminderfrequenytext,
                'managemypreferences' => get_string('managemypreferencestext', 'block_goals', $preferenceslink)
            ];
            $messagetext = get_string('reminderemailbody', 'block_goals', $paramstext);

            $paramshtml = [
                'fullname' => fullname($goaluser),
                'goals' => $goalhtml,
                'viewyourdashboard' => get_string('viewyourdashboardhtml', 'block_goals', $dashboardlink) . '<br>',
                'reminderfrequency' => $reminderfrequenytext,
                'managemypreferences' => get_string('managemypreferenceshtml', 'block_goals', $preferenceslink)
            ];
            $messagehtml = get_string('reminderemailbody', 'block_goals', $paramshtml);
            $messagehtml = text_to_html($messagehtml, false, false, true);

            $subject = get_string('reminderemailsubject', 'block_goals');

            $noreplyuser = \core_user::get_noreply_user();

            mtrace('Emailing : ' . fullname($goaluser) . ' ' . $goaluser->email . '...', '');
            $result = email_to_user($goaluser, $noreplyuser, $subject, $messagetext, $messagehtml);
            if ($result) {
                if ($reminderfrequency == BLOCK_GOALS_REMINDER_TESTING) {
                    /*
                    mtrace('Email to: ' . fullname($goaluser));
                    mtrace('Email from: ' . fullname($noreplyuser));
                    mtrace('Subject: ' . $subject);
                    mtrace('Message Text: ' . $messagetext);
                    mtrace('Message HTML: ' . $messagehtml);
                    */
                }
                $success ++;
                mtrace(' okay');
            } else {
                mtrace(' failure');
                $failure ++;
            }

        }
    } catch (\Exception $e) {
        mtrace($e->getMessage());
        return false;
    }
    mtrace('Sent ' . $success . ' emails and failed to send ' . $failure . ' emails.');
    return true;
}

function has_goalaccessfrombadge() {

    global $USER;

    $requiredbadge = get_config('block_goals', 'requiredbadge');

    if (empty($requiredbadge)) {
        return true;
    }

    $userbadges = badges_get_user_badges($USER->id);
    foreach($userbadges as $badge) {
        if ($badge->id == $requiredbadge) {
            return true;
        }
    }
    return false;
}

function enforce_security($requirecapability = false) {
    require_login(null, false);
    if (isguestuser()) {
        redirect('/login/index.php');
    }

    if ($requirecapability) {
        require_capability('block/goals:managegoals', context_system::instance());
    }

    $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

    // If we cannot manage goals and we do not have badge access,
    if (!$managegoals && !has_goalaccessfrombadge()) {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo get_config('block_goals', 'nobadgeaccess');
        echo $OUTPUT->footer();
        die();
    }
}

/*
 * RCVS Kmowledge Add link to FVC Smart Goals page
 */
/*function block_goals_extend_navigation_frontpage(navigation_node $navigation) {
    global $PAGE, $USER, $DB;

    //Does user have privileges to manage smart goals?
    $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

    //Or doess user have a FVC badge?
    $FVC_badge_array = array('name'=>'Farm Vet Champions access badge');
    $badgeid = $DB->get_field('badge','id',$FVC_badge_array);
    $access_FVC_goals_array = array('userid'=>$USER->id, 'badgeid'=>$badgeid);

    //if so, add a link for them
    if ($managegoals OR $DB->record_exists('badge_issued',$access_FVC_goals_array)) {
        $node = navigation_node::create(get_string('goals', 'block_goals'),
            new moodle_url('/blocks/goals/view.php', array('courseid' => $PAGE->course->id)),
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/competencies', ''));
        $navigation->add_node($node);
    }
}*/