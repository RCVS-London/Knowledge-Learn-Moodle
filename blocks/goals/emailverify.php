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
 * Endpoint for the verification email link.
 *
 * @package    core
 * @subpackage badges
 * @copyright  2016 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once __DIR__ . '/../../config.php';
global $CFG, $PAGE, $USER, $OUTPUT;
require_once $CFG->dirroot . '/blocks/goals/lib.php';

use block_goals\models;
use core\notification;

$secret = optional_param('data', '', PARAM_RAW);
$action = optional_param('action', '', PARAM_RAW);
$url = new moodle_url('/blocks/goals/emailverify.php', ['data' => $secret, 'action' => $action]);
$PAGE->set_url($url);
global $SESSION;
$SESSION->wantsurl = $url->out(false);
enforce_security();
$PAGE->set_context(context_user::instance($USER->id));

if (empty($secret)) {
    notification::error("Part of your invitation link is missing. Please try copying the entire link. Please contact support for assistance.");
    redirect(new moodle_url('/blocks/goals/teams.php'));
}

$secretcount = models\teammember::count_records(['secret' => $secret]);
if ($secretcount > 1) {
    notification::error("Multiple invitations found with the same unique secret. This should not happen. Please contact support for assistance.");
    redirect(new moodle_url('/blocks/goals/teams.php'));
}

$teammember = models\teammember::get_record(['secret' => $secret]);
if (!$teammember) {
    notification::warning("Unable to find team invitation, or invitation link already used.");
    redirect(new moodle_url('/blocks/goals/teams.php'));
}

switch ($action) {
    case 'accept':

        if (models\team::team_has_member($teammember->get('teamid'), $USER->id)) {
            notification::info("You already exist as a member on this team!");
            redirect(new moodle_url('/blocks/goals/teams.php'));
        } else {
            $teammember->set('userid', $USER->id);
            $teammember->set('confirmed', models\teammember::CONFIRMED_TRUE);
            $teammember->set('name', '');
            $teammember->set('email', '');
            $teammember->set('secret', '');
            $teammember->usermodified = $USER->id;
            $teammember->timemodified = time();
            if ($teammember->update()) {
                notification::success("Team invitation accepted.");
            } else {
                notification::error("Unable to accept team invitation.");
            }
            redirect(new moodle_url('/blocks/goals/teams.php'));
        }

        break;
    case 'decline':
        if ($teammember->delete()) {
            notification::success("Team invitation declined.");
        } else {
            notification::error("Unable to decline team invitation.");
        }
        redirect(new moodle_url('/blocks/goals/teams.php'));
        break;
    default:
        $name = $teammember->get('name');
        $email = $teammember->get('email');
        $team = $teammember->get_team();
        $teamname = $team->get('name');
        $teammembernames = $team->get_teammembernames();
        $teamdescription = format_text($team->get('description'), $team->get('descriptionformat'));

        // If a description exists, prepend it with a heading.
        if (!empty($team->get('description'))) {
            $teamdescription = '<b>Team Description:</b><br>' . $teamdescription;
        }

        $params = (object)[
            'name' => $name,
            'email' => $email,
            'teamname' => $teamname,
            'teammembernames' => $teammembernames,
            'teamdescription' => $teamdescription,
        ];
        $output = get_string('goalsemailverify', 'block_goals', $params);

        echo $OUTPUT->header();
        echo $output;
        if ($teammember->isconfirmed()) {
            notification::info("You are already set to be confirmed as a member on this team, but the invitation remains unaccepted.");
        }

        if (models\team::team_has_member($teammember->get('teamid'), $USER->id, $teammember)) {
            notification::info("You already exist as a member on this team! This additional invitation cannot be accepted.");
        } else {
            echo $OUTPUT->single_button(new \moodle_url('emailverify.php', ['data' => $secret, 'action' => 'accept']), get_string('accept', 'block_goals'));
        }

        echo $OUTPUT->single_button(new \moodle_url('emailverify.php', ['data' => $secret, 'action' => 'decline']), get_string('decline', 'block_goals'));
        echo $OUTPUT->footer();
}
