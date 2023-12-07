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
 * Form class for editing badges preferences.
 *
 * @package    core
 * @subpackage block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/goals/lib.php');

class goals_preferences_form extends moodleform {
    public function definition() {
        global $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('header', 'goals', get_string('goals', 'block_goals'));

        //goalreminderfrequencysetting
        $default = get_config('block_goals', 'defaultreminderfrequency');
        if (empty($default)) {
            $default = 0;
        }

        $mform->addElement('select', 'goalreminderfrequencysetting', get_string('goalreminderfrequencysetting', 'block_goals'), get_reminderfrequencies(), $default);
        $mform->setType('goalreminderfrequencysetting', PARAM_INT);
        $mform->setDefault('goalreminderfrequencysetting', 1);
        $mform->addHelpButton('goalreminderfrequencysetting', 'goalreminderfrequencysetting', 'block_goals');

        $mform->addElement('html', $OUTPUT->box(get_string('goalreminderfrequencydescription', 'block_goals')));

        $this->add_action_buttons();
    }
}
