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
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro;

use videotimeplugin_pro\exception\session_not_found_exception;
use videotimeplugin_pro\session;
use mod_videotime\videotime_instance;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/videotime/lib.php');

class external extends \external_api
{
    #region record_watch_time

    public static function record_watch_time_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'time' => new \external_value(PARAM_INT, 'Time in seconds watched on video', VALUE_REQUIRED)
        ]);
    }

    public static function record_watch_time($session_id, $time) {
        global $USER;

        $params = self::validate_parameters(self::record_watch_time_parameters(), [
            'session_id' => $session_id,
            'time' => $time
        ]);
        $session_id = $params['session_id'];
        $time = $params['time'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($session_id)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        $session->set_time($time);
        $session->persist();

        videotime_update_completion($session->get_module_id());

        return ['success' => true];
    }

    public static function record_watch_time_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    #endregion

    #region set_percent

    public static function set_percent_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'percent' => new \external_value(PARAM_FLOAT, 'Percent the video has been watched. 0.0 through 1.0', VALUE_REQUIRED)
        ]);
    }

    /**
     * @param $session_id
     * @param $percent
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws session_not_found_exception
     * @throws \Exception
     */
    public static function set_percent($session_id, $percent) {
        global $USER, $CFG, $DB;

        require_once("$CFG->dirroot/mod/videotime/lib.php");

        $params = self::validate_parameters(self::set_percent_parameters(), [
            'session_id' => $session_id,
            'percent' => $percent
        ]);
        $session_id = $params['session_id'];
        $percent = $params['percent'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($session_id)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        // Only update if new percent is greater.
        if ($percent > $session->get_percent()) {
            $session->set_percent($percent);
            $session->persist();

            $cm = get_coursemodule_from_id('videotime', $session->get_module_id());
            $videotime = $DB->get_record('videotime', ['id' => $cm->instance]);

            videotime_update_grades($videotime, $session->get_user_id());

            videotime_update_completion($session->get_module_id());
        }

        return ['success' => true];
    }

    public static function set_percent_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    #endregion

    #region set_session_state

    public static function set_session_state_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'state' => new \external_value(PARAM_INT, 'Session state', VALUE_REQUIRED)
        ]);
    }

    public static function set_session_state($session_id, $state) {
        global $USER;

        $params = self::validate_parameters(self::set_session_state_parameters(), [
            'session_id' => $session_id,
            'state' => $state
        ]);
        $session_id = $params['session_id'];
        $state = $params['state'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($session_id)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        $session->set_state($state);
        $session->persist();

        videotime_update_completion($session->get_module_id());

        return ['success' => true];
    }

    public static function set_session_state_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    #endregion

    #region set_session_current_time

    public static function set_session_current_time_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'current_time' => new \external_value(PARAM_FLOAT, 'Current watch time', VALUE_REQUIRED)
        ]);
    }

    public static function set_session_current_time($session_id, $current_time) {
        global $USER;

        $params = self::validate_parameters(self::set_session_current_time_parameters(), [
            'session_id' => $session_id,
            'current_time' => $current_time
        ]);
        $session_id = $params['session_id'];
        $current_time = $params['current_time'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($session_id)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        $session->set_current_watch_time($current_time);
        $session->persist();

        return ['success' => true];
    }

    public static function set_session_current_time_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    #endregion

    #region get_next_activity_button_data

    public static function get_next_activity_button_data_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_next_activity_button_data($session_id) {
        global $USER;

        $params = self::validate_parameters(self::get_next_activity_button_data_parameters(), [
            'session_id' => $session_id
        ]);
        $session_id = $params['session_id'];

        $context = \context_system::instance();
        self::validate_context($context);

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($session_id)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }



        $cm = get_coursemodule_from_id('videotime', $session->get_module_id(), 0, false, MUST_EXIST);
        $cm = \cm_info::create($cm);

        require_login($cm->course, false, $cm);

        $next_activity_button = new \mod_videotime\output\next_activity_button($cm);

        return ['data' => json_encode($next_activity_button->get_data())];
    }

    public static function get_next_activity_button_data_returns() {
        return new \external_single_structure([
            'data' => new \external_value(PARAM_RAW, 'JSON encoded data for next activity button template.')
        ]);
    }

    #endregion

    #region get_new_session

    public static function get_new_session_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
            'userid' => new \external_value(PARAM_INT, 'User ID to retrieve session for. Defaults to current user',
                VALUE_DEFAULT)
        ]);
    }

    public static function get_new_session($cmid, $userid = null) {
        global $USER;

        $params = self::validate_parameters(self::get_new_session_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid
        ]);

        if (is_null($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        $context = \context_module::instance($params['cmid']);
        self::validate_context($context);

        $session = \videotimeplugin_pro\session::create_new($params['cmid'], $params['userid']);

        return $session->jsonSerialize();
    }

    public static function get_new_session_returns() {
        return \videotimeplugin_pro\session::get_external_definition();
    }

    #endregion

    #region get_resume_time

    public static function get_resume_time_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
            'userid' => new \external_value(PARAM_INT, 'User ID to retrieve resume time for. Defaults to current user',
                VALUE_DEFAULT)
        ]);
    }

    public static function get_resume_time($cmid, $userid = null) {
        global $USER;

        $params = self::validate_parameters(self::get_resume_time_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid
        ]);

        if (is_null($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        $cm = get_coursemodule_from_id('videotime', $params['cmid'], 0, false, MUST_EXIST);

        $context = \context_module::instance($params['cmid']);
        self::validate_context($context);

        $moduleinstance = videotime_instance::instance_by_id($cm->instance);

        if ($moduleinstance->resume_playback) {
            $sessions = \videotimeplugin_pro\module_sessions::get($cm->id, $USER->id);
            return ['seconds' => (int)$sessions->get_current_watch_time()];
        }

        return ['seconds' => 0];
    }

    public static function get_resume_time_returns() {
        return new \external_single_structure([
            'seconds' => new \external_value(PARAM_INT, 'Resume time in seconds')
        ]);
    }

    #endregion
}
