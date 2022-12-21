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
 * Class for filters persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing filters from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class filter extends \core\persistent {

    const TABLE = 'goal_filters';

    public const MOVE_UP = 'up';
    public const MOVE_DOWN = 'down';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'categoryid' => [
                'type' => PARAM_INT,
                'description' => 'The category id.',
                'null' => NULL_NOT_ALLOWED,
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'The filter name.',
            ],
            'faicon' => [
                'type' => PARAM_TEXT,
                'description' => 'Font Awesome Icon.',
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0,
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

    public function move($direction) {

        // Get all filters.
        $filters = filter::get_records(['categoryid' => $this->get('categoryid')], 'sortorder');

        // Calculate the new sortorder.
        switch ($direction) {
            case self::MOVE_UP:
                if ($this->get('sortorder') > 1) {
                    $neworder = $this->get('sortorder') - 1;
                } else {
                    return false;
                }
                break;
            case self::MOVE_DOWN:
                if ($this->get('sortorder') < count($filters)) {
                    $neworder = $this->get('sortorder') + 1;
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        // Find a category that has the new sortorder
        foreach ($filters as $swapfilter) {
            if ($swapfilter->get('sortorder') == $neworder) {
                $swapfilter->set('sortorder', $this->get('sortorder'));
                $this->set('sortorder', $neworder);
                $swapfilter->update();
                $this->update();
                return true;
            }
        }

        return false;
    }

    public static function reorder() {
        $categories = category::get_records([],'sortorder');
        foreach ($categories as $category) {
            $sortorder = 1;
            if ($filters = filter::get_records(['categoryid' => $category->get('id')],'sortorder')) {
                foreach ($filters as $filter) {
                    $filter->set('sortorder', $sortorder);
                    $filter->update();
                    $sortorder++;
                }
            }
        }
    }
}
