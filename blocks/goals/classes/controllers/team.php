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
 * Team Controller
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

class team {

    private static function do_redirect() {
        global $CFG;
        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
        if ($managegoals) {
            redirect($CFG->wwwroot . '/blocks/goals/admin/teams.php');
        }
        redirect($CFG->wwwroot . '/blocks/goals/teams.php');
    }

    public static function display($id, $form=null) {
        global $OUTPUT, $PAGE;

        // Are we 'creating' or 'editing'?
        $team = null;
        if (empty($id)) {
            $strheading = get_string('createnewteam', 'block_goals');

            // Set default on persistent.
            $team = new models\team(0);
            $team->set('description', get_string('teamdescriptiondefault', 'block_goals'));
            $team->set('descriptionformat', FORMAT_HTML);

        } else {
            $team = new models\team($id);
            $record = $team->read();
            $strheading = get_string('editteam', 'block_goals', format_string($record->get('name')));
        }

        // Initialise a form object if we haven't been provided with one.
        if ($form == null) {
            $form = new forms\team($PAGE->url->out(false), ['persistent' => $team, 'action' => 'editteam']);
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

        $team = null;
        if (!empty($id)) {
            $team = new models\team($id);


            /*
             *
             * TODO:
            $editoroptions = array(
                'maxfiles'  => EDITOR_UNLIMITED_FILES,
                'maxbytes'  => $CFG->maxbytes,
                'trusttext' => false,
                'context'   => $systemcontext,
                'subdirs'   => file_area_contains_subdirs($systemcontext, 'tag', 'description', $tag->id),
            );

            $context = \context_system::instance();
            global $CFG;
            $editoroptions = array(
                'subdirs' => 0,
                'maxbytes' => $CFG->maxbytes,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'changeformat' => 0,
                'context' => $context,
                'noclean' => false,
                'trusttext' => false
            );
            $team = file_prepare_standard_editor($team, 'description', $editoroptions, $context, 'block_goals', 'content', $team->id);
            */

        }
        $form = new forms\team($PAGE->url->out(false), ['persistent' => $team, 'id' => $id, 'action' => 'editteam']);

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

                /*
                $data->exampleformat = FORMAT_HTML;
                if (isset($data->example)) {
                    if (isset($data->example['text'])) {
                        $data->exampleformat = isset($data->example['format']) ? $data->example['format'] : FORMAT_HTML;
                        $data->example = $data->example['text'];
                    }
                }
                */

                if (empty($data->id)) {

                    $team = new models\team(0, $data);
                    if (!$team->create()) {
                        notification::error("Could not create team $data->name");
                    } else {
                        notification::success("Team $data->name created");

                        // Add team creator as admin to their team.
                        $teammember = new models\teammember(0, (object)[
                            'teamid' => $team->get('id'),
                            'userid' => $USER->id,
                            'confirmed' => models\teammember::CONFIRMED_TRUE,
                            'name' => '',
                            'email' => '',
                            'secret' => '',
                            'isadmin' => models\teammember::ISADMIN_TRUE,
                            'usercreated' => $USER->id,
                            'timecreated' => time(),
                            'timemodified' => time(),
                        ]);
                        if ($teammember->create()) {
                            notification::success('Team Member: ' . fullname($USER) . ' (' . $USER->username . ') added to Team: ' . $data->name);
                        } else {
                            notification::error('Could not add user: ' . fullname($USER) . ' (' . $USER->username . ')  to Team: ' . $data->name);
                        }
                    }

                } else {
                    $team = new models\team();
                    $team->from_record($data);
                    if (!$team->update()) {
                        notification::error("Could not update Team $data->name");
                    }

                    notification::success("Team $data->name updated");
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
            $team = new models\team($id);
            $team->set('hidden', models\team::HIDDEN_FALSE);
            if ($team->update()) {
                notification::success('Team ' . $team->get('name') . ' shown');
            } else {
                notification::error('Could not show team ' . $team->get('name'));
            }
        }
        self::do_redirect();
    }

    public static function hide($id) {
        if (!empty($id)) {
            $team = new models\team($id);
            $team->set('hidden', models\team::HIDDEN_TRUE);
            if ($team->update()) {
                notification::success('Team ' . $team->get('name') . ' hidden');
            } else {
                notification::error('Could not hide team ' . $team->get('name'));
            }
        }
        self::do_redirect();
    }

    public static function delete($id) {
        if (!empty($id)) {

            $team = new models\team($id);
            $isadmin = $team->user_isadmin();
            $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

            if (!$isadmin && !$managegoals || !confirm_sesskey()) {
                notification::error('Could not delete Team: ' . $team->get('name') . '.');
            } else {
                if ($team->cascadedelete()) {
                    notification::success('Team: ' . $team->get('name') . ' deleted');
                } else {
                    notification::error('Could not delete Team: ' . $team->get('name') . '.');
                }
            }
        }

        self::do_redirect();
    }

    public static function move($id, $direction) {

        $team = new models\team($id);

        if (!confirm_sesskey()) {
            return false;
        }

        if ($team->move($direction)) {
            notification::success('Team: ' . $team->get('name') . ' moved ' . $direction);
        } else {
            notification::success('Could not move Team: ' . $team->get('name'));
        }
        self::do_redirect();

    }

    public static function renderpage($confirm='') {

        global $USER, $OUTPUT;

        $userprofilefieldsdisplay = explode(',', get_config('block_goals', 'userprofilefieldsdisplay'));
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal($userprofilefieldsdisplay);
        $sql = '
                    SELECT id, shortname, name
                    FROM {user_info_field}
                    WHERE id ' . $insql
        ;
        $userprofilefields = $DB->get_records_sql($sql, $inparams);

        $teams = [];
        $showaddteammember = false;
        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

        // TODO: remove this:
        // $managegoals = false;

        if ($managegoals) {
            $showaddteammember = true;
            $teams = models\team::get_records([],'id');
        } else {
            $myteammembers = models\teammember::get_records(['userid' => $USER->id], 'timemodified', 'DESC');
            foreach($myteammembers as $myteammember) {
                $team = new models\team($myteammember->get('teamid'));
                if (!$team->ishidden()) {
                    if ($myteammember->isadmin()) {
                        $showaddteammember = true;
                    }
                    $teams[] = $team;
                }
            }
        }

        // Print the header.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('teams', 'block_goals'));

        echo $confirm;

        if (empty($teams)) {
            notification::error(get_string('noteamsdefined', 'block_goals'));
        } else {
            echo get_string('icons', 'block_goals');
        }

        foreach ($teams as $teammodel) {
            $team = $teammodel->to_record();
            $table = new \html_table();

            $isadmin = $teammodel->user_isadmin($USER->id);
            if ($managegoals) {
                $isadmin = true;
            }
            $addnew = '';
            if ($isadmin) {
                $addnew = $OUTPUT->spacer() . '<a title="' . get_string('add') . '" href="teams.php?id=0&amp;action=createteammember&amp;teamid=' . $team->id . '">';
                $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';
            }

            $table->head  = [
                get_string('teammember', 'block_goals'),
                get_string('teammemberstatus', 'block_goals')
            ];

            // Add user profile fields to header
            if (!empty($userprofilefieldsdisplay)) {
                foreach ($userprofilefields as $userprofilefield) {
                    $table->head[] = $userprofilefield->name;
                }
                //$customfieldsdata = profile_user_record($userid, false);
            }
            $table->head[] = \get_string('edit') . $addnew;


/*
                $customfields = $authplugin->get_custom_user_profile_fields();
                $customfieldsdata = profile_user_record($userid, false);
                $fields = array_merge($fields, $customfields);
                foreach ($fields as $field) {
                    if ($field === 'description') {
                        // Hard coded hack for description field. See MDL-37704 for details.
                        $formfield = 'description_editor';
                    } else {
                        $formfield = $field;
                    }

                    // Get the original value for the field.
                    if (in_array($field, $customfields)) {
                        $key = str_replace('profile_field_', '', $field);
                        $value = isset($customfieldsdata->{$key}) ? $customfieldsdata->{$key} : '';
                    } else {
                        $value = $user->{$field};
                    }
            }
*/

            $table->align = array('left', 'left');
            $table->width = '95%';
            $table->attributes['class'] = 'generaltable teammember';
            $table->data = array();

            $teammembers = $teammodel->get_teammembers();
            //$teammembers = models\teammember::get_records(['teamid' => $team->id]);
            //if ($teammembers = $DB->get_records('goal_teammembers', array('teamid' => $team->id))) {

            foreach ($teammembers as $teammembermodel) {
                // $teammember = $teammembermodel->read();
                $user = $teammembermodel->get_user();

                // Skip this record if the user isn't confirmed and we're not a team admin!
                if (!$teammembermodel->isconfirmed() && !$isadmin) {
                    continue;
                }

                $userstatus = $teammembermodel->get_userstatus();
                $name = $teammembermodel->get_teammembername();
                $row = [$name, $userstatus];

                // Add user profile fields to header
                if (!empty($userprofilefieldsdisplay)) {
                    if (empty($user)) {
                        foreach($userprofilefields as $userprofilefield) {
                            $row[] = '';
                        }
                    } else {
                        $customfieldsdata = profile_user_record($user->id, false);
                        foreach($userprofilefields as $userprofilefield) {
                            $propertyname = $userprofilefield->shortname;
                            if (property_exists($customfieldsdata, $propertyname)) {
                                $row[] = $customfieldsdata->$propertyname;
                            }
                        }
                    }
                }

                $row[] = self::teammember_icons($teammembermodel, $isadmin);

                $table->data[] = $row;



            }

            echo $OUTPUT->heading(format_string($team->name) . ' ' . self::teammember_team_icons($team, $isadmin));
            $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
            if ($managegoals && $teammodel->ishidden()) {
                // $OUTPUT->notification::warning();
                echo $OUTPUT->notification('This team is currently hidden - (not visible to non-admin users)', '');
            }
            $description = '';
            if (!empty($team->description)) {
                //Description
                $description = $OUTPUT->box(
                    'Description<hr>' .
                    format_text($team->description, $team->descriptionformat),  //,  ['context' => context_block::instance($this->blockinstanceid)]
                    'description-description'
                );
            }

            if (!empty($description)) {
                echo $OUTPUT->container($description);
            }

            if (count($table->data)) {
                echo \html_writer::table($table);
            } else {
                echo $OUTPUT->notification(get_string('noteammembersdefined', 'block_goals'));
            }

        } // End of $teams foreach.

        echo '<hr />';
        echo '<div class="fitlereditor">';

        // Create a new teammember link.
        if ($showaddteammember) {
            echo $OUTPUT->single_button(new \moodle_url('teams.php', array('action' => 'createteammember')), get_string('createteammember', 'block_goals'));
        }

        // Create a new team link.
        echo $OUTPUT->single_button(new \moodle_url('teams.php', array('action' => 'createteam')), get_string('createteam', 'block_goals'));

        echo $OUTPUT->single_button(new \moodle_url('/blocks/goals/view.php'), get_string('dashboard', 'block_goals'));

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
     * Create a string containing the editing icons for the teams
     * @param stdClass $team the team object
     * @return string the icon string
     */
    public static function teammember_team_icons($team, $isadmin) {
        global $DB, $OUTPUT;

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

        $strhide     = get_string('hide');
        $strshow     = get_string('show');
        $strdelete   = get_string('delete');
        $stredit     = get_string('edit');
        $editstr = '';

        if ($isadmin) {
            // Edit.
            $editstr = '<a title="' . $stredit . '" href="teams.php?id=' . $team->id . '&amp;action=editteam">' .
                $OUTPUT->pix_icon('t/edit', $stredit) . '</a> ';

            // Add.
            $addnew = '<a title="' . get_string('add') . '" href="teams.php?id=0&amp;action=createteammember&amp;teamid=' . $team->id . '">';
            $addnew .= $OUTPUT->pix_icon('t/add', get_string('add')) . '</a> ';
            $editstr .= $addnew;

            if ($managegoals) {
                if ($team->hidden) {
                    // Show.
                    $editstr .= '<a title="' . $strshow . '" href="teams.php?id=' . $team->id . '&amp;action=showteam">';
                    $editstr .= $OUTPUT->pix_icon('t/show', $strshow) . '</a> ';
                } else {
                    // Hide.
                    $editstr .= '<a title="' . $strhide . '" href="teams.php?id=' . $team->id . '&amp;action=hideteam">';
                    $editstr .= $OUTPUT->pix_icon('t/hide', $strhide) . '</a> ';
                }

                // Delete.
                $editstr .= '<a title="' . $strdelete . '"';
                $editstr .= ' href="teams.php?id=' . $team->id . '&amp;action=deleteteam&amp;sesskey=' . sesskey() . '">';
                $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';

            } else {

                // Delete.
                $editstr .= '<a title="' . $strdelete . '"';
                $editstr .= ' href="teams.php?id=' . $team->id . '&amp;action=hideteam">';
                $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';
            }

        }

        return $editstr;
    }

    /**
     * Create a string containing the editing icons for the goal teammembers
     * @param stdClass $teammember the teammember object
     * @return string the icon string
     */
    public static function teammember_icons($teammember, $isadmin) {
        global $USER, $OUTPUT;

        $strdelete   = get_string('delete');
        $stredit     = get_string('edit');
        $editstr = '';
        if ($isadmin) {

            if (empty($teammember->get('email')) || $teammember->get('usercreated') == $USER->id) {
                // Edit.
                $editstr = '<a title="'.$stredit.'" href="teams.php?id='.$teammember->get('id').'&amp;action=editteammember">';
                $editstr .= $OUTPUT->pix_icon('t/edit', $stredit) . '</a> ';
            }

            // Delete.
            $editstr .= '<a title="'.$strdelete.'" href="teams.php?id='.$teammember->get('id').'&amp;action=deleteteammember&amp;sesskey='.sesskey().'">';
            $editstr .= $OUTPUT->pix_icon('t/delete', $strdelete) . '</a> ';
        }

        return $editstr;
    }
}