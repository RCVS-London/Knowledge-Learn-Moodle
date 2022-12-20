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
 * Team Member Controller
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_goals\controllers;

use block_goals\models;
use block_goals\forms;
use core\notification;

defined('MOODLE_INTERNAL') || die();

class teammember {

    private static function do_redirect() {
        global $CFG;
        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
        if ($managegoals) {
            redirect($CFG->wwwroot . '/blocks/goals/admin/teams.php');
        }
        redirect($CFG->wwwroot . '/blocks/goals/teams.php');
    }

    public static function display($id, $teamid=0, $form=null) {
        global $OUTPUT, $PAGE;

        // Are we 'creating' or 'editing'?
        $teammember = null;
        if (empty($id)) {
            $strheading = get_string('createnewteammember', 'block_goals');
        } else {
            $teammember = new models\teammember($id);
            $teamuser = $teammember->get_user();
            $strheading = get_string('editteammember', 'block_goals', format_string(fullname($teamuser)));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $form = new forms\teammember($PAGE->url->out(false), ['persistent' => $teammember, 'id' => $id, 'action' => 'editteammember', 'teamid' => $teamid, 'extravalidationid' => $id]);
        }

        if ($form->is_cancelled()) {
            self::do_redirect();
        }

        // Print the page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $form->display();
        echo $OUTPUT->footer();
        die;
    }

    public static function process($id) {

        global $DB, $PAGE, $USER;

        $options = null;
        $teammember = null;
        if (!empty($id)) {
            $teammember = new models\teammember($id);
            if (!empty($teammember->get('email')) && $teammember->get('usercreated') != $USER->id) {
                notification::error('You do not have permission to view/edit team member invitation: ' . $teammember->get_teammembername());
                self::do_redirect();
            }
        }
        $form = new forms\teammember($PAGE->url->out(false), ['persistent' => $teammember, 'id' => $id, 'action' => 'editteammember', 'extravalidationid' => $id]);

        if ($data = $form->get_data()) {

            //var_dump($data);

            $action = $data->action;
            unset($data->action);

            try {
                $data->timemodified = time();

                if (empty($data->id)) {

                    $data->usercreated = $USER->id;
                    $data->timecreated = time();
                    if (!property_exists($data, 'secret') || empty($data->secret)) {
                        $data->secret = random_string(15);
                    }
                    //var_dump($data);
                    $teammember = new models\teammember(0, $data);
                    //var_dump($teammember);
                    if ($teammember->create()) {
                        $team = $teammember->get_team();
                        $user = $teammember->get_user();

                        $name = '';
                        if (!empty($teammember->userid)) {
                            $name = fullname($user) . ' (' . $user->username . ')';
                        } else {
                            $name = $teammember->get_teammembername();
                        }
                        notification::success('Team Member: ' . $name . ' added to Team: ' . $team->get('name'));

                        // Send an email if the user isn't confirmed.
                        if ($teammember->get('confirmed') == models\teammember::CONFIRMED_TRUE) {
                            notification::info('User set as confirmed so skipping invitation email : ' . $name);
                        } else {
                            if (self::goals_send_verification_email($USER, $teammember)) {
                                notification::success('Invitation email sent to : ' . $name);
                            } else {
                                notification::success('Could not send invitation email to : ' . $name);
                            }
                        }
                    } else {
                        notification::error('Could not create team member');
                    }
                } else {

                    $resendinvitation = false;
                    $existingteammember = new models\teammember($data->id);
                    if (!$existingteammember->isconfirmed()) {
                        $existingemail = $existingteammember->get('email');
                        if ($data->email != $existingemail) {
                            $resendinvitation = true;
                            if (!property_exists($data, 'secret') || empty($data->secret)) {
                                $data->secret = random_string(15);
                            }
                        }
                    }
                    $teammember = new models\teammember();
                    $teammember->from_record($data);
                    if ($teammember->update()) {
                        $team = $teammember->get_team();
                        $name = $teammember->get_teammembername();
                        notification::success('Team Member: ' . $name . ' updated on Team: ' . $team->get('name'));

                        if ($resendinvitation) {
                            if (self::goals_send_verification_email($USER, $teammember)) {
                                notification::success('New Invitation email sent to : ' . $name);
                            } else {
                                notification::success('Could not send new invitation email to : ' . $name);
                            }
                        }

                    }
                }
                // $teammember->reorder();

                /*
                $team = new models\team();
                $team->reorder();
                */

            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }

            self::do_redirect();
        }
        self::display($id, 0, $form);
    }

    public static function delete($id) {

        $teammember = new models\teammember($id);
        $data = $teammember->to_record();

        global $DB;
        if ($data->isadmin == models\teammember::ISADMIN_TRUE) {
            $teammembers = $DB->count_records_sql('
                SELECT count(id)
                FROM {goal_teammembers}
                WHERE teamid = :teamid
                AND  isadmin = :isadmin
            ', [
                'teamid' => $data->teamid,
                'isadmin' => models\teammember::ISADMIN_TRUE,
            ]);
            if ($teammembers <= 1) {
                $errors['isadmin'] = 'Must have at least one admin in a team.';
            }
        }


        if (!empty($data->id)) {
            if (confirm_sesskey()) {
                if ($teammember->delete()) {
                    notification::success('Team Member ' . fullname($teammember->get_user()) . ' deleted');
                }
            }
        }
        self::do_redirect();
    }

    public static function move($id, $direction) {

        $teammember = new models\teammember($id);

        $data = $teammember->to_record();
        if (!empty($data->id)) {
            if (confirm_sesskey()) {
                if ($teammember->move($direction)) {
                    notification::success('Team Member ' . fullname($teammember->get_user()) . ' moved ' . $direction);
                } else {
                    notification::error('Could not move Team Member ' . fullname($teammember->get_user()) . ' ' . $direction);
                    // models\teammember::reorder();
                }
            }
        }
        self::do_redirect();
    }

    /**
     * Create and send a verification email to the email address supplied.
     *
     * Since we're not sending this email to a user, email_to_user can't be used
     * but this function borrows largely the code from that process.
     *
     * @param \stdClass $user The user making the invitation
     * @param models\teammember $teammember
     * @return true if the email was sent successfully, false otherwise.
     */
    public static function goals_send_verification_email($fromuser, $teammember) {
        global $DB, $USER;

        // Store a user secret (goals_email_verify_secret) and the address (goals_email_verify_address) as users prefs.
        // The address will be used by edit_backpack_form for display during verification and to facilitate the resending
        // of verification emails to said address.
        // set_user_preference('goals_email_verify_secret', $teammember->secret);
        // set_user_preference('goals_email_verify_address', $teammember->email);
        // set_user_preference('goals_email_verify_backpackid', $backpackid);
        // set_user_preference('goals_email_verify_password', $backpackpassword);

        $team = $teammember->get_team();
        $teamname = $team->get('name');

        // To, from.
        $touser = $teammember->get_user();
        if (empty($touser)) {
            $touser = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $touser->email = $teammember->get('email');
        }

        $noreplyuser = \core_user::get_noreply_user();

        // Generate the verification email body.
        $verificationurl = '/blocks/goals/emailverify.php';
        $verificationurl = new \moodle_url($verificationurl);
        $verificationpath = $verificationurl->out(false);

        $site = get_site();
        $args = new \stdClass();
        $args->link = $verificationpath . '?data='. $teammember->get('secret');
        $args->name = $teammember->get('name');
        $args->fullname = fullname($fromuser);
        $args->teamname = $teamname;
        $args->teamdescription = format_text($team->get('description'), $team->get('descriptionformat'));
        $args->sitename = $site->fullname;

        $messagesubject = get_string('goalsemailverifyemailsubject', 'block_goals', $site->fullname);
        $messagetext = get_string('goalsemailverifyemailbody', 'block_goals', $args);
        $messagehtml = text_to_html($messagetext, false, false, true);

        return email_to_user($touser, $noreplyuser, $messagesubject, $messagetext, $messagehtml);
    }
}