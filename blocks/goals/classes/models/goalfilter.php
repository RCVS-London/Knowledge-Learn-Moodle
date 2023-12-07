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
 * Class for goalfilters persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing goalfilters from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class goalfilter extends \core\persistent {

    const TABLE = 'goal_goalfilters';

    const DATATYPE_BOOLEAN = 0;

    private $goal = null;
    private $filter = null;

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
            'filterid' => [
                'type' => PARAM_INT,
                'description' => 'The filter id.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'datatype' => [
                'choices' => [self::DATATYPE_BOOLEAN],
                'type' => PARAM_INT,
                'default' => self::DATATYPE_BOOLEAN,
            ],
            'booleanvalue' => [
                'type' => PARAM_BOOL,
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

    public function get_goal() {
        if ($this->goal) {
            return $this->goal;
        } else {
            $this->goal = $this->read_goal();
            return $this->goal;
        }
    }

    private function read_goal() {
        $goalid = $this->get('goalid');
        if (!empty($goalid)) {
            $goal = new goal($goalid);
            return $goal;
        }
    }

    public function get_filter() {
        if ($this->filter) {
            return $this->filter;
        } else {
            $this->filter = $this->read_filter();
            return $this->filter;
        }
    }

    private function read_filter() {
        $filterid = $this->get('filterid');
        if (!empty($filterid)) {
            $filter = new filter($filterid);
            return $filter;
        }
    }


}
