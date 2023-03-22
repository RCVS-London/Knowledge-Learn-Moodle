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
 * Not possible to combine templating engine with moodle quickforms (mforms) engine, so having to do things the 'other way'.
 *
 * @package   block_goals
 * @copyright 2022 David Aylmer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once __DIR__ . '/../../config.php';
global $CFG, $PAGE, $OUTPUT;
require_once $CFG->dirroot . '/blocks/goals/lib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

use block_goals\models;
use block_goals\controllers;
use core\notification;

$PAGE->navbar->add(get_string('goals', 'block_goals'), new moodle_url('/blocks/goals/view.php'));
$PAGE->navbar->add('Add goals', new moodle_url('/blocks/goals/addgoals.php'));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/addgoal.php'));
$PAGE->set_title('Create new goal');
$PAGE->set_heading(get_string('goals', 'block_goals'));

enforce_security();

// Include CSS.
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/goals/fontawesome-pro/css/all.css'));
$PAGE->requires->css(new moodle_url("https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css"));
$PAGE->requires->css(new moodle_url("https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js")); // TODO: inhead true
$PAGE->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/goals/styles.css'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

// $editor = \editors_get_preferred_editor();
// $editor->use_editor("description", []);

if ($action == 'creategoal') {

    require_sesskey();

    global $USER;
    $data = new stdClass();
    $data->userid = $USER->id;
    switch (optional_param('goaltype', 'individual', PARAM_TEXT)) {
        case 'individual':
            $data->type = models\goal::TYPE_INDIVIDUAL;
            $data->teamid = 0;
            break;
        case 'team':
            $data->type = models\goal::TYPE_TEAM;
            $data->teamid = required_param('teamid',PARAM_TEXT);
            break;
        default:
            notification::error('Error here');
    }
    $data->verbid = required_param('verb',PARAM_INT);
    $data->amount = required_param('amount',PARAM_INT);
    $data->percentageflag = models\goal::PERCENTAGE_FALSE;
    if (optional_param('percentageflag','', PARAM_TEXT) == 'on') {
        $data->percentageflag = models\goal::PERCENTAGE_TRUE;
    }
    $data->objective = required_param('objective',PARAM_TEXT);
    $duedate = required_param('duedate',PARAM_TEXT);
    if (empty($duedate)) {
        $duedate = 'today +1 month';
    }
    $data->duedate = strtotime($duedate);
    //$data->template = models\goal::NOT_TEMPLATE;
    $data->goaltext = required_param('goaltext',PARAM_TEXT);
    $data->description = optional_param('description', '', PARAM_TEXT);
    $data->descriptionformat = FORMAT_HTML;
    $data->progress = 0;

    $goal = new models\goal(0, $data);
    if ($goal = $goal->create()) {

        // create goalfilters
        foreach ($_POST as $field => $value) {
            if (substr($field, 0, 7) == 'filter_') {

                $filterid = strtok($field, 'filter_');
                $goalfilter = new models\goalfilter(0);
                $goalfilter->set('goalid', $goal->get('id'));
                $goalfilter->set('filterid', $filterid);
                $goalfilter->set('booleanvalue', $value);
                if (!$goalfilter->create()) {
                    notification::error("Could not create SMART Goal filter $data->name");
                }
            }
        }

        $userreminderfrequency = get_user_preferences('goalreminderfrequencysetting', 0);
        $reminderfrequencies = get_reminderfrequencies();

        $reminderfrequenytext = 'Never';
        if (array_key_exists($userreminderfrequency, $reminderfrequencies)) {
            $reminderfrequenytext = $reminderfrequencies[$userreminderfrequency];
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading('Goal Added!');

        $output = '<div class="container-fluid">
            <div class="mb-5 p-4 shadow-sm">
                <h3>Create a SMART Goal</h3>
                <div class="jumbotron">
                    <h1 class="display-4">SMART Goal Successfully Created</h1>
                    <p class="lead">Congratulations on setting up a new goal.</p>
                    <hr class="my-4">
                    <p>You are currently being reminded (' . $reminderfrequenytext . ') about your SMART goals? You can change your settings <a href="/blocks/goals/preferences.php">here</a>.</p>
                    <p>Click here to return to your SMART goal dashboard.</p>
                    <p class="lead">
                        <a class="btn btn-primary btn-lg" href="/blocks/goals/view.php" role="button">Return to dashboard</a>
                    </p>
                </div>
            </div>
        </div>';
        echo $output;
        echo $OUTPUT->footer();
        die();
    } else {
        // notification::error('ERror here');
    }
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addnewgoal', 'block_goals'));

global $DB, $USER;
$query = '
    SELECT gtm.id, gt.id as teamid, gt.name
    FROM {goal_team} gt
    INNER JOIN {goal_teammembers} gtm ON gtm.teamid = gt.id
    WHERE gtm.userid = :userid
    ';
$myteams = $DB->get_records_sql($query, ['userid' => $USER->id]); // ['userid' => $USER->id] ['userid' => 5]
$teamshtml = '';
if (!empty($myteams)) {
    $teamshtml = '
    <div class="form-group">
    <label for="teamid">Select team:</label>
    <select id="teamid" name="teamid" class="form-control">';

    foreach ($myteams as $team) {
        $teamshtml .= '<option value="' . $team->teamid . '">' . $team->name . '</option>';
    }
    $teamshtml .= '</select></div>';
}

/*
$query = '
    SELECT gt.id, gt.name
    FROM {goal_team} gt
    INNER JOIN {goal_teammembers} gtm ON gtm.teamid = gt.id
    WHERE gtm.userid = :userid
    ';
$myteams = $DB->get_records_sql($query, ['userid' => 5]); // $USER->id]);
*/
//$verbs = $DB->get_records_menu('goal_verbs', null, 'sortorder', 'id, verb');
$verbs = $DB->get_records('goal_verbs', ['hidden' => models\verb::HIDDEN_FALSE], 'sortorder', 'id, verb');
$verbshtml = '';
if (!empty($verbs)) {
    $verbshtml = '
    <div class="input-group-prepend">
    <label id="committo" class="input-group-text" for="verb">I commit to </label>
</div>
    <select id="verb-select" name="verb-select" class="form-control">
    <option value="0">Please select from list options</option>
    ';

    $default = '';
    foreach ($verbs as $verb) {
        if (empty($default)) {
            $default = $verb->id;
        }
        $verbshtml .= '<option value="' . $verb->id . '">' . $verb->verb . '</option>';
    }
    $verbshtml .= '</select>
    <input type="hidden" id="verb" name="verb" value="' . $default . '"/>';
}

/*
<div class="input-group-prepend">
    <label id="committo" class="input-group-text" for="verb">I commit to </label>
</div>
<select class="form-control custom-select" id="verb" name="verb">
    <option selected>working with</option>
    <option value="talking with">talking with</option>
    <option value="inviting">inviting</option>
    <option value="arranging">arranging</option>
</select>
*/




$people = models\category::get_record(['name' => 'People']);
$data = models\category::get_record(['name' => 'Data']);
$animals = models\category::get_record(['name' => 'Animals, Herds and Flocks']);
$who = models\category::get_record(['name' => 'Who']);
$species = models\category::get_record(['name' => 'Species']);

?>


    <div class="container-fluid">
        <div class="mb-5 p-4 shadow-sm">
            <div id="stepper2" class="bs-stepper">
                <!-- vertical -->
                <div class="bs-stepper-header" role="tablist">
                    <div class="step" data-target="#test-nl-1">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger1" aria-controls="test-nl-1">
                            <span class="bs-stepper-circle">
                                <span class="fas fa-video" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">SMART Start</span>
                        </button>
                    </div>
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#test-nl-2">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger2" aria-controls="test-nl-2">
                            <span class="bs-stepper-circle">
                            <span class="fas fa-tasks" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">What? (Category)</span>
                        </button>
                    </div>
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#test-nl-3">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger3" aria-controls="test-nl-3">
                            <span class="bs-stepper-circle">
                            <span class="fas fa-user-md" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">Who? (People)</span>
                        </button>
                    </div>
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#test-nl-4">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger4" aria-controls="test-nl-4">
                            <span class="bs-stepper-circle">
                            <span class="fas fa-info" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">How? (Detail)</span>
                        </button>
                    </div>
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#test-nl-5">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger5" aria-controls="test-nl-5">
                            <span class="bs-stepper-circle">
                            <span class="fa fa-paw" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">Which? (Species)</span>
                        </button>
                    </div>
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#test-nl-6">
                        <button type="button" class="step-trigger" role="tab" id="stepper2trigger5" aria-controls="test-nl-6">
                            <span class="bs-stepper-circle">
                            <span class="fas fa-calendar-day" aria-hidden="true"></span>
                            </span>
                            <span class="bs-stepper-label">When? (Date)</span>
                        </button>
                    </div>
                </div>
                <div class="bs-stepper-content">
                    <form method="POST" action="addgoal.php" >  <!--  onSubmit="return false" -->
                        <input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
                        <input type="hidden" name="action" value="creategoal" />
<!--
                        $data->verbid = required_param('verb',PARAM_INT);
                        $data->amount = required_param('amount',PARAM_INT);
                        $data->percentageflag = required_param('percentageflag',PARAM_INT);
                        $data->objective = required_param('objective',PARAM_TEXT);
                        $data->duedate = required_param('duedate',PARAM_INT);
                        $data->template = models\goal::NOT_TEMPLATE;
                        $data->goaltext = required_param('goaltext',PARAM_INT);
                        $data->progress = 0;
-->
                        <div id="test-nl-1" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger1">


                            <input type="hidden" id="goaltype" name="goaltype" value="individual">
                            <div class="d-flex align-items-stretch">
                                <div class="d-inline p-2 m-2  bg-success rounded bg-opacity-25">
                                    <div class='d-block mb-2'>
                                        <span class = 'pb-4'><?php echo get_string('createindividualgoalpreamble', 'block_goals'); ?>
                                    </div>
                                    <div class='d-block text-center'>
                                        <button id="addgoal1" type="button" class="btn btn-success btn-lg active  bottom-0"  role="button" aria-pressed="true">
                                            <?php echo get_string('createindividualgoal', 'block_goals'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-inline p-2 bg-success m-2 rounded bg-opacity-25">
                                    <div class='d-block mb-2'>
                                        <span class = 'pb-4'><?php echo get_string('createteamgoalpreamble', 'block_goals'); ?>
                                    </div>
                                    <div class='d-block text-center'>
                                    <?php
                                        if (!empty($teamshtml)) {
                                            echo $teamshtml;
                                            echo '<button id="addgoal2" type="button" class="btn btn-success btn-lg active align-bottom mb-2">';
                                            echo get_string('createteamgoal', 'block_goals');
                                            echo '</button><br>';
    
                                        }
                                    ?>    
                                        <a href="/blocks/goals/teams.php" class="btn btn-success active align-bottom" role="button" aria-pressed="true">
                                        <?php echo get_string('managemyteams', 'block_goals') . '</a>'; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="container-fluid">
                                <?php echo get_config('block_goals', 'introcontent'); ?>
                            </div>

                            <hr>

                        </div>
                        <div id="test-nl-2" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger2">
                            <?php echo get_string('whatpreamble', 'block_goals'); ?>
                            <div id="accordion">
                                <div class="card">
                                    <div class="card-header" id="headingOne">
                                        <h5 class="mb-0">
                                            <button type="button" class="btn btn-link p-0" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                <i class="fa" aria-hidden="true"></i>
                                                <?php echo $people->get('name'); ?>
                                            </button>
                                        </h5>
                                        <p class="card-text"><?php echo $people->get('description'); ?></p>
                                    </div>
                                    <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                        <div class="card-body">
                                            <p class="card-text"><?php echo $people->get('example'); ?></p>
                                            <fieldset class="form-group People">
                                                <?php echo controllers\category::get_filtershtml($people); ?>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingTwo">
                                        <h5 class="mb-0">
                                            <button type="button" class="btn btn-link p-0 collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                <i class="fa" aria-hidden="true"></i>
                                                <?php echo $data->get('name'); ?>
                                            </button>
                                        </h5>
                                        <p class="card-text"><?php echo $data->get('description'); ?></p>
                                    </div>
                                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                                        <div class="card-body">
                                            <p class="card-text"><?php echo $data->get('example'); ?></p>
                                            <fieldset class="form-group Data">
                                                <?php echo controllers\category::get_filtershtml($data); ?>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingThree">
                                        <h5 class="mb-0">
                                            <button type="button" class="btn btn-link p-0 collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                <i class="fa" aria-hidden="true"></i>
                                                <?php echo $animals->get('name'); ?>
                                            </button>
                                        </h5>
                                        <p class="card-text"><?php echo $animals->get('description'); ?></p>
                                    </div>
                                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                                        <div class="card-body">
                                            <p class="card-text"><?php echo $animals->get('example'); ?></p>
                                            <fieldset class="form-group Animals">
                                                <?php echo controllers\category::get_filtershtml($animals); ?>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="stepper2.next()"><?php echo get_string('skip', 'block_goals'); ?></button>
                            <button type="button" class="btn btn-primary float-right" onclick="stepper2.next()"><?php echo get_string('next', 'block_goals'); ?></button>
                        </div>
                        <div id="test-nl-3" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger3">
                            <h5><?php echo $who->get('description'); ?></h5>
                            <p><?php echo $who->get('example'); ?></p>
                            <fieldset class="form-group Who">
                                <?php echo controllers\category::get_filtershtml($who); ?>
                            </fieldset>
                            <button type="button" class="btn btn-primary" onclick="stepper2.next()"><?php echo get_string('skip', 'block_goals'); ?></button>
                            <button type="button" class="btn btn-primary float-right" onclick="stepper2.next()"><?php echo get_string('next', 'block_goals'); ?></button>
                        </div>
                        <div id="test-nl-4" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger4">

                            <div id="goalstablewrapper" class="container-fluid">
                            <?php
                                echo get_string('goalspreamble', 'block_goals');

                                // $templategoals = models\goal::get_records(['template' => models\goal::IS_TEMPLATE]);
                                $templategoals = models\goal::get_records(['type' => models\goal::TYPE_TEMPLATE]);
                                echo controllers\goal::get_goaltable('example', $templategoals, false, true);
                            ?>
                            </div>
                            <div class="container-fluid">
                                <button id="goalstablereveal" type="button" class="btn btn-success">Change suggested SMART goal <i class="fas fa-edit"></i></button>
                            </div>

                            <hr>
                            <br><br>
                            <div class="container-fluid">
                                <button id="addgoal" type="button" class="btn btn-success"><?php echo get_string('createnewgoal', 'block_goals'); ?>&nbsp;<i class="fas fa-plus"></i></button>
                            </div>
                            <br>
                            <div class="container-fluid">
                                <div class="form-group">
                                    <!-- TODO: Fix this -->
                                    <textarea id="goal" name="goaltext" rows="4" class="form-control border-0" readonly></textarea>
                                </div>
                            </div>

                            <div class="container-fluid">
                                <h4><?php echo get_string('additionaldetails', 'block_goals'); ?></h4>
                                <?php echo get_string('additionaldetailsdescription', 'block_goals'); ?>
                                <div class="form-group">
                                <textarea id="description" name="description" rows="5" class="form-control border border-primary"></textarea><br>
                                </div>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel"><?php echo get_string('createnewgoal', 'block_goals'); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo get_string('goalverbpreamble', 'block_goals'); ?>
                                            <hr />
                                        </div>
                                        <div class="modal-body">
                                            <div class="input-group mb-3">

                                                <?php echo $verbshtml; ?>
                                                <!--
                                                <div class="input-group-prepend">
                                                    <label id="committo" class="input-group-text" for="verb">I commit to </label>
                                                </div>
                                                <select class="form-control custom-select" id="verb" name="verb">
                                                    <option selected>working with</option>
                                                    <option value="talking with">talking with</option>
                                                    <option value="inviting">inviting</option>
                                                    <option value="arranging">arranging</option>
                                                </select>
                                                -->


                                                <input class="form-control input-lg" id="amount" name="amount" type="amount" value="0">

                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">
                                                        <input type="checkbox" id="percentageflag" name="percentageflag" />
                                                        &nbsp; percentage?
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="input-group mb-3">
                                                <input class="form-control input-lg" id="objective" name="objective" type="text" placeholder="What is your objective? Consider your target audience and what you aim to achieve.">
                                                <!--
                                                <select class="custom-select" id="verb">
                                                    <option selected>Choose...</option>
                                                    <option value="1">farmers about their colostrum management</option>
                                                    <option value="2">farmer(s) to create a veterinary health plan for their farm</option>
                                                    <option value="3">farmer(s) to reduce their use of HPCIAs</option>
                                                    <option value="4">farmer(s) to reduce routine treatments to neonatal youngstock</option>
                                                    <option value="5">farmer(s) to improve cleaning and disinfection protocols on the farm</option>
                                                    <option value="6">meeting(s) for farmer(s) to discuss how to mitigate the risks when buying animals</option>
                                                    <option value="7">farmer(s) to implement routine health testing </option>
                                                </select>
                                                -->
                                                <div class="input-group-append">
                                                    <span id="bydate" class="input-group-text" id="basic-addon2">by [date].</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info" role="alert">
                                                <h5 id="sentence" class="alert-link"></h5>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo get_string('close', 'block_goals'); ?></button>
                                            <button type="submit" class="btn btn-primary modalconfirm" data-dismiss="modal"><?php echo get_string('savechanges', 'block_goals'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!--
                            <h5><em>How</em> will your SMART goal be measured?</h5>
                            <div class="mb-3">
                                <label for="additionaldetails" class="form-label">Additional Details</label>
                                <textarea class="form-control" id="additionaldetails" rows="3"></textarea>
                            </div>
                            -->

                            <!-- <button type="button" class="btn btn-primary" onclick="stepper2.next()">Skip</button> -->
                            <button type="button" class="btn btn-primary float-right" onclick="stepper2.next()"><?php echo get_string('next', 'block_goals'); ?></button>
                        </div>
                        <div id="test-nl-5" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger5">
                            <h5><?php echo $species->get('description'); ?></h5>
                            <div class="form-group">
                                <p class="card-text"><?php echo $species->get('example'); ?></p>
                                <fieldset class="form-group Species">
                                    <?php echo controllers\category::get_filtershtml($species); ?>
                                </fieldset>
                            </div>
                            <!-- <button type="button" class="btn btn-primary" onclick="stepper2.next()">Skip</button> -->
                            <button type="button" class="btn btn-primary float-right" onclick="stepper2.next()"><?php echo get_string('next', 'block_goals'); ?></button>
                        </div>
                        <div id="test-nl-6" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger6">
                            <?php echo get_string('whenpreamble', 'block_goals'); ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="duedate"><?php echo get_string('duedate', 'block_goals'); ?>:</label>
                                    <input type="date" id="duedate" name="duedate" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                            <hr>
                            <!--
                            <div class="form-row">
                                <fieldset class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                                        <label class="form-check-label" for="flexRadioDefault1">
                                        Weekly - (Wednesday each week)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" checked>
                                        <label class="form-check-label" for="flexRadioDefault2">
                                        Monthly - (First Wednesday of the month)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault3">
                                        <label class="form-check-label" for="flexRadioDefault3">
                                        No reminders
                                        </label>
                                        </div
                                </fieldset>
                                </div>
                                <button class="btn btn-primary" onclick="stepper2.next()">Skip</button>
                            <button class="btn btn-primary float-right" onclick="stepper2.next()">Next</button>
                            </div>
                            -->
                            <div class="container-fluid">
                                <h5 id="goal"></h5>
                                <button id="submitgoal" type="button" class="btn btn-success"><?php echo get_string('submitgoal', 'block_goals'); ?></button>
                            </div>


                            <!-- Modal -->
                            <div class="modal fade" id="modalsummary" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel"><?php echo get_string('goalsummary', 'block_goals'); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <dl>
                                                <dt><?php echo get_string('goaltype', 'block_goals'); ?></dt><dd id="summarygoaltype">None</dd>
                                                <dt><?php echo get_string('goalfilters', 'block_goals'); ?></dt><dd id="summarygoalcategories">None</dd>
                                                <dt><?php echo get_string('goalpeople', 'block_goals'); ?></dt><dd id="summarygoalpeople">None</dd>
                                                <dt><?php echo get_string('goal', 'block_goals'); ?></dt><dd id="summarygoal">None</dd>
                                                <dt><?php echo get_string('goaldetails', 'block_goals'); ?></dt><dd id="summarygoaldetails">None</dd>
                                                <dt><?php echo get_string('goalspecies', 'block_goals'); ?></dt><dd id="summarygoalspecies">None</dd>
                                                <dt><?php echo get_string('goalduedate', 'block_goals'); ?></dt><dd id="summarygoalduedate">None</dd>
                                            </dl>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo get_string('close', 'block_goals'); ?></button>
                                            <button type="submit" class="btn btn-primary"><?php echo get_string('savechanges', 'block_goals'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bs-stepper/dist/js/bs-stepper.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>


    <!--
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
        -->
    <script type="text/javascript">
        var stepper2;

        function updateGoal() {
            var percentagestring = ($("#percentageflag").is(':checked')) ? '%' : '';
            $("#sentence").text($("#committo").text() + ' ' + $("#verb-select option:selected").text() + ' ' + $("#amount").val() + percentagestring + ' ' + $("#objective").val() + ' ' + $("#bydate").text() );
            $("#goal").text($("#sentence").text());
        }

        $(document).ready(function() {

            $("#goalstablereveal").hide();

            
            var table = $('#example').DataTable({
                    "columnDefs": [
                        {"visible": true, "searchable": false, "targets": 0, "width": "10%"}, // Select
                        {"visible": true, "searchable": true, "targets": 1}, // Goal
                        {"visible": false, "searchable": false, "targets": 2}, // Progress
                        {"visible": false, "searchable": false, "targets": 3}, // Type
                        {"visible": false, "searchable": true, "targets": 4}, // Verb
                        {"visible": false, "searchable": false, "targets": 5}, // Percentage
                        {"visible": false, "searchable": true, "targets": 6}, // Noun
                        {"visible": false, "searchable": true, "targets": 7}, // Filters
                        {"visible": false, "searchable": false, "targets": 8}, // Recent Update
                        {"visible": false, "searchable": false, "targets": 9}, // Due Date
                    ],
                    "columns": [
                        {"width": "10%"},
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    ]
                }
            );

            $("#addgoal").on('click', function() {
                $("#verb-select").prop("disabled", false);
                $("#objective").prop("readonly", false);

                $("#exampleModal").modal('show');
            });


            $("#addgoal1").on('click', function() {
                // table.column(2).search('individual').draw();
                $('#type').val("0");
            });
            $("#addgoal2").on('click', function() {
                // table.column(2).search('team').draw();
                $('#type').val("1");
            });

            var checkboxes = 'fieldset.People input:checkbox, fieldset.Data input:checkbox, fieldset.Animals input:checkbox, fieldset.Who input:checkbox, fieldset.Species input:checkbox';

            $(checkboxes).on('keypress keyup blur change',function() {

                

                var search = '';
                $(checkboxes).each(function(index) {
                    var checked = $(this).prop('checked');
                    if (checked) {
                        search += $("label[for='" + $(this).attr("name") + "']").text().trim() + ' ';
                        
                    }
                })

                
                table.column(7).search(search).draw();
            });

            $('#example').on('click','.add-goal', function() {
                //var rowIndex =  $(this).find('td').first().text()

                
                var td = $(this).find('td').first();
                var tr = $(this).closest('tr');
                var cell = table.cell(td);
                var rowindex = table.row(tr).index();
                var goal = table.cell(rowindex, 1).data();
                var type = table.cell(rowindex, 3).data();
                var verb = table.cell(rowindex, 4).data();
                var percentagestring = table.cell(rowindex, 5).data();
                var noun = table.cell(rowindex, 6).data();
                var categories = table.cell(rowindex, 7).data();

                $("#sentence").text(goal.trim());

                //if (type.trim() == 'individual') {
                //    $("#committo").text("I commit to");
                //} else {
                //    $("#committo").text("We commit to");
                //}

                //$("#verb").val(verb.trim());
                //$("#verb").text(verb.trim());

                
                $('#verb-select option:contains("' + verb.trim() + '")').prop('selected', true);


                //$("#verb option:selected").text();

                if (percentagestring.trim() == 'No') {
                    $('#percentageflag').prop('checked', false);
                } else {
                    $('#percentageflag').prop('checked', true);
                }

                $("#objective").val(noun.trim());

                updateGoal();

                $("#verb-select").prop("disabled", true);
                $("#objective").prop("readonly", true);

                $("#exampleModal").modal('show');

            });

            $("#submitgoal").on('click', function() {

                

                var goaltype = $("#goaltype").val();
                goaltype = goaltype[0].toUpperCase() + goaltype.substring(1)
                $("#summarygoaltype").text(goaltype);

                var peoplechecks = $("fieldset.People input:checkbox:checked");
                var datachecks = $("fieldset.Data input:checkbox:checked");
                var animalschecks = $("fieldset.Animals input:checkbox:checked");

                // TODO: remove comments
                // var animalschecks = $("input:checkbox[name^=animals]:checked");
                // becomes:
                // var animalschecks = $("fieldset.Animals input:checkbox:checked");

                var peoplecategories = '';
                var datacategories = '';
                var animalscategories = '';

                peoplechecks.each(function() {
                    
                    peoplecategories = peoplecategories + $("label[for='" + $(this).attr("name") + "']").text() + ', ';
                });
                datachecks.each(function() {
                    datacategories = datacategories + $("label[for='" + $(this).attr("name") + "']").text() + ', ';
                });
                animalschecks.each(function() {
                    animalscategories = animalscategories + $("label[for='" + $(this).attr("name") + "']").text() + ', ';
                });

                var categories = '';
                if (peoplecategories.length > 0) {
                    categories += '<em>People:</em> ' + peoplecategories;
                }
                if (datacategories.length > 0) {
                    categories += '<em>Data:</em> ' + datacategories;
                }
                if (animalscategories.length > 0) {
                    categories += '<em>Animals:</em> ' + animalscategories;
                }
                $("#summarygoalcategories").html(categories);

                var whochecks = $("fieldset.Who input:checkbox:checked");
                var who = '';
                whochecks.each(function() {
                    
                    who = who + $("label[for='" + $(this).attr("name") + "']").text() + ', ';
                });
                $("#summarygoalpeople").html(who);

                
                $("#summarygoal").text($("#sentence").text());
                $("#summarygoaldetails").html($("#description").val());

                var specieschecks = $("fieldset.Species input:checkbox:checked");
                var species = '';
                specieschecks.each(function() {
                    
                    species = species + $("label[for='" + $(this).attr("name") + "']").text() + ', ';
                });
                $("#summarygoalspecies").html(species);

                var date = ''
                var d = new Date($("#duedate").val());
                if (!isNaN(d.valueOf())) {
                    date = d.toLocaleDateString();
                }
                $("#summarygoalduedate").text(date);

                
                $("#modalsummary").modal('show');

            });

            $("#addgoal1").on('click', function() {
                $("#goaltype").val('individual');
                var pronoun = $("#goaltype").val() == 'individual' ? 'I' : 'We';
                $("#committo").text(pronoun + ' commit to');
                updateGoal();
                stepper2.next();
            });

            $("#addgoal2").on('click', function() {
                $("#goaltype").val('team');
                var pronoun = $("#goaltype").val() == 'individual' ? 'I' : 'We';
                $("#committo").text(pronoun + ' commit to');
                updateGoal();
                stepper2.next();
            });

            $("#duedate").on('keypress keyup blur change', function() {
                var date = '[date]';

                
                var d = new Date($("#duedate").val());
                if (!isNaN(d.valueOf())) {
                    date = d.toLocaleDateString();
                    // date = new Date(Date.parse(new Date())).format("MM/dd/yyyy");
                }
                $("#bydate").text('by ' + date + '.');
                updateGoal();
            });

            $(".modalconfirm").on('click', function() {
                $("#addgoal").html('Edit SMART goal <i class="fas fa-edit"></i>');
                $("#goal").text($("#sentence").text());

                $("#goalstablewrapper").hide();
                $("#goalstablereveal").show();

            });

            $("#goalstablereveal").on('click', function() {
                
                $("#goalstablewrapper").show();
                table.draw();
                $("#goalstablereveal").hide();
            });

            $("#verb-select").on('keypress keyup blur change', function() {
                var id = $(this).children(":selected").attr("id");
                $("#verb").val(id);
                updateGoal();
            });

            $("#amount").on('keypress keyup blur change',function() {
                updateGoal();
            });

            $("#objective").on('keypress keyup blur change',function() {
                updateGoal();
            });

            $("#percentageflag").on('keypress keyup blur change',function() {
                updateGoal();
            });

            stepper2 = new Stepper(document.querySelector('#stepper2'), {
                linear: false,
                animation: true
            })
        });
    </script>

<?php




echo $OUTPUT->footer();
