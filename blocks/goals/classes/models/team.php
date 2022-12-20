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
 * Class for teams persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing teams from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class team extends \core\persistent {

    const TABLE = 'goal_team';

    public const HIDDEN_FALSE = 0;
    public const HIDDEN_TRUE = 1;

    private $teammembers = null;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {

        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_CLEANHTML,
                'default' => ''
            ],
            'descriptionformat' => [
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'type' => PARAM_INT,
                'default' => FORMAT_HTML,
            ],
            'hidden' => [
                'type' => PARAM_BOOL,
                'description' => 'Boolean flag for representation of whether team is hidden.',
                'default' => false,
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

    /**
     * @return bool
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public function cascadedelete() {

        // Does the team contain any members?
        $teammembers = teammember::get_records(['teamid' => $this->get('id')]);
        foreach ($teammembers as $teammember) {
            $teammember->delete();
        }

        // Does the team have any goals?
        $teamgoals = goal::get_records(['teamid' => $this->get('id')]);
        foreach ($teamgoals as $teamgoal) {
            $teamgoal->delete();
        }

        // Finally we get to delete the team.
        parent::delete();

        return true;
    }

    public function ishidden() {
        if ($this->get('hidden') == SELF::HIDDEN_TRUE) {
            return true;
        }
        return false;
    }

    public function user_isadmin($userid=null) {
        if ($userid == null) {
            global $USER;
            $userid = $USER->id;
        }

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance(), $userid);
        if ($managegoals) {
            return true;
        }
        foreach ($this->get_teammembers() as $teammember) {
            if ($userid == $teammember->get('userid')) {
                return $teammember->isadmin();
            }
        }
        return false;
    }

    public function get_teammembers() {
        if ($this->teammembers) {
            return $this->teammembers;
        } else {
            $this->teammembers = $this->read_teammembers();
            return $this->teammembers;
        }
    }

    private function read_teammembers() {
        $id = $this->get('id');
        if (!empty($id)) {
            $teammembers = teammember::get_records(['teamid' => $id],'timecreated', 'ASC');
            return $teammembers;
        }
    }

    public function get_teammembernames($seperator=', ') {

        global $USER;


        $teammembers = $this->get_teammembers();

        $names = '';
        foreach ($teammembers as $teammember) {
            if (!$teammember->isconfirmed() && $this->user_isadmin($USER->id)) {
                $names .= '(unconfirmed user)';
            } else {
                $names .= $teammember->get_teammembername();
            }
            if (next($teammembers) === false) {
            } else {
                $names .= $seperator;
            }

        }
        return $names;
    }

    public function has_member($userid) {
        return static::team_has_member($this->get('teamid'), $userid);
    }

    // Third parameter - exclude given teammember from search.
    public static function team_has_member($teamid, $userid, $teammember=null) {
        $teammemberid = -1;
        if (!empty($teammember)) {
            $teammemberid = $teammember->get('id');
        }
        return teammember::record_exists_select(
            '(teamid = :teamid AND userid = :userid AND id != :teammemberid AND confirmed = 1)',
            ['userid' => $userid, 'teamid' => $teamid, 'teammemberid' => $teammemberid]
        );
    }
}
