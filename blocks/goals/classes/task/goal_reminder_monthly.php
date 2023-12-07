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
* Goal Reminder task
*
* @package    block_Goals
* @copyright  2022 David Aylmer
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*
*/

/*
        php admin\tool\task\cli\schedule_task.php --execute=\block_goals\task\goal_reminder_monthly
 */

namespace block_goals\task;

global $CFG;
require_once($CFG->dirroot . '/blocks/goals/lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * An example of a scheduled task.
 */
class goal_reminder_monthly extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('goalremindermonthly', 'block_goals');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        return send_reminder_emails(BLOCK_GOALS_REMINDER_MONTHLY);
    }
}