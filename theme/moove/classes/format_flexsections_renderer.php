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
 * Overriden course topics format renderer.
 *
 * @package    theme_moove
 * @copyright  2021 David Aylmer david@rcvsknowledge.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/flexsections/renderer.php');

/**
 * Rewrite format topics renderer base class
 *
 * @package    theme_moove
 * @copyright  2021 David Aylmer david@rcvsknowledge.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_moove_format_flexsections_renderer extends format_flexsections_renderer {

    /**
     * Display section and all its activities and subsections (called recursively)
     *
     * @param int|stdClass $course
     * @param int|section_info $section
     * @param int $sr section to return to (for building links)
     * @param int $level nested level on the page (in case of 0 also displays additional start/end html code)
     */
    public function display_section($course, $section, $sr, $level = 0) {

        $course = course_get_format($course)->get_course();
        $section = course_get_format($course)->get_section($section);

        // TODO: Remove hack by 2022.
        // Only apply this hack for listings of the EBVM course, using it's idnumber
        if (!isguestuser() && $course->idnumber == 'ebvmlearning') {

            $context = context_course::instance($course->id);
            global $CFG, $USER, $DB;

            // Find embedded book URLs.
            $regex = preg_quote($CFG->wwwroot . '/mod/book/view.php?id=') . '([0-9]+)';
            preg_match_all('#' . $regex . '#', $section->summary, $matches);

            $book_urls = $matches[0];
            $book_cmids = $matches[1];

            if (!empty($book_cmids)) {

                $params = [
                    'userid' => $USER->id,
                    'courseid' => $course->id,
                ];
                list($insql, $inparams) = $DB->get_in_or_equal($book_cmids, SQL_PARAMS_NAMED);

                $sql = "SELECT cmid, chapterid
                FROM (
                    SELECT contextinstanceid AS cmid, objectid AS chapterid, timecreated,
                    rank() over(partition by contextinstanceid order by timecreated DESC) AS rank
                    FROM {logstore_standard_log} sl
                    WHERE sl.eventname = '\\mod_book\\event\\chapter_viewed'
                    AND sl.objecttable = 'book_chapters'
                    AND (sl.realuserid is null OR sl.realuserid NOT IN (1,2))
                    AND sl.userid = :userid
                    AND sl.courseid = :courseid
                    AND sl.contextinstanceid $insql
                ) logs 
                WHERE rank = 1";
                $recentbooks = $DB->get_records_sql($sql, array_merge($params, $inparams));

                $patterns = [];
                $replacements = [];
                foreach ($recentbooks as $book) {
                    $index = array_search($book->cmid, $book_cmids);
                    array_push($patterns, '#' . preg_quote($book_urls[$index]) . '#');
                    array_push($replacements, $book_urls[$index] . '&amp;chapterid=' . $book->chapterid);
                }

                $section->summary = preg_replace($patterns, $replacements, $section->summary);
            }
        }

        // TODO: Super hacky for now
        if (isguestuser()) {

            $referer = new moodle_url(get_local_referer());
            $ref = print_r($referer, true);

            if ($referer->get_path() != '/'
                && $referer->get_path() != '/mod/book/view.php'
                && $referer->get_path() != '/login/index.php'
            ) {

                $section->summary .= '
        
                <!-- Modal -->
                <div class="modal fade in" id="modalguestcourse' . $course->id . '" tabindex="-1" role="dialog" aria-labelledby="modalguestcourse' . $course->id . 'Title" aria-hidden="true" style="display:block;">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalguestcourse' . $course->id . 'Title">Please Note</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">×</span>
                        </button>
                            </div>  
                            <div class="modal-body">
                                You are about to access this course as a guest. If you would like to save your progress and receive a certificate when you complete the course, please <a href="https://learn.rcvsknowledge.org/login/index.php">log in or register</a>.
                            </div>
                            <div class="modal-footer">
                                <a class="btn btn-secondary" data-dismiss="modal" onclick = "$(\'.modal\').hide()">Continue to course
                        </a>
                                <a class="btn btn-primary" href="https://learn.rcvsknowledge.org/login/index.php">Log in/Register
                            </a></div>
                        </div>
                    </div>
                </div>  
                ';
            }
        }

        return parent::display_section($course, $section, $sr, $level);
    }
}
