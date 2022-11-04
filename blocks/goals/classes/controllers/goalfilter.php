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
 * Filter Controller
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

class goalfilter {

    private static function do_redirect() {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/goals/admin/goals.php');
    }

    public static function display($id, $categoryid=0, $form=null) {
        global $OUTPUT, $PAGE;

        // Are we 'creating' or 'editing'?
        $filter = null;
        if (empty($id)) {
            $strheading = get_string('createnewfilter', 'block_goals');
        } else {
            $filter = new models\filter($id);
            $record = $filter->get_record();
            $strheading = get_string('editfilter', 'block_goals', format_string($record->name));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $form = new forms\filter($PAGE->url->out(false), ['persistent' => $filter, 'action' => 'editfilter', 'categoryid' => $categoryid]);
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
        $filter = null;
        if (!empty($id)) {
            $filter = new models\filter($id);
        }
        $form = new forms\filter($PAGE->url->out(false), ['persistent' => $filter, 'id' => $id, 'action' => 'editfilter']);

        if ($data = $form->get_data()) {

            $action = $data->action;
            unset($data->action);

            try {
                $data->timemodified = time();

                if (empty($data->id)) {

                    $data->sortorder = $DB->count_records('goal_filters', array('categoryid' => $data->categoryid)) + 1;
                    $data->usercreated = $USER->id;
                    $data->timecreated = time();
                    $filter = new models\filter(0, $data);
                    if ($filter->create()) {
                        notification::success("Filter $data->name created");
                    }
                } else {
                    $filter = new models\filter();
                    $filter->from_record($data);
                    if ($filter->update()) {
                        notification::success("filter $filter->name updated");
                    }
                }
                $filter->reorder();

                $category = new models\category();
                $category->reorder();

            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }

            self::do_redirect();
        }
        self::display($id, 0, $form);
    }

    public static function delete($id) {

        $filter = new models\filter($id);

        $data = $filter->to_record();
        if (!empty($data->id)) {
            if (confirm_sesskey()) {
                if ($filter->delete()) {
                    notification::success("Filter $data->name deleted");
                }
            }
        }
        self::do_redirect();
    }

    public static function move($id, $direction) {

        $filter = new models\filter($id);

        $data = $filter->to_record();
        if (!empty($data->id)) {
            if (confirm_sesskey()) {
                if ($filter->move($direction)) {
                    notification::success("Filter $data->name moved $direction");
                } else {
                    notification::error("Could not move Filter $data->name $direction");
                    models\filter::reorder();
                }
            }
        }
        self::do_redirect();
    }
}