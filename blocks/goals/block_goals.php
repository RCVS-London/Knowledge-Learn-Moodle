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
 * Contains the class for the Goals block.
 *
 * @package    block_goals
 * @copyright  2021 Royal College of Veterinary Surgeons
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Aylmer
 */

defined('MOODLE_INTERNAL') || die();

use block_goals\controllers;
use block_goals\models;

global $CFG;
require_once $CFG->dirroot . '/blocks/goals/lib.php';

/**
 * Goals block class.
 *
 * @package    block_goals
 * @copyright  2021 Royal College of Veterinary Surgeons
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Aylmer
 */
class block_goals extends block_base {

    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_goals');
    }

    function instance_allow_multiple() {
        return true;
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        if (isset($this->content)) {
            return $this->content;
        }
        //$group = get_user_preferences('block_goals_user_grouping_preference');
        //$sort = get_user_preferences('block_goals_user_sort_preference');
        //$view = get_user_preferences('block_goals_user_view_preference');
        //$paging = get_user_preferences('block_goals_user_paging_preference');
        //$customfieldvalue = get_user_preferences('block_goals_user_grouping_customfieldvalue_preference');

        //$renderable = new \block_goals\output\main($group, $sort, $view, $paging, $customfieldvalue);
        //$renderer = $this->page->get_renderer('block_goals');
        //$this->content = new stdClass();
        //$this->content->text = $renderer->render($renderable);

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance());

        // If we cannot manage goals and we do not have badge access,
        if (!$managegoals && !has_goalaccessfrombadge()) {
            $this->content = (object)[
                'text' => get_config('block_goals', 'nobadgeaccess'),
                'footer' => '',
            ];
            return $this->content;
        }

        $output = '';
        $this->page->requires->css(new moodle_url('/blocks/goals/fontawesome-pro/css/all.css'));
        $this->page->requires->css(new moodle_url("https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css"));
        $this->page->requires->css(new moodle_url("https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css"));
        $this->page->requires->css(new moodle_url('/blocks/goals/styles.css'));

        $this->page->requires->jquery();
        $this->page->requires->js(new moodle_url("https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"), true);
        $this->page->requires->js(new moodle_url("https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"), true);
        $this->page->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"), true);
        $this->page->requires->js(new moodle_url("https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"), true);

        $this->page->requires->js(new moodle_url('/blocks/goals/js/dashboard.js'));

        global $USER;


        $output = '<h5>' . get_string('mysmartgoals', 'block_goals') . '</h5>';
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
            // 'template' => models\goal::NOT_TEMPLATE,
            'type' => models\goal::TYPE_INDIVIDUAL,
            'duedate' => time(),
            'hidden' => models\goal::HIDDEN_FALSE
        ], 'duedate DESC');
        $output .= controllers\goal::get_goaltable('individualgoals', $individualgoals, true, false);

        $output .= '<hr><h4>' . get_string('teamgoals', 'block_goals') . '</h4>';
        $myteams = models\teammember::get_records(['userid' => $USER->id], 'timemodified', 'DESC');
        foreach($myteams as $myteam) {
            //$team = models\team::get_record(['id' => $myteam->get('teamid')], 'timemodified', 'DESC');
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

        $output = get_string('dashboardheading', 'block_goals') . $buttons . '<br><br>' . $output . $buttons;

        $this->content = new stdClass();
        $this->content->text = $output;
        $this->content->footer = '';



        // TODO: Use template and renderer

        //$content = (object) [
        //    'loaded' => true,
        //    'individualgoals' => false,
        //    'teamgoals' => false,
        //];

        //$PAGE->requires->css('/blocks/goals/styles/datatables.css');
        //$PAGE->requires->js_call_amd('/blocks/my_datatables', 'init');

        // If you are developing a block, then do the following:


        //$renderer = $this->page->get_renderer('block_goals');
        //$this->content->text = $renderer->render($content);

        //$this->page->requires->js_call_amd(
        //    'block_goals/goals',
        //    'init',
        //    [$config, $strings]
        //);

        return $this->content;

    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'all' => true,
            'admin' => true,
            'site' => true,
            'site-index' => true,
            'my' => true,
            'course' => true,
            'course-view' => true,
            'mod' => true,
            'tag' => true
        ];
    }

    /**
     * Allow the block to have a configuration page.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = get_config('block_goals');

        // Get the customfield values (if any).
        if ($configs->displaygroupingcustomfield) {
            $group = get_user_preferences('block_goals_user_grouping_preference');
            $sort = get_user_preferences('block_goals_user_sort_preference');
            $view = get_user_preferences('block_goals_user_view_preference');
            $paging = get_user_preferences('block_goals_user_paging_preference');
            $customfieldvalue = get_user_preferences('block_goals_user_grouping_customfieldvalue_preference');

            $renderable = new \block_goals\output\main($group, $sort, $view, $paging, $customfieldvalue);
            $customfieldsexport = $renderable->get_customfield_values_for_export();
            if (!empty($customfieldsexport)) {
                $configs->customfieldsexport = json_encode($customfieldsexport);
            }
        }

        return (object) [
            'instance' => new stdClass(),
            'plugin' => $configs,
        ];
    }
}

