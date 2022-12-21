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
 * Goals Block
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_goals\forms;
use block_goals\models;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/classes/form/persistent.php');

/**
 * Class teammember
 *
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teammember extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'block_goals\\models\\teammember';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('action', 'extravalidationid');

    /**
     * Define the form.
     */
    public function definition() {
        global $CFG, $DB, $USER, $OUTPUT;
        $mform = $this->_form;

        $id = 0;
        if (!empty($this->_customdata['id'])) {
            $id = $this->_customdata['id'];
        }
        $isediting = false;
        if (!empty($id)) {
            $isediting = true;
        }

        $teammember = $this->get_persistent();

        $isconfirmed = $teammember->isconfirmed();

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance(), $USER);
        if ($managegoals) {
            $mform->addElement('html', $OUTPUT->box(get_string('managegoals', 'block_goals')));
        }

        // Action.
        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $mform->setConstant('action', $this->_customdata['action']);

        // teamid.
        if ($managegoals) {
            $teams = $DB->get_records_menu('goal_team', null, 'id ASC', 'id, name');
        } else {
            $query = '
                SELECT gt.id, gt.name 
                FROM {goal_team} gt
                INNER JOIN {goal_teammembers} gtm ON gtm.teamid = gt.id AND gtm.isadmin = :isadmin
                WHERE gtm.userid = :userid';

            $teams = $DB->get_records_sql_menu($query, ['isadmin' => models\teammember::ISADMIN_TRUE,'userid' => $USER->id]);
        }

        $mform->addElement('select', 'teamid', get_string('team', 'block_goals'), $teams);

        // userid.
        $usertotal = $DB->count_records('user', array('deleted' => 0, 'suspended' => 0, 'confirmed' => 1));

        if ($managegoals) {
            if ($usertotal < 10000) {
                list($sort, $params) = users_order_by_sql('u');
                // User searchable selector - return users who are confirmed, not deleted, not suspended and not a guest.
                $sql = 'SELECT u.id, ' . get_all_user_name_fields(true, 'u') . '
                    FROM {user} u
                    WHERE u.deleted = 0
                    AND u.confirmed = 1
                    AND u.suspended = 0
                    AND u.id != :siteguestid
                    ORDER BY ' . $sort;
                $params['siteguestid'] = $CFG->siteguest;
                // -- LEFT JOIN {goal_teammembers} gt ON gt.userid = u.id
                // -- AND (gt.teamid IS NULL OR gt.teamid != :teamid)
                // $params['teamid'] = $CFG->siteguest;
                $users = $DB->get_records_sql($sql, $params);
                $options = array();
                foreach ($users as $userid => $user) {
                    $options[$userid] = fullname($user);
                }
                $mform->addElement('searchableselector', 'userid', get_string('user'), $options);
                $mform->setType('userid', PARAM_INT);
            } else {
                //simple text box for username or user id (if two username exists, a form error is displayed)
                $mform->addElement('text', 'userid', get_string('usernameorid', 'webservice'));
                $mform->setType('userid', PARAM_RAW_TRIMMED);
            }
            // $mform->addRule('userid', get_string('required'), 'required', null, 'client');
        } else {
            $mform->addElement('hidden', 'userid', $teammember->get('userid'));
            $mform->setType('userid', PARAM_INT);
        }

        // Name and Email.
        // If adding new, the user isn't confirmed yet, or we're an admin, show name and email.
        if (!$isediting || !$isconfirmed || $managegoals) {

            $mform->addElement('text', 'name', get_string('name', 'block_goals'), 'maxlength="100" size="64"');
            $mform->setType('name', PARAM_TEXT);
            $mform->addHelpButton('name', 'name', 'block_goals');

            $mform->addElement('html', $OUTPUT->box(get_string('permissionnotify', 'block_goals')));

            $mform->addElement('text', 'email', get_string('email'));
            $mform->setType('email', PARAM_EMAIL); //PARAM_EMAIL
            $mform->addHelpButton('email', 'email', 'block_goals');

            if (!$managegoals) {
                $mform->addRule('name', get_string('required'), 'required', null, 'client');
                $mform->addRule('email', get_string('required'), 'required', null, 'client');
            }
        } else {
            $mform->addElement('hidden', 'name', $teammember->get('name'));
            $mform->setType('name', PARAM_RAW);

            $mform->addElement('hidden', 'email', $teammember->get('email'));
            $mform->setType('email', PARAM_RAW);
        }

        // Secret and Confirmed.
        if ($managegoals) {
            $mform->addElement('text', 'secret', get_string('secret', 'block_goals'));
            $mform->setType('secret', PARAM_RAW);
            $mform->addElement('selectyesno', 'confirmed', get_string('confirmed', 'block_goals'));
        } else {
            $mform->addElement('hidden', 'secret', $teammember->get('email'));
            $mform->setType('secret', PARAM_RAW);

            if (!$isediting) {
                $mform->addElement('hidden', 'confirmed', get_string('confirmed', 'block_goals'), get_string('no'));
            } else {
                $mform->addElement('hidden', 'confirmed', $teammember->get('email'));
                $mform->setType('confirmed', PARAM_BOOL);
            }
        }

        // Isadmin.
        $mform->addElement('selectyesno', 'isadmin', get_string('isadmin', 'block_goals'));
        $mform->addHelpButton('isadmin', 'isadmin', 'block_goals');

        $this->add_action_buttons(true);
    }

    function extra_validation($data, $files, &$errors) {
        global $DB;
        $teammembers = 0;
        $id = 0;
        $isediting = false;
        if (!empty($data->id)) {
            $id = $data->id;
            $isediting = true;
        }

        if ($isediting) {
            $teammembers = $DB->count_records_sql('
                SELECT count(id) FROM {goal_teammembers}
                WHERE userid = :userid 
                AND teamid = :teamid
                AND id <> :id
            ', [
                'userid' => $data->userid,
                'teamid' => $data->teamid,
                'id' => $id
            ]);
            if ($teammembers > 0) {
                $errors['userid'] = 'User already exists on team';
            }

            if ($data->isadmin == models\teammember::ISADMIN_FALSE) {
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


        }
        if (!empty($data->email)) {
            $teammembers = $DB->get_records_sql('
                SELECT gtm.id, u.id as userid, u.email, gtm.teamid
                FROM {goal_teammembers} gtm
                INNER JOIN {user} u ON u.id = gtm.userid
                WHERE u.email = :email
                    AND gtm.teamid = :teamid
                    AND gtm.id <> :id
            ', [
                'email' => $data->email,
                'teamid' => $data->teamid,
                'id' => $id
            ]);
            if (count($teammembers) > 0) {
                foreach ($teammembers as $teammember) {
                    $user = \core_user::get_user($teammember->userid, '*', MUST_EXIST);
                    //var_dump($user);
                    //var_dump(fullname($user));
                    $errors['email'] = 'Email ' . $data->email . ' already exists on team as user: ' . fullname($user);
                }
            }
            $teammembers = $DB->get_records_sql('
                SELECT *
                FROM {goal_teammembers}
                WHERE email = :email 
                  AND teamid = :teamid 
                  AND id <> :id
                ', [
                'email' => $data->email,
                'teamid' => $data->teamid,
                'id' => $id
            ]);
            if (count($teammembers) > 0) {
                $error = 'Invitation already sent to ' . $data->email;
                if (array_key_exists('email', $errors)) {
                    $errors['email'] .= $error;
                } else {
                    $errors['email'] = $error;
                }
            }
        }

        //var_dump($errors);
        return $errors;
    }

    function definition_after_data() {
        $mform = $this->_form;

        $isediting = false;
        if (!empty($this->_customdata['action'])) {
            if ($this->_customdata['action'] == 'editteammember') {
                $isediting = true;
            }
        }

        if (!empty($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }

        if (!empty($this->_customdata['teamid'])) {
            $mform->getElement('teamid')->setSelected($this->_customdata['teamid']);
        }

        /*
        if (!empty($this->_customdata['userid'])) {
            if (!$mform->elementExists('userid')) {
                $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
                $mform->setType('userid', PARAM_INT);
            }
        }

        if (!empty($this->_customdata['teamid'])) {
            $mform->getElement('teamid')->setSelected($this->_customdata['teamid']);
        }
        */

        /*
        if (!empty($this->_customdata['teamid'])) {

            $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

            // TODO: fix.
            $managegoals = false;

            if ($managegoals) {
                $mform->getElement('teamid')->setSelected($this->_customdata['teamid']);
            } else {
                $mform->addElement('hidden', 'teamid', $this->_customdata['teamid']);
                $mform->setType('teamid', PARAM_INT);
                $mform->setConstant('teamid', $this->_customdata['teamid']);
            }
        }
        */
    }
}