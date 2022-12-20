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
 *
 * @package   block_rcgp_quiz_summary
 * @copyright 2016 onwards Jide Okunoren {@link http://www.rcgp.org.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jide Okunoren <Jide.Okunoren@rcgp.org.uk>
 * @author    David Aylmer <David.Aylmer@rcgp.org.uk>
 *
 *  custom view page for displaying the quiz summary block
 *
 */
require_once(__DIR__ . '/../../config.php');
global $CFG;
// require_once $CFG->dirroot.'/lib/form/editor.php';
// require_once $CFG->dirroot . '/lib/editorlib.php';

require_once $CFG->dirroot . '/blocks/goals/lib.php';


use block_goals\controllers;
use block_goals\models;
use block_goals\forms;
use core\notification;

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $OUTPUT, $USER;
$PAGE->set_context(context_system::instance());
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$PAGE->set_url(new moodle_url('/blocks/goals/view.php'), ['action' => $action]);
enforce_security();

$managegoals = has_capability('block/goals:managegoals', \context_system::instance());

$output = '';

$buttons = '';

switch ($action) {
    case 'updategoal':
        $id = required_param('id', PARAM_INT);
        $action = required_param('action', PARAM_ALPHANUMEXT);
        $progress = required_param('progress', PARAM_INT);
        $description = required_param('description', PARAM_TEXT);

        $goal = new models\goal($id);
        $goal->read();
        $goal->set('progress', $progress);
        $goal->update();

        $goalhistory = new models\goalhistory(0,
            (object)[
                'goalid' => $id,
                'progress' => $progress,
                'description' => $description,
                'descriptionformat' => FORMAT_HTML,
                'usercreated' => $USER->id
            ]
        );
        if ($goalhistory->create()) {

        } else {

        }
        // TODO: Notification

        redirect(new moodle_url('/blocks/goals/view.php'));

    case 'trackgoal':
        $id = required_param('id', PARAM_INT);
        $goal = new models\goal($id);

        $PAGE->set_title(get_string('tracksmartgoal', 'block_goals', $goal->get_abstract()));
        $PAGE->set_heading(get_string('tracksmartgoal', 'block_goals', $goal->get_abstract()));

        if (empty($goal->get('progress'))) {
            $colour = 'bg-warning';
        } else {
            if ($goal->get('progress') == 100) {
                $colour = 'bg-success';
            } else {
                $colour = 'bg-info';
            }
        }

        $output = '<h5>' . $goal->get('goaltext') . '</h5>';
        if ($goal->get('duedate') < time()) {
            $output .= '
            <div class="jumbotron ' . $colour . ' text-light">
              <h1 class="display-4">Congratulations!</h1>
              <p class="lead">The due date for this SMART goal is now in the past. Congratulations for setting a SMART goal.</p>
              <hr class="my-4">
              <p>You can still track your SMART goal below</p>
            </div>';
        }

        $output .= '<div class="col-md-4">';
        $type = $goal->get('type');
        $teamhtml = '';
        if ($type == models\goal::TYPE_TEAM) {
            $teamhtml = '<dt>' . get_string('team', 'block_goals') . '</dt><dd>' . $goal->get_team()->get('name') . '</dd>';
            $teamhtml .= '<dt>' . get_string('teammembers', 'block_goals') . '</dt><dd>' . $goal->get_team()->get_teammembernames() . '</dd>';
        }
        $output .= '<dl>';
        $output .= '<dt>' . get_string('progress', 'block_goals') . '</dt><dd>' . controllers\goal::progress($goal->get('progress')) . '</dd>';
        $output .= '<dt>' . get_string('goal', 'block_goals') . '</dt><dd>' . $goal->get('goaltext') . '</dd>';
        $output .= '<dt>' . get_string('additionaldetails', 'block_goals') . '</dt><dd>' . $goal->get('description') . '</dd>';
        $output .= $teamhtml;
        $output .= '<dt>' . get_string('duedate', 'block_goals') . '</dt><dd>' . $goal->get_formattedduedate() . '</dd>';
        $output .= '<dt>' . get_string('relevantfilters', 'block_goals') . '</dt><dd>' . $goal->get_filternames() . '</dd>';

        $output .= '</dl>';


        // $editor = \editors_get_preferred_editor();
        // $editor->use_editor("description", []);

        $output .= '
            <hr>
            
            <button type="button" class="btn btn-primary btn active updategoal">' . get_string('update', 'block_goals') . '</button>
            <a href="/blocks/goals/view.php" class="btn btn-secondary" role="button" aria-pressed="true">' . get_string('backtodashboard', 'block_goals') . '</a>
            
            <form method="POST">
            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">' . get_string('updategoal', 'block_goals') . '</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <h5>' . get_string('updatepreamble', 'block_goals') . '</h5><br>
                            <div class="form-group">
                                <input type="hidden" name="id" value="' . $goal->get('id') . '">
                                <input type="hidden" name="action" value="updategoal">
                                <div class="input-group mb-3">    
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . get_string('progress', 'block_goals') . '</span>
                                    </div>             
                                    <input type="range" id="progress" name="progress" min="0" max="100" value="' . $goal->get('progress') . '" class="mx-2" oninput="this.form.progressnumber.value=this.value" />
                                    <input type="number" id="progressnumber" name="progressnumber" min="0" max="100" value="' . $goal->get('progress') . '" oninput="if(this.value > 100) this.value = 100; this.form.progress.value=this.value" />
                                    <div class="input-group-append">
                                        <span class="input-group-text">' . get_string('percentcomplete', 'block_goals') . '</span>
                                    </div>
                                </div>
                                
                                
                                  <!--  rows="8", cols="80"-->
                                <div class="col-xs-12"> 
                                    <div class="form-group">
                                      <label>Notes</label>
                                      <textarea id="description" name="description" class="form-control border border-primary"></textarea>
                                    </div>
                                </div>
                                
                            </div>                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary modalconfirm" data-dismiss="modal">' . get_string('close', 'block_goals') . '</button>
                            <button type="submit" class="btn btn-primary modalconfirm">' . get_string('savechanges', 'block_goals') . '</button>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        ';

        $progressdates = [];
        $progressamounts = [];


        $progressdates[] = $goal->get_formattedtimecreated();
        $progressamounts[] = 0;

        $goalhistorieshtml = '';
        if ($goalhistories = models\goalhistory::get_records(['goalid' => $id], 'timecreated', 'ASC')) {
            foreach ($goalhistories as $goalhistory) {
                $date = $goalhistory->get_formattedtimecreated();
                $description = $goalhistory->get('description'); // .  controllers\goal::progress($goalhistory->get('progress'));

                // Prepend to reverse order for display.
                $goalhistorieshtml = controllers\goal::progress($goalhistory->get('progress')) . '<dt>' . $date . ' - ' . fullname($goalhistory->get_user()) . ' - ' . $goalhistory->get('progress') . '%' . '</dt><dd>' . $description . '</dd><hr>' . $goalhistorieshtml;

                $progressdates[] = $goalhistory->get_formattedtimecreated();;
                $progressamounts[] = $goalhistory->get('progress');
            }
            $chart = new \core\chart_line();
            $chart->set_title($goal->get('goaltext'));
            $chart->set_smooth(true);
            $chart->add_series(new \core\chart_series('Progress', $progressamounts));
            $chart->set_labels($progressdates);

            $goalhistorieshtml = '<h5>History</h5>' . $goalhistorieshtml;
            $output .= '<hr>' . $goalhistorieshtml . $OUTPUT->render($chart) . '</div>';
        } else {

        }

        break;
    default:
        $PAGE->set_title(get_string('goals', 'block_goals'));
        $PAGE->set_heading(get_string('goals', 'block_goals'));

        global $USER;

        $output = '<h4>' . get_string('mysmartgoals', 'block_goals') . '</h4>';
        $individualgoals = models\goal::get_records_select(
            'userid = :userid AND type = :type AND duedate >= :duedate AND hidden = :hidden', [
                'userid' => $USER->id,
                'type' => models\goal::TYPE_INDIVIDUAL,
                'duedate' => time(),
                'hidden' => models\goal::HIDDEN_FALSE
        ], 'duedate ASC');

        $individualgoals += models\goal::get_records_select(
            'userid = :userid AND type = :type AND duedate < :duedate  AND hidden = :hidden', [
            'userid' => $USER->id,
            'type' => models\goal::TYPE_INDIVIDUAL,
            'duedate' => time(),
            'hidden' => models\goal::HIDDEN_FALSE
        ], 'duedate DESC');



        $output .= controllers\goal::get_goaltable('individualgoals', $individualgoals, true, false);

        $output .= '<hr><h4>' . get_string('teamgoals', 'block_goals') . '</h4>';

        $myteams = models\teammember::get_records(['userid' => $USER->id], 'timemodified', 'DESC');
        foreach($myteams as $myteam) {
            $team = new models\team($myteam->get('teamid'));

            $teamgoals = models\goal::get_records_select(
                'teamid = :teamid AND type = :type AND duedate >= :duedate AND hidden = :hidden', [
                'teamid' => $team->get('id'),
                'type' => models\goal::TYPE_TEAM,
                'duedate' => time(),
                'hidden' => models\goal::HIDDEN_FALSE
            ], 'duedate ASC');
            $teamgoals += models\goal::get_records_select(
                'teamid = :teamid AND type = :type AND duedate < :duedate AND hidden = :hidden', [
                'teamid' => $team->get('id'),
                'type' => models\goal::TYPE_TEAM,
                'duedate' => time(),
                'hidden' => models\goal::HIDDEN_FALSE
            ], 'duedate DESC');

            $showthisteam = true;
            if ($team->ishidden()) {
                $showthisteam = false;
                if ($team->user_isadmin($USER->id)) {
                    $showthisteam = true;
                }
                if ($managegoals) {
                    $showthisteam = true;
                }
            }

            if ($showthisteam) {
                $output .= '<h5><b>' . $team->get('name') . '</b></h5>';
                $output .= format_text($team->get('description'), $team->get('descriptionformat'));
                $output .= controllers\goal::get_goaltable('team' . $team->get('id'), $teamgoals, true, false);
            }
        }
        $buttons = '<a href="/blocks/goals/addgoal.php" class="btn btn-primary active" role="button" aria-pressed="true">' . get_string('addnewgoal', 'block_goals') . '</a> ';
        $buttons .= '<a href="/blocks/goals/teams.php" class="btn btn-primary active" role="button" aria-pressed="true">' . get_string('managemyteams', 'block_goals') . '</a> ';
        $buttons .= '<a href="/blocks/goals/preferences.php" class="btn btn-primary active" role="button" aria-pressed="true">' . get_string('preferences', 'block_goals') . '</a>';
        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());
        if ($managegoals) {
            $buttons .= ' <a href="/blocks/goals/admin/index.php" class="btn btn-primary active" role="button" aria-pressed="true">' . get_string('admin', 'block_goals') . '</a>';
        }
}




$PAGE->requires->css(new moodle_url('/blocks/goals/fontawesome-pro/css/all.css'));
$PAGE->requires->css(new moodle_url("https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css"));
$PAGE->requires->css(new moodle_url("https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"));
$PAGE->requires->css(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css"));
$PAGE->requires->css(new moodle_url("https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css"));
$PAGE->requires->css(new moodle_url('/blocks/goals/styles.css'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"), true);
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"), true);
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"), true);
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"), true);
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"), true);
//$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/buttons/2.2.2/js/buttons.colVis.min.js"), true);


$PAGE->requires->js(new moodle_url('/blocks/goals/js/dashboard.js'));
// $PAGE->requires->get_end_code();
echo $OUTPUT->header();
echo get_string('dashboardheading', 'block_goals');
echo $buttons . '<br><br>';
echo $output;
echo $buttons;
echo $OUTPUT->footer();