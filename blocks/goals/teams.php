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
 * Script to let a user add a new team
 *
 * @package   block_goals
 * @copyright 2022 David Aylmer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_goals\controllers\team;
use block_goals\controllers\teammember;
use block_goals\models;
use core\notification;

require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE;
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goals', 'block_goals'), new moodle_url('/blocks/goals/view.php'));
$PAGE->navbar->add('Teams', new moodle_url('/blocks/goals/teams.php'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/teams.php', ['action' => $action]));

$PAGE->requires->css(new moodle_url('/blocks/goals/fontawesome-pro/css/all.css'));

$managegoals = has_capability('block/goals:managegoals', context_system::instance());
if ($managegoals) {
    // TODO: add button to go to admin team management
}
enforce_security();

switch ($action) {
    case 'createteam':
        $id = optional_param('id', 0, PARAM_INT);
        team::display($id);
        break;

    case 'editteam':
        $id = optional_param('id', 0, PARAM_INT);
        team::process($id);
        break;

    case 'showteam':
        $id = required_param('id', PARAM_INT);
        team::show($id);
        break;

    case 'hideteam':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        $team = new models\team($id);
        $isadmin = $team->user_isadmin();
        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

        if ($isadmin || $managegoals) {
            team::hide($id);
        } else {
            if ($confirm) {
                team::hide($id);
            } else {
                global $OUTPUT;

                $team = new models\team($id);
                $yesno = [0 => get_string('no'), 1 => get_string('yes')];

                $output = 'Confirm delete of team?<br>';
                $output .= '<dl>';
                $output .= '<dt>Team</dt><dd>' . $team->get('name') . '</dd>';
                $output .= '<dt>Description</dt><dd>' . format_text($team->get('description'), $team->get('descriptionformat')) . '</dd>';
                $output .= '<dt>Team Members</dt><dd>' . $team->get_teammembernames() . '</dd>';
                $output .= '</dl>';

                $continue = new moodle_url('', ['action' => 'hideteam', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
                // $OUTPUT->confirm($message, $continue, $cancel)
                $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
                team::renderpage($confirm);
            }
        }
        break;

    case 'deleteteam':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm) {
            team::delete($id);
        } else {
            global $OUTPUT;

            $team = new models\team($id);
            $yesno = [0 => get_string('no'), 1 => get_string('yes')];

            $output = 'Confirm delete of team?<br>';
            $output .= '<dl>';
            $output .= '<dt>Team</dt><dd>' . $team->get('name') . '</dd>';
            $output .= '<dt>Description</dt><dd>' . format_text($team->get('description'), $team->get('descriptionformat')) . '</dd>';
            $output .= '<dt>Team Members</dt><dd>' . $team->get_teammembernames() . '</dd>';
            $output .= '<dt>Hidden</dt><dd>' . $yesno[$team->get('hidden')] . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deleteteam', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            team::renderpage($confirm);
        }
        break;

    case 'createteammember':
        $id = optional_param('id', 0, PARAM_INT);
        $teamid = optional_param('teamid', 0, PARAM_INT);
        teammember::display($id, $teamid);
        break;

    case 'editteammember':
        $id = optional_param('id', 0, PARAM_INT);
        teammember::process($id);
        break;

    case 'deleteteammember':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm && confirm_sesskey()) {
            teammember::delete($id);
        } else {
            global $OUTPUT, $USER;

            $teammember = new models\teammember($id);
            $yesno = [0 => get_string('no'), 1 => get_string('yes')];

            $output = 'Confirm delete of team member?<br>';
            $output .= '<dl>';
            $output .= '<dt>Team</dt><dd>' . $teammember->get_team()->get('name') . '</dd>';
            $output .= '<dt>Team Description</dt><dd>' . format_text($teammember->get_team()->get('description'), $teammember->get_team()->get('descriptionformat')) . '</dd>';
            $output .= '<dt>Team Members</dt><dd>' . $teammember->get_team()->get_teammembernames() . '</dd>';
            $output .= '<dt>Team Member user</dt><dd>' . fullname($teammember->get_user()) . '</dd>';
            $output .= '<dt>Team Member name</dt><dd>' . $teammember->get('name') . '</dd>';
            $output .= '<dt>Team Member email</dt><dd>' . $teammember->get_emailaddress() . '</dd>';
            $output .= '<dt>Team Member is confirmed</dt><dd>' . $yesno[$teammember->get('confirmed')] . '</dd>';
            $output .= '<dt>Team Member is admin</dt><dd>' . $yesno[$teammember->get('isadmin')] . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deleteteammember', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            team::renderpage($confirm);
        }
        break;

    default:
        team::renderpage();
}