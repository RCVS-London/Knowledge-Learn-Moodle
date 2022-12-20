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
 * Goal Controller
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

class goal {

    private static function do_redirect() {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/goals/admin/goals.php');
    }

    public static function display($id, $form=null) {
        global $OUTPUT, $PAGE;

        // Are we 'creating' or 'editing'?
        $goal = null;
        if (empty($id)) {
            $strheading = get_string('createnewgoal', 'block_goals');
        } else {
            $goal = new models\goal($id);
            $record = $goal->read();
            $strheading = get_string('editgoal', 'block_goals', shorten_text(format_string($record->get('goaltext'))));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $categories = models\category::get_records([], 'sortorder');
            $form = new forms\goal($PAGE->url->out(false), ['persistent' => $goal, 'action' => 'editgoal', 'categories' => $categories]);
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

        $goal = null;

        $customdata = [
            'persistent' => $goal,
            'id' => $id,
            'categories' => models\category::get_records([], 'sortorder'),
            'action' => 'editgoal'
        ];

        if (!empty($id)) {
            $goal = new models\goal($id);
            $customdata['persistent'] = $goal;
            //$customdata['sortorder'] = $goal->get('sortorder');

            // 1. Get all the categories, and their filters
            // 2. Get all the saved filters
            //$customdata['filters'] = models\filter::get_records();
            $customdata['goalfilters'] = models\goalfilter::get_records(['goalid' => $id]);
        }
        $form = new forms\goal($PAGE->url->out(false), $customdata);
        $data = $form->get_data();

        if ($data) {

            unset($data->action);

            try {
                $data->usercreated = $USER->id;

                // TODO: unset filters from data
                // TODO: Create a bunch of filter records here
                $goaltext = shorten_text($data->goaltext);
                $formgoalfilters = [];

                foreach ($data as $property => $value) {
                    if (strpos($property, 'filter_') !== false) {
                        $filterid = strtok($property, 'filter_');
                        $formgoalfilters[$filterid] = (object)[
                            'filterid' => $filterid,
                            'booleanvalue' => $value
                        ];
                        unset($data->$property);
                    }
                }

                if (empty($data->id)) {
                    //$data->sortorder = models\goal::count_records() + 1;
                    $goal = new models\goal(0, $data);
                    if (!$goal->create()) {
                        notification::error("Could not create SMART Goal: $goaltext");
                    }
                    notification::success("SMART Goal: $goaltext created");
                } else {
                    $goal = new models\goal();
                    $goal->from_record($data);
                    if (!$goal->update()) {
                        notification::error("Could not update SMART Goal: $goaltext");
                    }
                    notification::success("SMART Goal: $goaltext updated");
                }

                // Update existing filters go goal first.
                if (!empty($data->id)) {
                    $existinggoalfilters = models\goalfilter::get_records(['goalid' => $data->id]);
                    foreach ($existinggoalfilters as $existinggoalfilter) {
                        $filterid = $existinggoalfilter->get('filterid');
                        $name = $existinggoalfilter->get_filter()->get('name');
                        $value = '';
                        if (array_key_exists($id, $formgoalfilters)) {
                            $value = $formgoalfilters[$filterid]->booleanvalue;
                            unset($formgoalfilters[$filterid]);
                        }

                        if (!empty($value)) {
                            $existinggoalfilter->set('booleanvalue', $value);
                            if ($existinggoalfilter->update()) {
                                notification::success('SMART Goal filter ' . $name . ' updated to ' . var_export($value, true));
                            } else {
                                notification::error('SMART Goal filter ' . $name . ' could not be updated.');
                            }
                        } else {
                            if ($existinggoalfilter->delete()) {
                                notification::success('SMART Goal filter ' . $name . ' deleted.');
                            } else {
                                notification::error('SMART Goal filter ' . $name . ' could not be deleted.');
                            }
                        }

                    }
                }

                // The filters that remain in $goalfilters need to be created.
                foreach ($formgoalfilters as $formgoalfilter) {

                    if (!empty($formgoalfilter->booleanvalue)) {
                        $goalfilter = new models\goalfilter(0);
                        $goalfilter->set('goalid', $goal->get('id'));
                        $goalfilter->set('filterid', $formgoalfilter->filterid);
                        $goalfilter->set('booleanvalue', $formgoalfilter->booleanvalue);
                        $name = $goalfilter->get_filter()->get('name');

                        if ($goalfilter->create()) {
                            notification::success('SMART Goal filter: ' . $name . ' created with value: ' . var_export($formgoalfilter->booleanvalue, true));
                        } else {
                            notification::error('Could not create SMART goal filter: ' . $name);
                        }
                    }

                }

            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }

            self::do_redirect();
        }
        self::display($id, $form);
    }

    public static function show($id) {
        if (!empty($id)) {
            $goal = new models\goal($id);
            $goal->set('hidden', models\goal::HIDDEN_FALSE);
            if ($goal->update()) {
                notification::success('Goal ' . $goal->get_abstract() . ' shown');
            } else {
                notification::error('Could not show goal ' . $goal->get_abstract());
            }
        }
        self::do_redirect();
    }

    public static function hide($id) {
        if (!empty($id)) {
            $goal = new models\goal($id);
            $goal->set('hidden', models\goal::HIDDEN_TRUE);
            if ($goal->update()) {
                notification::success('Goal ' . $goal->get_abstract() . ' is now hidden.');
            } else {
                notification::error('Could not hide goal ' . $goal->get_abstract());
            }
        }
        self::do_redirect();
    }
    
    public static function delete($id) {
        $goal = new models\goal($id);
        if (!empty($id)) {
            if (confirm_sesskey()) {
                $name = get_string('missinggoaltext','block_goals');
                if (models\goal::has_property('goaltext')) {
                    $name = $goal->get('goaltext');
                }

                if ($goal->cascadedelete()) {
                    notification::success('Goal: ' . $name . ' deleted');
                }
            }
        }

        self::do_redirect();
    }

    public static function move($id, $direction) {

        $goal = new models\goal($id);

        if (!confirm_sesskey()) {
            return false;
        }

        if ($goal->move($direction)) {
            notification::success('Goal: ' . $goal->get('name') . ' moved ' . $direction);
        } else {
            notification::success('Could not move Goal: ' . $goal->get('name'));
        }
        self::do_redirect();

    }

    public static function renderpage($confirm='') {

        global $OUTPUT, $DB;

        // Print the header.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('goals', 'block_goals'));

        echo $confirm;

        self::rendergoals(models\goal::TYPE_TEMPLATE);

        self::rendergoals(models\goal::TYPE_TEAM);

        self::rendergoals(models\goal::TYPE_INDIVIDUAL);

        // Create a new goal link.
        echo $OUTPUT->single_button(new \moodle_url('goals.php', array('action' => 'creategoal')), get_string('creategoal', 'block_goals'));

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
        if ($managegoals) {
            echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/admin/index.php'), get_string('admin', 'block_goals'));
        }

        echo '<hr />';

        echo $OUTPUT->footer();
        die;

    }

    private static function rendergoals($type) {

        $heading = '';
        $name = '';
        switch ($type) {
            case models\goal::TYPE_TEMPLATE:
                $heading = get_string('templategoals', 'block_goals');
                $name = get_string('template', 'block_goals');
                break;
            case models\goal::TYPE_TEAM:
                $heading = get_string('teamgoals', 'block_goals');
                $name = get_string('team', 'block_goals');
                break;
            case models\goal::TYPE_INDIVIDUAL:
                $heading = get_string('individualgoals', 'block_goals');
                $name = get_string('individual', 'block_goals');
                break;
            default:
                return false;
        }

        $goals = models\goal::get_records(['type' => $type], 'duedate', 'DESC');

        global $OUTPUT, $DB;
        echo $OUTPUT->heading($heading);

        $table = new \html_table();

        $addnew = $OUTPUT->spacer() . '<a title="' . get_string('add') . '" href="goals.php?id=0&amp;action=creategoal&amp;type=' . $type . '">';
        $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';

        $headerrow = [
            get_string('duedate', 'block_goals'),
            $name,
            get_string('goal', 'block_goals'),
        ];

        if ($type != models\goal::TYPE_TEMPLATE) {
            $headerrow[] = get_string('progress', 'block_goals');
        }

        $headerrow[] = get_string('edit') . $addnew;

        $table->head = $headerrow;
        $table->align = ['left', 'left', 'left', 'left', 'right'];
        $table->width = '95%';
        $table->attributes['class'] = 'generaltable filter';
        $table->data = [];

        foreach ($goals as $goal) {

            $name = '';
            switch ($type) {

                case models\goal::TYPE_TEMPLATE:
                    $name = get_string('missingtemplatename','block_goals');
                    if (!empty($goal)) {
                        $name = fullname($goal->get_createuser());
                    }
                    break;
                case models\goal::TYPE_TEAM:
                    $name = get_string('missingteamname','block_goals');
                    if (!empty($goal)) {
                        $name = $goal->get_teamname();
                    }
                    break;
                case models\goal::TYPE_INDIVIDUAL:
                    $name = get_string('missingindividualname','block_goals');
                    $name = fullname($goal->get_user());
                    break;
                default:
                    return false;
            }

            $row = [
                userdate($goal->get('duedate'), get_string('strftimedatefullshort', 'core_langconfig')),
                $name,
                $goal->get('goaltext'),
            ];

            if ($type != models\goal::TYPE_TEMPLATE) {
                $row[] = self::progress($goal->get('progress'));
            }

            $row[] = self::goal_icons($goal->to_record());

            $table->data[] = $row;
        }

        if (count($table->data)) {
            echo \html_writer::table($table);
        } else {
            echo $OUTPUT->notification(get_string('nogoalsdefined', 'block_goals'));
        }

        echo '<hr />';
    }

    /***** Some functions relevant to this script *****/

    /**
     * Create a string containing the editing icons for the goals
     * @param stdClass $goal the goal object
     * @return string the icon string
     */
    public static function goal_icons($goal) {
        global $CFG, $USER, $DB, $OUTPUT;

        $strhide     = get_string('hide');
        $strshow     = get_string('show');
        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');
        $stradd      = get_string('add');

        $goalcount = $DB->count_records('goal_goals');
        $filtercount    = $DB->count_records('goal_goalfilters', array('goalid' => $goal->id));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="goals.php?id='.$goal->id.'&amp;action=editgoal">' .
            $OUTPUT->pix_icon('t/edit', $stredit) .'</a> ';

        // Add.
        //$addnew = '<a title="' . get_string('add') . '" href="goals.php?id=0&amp;action=creategoalfilter&amp;goalid=' . $goal->id . '">';
        //$addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';
        //$editstr .= $addnew;

        if ($goal->hidden) {
            // Show.
            $editstr .= '<a title="' . $strshow . '" href="goals.php?id=' . $goal->id . '&amp;action=showgoal">';
            $editstr .= $OUTPUT->pix_icon('t/show', $strshow) . '</a> ';
        } else {
            // Hide.
            $editstr .= '<a title="' . $strhide . '" href="goals.php?id=' . $goal->id . '&amp;action=hidegoal">';
            $editstr .= $OUTPUT->pix_icon('t/hide', $strhide) . '</a> ';
        }

        // Delete.
        // Can only delete the last goal if there are no filters in it.
        if (($goalcount > 1) or ($filtercount == 0)) {
            $editstr .= '<a title="'.$strdelete.'"';
            $editstr .= ' href="goals.php?id='.$goal->id.'&amp;action=deletegoal&amp;sesskey='.sesskey() . '">';
            $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete).'</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move up.
        /*
        if ($goal->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="goals.php?id='.$goal->id.'&amp;action=movegoal&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($goal->sortorder < $goalcount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="goals.php?id='.$goal->id.'&amp;action=movegoal&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }
        */

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

        $filtercount = $DB->count_records('goal_filters', array('goalid' => $filter->goalid));
        $datacount  = $DB->count_records('goal_goalfilters', array('filterid' => $filter->id));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="filters.php?id='.$filter->id.'&amp;action=editfilter">';
        $editstr .= $OUTPUT->pix_icon('t/edit', $stredit) . '</a> ';

        // Delete.
        $editstr .= '<a title="'.$strdelete.'" href="filters.php?id='.$filter->id.'&amp;action=deletefilter&amp;sesskey='.sesskey().'">';
        $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';

        // Move up.
        if ($filter->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="filters.php?id='.$filter->id.'&amp;action=movefilter&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($filter->sortorder < $filtercount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="filters.php?id='.$filter->id.'&amp;action=movefilter&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }
        // Add

        return $editstr;
    }

    public static function progress($progress) {
        $content = '';
        $content .= \html_writer::start_tag('div', array('class' => 'progress'));
        $content .= \html_writer::start_tag('div', array('class' => 'progress-bar bar', 'role' => 'progressbar',
            'style' => 'width: ' . $progress .'%', 'aria-valuenow' => $progress, 'aria-valuemin' => 0, 'aria-valuemax' => 100));
        $content .= $progress . "%";
        $content .= \html_writer::end_tag('div');
        $content .= \html_writer::end_tag('div');
        return $content;
    }

    public static function get_goaltable($id, $goals, $progress = false, $add = false) {
        //$templategoals = models\goal::get_records(['template' => true]);
        $table = new \html_table();
        $table->id = $id;
        $table->attributes['class'] = 'goaltable table table-striped table-bordered dt-responsive';
        $table->attributes['style'] = 'width:100%';
        $table->head  = [
            get_string('select', 'block_goals'),
            get_string('goal', 'block_goals'),
            get_string('progress', 'block_goals'),
            get_string('type', 'block_goals'),
            get_string('verb', 'block_goals'),
            get_string('percentage', 'block_goals'),
            get_string('noun', 'block_goals'),
            get_string('filters', 'block_goals'),
            get_string('recentupdate', 'block_goals'),
            get_string('duedate', 'block_goals'),
        ];
        $table->data = [];

        foreach ($goals as $goal) {

            $goaltext = $goal->get('goaltext');

            $button = '';
            if ($add) {
                $button = '<button type="button" class="btn btn-success add-goal">Use this SMART goal &nbsp;<i class="fas fa-plus"></i></button>';
            } else {
                $button = '
                    <form method="GET" action="/blocks/goals/view.php">
                        <input type="hidden" name="action" value="trackgoal">
                        <input type="hidden" name="id" value="' . $goal->get('id') . '">
                        <button type="submit" class="btn btn-success">' . get_string('trackprogress', 'block_goals') . ' &nbsp;<i class="fas fa-edit"></i></button>
                    </form>';
            }

            //

/*
            $table = new html_table();

            $cell = new html_table_cell('TEXT');
            $cell->attributes['class'] = 'showFine';
            $row->cells[] = $cell;
            $table->data[] = $row;
*/
            $row = new \html_table_row();
            if ($goal->get('type') != models\goal::TYPE_TEMPLATE) {
                if (empty($goal->get('progress'))) {
                    $rowclass = 'table-warning';
                } else {
                    if ($goal->get('progress') == 100) {
                        $rowclass = 'table-success';
                    } else {
                        $rowclass = 'table-info';
                    }
                }
                $row->attributes['class'] = $rowclass;
            }

            $row->cells = [
                $button,
                $goaltext,
                self::progress($goal->get('progress')),
                $goal->get_typename(),
                $goal->get_verbname(),
                $goal->get_percentagename(),
                $goal->get('objective'),
                $goal->get_filternames(),
                $goal->get_formattedrecenthistory(),
                $goal->get_formattedduedate()
            ];

            $table->data[] = $row;
        }
        if (count($table->data)) {
            return \html_writer::table($table);
        } else {
            global $OUTPUT;
            return $OUTPUT->notification(get_string('nogoalsdefined', 'block_goals'));
        }
    }

}