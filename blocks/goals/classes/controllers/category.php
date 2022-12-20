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
 * Category Controller
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_goals\controllers;

use block_goals\models;
use block_goals\forms;
use core;

defined('MOODLE_INTERNAL') || die();

class category {

    private static function do_redirect() {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/goals/admin/filters.php');
    }

    public static function display($id, $form=null) {
        global $OUTPUT, $PAGE;

        // Are we 'creating' or 'editing'?
        $category = null;
        if (empty($id)) {
            $strheading = get_string('createnewcategory', 'block_goals');
        } else {
            $category = new models\category($id);
            $record = $category->read();
            $strheading = get_string('editcategory', 'block_goals', format_string($record->get('name')));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $form = new forms\category($PAGE->url->out(false), ['persistent' => $category, 'action' => 'editcategory']);
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
        global $PAGE, $USER;

        $category = null;
        $customdata = [
            'persistent' => $category,
            'id' => $id,
            'action' => 'editcategory'
        ];
        if (!empty($id)) {
            $category = new models\category($id);
            $customdata['persistent'] = $category;
            $customdata['sortorder'] = $category->get('sortorder');
        }
        $form = new forms\category($PAGE->url->out(false), $customdata);

        if ($data = $form->get_data()) {

            unset($data->action);

            try {
                $data->usercreated = $USER->id;
                $data->descriptionformat = intval(FORMAT_HTML);
                if (isset($data->description)) {
                    if (isset($data->description['text'])) {
                        $data->descriptionformat = isset($data->description['format']) ? intval($data->description['format']) : intval(FORMAT_HTML);
                        $data->description = $data->description['text'];
                    }
                }

                $data->exampleformat = FORMAT_HTML;
                if (isset($data->example)) {
                    if (isset($data->example['text'])) {
                        $data->exampleformat = isset($data->example['format']) ? $data->example['format'] : FORMAT_HTML;
                        $data->example = $data->example['text'];
                    }
                }

                if (empty($data->id)) {

                    $data->sortorder = models\category::count_records() + 1;
                    $category = new models\category(0, $data);
                    if (!$category->create()) {
                        core\notification::error("Could not create Category $data->name");
                    }

                    core\notification::success("Category $data->name created");
                } else {
                    $category = new models\category();
                    $category->from_record($data);
                    if (!$category->update()) {
                        core\notification::error("Could not update Category $data->name");
                    }

                    core\notification::success("Category $data->name updated");
                }

            } catch (\Exception $e) {
                core\notification::error($e->getMessage());
            }

            self::do_redirect();
        }
        self::display($id, $form);
    }

    public static function delete($id) {
        $category = new models\category($id);
        if (!empty($id)) {
            if (confirm_sesskey()) {
                if ($category->cascadedelete()) {
                    core\notification::success('Category: ' . $category->get('name') . ' deleted');
                    core\notification::success('Moved any filters on ' . $category->get('name'));
                }
            }
        }

        self::do_redirect();
    }

    public static function move($id, $direction) {

        $category = new models\category($id);

        if (!confirm_sesskey()) {
            return false;
        }

        if ($category->move($direction)) {
            core\notification::success('Category: ' . $category->get('name') . ' moved ' . $direction);
        } else {
            core\notification::success('Could not move Category: ' . $category->get('name'));
            $category->reorder();
            core\notification::success('Categories reordered');
        }
        self::do_redirect();

    }

    public static function renderpage($confirm='') {

        global $OUTPUT, $DB;
        $categories = models\category::get_records([],'sortorder');

        // Check that we have at least one category defined.
        if (empty($categories)) {
            $defaultcategory = new \stdClass();
            $defaultcategory->name = get_string('defaultcategory', 'block_goals');
            $defaultcategory->sortorder = 1;
            $category = new models\category(0, $defaultcategory);
            $category->create();

            self::do_redirect();
        }

        // Print the header.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('filters', 'block_goals'));

        echo $confirm;

        foreach ($categories as $categorymodel) {
            $category = $categorymodel->to_record();
            $table = new \html_table();

            $addnew = $OUTPUT->spacer() . '<a title="' . get_string('add') . '" href="filters.php?id=0&amp;action=createfilter&amp;categoryid=' . $category->id . '">';
            $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';


            $table->head  = array(get_string('filter', 'block_goals'), get_string('edit') . $addnew);
            $table->align = array('left', 'right');
            $table->width = '95%';
            $table->attributes['class'] = 'generaltable filter';
            $table->data = array();


            //if ($filters = $DB->get_records('goal_filters', array('categoryid' => $category->id), 'sortorder ASC')) {
            if ($filters = models\filter::get_records(['categoryid' => $category->id], 'sortorder')) {
                foreach ($filters as $filter) {
                    $table->data[] = array(filter::icon($filter) . format_string($filter->get('name')), self::filter_icons($filter));
                }
            }

            echo $OUTPUT->heading(format_string($category->name) . ' ' . self::filter_category_icons($category));
            $description = '';
            if (!empty($category->description)) {
                //Description
                $description = $OUTPUT->box(
                    'Description<hr>' .
                    format_text($category->description, $category->descriptionformat),  //,  ['context' => context_block::instance($this->blockinstanceid)]
                    'description-description'
                );
            }
            $example = '';
            if (!empty($category->example)) {
                $example = $OUTPUT->box(
                    'Example<hr>' .
                    format_text($category->example, $category->exampleformat), // ['context' => context_block::instance($this->blockinstanceid)]
                    'example-description'
                );
                //$OUTPUT->render_from_template()
            }
            if (!empty($description) || !empty($example)) {
                echo $OUTPUT->container($description . $example);
            }

            if (count($table->data)) {
                echo \html_writer::table($table);
            } else {
                echo $OUTPUT->notification(get_string('nofiltersdefined', 'block_goals'));
            }

        } // End of $categories foreach.

        echo '<hr />';
        echo '<div class="fitlereditor">';

        // Create a new filter link.s
        echo $OUTPUT->single_button(new \moodle_url('filters.php', array('action' => 'createfilter')), get_string('createfilter', 'block_goals'));

        // Create a new category link.
        echo $OUTPUT->single_button(new \moodle_url('filters.php', array('action' => 'createcategory')), get_string('createcategory', 'block_goals'));

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
        if ($managegoals) {
            echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/index.php'), get_string('admin', 'block_goals'));
        }

        echo '</div>';
        echo $OUTPUT->footer();
        die;

    }

    /***** Some functions relevant to this script *****/

    /**
     * Create a string containing the editing icons for the categories
     * @param stdClass $category the category object
     * @return string the icon string
     */
    public static function filter_category_icons($category) {
        global $CFG, $USER, $DB, $OUTPUT;

        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');
        $stradd      = get_string('add');

        $categorycount = $DB->count_records('goal_filtercategories');
        $filtercount    = $DB->count_records('goal_filters', array('categoryid' => $category->id));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="filters.php?id='.$category->id.'&amp;action=editcategory">' .
            $OUTPUT->pix_icon('t/edit', $stredit) .'</a> ';

        // Add.
        $addnew = '<a title="' . get_string('add') . '" href="filters.php?id=0&amp;action=createfilter&amp;categoryid=' . $category->id . '">';
        $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';
        $editstr .= $addnew;

        // Delete.
        // Can only delete the last category if there are no filters in it.
        if (($categorycount > 1) or ($filtercount == 0)) {
            $editstr .= '<a title="'.$strdelete.'"';
            $editstr .= ' href="filters.php?id='.$category->id.'&amp;action=deletecategory&amp;sesskey='.sesskey() . '">';
            $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete).'</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move up.
        if ($category->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="filters.php?id='.$category->id.'&amp;action=movecategory&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($category->sortorder < $categorycount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="filters.php?id='.$category->id.'&amp;action=movecategory&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        return $editstr;
    }

    /**
     * Create a string containing the editing icons for the goal filters
     * @param stdClass $filter the filter object
     * @return string the icon string
     */
    public static function filter_icons($filter) {
        global $CFG, $USER, $DB, $OUTPUT;

        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');

        $filtercount = $DB->count_records('goal_filters', array('categoryid' => $filter->get('categoryid')));
        $datacount  = $DB->count_records('goal_goalfilters', array('filterid' => $filter->get('id')));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="filters.php?id='.$filter->get('id').'&amp;action=editfilter">';
        $editstr .= $OUTPUT->pix_icon('t/edit', $stredit) . '</a> ';

        // Delete.
        $editstr .= '<a title="'.$strdelete.'" href="filters.php?id='.$filter->get('id').'&amp;action=deletefilter&amp;sesskey='.sesskey().'">';
        $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';

        // Move up.
        if ($filter->get('sortorder') > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="filters.php?id='.$filter->get('id').'&amp;action=movefilter&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($filter->get('sortorder') < $filtercount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="filters.php?id='.$filter->get('id').'&amp;action=movefilter&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }
        // Add

        return $editstr;
    }

    public static function get_filtershtml($category) {
        $result = '';
        if (empty($category)) {
            global $OUTPUT;
            $result = $OUTPUT->notification(get_string('cannotfindcategory', 'block_goals', ''), core\output\notification::NOTIFY_WARNING);
        } else {
            $filters = $category->get_filters();
            foreach ($filters as $filter) {
                $name = 'filter_' . $filter->get('id');
                $label = $filter->get('name');
                $result .= \html_writer::start_div('form-check');
                $result .= \html_writer::start_tag('label', ['title' => $label, 'for' => $name, 'class' => 'form-check-label']);
                $result .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => '0']);
                $result .= \html_writer::checkbox($name, '1', '', null, ['class' => 'form-check-input']);
                $result .= filter::icon($filter);
                $result .= $label;
                $result .= \html_writer::end_tag('label');
                //$result .= \html_writer::checkbox($name, '1', '', $label, ['class' => 'form-check-input']);
                $result .= \html_writer::end_div();
            }
        }
        return $result;
    }


    /*
<div class="form-check">
    <label title="No Specific Species" for="speciesnone" class="form-check-label">
        <input id="speciesnone" name="speciesnone" title="No Specific Species" type="checkbox" class="form-check-input">
        <i class="fad fa-warehouse-alt"></i>No Specific Species
    </label>
</div>
     */
}