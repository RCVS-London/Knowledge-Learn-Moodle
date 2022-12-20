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
 * Class for teammembers persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
use core_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing teammembers from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class teammember extends \core\persistent {

    const TABLE = 'goal_teammembers';

    public const CONFIRMED_FALSE = 0;
    public const CONFIRMED_TRUE = 1;

    public const ISADMIN_FALSE = 0;
    public const ISADMIN_TRUE = 1;

    private $user = null;
    private $userstatus = '';
    private $team = null;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'description' => 'Primary key.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'teamid' => [
                'type' => PARAM_INT,
                'description' => 'The team id.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'userid' => [
                'type' => PARAM_INT,
                'description' => 'The user id.',
                'null' => NULL_ALLOWED,
                'default' => 0,
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'Name for invitation',
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'email' => [
                'type' => PARAM_EMAIL,
                'description' => 'Email address for invitation',
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'secret' => [
                'type' => PARAM_ALPHANUMEXT,
                'description' => 'The Email verification secret.',
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'confirmed' => [
                'type' => PARAM_BOOL, // PARAM_INT,
                'description' => 'Boolean value to store whether user has confirmed their membership to the group.',
                'choices' => [
                    self::CONFIRMED_FALSE,
                    self::CONFIRMED_TRUE
                ],
                'default' => false, // self::CONFIRMED_FALSE,
            ],
            'isadmin' => [
                'type' => PARAM_BOOL, // PARAM_INT,
                'description' => 'Boolean field to set/unset team member admin status.',
                'choices' => [
                    self::ISADMIN_FALSE,
                    self::ISADMIN_TRUE
                ],
                'default' => false, // self::ISADMIN_FALSE,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'description' => 'User that created the record.',
                'default' => function () {
                    global $USER;
                    return $USER->id;
                },
            ]
        ];
    }

    public function __construct($id = 0, \stdClass $record = null) {
        parent::__construct($id, $record);

        if (!empty($id)) {
            if (!empty($this->get('userid'))) {
                $this->read_user();
            }
            if (!empty($this->get('teamid'))) {
                $this->read_team();
            }

        }

    }

    public function isconfirmed() {
        return ($this->get('confirmed') == SELF::CONFIRMED_TRUE);
    }

    public function isadmin() {
        return ($this->get('isadmin') == SELF::ISADMIN_TRUE);
    }

    public function get_user() {
        if ($this->user) {
            return $this->user;
        } else {
            $this->user = $this->read_user();
            return $this->user;
        }
    }

    private function read_user() {
        $userid = $this->get('userid');
        if (!empty($userid)) {

            if (!core_user::is_real_user($userid)) {
                $this->userstatus = get_string('invaliduser', 'error');
                return false;
            }

            $user = core_user::get_user($userid);

            if (!$user) {
                $this->userstatus = get_string('invaliduser', 'error');
                return false;
            }

            if ($user->deleted) {
                $this->userstatus = get_string('userdeleted', 'moodle'); // error
                return $user;
            }

            if (empty($user->confirmed)) {
                $this->userstatus = get_string('usernotconfirmed', 'moodle', $user->username);
                return $user;
            }

            if (isguestuser($user)) {
                $this->userstatus = get_string('guestsarenotallowed', 'error');
                return $user;
            }

            if ($user->suspended) {
                $this->userstatus = get_string('suspended', 'auth');
                return $user;
            }

            if ($user->auth == 'nologin') {
                $this->userstatus = get_string('suspended', 'auth');
                return $user;
            }

            $this->userstatus = '';

            return $user;
        }
    }

    /**
     * @param $requestuserid The user requesting access to the team members email address
     */
    public function get_emailaddress() {
        global $USER;

        // Only allow creator of user invitation (or site admin) to see email address.
        if ($USER->id == $this->get('usercreated') || is_siteadmin()) {
            return $this->get('email');
        } else {
            return preg_replace('/(.)(.*)@(.*)/', '$1*****@$3', $this->get('email'));
        }
    }

    public function get_team() {
        if ($this->team) {
            return $this->team;
        } else {
            $this->team = $this->read_team();
            return $this->team;
        }
    }

    private function read_team() {
        $teamid = $this->get('teamid');
        if (!empty($teamid)) {
            $team = new team($teamid);
            return $team;
        }
    }

    public function get_userstatus() {
        if ($this->isconfirmed()) {
            $confirmedicon = '<i class="fa fa-badge-check icon fa-fw"></i>';
        } else {
            $confirmedicon = '<i class="fad fa-question-circle icon fa-fw"></i>';
        }

        $isadminicon = '';
        if ($this->isadmin()) {
            $isadminicon = '<i class="fad fa-user-secret icon fa-fw"></i>';
        }

        return $this->userstatus . $confirmedicon . $isadminicon;
    }

    public function get_teammembername() {
        $user = $this->get_user();
        if (!$user) {
            return $this->get('name');
        } else {
            return fullname($user);
        }
    }
}