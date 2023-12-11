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

use block_recentlyaccesseditems\helper;
use core_completion\progress;
use core_course\external\course_summary_exporter;

/**
 * Block definition class for the block_pickup plugin.
 *
 * @package   block_pickup
 * @copyright 2023 Stuart Lamour
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_pickup extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_pickup');
    }

    /**
     * Gets the block settings.
     *
     */
    public function specialization() {
        /* Don't show the block title. */
        /* The title is output as part of the block. */
        $this->title = "";
    }

    /**
     * Gets the block contents.
     *
     * @return stdClass - the block content.
     */
    public function get_content() : stdClass {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        /* RCVSK block see course overview */
        if ($_GET['see_all_courses']) {
            $this->content->footer = 
            <<<FOOTER
                <a href ='?see_all_courses=0'>Hide course overview</a>.
FOOTER;
        } else {
            $this->content->footer = 
            <<<FOOTER
                <a href ='?see_all_courses=1'>See course overview</a>.
FOOTER;
        }
        $template = new stdClass();
        $template->courses = $this->fetch_recent_courses();
        $coursecount = count($template->courses);

        $template->mods = $this->fetch_recent_mods();
        $modcount = count($template->mods);

        /* Only output if we have content. */
        if ($coursecount || $modcount) {
            /* Render from template. */
            $this->content->text = $OUTPUT->render_from_template('block_pickup/content', $template);
        }

        return $this->content;
    }

    /**
     *  Get recently accessed mods.
     *
     * @return array mods.
     */
    public function fetch_recent_mods() : array {
        /* Get the recent items using recentlyaccesseditems block's helper class */
        $modrecords = helper::get_recent_items(4);
        /* RCVSK logic - if no activities say something */
        if (!count($modrecords)) {
            $text = new stdClass();
            $text->name = 'No activities in progress';
            $text->icon = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
            $text->url = '/my/courses.php#block-pickup-activites';
            $template[] = $text;
            return $template;
        }

        /* Template data for mustache. */
        $template = new stdClass();

        foreach ($modrecords as $cm) {
            $modinfo = get_fast_modinfo($cm->courseid)->get_cm($cm->cmid);

            /* Template per mod. */
            $mod = new stdClass();
            $mod->name = $modinfo->name;
            $mod->type = $modinfo->modname;
            $mod->icon = $modinfo->get_icon_url()->out(false);
            $mod->filter = $modinfo->get_icon_url()->get_param('filtericon');
            $mod->purpose = plugin_supports('mod', $modinfo->modname, FEATURE_MOD_PURPOSE);
            $mod->url = $modinfo->url;
            $mod->coursename = $modinfo->get_course()->fullname;
            $template->mods[] = $mod;
        }

        return $template->mods;
    }

    /**
     *  Get recent courses.
     *
     * @return array courses.
     */
    public function fetch_recent_courses() : array {
        global $USER, $DB, $OUTPUT;

        // Get recent courses.
        /* RCVSK do not limit query */
         $sql = "SELECT c.id, c.fullname, c.visible, cc.name as catname
                   FROM {user_lastaccess} ula
                   JOIN {course} c ON c.id = ula.courseid
                   JOIN {course_categories} cc ON cc.id = c.category
                  WHERE ula.userid = :userid
               ORDER BY ula.timeaccess DESC";

        $params = array(
            'userid' => $USER->id,
        );

        $courserecords = $DB->get_records_sql($sql, $params);

        if (!count($courserecords)) {
            return array();
        }

        /* Template data for mustache. */
        $template = new stdClass();
        $i=0;
        foreach ($courserecords as $cr) {
            /* RCVSK restriction no more than 3 */
            if ($i>2) continue;
            /* Template per course. */
            $course = new stdClass();
            $course->fullname = $cr->fullname;
            $course->viewurl = new moodle_url('/course/view.php', array('id' => $cr->id));
            $course->visible = $cr->visible;
            $course->coursecategory = $cr->catname;

            /* Progress. */
            if ($percentage = progress::get_course_progress_percentage($cr, $USER->id)) {
                $percentage = floor($percentage);
                $course->progress = $percentage;
            }
            /* RCVSK logic - only display if % not 0 or 100 */
            if (!$percentage || $percentage == '100' || $percentage == '0') {
                continue;
            }
            
            /* Course image. */
            $course->courseimage = course_summary_exporter::get_course_image($cr);
            if (!$course->courseimage) {
                $course->courseimage = $OUTPUT->get_generated_image_for_id($cr->id);
            }
            $template->courses[] = $course;
             /* RCVSK count */
            $i++;
        }

        return  $template->courses;
    }

    /**
     * Defines on which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats() : array {
        return [
            'admin' => false,
            'site-index' => false,
            'course-view' => false,
            'mod' => false,
            'my' => true,
        ];
    }

    /**
     * Defines if the block can be added multiple times.
     *
     * @return bool.
     */
    public function instance_allow_multiple() : bool {
        return false;
    }

    /**
     * Defines if the has config.
     *
     * @return bool.
     */
    public function has_config() : bool {
        return false;
    }
}
