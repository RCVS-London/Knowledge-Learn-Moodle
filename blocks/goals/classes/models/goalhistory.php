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
 * Class for goalhistory persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
use core_user;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing goalhistories from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class goalhistory extends \core\persistent {

    const TABLE = 'goal_goalhistory';

    private $user = null;
    private $userstatus = '';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'goalid' => [
                'type' => PARAM_INT,
                'description' => 'The goal id.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'progress' => [
                'type' => PARAM_TEXT,
                'description' => 'The goal id.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'description' => [
                'type' => PARAM_CLEANHTML,
                'description' => 'Comment to add to progress update for description purposes.',
                'null' => NULL_NOT_ALLOWED,
                'default' => ''
            ],
            'descriptionformat' => [
                'type' => PARAM_INT,
                'description' => 'The format of the description field',
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'default' => FORMAT_HTML,
            ],
            'usercreated' => [
                'default' => function() {
                    global $USER;
                    return $USER->id;
                },
                'description' => 'User that created the record.',
                'type' => PARAM_INT,
            ]
        ];
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
        $userid = $this->get('usercreated');
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

    public function get_formattedtimecreated() {
        return userdate($this->get('timecreated'), get_string('strftimedatefullshort', 'langconfig'));
    }
}
