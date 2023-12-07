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
 * Table that reports session information for a specific course module.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro;

defined('MOODLE_INTERNAL') || die();

use context_course;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table that reports session information for a specific course module.
 *
 * @package videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessions_report_table extends \table_sql {
    /** @var array */
    private static $aggregatecolumns = ['watch_time', 'views'];

    /** @var int */
    private $cmid;

    /**
     * sessions_report_table constructor.
     * @param int $cmid
     * @param int $download
     * @throws \coding_exception
     */
    public function __construct($cmid, $download) {
        global $COURSE;

        parent::__construct($cmid);

        $this->cmid = $cmid;

        if ($download) {
            raise_memory_limit(MEMORY_EXTRA);
            $this->is_downloading($download, 'video-time-report');
        }

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('user');
        $columns[] = 'fullname';
        // TODO Does not support custom user profile fields (MDL-70456).
        if (class_exists('\\core_user\\fields')) {
            $extrafields = \core_user\fields::get_identity_fields(context_course::instance($COURSE->id), false);
        } else {
            $extrafields = get_extra_user_fields(context_course::instance($COURSE->id));
        }
        foreach ($extrafields as $field) {
            $columns[] = $field;
            if (class_exists('\\core_user\\fields')) {
                $headers[] = \core_user\fields::get_display_name($field);
            } else {
                $headers[] = get_user_field_name($field);
            }
        }
        $headers[] = get_string('views', 'videotime');
        $columns[] = 'views';
        $headers[] = get_string('watch_time', 'videotime');
        $columns[] = 'watch_time';
        $headers[] = get_string('watch_percent', 'videotime');
        $columns[] = 'percent';
        $headers[] = get_string('state', 'videotime');
        $columns[] = 'state';

        if (is_siteadmin() && !$this->is_downloading()) {
            $headers[] = get_string('actions');
            $columns[] = 'actions';
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->no_sorting('state');

        $this->set_attribute('cm_id', $cmid);

        // Set help icons.
        $this->define_help_for_headers([
            count($extrafields) + '1' => new \help_icon('views', 'videotime'),
            count($extrafields) + '2' => new \help_icon('watch_time', 'videotime'),
            count($extrafields) + '3' => new \help_icon('watch_percent', 'videotime'),
            count($extrafields) + '4' => new \help_icon('state', 'videotime'),
        ]);
    }

    /**
     * Fullname is treated as a special columname in tablelib and should always
     * be treated the same as the fullname of a user.
     * @uses $this->useridfield if the userid field is not expected to be id
     * then you need to override $this->useridfield to point at the correct
     * field for the user id.
     *
     * @param object $row the data from the db containing all fields from the
     *                    users table necessary to construct the full name of the user in
     *                    current language.
     * @return string contents of cell in column 'fullname', for this row.
     */
    public function col_fullname($row) {
        global $COURSE, $DB;

        if (!$user = $DB->get_record('user', ['id' => $row->userid])) {
            return '';
        }

        $name = fullname($user);
        if ($this->download) {
            return $name;
        }

        if ($COURSE->id == SITEID) {
            $profileurl = new \moodle_url('/user/profile.php', array('id' => $user->id));
        } else {
            $profileurl = new \moodle_url('/user/view.php',
                array('id' => $user->id, 'course' => $COURSE->id));
        }
        return \html_writer::link($profileurl, $name);
    }

    /**
     * Watch time column
     *
     * @param stdClass $data
     * @return int
     * @throws \dml_exception
     */
    public function col_watch_time($data) {
        $modulesessions = module_sessions::get($this->uniqueid, $data->userid);
        return session::format_time($modulesessions->get_total_time());
    }

    /**
     * Percent time
     *
     * @param stdClass $data
     * @return string
     * @throws \dml_exception
     */
    public function col_percent($data) {
        $modulesessions = module_sessions::get($this->uniqueid, $data->userid);
        return floor($modulesessions->get_percent() * 100) . '%';
    }

    /**
     * State column
     *
     * @param stdClass $data
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function col_state($data) {
        $modulesessions = module_sessions::get($this->uniqueid, $data->userid);
        return session::get_state_label($modulesessions->get_state());
    }

    /**
     * Actions column
     *
     * @param stdClas $data
     */
    public function col_actions($data) {
        global $OUTPUT, $PAGE;

        return $OUTPUT->single_button(new \moodle_url('/mod/videotime/action.php', [
            'id' => $this->cmid,
            'return' => $PAGE->url,
            'action' => 'delete_session_data',
            'userid' => $data->userid
        ]), get_string('deletesessiondata', 'videotime'), 'get');
    }

    /**
     * Datbase query
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $COURSE, $DB;

        list($wsql, $params) = $this->get_sql_where();
        if ($wsql) {
            $wsql = 'AND ' . $wsql;
        }
        $cm = get_coursemodule_from_id('videotime', $this->cmid, 0, false, MUST_EXIST);
        if (
            groups_get_activity_groupmode($cm) != NOGROUPS
            && $groupid = groups_get_activity_group($cm)
        ) {
            $groupusers = array_keys(groups_get_members($groupid));
            if (!empty($groupusers)) {
                list($grpsql, $grpparams) = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED, 'gm');
                $wsql .= " AND u.id $grpsql";
                $params = array_merge($params, $grpparams);
            } else {
                $wsql = 'AND false';
            }
        }
        if (class_exists('\\core_user\\fields')) {
            $extrafields = \core_user\fields::get_identity_fields(context_course::instance($COURSE->id), false);
        } else {
            $extrafields = get_extra_user_fields(context_course::instance($COURSE->id));
        }
        $sql = 'SELECT u.id AS id,
            ' . implode(array_map(function($field) {
            return "u.$field, ";
        }, $extrafields)) . '
                u.id AS userid,
                SUM(s.time) AS watch_time,
                COUNT(s.id) AS views
                FROM {'.session::TABLE.'} s JOIN {user} u ON u.id = s.user_id
                WHERE s.module_id = :module_id
                '.$wsql.'
                GROUP BY u.id';

        $params['module_id'] = $this->uniqueid;

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sql = $sql . ' ORDER BY ' . $sort;
        }

        if ($pagesize != -1) {
            $countsql = 'SELECT COUNT(DISTINCT u.id) FROM {'.session::TABLE.'} s JOIN {user} u ON u.id = s.user_id
                WHERE s.module_id = :module_id
                '.$wsql;
            $total = $DB->count_records_sql($countsql, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars(true);
        }

        $this->rawdata = $DB->get_recordset_sql($sql, $params, $this->get_page_start(), $this->get_page_size());
    }

    /**
     * Get sort
     *
     * @return SQL fragment that can be used in an ORDER BY clause.
     */
    public function get_sql_sort() {
        return self::construct_order_by($this->get_sort_columns(), $this->get_sort_columns());
    }

    /**
     * Prepare an an order by clause from the list of columns to be sorted.
     *
     * @param array $cols column name => SORT_ASC or SORT_DESC
     * @param array $textsortcols column to sort
     * @return SQL fragment that can be used in an ORDER BY clause.
     */
    public static function construct_order_by($cols, $textsortcols=array()) {
        global $DB;
        $bits = array();

        foreach ($cols as $column => $order) {
            if (in_array($column, $textsortcols)) {
                $column = $DB->sql_order_by_text($column);
            }
            if (!in_array($column, self::$aggregatecolumns)) {
                $column = 'MAX("' . $column . '")';
            }
            if ($order == SORT_ASC) {
                $bits[] = $column . ' ASC';
            } else {
                $bits[] = $column . ' DESC';
            }
        }

        return implode(', ', $bits);
    }
}
