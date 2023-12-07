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
 * Class for verbs persistence.
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_goals\models;
defined('MOODLE_INTERNAL') || die();

use coding_exception;

/**
 * Class for loading/storing verbs from the DB.
 *
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class verb extends \core\persistent {

    const TABLE = 'goal_verbs';

    public const HIDDEN_FALSE = 0;
    public const HIDDEN_TRUE = 1;

    public const MOVE_UP = 'up';
    public const MOVE_DOWN = 'down';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'verb' => [
                'type' => PARAM_TEXT,
                'description' => 'Verb to build goal.',
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'description' => 'Verb to build goal.',
                'default' => 0,
            ],
            'hidden' => [
                'type' => PARAM_BOOL,
                'description' => 'Boolean flag for representation of whether verb is hidden.',
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
     * Hook to execute before a delete.
     *
     * This is only intended to be used by child classes, do not put any logic here!
     *
     * @return void
     */
    public function before_delete() {
        // TODO: prevent deletion if used as foreign key in {goals_goals}.
        // throw new coding_exception('cannot delete');
    }

    /**
     * Hook to execute after a delete.
     *
     * This is only intended to be used by child classes, do not put any logic here!
     *
     * @param bool $result Whether or not the delete was successful.
     * @return void
     */
    public function after_delete($result) {
        if (!empty($result)) {
            self::reorder();
        }
    }

    public function move($direction) {

        // Get all verbs.
        $verbs = verb::get_records();

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
                if ($this->get('sortorder') < count($verbs)) {
                    $neworder = $this->get('sortorder') + 1;
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        // Find a verb that has the new sortorder
        foreach ($verbs as $swapverb) {
            if ($swapverb->get('sortorder') == $neworder) {
                $swapverb->set('sortorder', $this->get('sortorder'));
                $this->set('sortorder', $neworder);
                $swapverb->update();
                $this->update();
                return true;
            }
        }

        return false;
    }

    public static function reorder() {
        // Get all verbs in ascending sortorder.
        if ($verbs = verb::get_records([],'sortorder')) {

            // normalise sortorders starting at 1.
            $sortorder = 1;
            foreach ($verbs as $verb) {
                $verb->set('sortorder', $sortorder);
                $verb->update();
                $sortorder++;
            }
        }
    }
}
