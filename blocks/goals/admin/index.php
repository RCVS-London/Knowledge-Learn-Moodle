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
 * Manage goal verbs.
 * @package block_goals
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require '../../../config.php';

global $CFG, $PAGE, $OUTPUT;
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goalsadmin', 'block_goals'), new moodle_url('/blocks/goals/admin/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/admin/index.php'));
//admin_externalpage_setup('verbs');
enforce_security(true);

echo $OUTPUT->header();
echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/verbs.php'), get_string('verbs', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/filters.php'), get_string('filters', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/teams.php'), get_string('teams', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/goals.php'), get_string('goals', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/admin/settings.php', ['section' => 'blocksettinggoals']), get_string('settings', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/admin/tool/task/scheduledtasks.php'), get_string('tasks', 'block_goals'));
echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/view.php'), get_string('dashboard', 'block_goals'));
echo $OUTPUT->footer();