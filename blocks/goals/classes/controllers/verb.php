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
 * Verb Controller
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

class verb {

    private static function do_redirect() {
        global $CFG;
        redirect($CFG->wwwroot . '/blocks/goals/admin/verbs.php');
    }

    public static function display($id, $form=null) {
        global $OUTPUT, $PAGE;


        // Are we 'creating' or 'editing'?
        $verb = null;
        if (empty($id)) {
            $strheading = get_string('createnewverb', 'block_goals');
        } else {
            $verb = new models\verb($id);
            $record = $verb->read();
            $strheading = get_string('editverb', 'block_goals', format_string($record->get('verb')));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $form = new forms\verb($PAGE->url->out(false), ['persistent' => $verb, 'id' => $id, 'action' => 'editverb']);
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

        $verb = null;
        $customdata = [
            'persistent' => $verb,
            'id' => $id,
            'action' => 'editverb'
        ];
        if (!empty($id)) {
            $verb = new models\verb($id);
            $customdata['persistent'] = $verb;
            $customdata['sortorder'] = $verb->get('sortorder');
        }
        $form = new forms\verb($PAGE->url->out(false), $customdata);

        if ($data = $form->get_data()) {

            unset($data->action);

            try {
                $data->usercreated = $USER->id;

                if (empty($data->id)) {

                    $data->sortorder = models\verb::count_records() + 1;
                    $verb = new models\verb(0, $data);
                    if (!$verb->create()) {
                        notification::error("Could not create Verb $data->verb");
                    }

                    notification::success("Verb $data->verb created");
                } else {
                    $verb = new models\verb();
                    $verb->from_record($data);
                    if (!$verb->update()) {
                        notification::error("Could not update Verb $data->verb");
                    }

                    notification::success("Verb $data->verb updated");
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
            $verb = new models\verb($id);
            $verb->set('hidden', models\verb::HIDDEN_FALSE);
            if ($verb->update()) {
                notification::success('Verb ' . $verb->get('verb') . ' shown');
            } else {
                notification::error('Could not show team ' . $verb->get('verb'));
            }
        }
        self::do_redirect();
    }

    public static function hide($id) {
        if (!empty($id)) {
            $verb = new models\verb($id);
            $verb->set('hidden', models\verb::HIDDEN_TRUE);
            if ($verb->update()) {
                notification::success('Verb ' . $verb->get('verb') . ' hidden');
            } else {
                notification::error('Could not hide verb ' . $verb->get('verb'));
            }
        }
        self::do_redirect();
    }
    
    public static function delete($id) {
        $verb = new models\verb($id);
        if (!empty($id)) {
            if (confirm_sesskey()) {
                if ($verb->delete()) {
                    notification::success('Verb: ' . $verb->get('verb') . ' deleted');
                }
            }
        }

        self::do_redirect();
    }

    public static function move($id, $direction) {

        $verb = new models\verb($id);

        if (!confirm_sesskey()) {
            return false;
        }

        if ($verb->move($direction)) {
            notification::success('Verb: ' . $verb->get('verb') . ' moved ' . $direction);
        } else {
            notification::success('Could not move Verb: ' . $verb->get('verb'));
        }
        self::do_redirect();

    }

    public static function renderpage($confirm='') {

        global $OUTPUT, $DB;
        $verbs = models\verb::get_records([],'sortorder');

        // Print the header.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('verbs', 'block_goals'));

        echo $confirm;

        if (empty($verbs)) {
            notification::error(get_string('noverbsdefined', 'block_goals'));
        }

        //foreach ($verbs as $verbmodel) {
            //$verb = $verbmodel->to_record();
            $table = new \html_table();


            //$addnew = $OUTPUT->spacer() . '<a title="' . get_string('add') . '" href="verbs.php?id=0&amp;action=createverb&amp;verbid=' . $verb->id . '">';
            //$addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';


            $table->head  = array(get_string('verb', 'block_goals'), get_string('edit')); // . $addnew
            $table->align = array('left', 'right');
            $table->width = '95%';
            $table->attributes['class'] = 'generaltable verb';
            $table->data = array();

            //if ($filters = $DB->get_records('goal_filters', array('verbid' => $verb->id), 'sortorder ASC')) {
                foreach ($verbs as $verbmodel) {
                    $verb = $verbmodel->to_record();
                    $table->data[] = array(format_string($verb->verb), self::verb_icons($verb));
                }
            //}

            // echo $OUTPUT->heading(format_string($verb->verb) . ' ' . self::verb_icons($verb));

            if (count($table->data)) {
                echo \html_writer::table($table);
            } else {
                echo $OUTPUT->notification(get_string('noverbsdefined', 'block_goals'));
            }

        //} // End of $verbs foreach.

        echo '<hr />';
        echo '<div class="verbeditor">';

        // Create a new verb link.
        echo $OUTPUT->single_button(new \moodle_url('verbs.php', array('action' => 'createverb')), get_string('createverb', 'block_goals'));

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
     * Create a string containing the editing icons for the verbs
     * @param stdClass $verb the verb object
     * @return string the icon string
     */
    public static function verb_icons($verb) {
        global $CFG, $USER, $DB, $OUTPUT;

        $strhide     = get_string('hide');
        $strshow     = get_string('show');
        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');
        $stradd      = get_string('add');

        $verbcount = $DB->count_records('goal_verbs');
        //$filtercount    = $DB->count_records('goal_filters', array('verbid' => $verb->id));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="verbs.php?id='.$verb->id.'&amp;action=editverb">' .
            $OUTPUT->pix_icon('t/edit', $stredit) .'</a> ';

        // Add.
        $addnew = '<a title="' . get_string('add') . '" href="verbs.php?id=0&amp;action=createverb&amp;verbid=' . $verb->id . '">';
        $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';
        $editstr .= $addnew;

        if ($verb->hidden) {
            // Show.
            $editstr .= '<a title="' . $strshow . '" href="verbs.php?id=' . $verb->id . '&amp;action=showverb">';
            $editstr .= $OUTPUT->pix_icon('t/show', $strshow) . '</a> ';
        } else {
            // Hide.
            $editstr .= '<a title="' . $strhide . '" href="verbs.php?id=' . $verb->id . '&amp;action=hideverb">';
            $editstr .= $OUTPUT->pix_icon('t/hide', $strhide) . '</a> ';
        }

        // Delete.
        // Can only delete the last verb if there are no filters in it.
        if (($verbcount > 1)) {
            $editstr .= '<a title="'.$strdelete.'"';
            $editstr .= ' href="verbs.php?id='.$verb->id.'&amp;action=deleteverb&amp;sesskey='.sesskey() . '">';
            $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete).'</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move up.
        if ($verb->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="verbs.php?id='.$verb->id.'&amp;action=moveverb&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($verb->sortorder < $verbcount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="verbs.php?id='.$verb->id.'&amp;action=moveverb&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        return $editstr;
    }

    /**
     * Create a string containing the editing icons for the verbs
     * @param stdClass $filter the filter object
     * @return string the icon string
     */
    public static function verb_icons2($filter) {
        global $CFG, $USER, $DB, $OUTPUT;

        $strhide     = get_string('hide');
        $strshow     = get_string('show');
        $strdelete   = get_string('delete');
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
        $stredit     = get_string('edit');

        $verbcount = $DB->count_records('goal_verbs', array('verbid' => $filter->verbid));
        //$datacount  = $DB->count_records('goal_goalfilters', array('filterid' => $filter->id));

        // Edit.
        $editstr = '<a title="'.$stredit.'" href="verbs.php?id='.$filter->id.'&amp;action=editfilter">';
        $editstr .= $OUTPUT->pix_icon('t/edit', $stredit) . '</a> ';

        if ($filter->hidden) {
            // Show.
            $editstr .= '<a title="' . $strshow . '" href="verbs.php?id=' . $filter->id . '&amp;action=showverb">';
            $editstr .= $OUTPUT->pix_icon('t/show', $strshow) . '</a> ';
        } else {
            // Hide.
            $editstr .= '<a title="' . $strhide . '" href="verbs.php?id=' . $filter->id . '&amp;action=hideverb">';
            $editstr .= $OUTPUT->pix_icon('t/hide', $strhide) . '</a> ';
        }

        // Delete.
        $editstr .= '<a title="'.$strdelete.'" href="verbs.php?id='.$filter->id.'&amp;action=deletefilter&amp;sesskey='.sesskey().'">';
        $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';

        // Move up.
        if ($filter->sortorder > 1) {
            $editstr .= '<a title="'.$strmoveup.'" ';
            $editstr .= ' href="verbs.php?id='.$filter->id.'&amp;action=movefilter&amp;dir=up&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/up', $strmoveup) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }

        // Move down.
        if ($filter->sortorder < $filtercount) {
            $editstr .= '<a title="'.$strmovedown.'" ';
            $editstr .= ' href="verbs.php?id='.$filter->id.'&amp;action=movefilter&amp;dir=down&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/down', $strmovedown) . '</a> ';
        } else {
            $editstr .= $OUTPUT->spacer() . ' ';
        }
        // Add

        return $editstr;
    }
}