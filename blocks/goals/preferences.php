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
 * Goals preferences page.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
global $CFG, $PAGE, $USER, $OUTPUT;

require_once $CFG->dirroot . '/blocks/goals/classes/forms/preferences.php';
require_once $CFG->dirroot . '/user/editlib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goals', 'block_goals'), new moodle_url('/blocks/goals/view.php'));
$PAGE->navbar->add('Preferences', new moodle_url('/blocks/goals/preferences.php'));


$url = new moodle_url('/goals/preferences.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
enforce_security();
$PAGE->set_context(context_user::instance($USER->id));

//if (empty($CFG->enablebadges)) {
//    print_error('badgesdisabled', 'badges');
//}

$default = get_config('block_goals','defaultreminderfrequency');

$setting = get_user_preferences('goalreminderfrequencysetting', $default, $USER);

$mform = new goals_preferences_form();
$mform->set_data(['goalreminderfrequencysetting' => $setting]);

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    set_user_preference('goalreminderfrequencysetting', $data->goalreminderfrequencysetting, $USER->id);
    redirect($CFG->wwwroot . '/blocks/goals/view.php');
}

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/blocks/goals/view.php');
}

$strpreferences = get_string('preferences', 'block_goals');
$strgoals      = get_string('goals', 'block_goals');

$title = "$strgoals: $strpreferences";
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));

echo $OUTPUT->header();
echo $OUTPUT->heading("$strgoals: $strpreferences", 2);

$mform->display();

echo $OUTPUT->footer();
