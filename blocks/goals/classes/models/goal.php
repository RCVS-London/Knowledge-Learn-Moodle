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
 * Class for goals persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
use core_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing goals from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class goal extends \core\persistent {

    const TABLE = 'goal_goals';

    public const TYPE_INDIVIDUAL = 0;
    public const TYPE_TEAM = 1;
    public const TYPE_TEMPLATE = 2;

    public const PERCENTAGE_FALSE = 0;
    public const PERCENTAGE_TRUE = 1;

    public const HIDDEN_FALSE = 0;
    public const HIDDEN_TRUE = 1;

    // public const NOT_TEMPLATE = 0;
    // public const IS_TEMPLATE = 1;

    private $createuser = null;

    private $user = null;
    private $userstatus = '';
    private $team = null;
    private $verb = null;
    private $goalfilters = null;
    private $filters = null;
    private $history = null;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'type' => [
                'type' => PARAM_INT,
                'description' => 'Determines whether this is an individual goal or a team goal.',
                'choices' => [
                    self::TYPE_INDIVIDUAL,
                    self::TYPE_TEAM,
                    self::TYPE_TEMPLATE,
                ],
                'default' => self::TYPE_INDIVIDUAL,
            ],
            'userid' => [
                'type' => PARAM_INT,
                'description' => 'The user id for individual goals.',
                'null' => NULL_ALLOWED,
                'default' => function () {
                    global $USER;
                    return $USER->id;
                },
            ],
            'teamid' => [
                'type' => PARAM_INT,
                'description' => 'The team id for team goals.',
                'null' => NULL_ALLOWED,
                'default' => 0,
            ],
            'verbid' => [
                'type' => PARAM_INT,
                'description' => 'The verb id for verbs.',
                'default' => 0,
            ],
            'amount' => [
                'type' => PARAM_INT,
                'description' => 'Numerical amount or percentage amount for goal.',
                'default' => 0,
            ],
            'percentageflag' => [
                'type' => PARAM_BOOL,
                'description' => 'Boolean flag for representation of numeric amount of percentage amount.',
                'default' => false,
            ],
            'objective' => [
                'type' => PARAM_TEXT,
                'description' => 'Subject of goal sentence.',
                'default' => '',
            ],
            'duedate' => [
                'type' => PARAM_INT,
                'description' => 'The due date of the goal.',
                'default' => 0,
            ],
            /*
            'template' => [
                'type' => PARAM_BOOL,
                'description' => 'Boolean flag to reuse this goal as template for other users.',
                'default' => false,
            ],
            */
            'goaltext' => [
                'type' => PARAM_TEXT,
                'description' => 'Generated goal text.',
                'default' => '',
            ],
            'progress' => [
                'type' => PARAM_INT,
                'description' => 'Numeric value to represent progress.',
                'default' => 0,
            ],
            'description' => [
                'type' => PARAM_RAW, // PARAM_CLEANHTML, PARAM_RAW, PARAM_TEXT
                'description' => 'Description text for the goal.',
                'null' => NULL_NOT_ALLOWED,
                'default' => ''
            ],
            'descriptionformat' => [
                'type' => PARAM_INT,
                'description' => 'The format of the description field',
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'default' => FORMAT_HTML,
            ],
            'hidden' => [
                'type' => PARAM_BOOL,
                'description' => 'Boolean flag for representation of whether goal is hidden.',
                'default' => false,
            ],
            'usercreated' => [
                'default' => function () {
                    global $USER;
                    return $USER->id;
                },
                'description' => 'User that created the record.',
                'type' => PARAM_INT,
            ]
        ];
    }

    public function __construct($id = 0, \stdClass $record = null) {
        parent::__construct($id, $record);

        if (!empty($id)) {
            if (!empty($this->get('userid'))) {
                $this->read_user($this->get('userid'));
            }
            if (!empty($this->get('teamid'))) {
                $this->read_team();
            }

        }

    }

    public function get_user() {
        if ($this->user) {
            return $this->user;
        } else {
            $this->user = $this->read_user($this->get('userid'));
            return $this->user;
        }
    }

    public function get_createuser() {
        if ($this->createuser) {
            return $this->createuser;
        } else {
            $this->createuser = $this->read_user($this->get('usercreated'));
            return $this->createuser;
        }
    }

    private function read_user($userid) {
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
            if (team::record_exists($teamid)) {
                $team = new team($teamid);
                return $team;
            } else {
                return null;
            }
        }
    }

    public function get_teamname() {
        $team = $this->get_team();
        if (is_object($team)) {
            return $team->get('name');
        } else {
            return get_string('missingteamname', 'block_goals');
        }
    }

    public function get_typename() {
        switch ($this->get('type')) {
            case self::TYPE_INDIVIDUAL:
                return get_string('individual', 'block_goals');
            case self::TYPE_TEAM:
                return get_string('team', 'block_goals');
            default: return '';
        }
    }

    public function get_verb() {
        if ($this->verb) {
            return $this->verb;
        } else {
            $this->verb = $this->read_verb();
            return $this->verb;
        }
    }

    private function read_verb() {
        $verbid = $this->get('verbid');
        if (!empty($verbid)) {
            $verb = new verb($verbid);
            return $verb;
        }
    }

    public function get_verbname() {
        $verb = $this->get_verb();
        if (is_object($verb)) {
            return $verb->get('verb');
        } else {
            return get_string('missingverbname', 'block_goals');
        }
    }

    public function get_percentagename() {
        switch ($this->get('percentageflag')) {
            case self::PERCENTAGE_FALSE:
                return get_string('percentageno', 'block_goals');
            case self::PERCENTAGE_TRUE:
                return get_string('percentageyes', 'block_goals');
            default: return '';
        }
    }

    public function get_goalfilters() {
        if ($this->goalfilters) {
            return $this->goalfilters;
        } else {
            $this->goalfilters = $this->read_goalfilters();
            return $this->goalfilters;
        }
    }

    private function read_goalfilters() {
        $goalfilters = goalfilter::get_records(['goalid' => $this->get('id')]);
        return $goalfilters;
    }

    public function get_filters() {
        if ($this->filters) {
            return $this->filters;
        } else {
            $this->filters = $this->read_filters();
            return $this->filters;
        }
    }

    private function read_filters() {
        $filters = [];
        $goalfilters = $this->get_goalfilters();
        foreach ($goalfilters as $goalfilter) {
            if ($goalfilter->get('booleanvalue') == true) {
                $filters[] = $goalfilter->get_filter();
            }
        }
        return $filters;
    }

    public function get_filternames() {
        $filternames = [];
        $filters = $this->get_filters();
        foreach ($filters as $filter) {
            //if ($filter->get('booleanvalue') == true) {
                $filternames[] = $filter->get('name');
            //}
        }
        return implode(', ', $filternames);
    }

    public function cascadedelete() {

        global $DB;

        $id = $this->get('id');

        // 1. Delete all the goalfilters.
        $result = $DB->delete_records('goal_goalfilters', ['goalid' => $id]);
        if (!$result) {
            return false;
        }

        // 2. Delete all the goalhistory.
        $result = $DB->delete_records('goal_goalhistory', ['goalid' => $id]);
        if (!$result) {
            return false;
        }

        // 3. Delete the goal
        return parent::delete();
    }

    public function get_history() {
        if ($this->history) {
            return $this->history;
        } else {
            $this->history = $this->read_history();
            return $this->history;
        }
    }

    private function read_history() {
        $id = $this->get('id');
        if (!empty($id)) {
            $history = goalhistory::get_records(['goalid' => $id], 'timecreated', 'ASC');
            return $history;
        }
    }

    public function get_abstract() {
        $goaltext = $this->get('goaltext');
        $truncatedgoaltext = get_string('missinggoaltext', 'block_goals');

        if (!empty($goaltext)) {
            if (strlen($goaltext) > 40) {
                $truncatedgoaltext = "'" . substr($goaltext, 0, 40) . "...'";
            } else {
                $truncatedgoaltext = $goaltext;
            }
        }

        return $truncatedgoaltext;
    }

    public function get_formattedtimecreated() {
        return userdate($this->get('timecreated'), get_string('strftimedatefullshort', 'langconfig'));
    }

    public function get_formattedduedate() {
        return userdate($this->get('duedate'), get_string('strftimedatefullshort', 'langconfig'));
    }

    public function get_formattedrecenthistory() {
        $id = $this->get('id');
        $goalhistory = null;
        if ($this->history) {
            $goalhistory = end($this->history);
        } else {
            $goalhistory = goalhistory::get_records(['goalid' => $id], 'timecreated', 'DESC', '', 1);
            $goalhistory = reset($goalhistory);
            if (!$goalhistory) {
                return '';
            }
        }
        $history = format_text($goalhistory->get('description'));
        return $history;
    }

    public static function get_individualgoalsforuser($userid) {

    }
}