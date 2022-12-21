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
 * Manage goals.
 * @package block_goals
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use block_goals\controllers\goal;
use block_goals\controllers\goalfilter;
use block_goals\models;

require '../../../config.php';

global $CFG, $PAGE;
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goalsadmin', 'block_goals'), new moodle_url('/blocks/goals/admin/index.php'));
$PAGE->navbar->add(get_string('goals', 'block_goals'), new moodle_url('/blocks/goals/admin/goals.php'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT );
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/admin/goals.php', ['action' => $action]));
//admin_externalpage_setup('goals');

enforce_security(true);

switch ($action) {
    case 'creategoal':
        $id = optional_param('id', 0, PARAM_INT);
        goal::display($id);
        break;

    case 'editgoal':
        $id = optional_param('id', 0, PARAM_INT);
        goal::process($id);
        break;

    case 'showgoal':
        $id = required_param('id', PARAM_INT);
        goal::show($id);
        break;

    case 'hidegoal':
        $id = required_param('id', PARAM_INT);
        goal::hide($id);
        break;

    case 'deletegoal':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm && confirm_sesskey()) {
            goal::delete($id);
        } else {
            global $OUTPUT;

            $goal = new models\goal($id);

            $output = 'Confirm delete of goal?<br>';
            $teamhtml = '';
            if ($goal->get('type') == models\goal::TYPE_TEAM) {
                $teamhtml = '<dt>Team</dt><dd>' . $goal->get_team()->get('name') . '</dd>';
                $teamhtml .= '<dt>Team Members</dt><dd>' . $goal->get_team()->get_teammembernames() . '</dd>';
            } else {
                $teamhtml = '<dt>User</dt><dd>' . fullname($goal->get_user()) . '</dd>';
            }
            $output .= '<dl>';
            $output .= '<dt>Progress</dt><dd>' . goal::progress($goal->get('progress')) . '</dd>';
            $output .= '<dt>Goal</dt><dd>' . $goal->get('goaltext') . '</dd>';
            $output .= $teamhtml;
            $output .= '<dt>Due Date</dt><dd>' . $goal->get_formattedduedate() . '</dd>';
            $output .= '<dt>Relevant Filters</dt><dd>' . $goal->get_filternames() . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deletegoal', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            goal::renderpage($confirm);
        }
        break;

    case 'movegoal':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);
        goal::move($id, $dir);
        break;

    case 'creategoalfilter':
        $id = optional_param('id', 0, PARAM_INT);
        $goalid = optional_param('goalid', 0, PARAM_INT);
        goalfilter::display($id, $goalid);
        break;

    case 'editgoalfilter':
        $id = optional_param('id', 0, PARAM_INT);
        goalfilter::process($id);
        break;

    case 'deletegoalfilter':
        $id = required_param('id', PARAM_INT);
        goalfilter::delete($id);
        break;

    case 'movegoalfilter':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);
        goalfilter::move($id, $dir);
        break;

    default:
        goal::renderpage();
}


