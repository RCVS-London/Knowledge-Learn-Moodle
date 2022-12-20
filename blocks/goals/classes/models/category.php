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
 * Class for categories persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for loading/storing categories from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class category extends \core\persistent {

    const TABLE = 'goal_filtercategories';

    public const MOVE_UP = 'up';
    public const MOVE_DOWN = 'down';

    private $filters = null;

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
                'type' => PARAM_RAW, // PARAM_CLEANHTML, PARAM_RAW, PARAM_TEXT
                'description' => 'Description text for the category.',
                'null' => NULL_NOT_ALLOWED,
                'default' => ''
            ],
            'descriptionformat' => [
                'type' => PARAM_INT,
                'description' => 'The format of the description field',
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'default' => FORMAT_HTML,
            ],
            'example' => [
                'type' => PARAM_RAW, // PARAM_CLEANHTML, PARAM_RAW, PARAM_TEXT
                'description' => 'Description text for the category.',
                'null' => NULL_NOT_ALLOWED,
                'default' => ''
            ],
            'exampleformat' => [
                'type' => PARAM_INT,
                'description' => 'The format of the example field',
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'default' => FORMAT_HTML,
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

    protected function validate_example($value) {
        //if ($value !== 'My expected value') {
        //    return new \lang_string('invaliddata', 'error');
        //}
        return true;
    }

    public function cascadedelete() {

        // Get all categories.
        if (!$categories = category::get_records([],'sortorder')) {
            return false;
        }
        // Remove the current category from the list of categories
        unset($categories[$this->get('id')]);

        // We can not delete the last category.
        if (!count($categories)) {
            return false;
        }

        // Does the category contain any filters?
        $filters = filter::get_records(['categoryid' => $this->get('id')],'sortorder');
        if ($filters) {

            // Move the filters to another category.
            if (array_key_exists($this->get('sortorder') - 2, $categories)) {
                $newcategory = $categories[$this->get('sortorder') - 2];
            } else if (array_key_exists($this->get('sortorder'), $categories)) {
                $newcategory = $categories[$this->get('sortorder')];
            } else {
                // Get first category if sortorder broken.
                $newcategory = reset($categories);
            }

            // Starting sortorder
            $sortorder = filter::count_records(['categoryid' => $newcategory->get('id')]) + 1;

            foreach ($filters as $filter) {
                $filter->set('sortorder', $sortorder);
                $filter->set('categoryid', $newcategory->get('id'));
                $filter->update();
                $sortorder ++;
            }
        }

        // Finally we get to delete the category.
        parent::delete();

        self::reorder();

        return true;
    }

    public function move($direction) {

        // Get all categories.
        $categories = category::get_records();

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
                if ($this->get('sortorder') < count($categories)) {
                    $neworder = $this->get('sortorder') + 1;
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        // Find a category that has the new sortorder
        foreach ($categories as $swapcategory) {
            if ($swapcategory->get('sortorder') == $neworder) {
                $swapcategory->set('sortorder', $this->get('sortorder'));
                $this->set('sortorder', $neworder);
                $swapcategory->update();
                $this->update();
                return true;
            }
        }

        return false;
    }

    public static function reorder() {
        // Get all categories in ascending sortorder.
        if ($categories = category::get_records([],'sortorder')) {

            // normalise sortorders starting at 1.
            $sortorder = 1;
            foreach ($categories as $category) {
                $category->set('sortorder', $sortorder);
                $category->update();
                $sortorder++;
            }
        }
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
        $id = $this->get('id');
        if (!empty($id)) {
            $filters = filter::get_records(['categoryid' => $id], 'sortorder');
            return $filters;
        }
    }

    public function get_categoryfilternames($seperator=', ') {

        $filters = $this->get_filters();

        $names = '';
        foreach ($filters as $filter) {

            $names .= $filter->get('name');
            
            if (next($filters) === false) {
            } else {
                $names .= $seperator;
            }

        }
        return $names;
    }
}
