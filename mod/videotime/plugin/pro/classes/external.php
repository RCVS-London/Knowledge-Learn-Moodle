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
 * Web service and ajax functions.
 *
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

/**
 * Web service and ajax functions.
 *
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {
    // Region record_watch_time.

    /**
     * Describes the parameters for record_watch_time
     *
     * @return external_function_parameters
     */
    public static function record_watch_time_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'time' => new \external_value(PARAM_INT, 'Time in seconds watched on video', VALUE_REQUIRED)
        ]);
    }

    /**
     * Record watch time
     *
     * @param  int $sessionid The session id
     * @param  int $time Time to record
     * @return array
     */
    public static function record_watch_time($sessionid, $time) {
        global $USER;

        $params = self::validate_parameters(self::record_watch_time_parameters(), [
            'session_id' => $sessionid,
            'time' => $time
        ]);
        $sessionid = $params['session_id'];
        $time = $params['time'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($sessionid)) {
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

    /**
     * Describes the record_watch_time return value.
     *
     * @return external_single_structure
     */
    public static function record_watch_time_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    // End region.

    // Region set_percent.

    /**
     * Describes the parameters for set_percent
     *
     * @return external_function_parameters
     */
    public static function set_percent_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'percent' => new \external_value(PARAM_FLOAT, 'Percent the video has been watched. 0.0 through 1.0', VALUE_REQUIRED)
        ]);
    }

    /**
     * Set percent
     *
     * @param int $sessionid
     * @param float $percent
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws session_not_found_exception
     * @throws \Exception
     */
    public static function set_percent($sessionid, $percent) {
        global $USER, $CFG, $DB;

        require_once("$CFG->dirroot/mod/videotime/lib.php");

        $params = self::validate_parameters(self::set_percent_parameters(), [
            'session_id' => $sessionid,
            'percent' => $percent
        ]);
        $sessionid = $params['session_id'];
        $percent = $params['percent'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($sessionid)) {
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

    /**
     * Describes the set_percent return value.
     *
     * @return external_single_structure
     */
    public static function set_percent_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    // End region.

    // Region set_session_state.

    /**
     * Describes the parameters for set_session_state
     *
     * @return external_function_parameters
     */
    public static function set_session_state_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'state' => new \external_value(PARAM_INT, 'Session state', VALUE_REQUIRED)
        ]);
    }

    /**
     * Set session state
     *
     * @param int $sessionid
     * @param int $state
     */
    public static function set_session_state($sessionid, $state) {
        global $USER;

        $params = self::validate_parameters(self::set_session_state_parameters(), [
            'session_id' => $sessionid,
            'state' => $state
        ]);
        $sessionid = $params['session_id'];
        $state = $params['state'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($sessionid)) {
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

    /**
     * Describes the set_session_state return value.
     *
     * @return external_single_structure
     */
    public static function set_session_state_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    // End region.

    // Region set_session_current_time.

    /**
     * Describes the parameters for set_session_current_time
     *
     * @return external_function_parameters
     */
    public static function set_session_current_time_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
            'current_time' => new \external_value(PARAM_FLOAT, 'Current watch time', VALUE_REQUIRED)
        ]);
    }

    /**
     * Set current time
     *
     * @param int $sessionid
     * @param int $currenttime
     * @return array
     */
    public static function set_session_current_time($sessionid, $currenttime) {
        global $USER;

        $params = self::validate_parameters(self::set_session_current_time_parameters(), [
            'session_id' => $sessionid,
            'current_time' => $currenttime
        ]);
        $sessionid = $params['session_id'];
        $currenttime = $params['current_time'];

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($sessionid)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        $session->set_current_watch_time($currenttime);
        $session->persist();

        return ['success' => true];
    }

    /**
     * Describes the set_session_current_time return value.
     *
     * @return external_single_structure
     */
    public static function set_session_current_time_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL)
        ]);
    }

    // End region.

    // Region get_next_activity_button_data.

    /**
     * Describes the parameters for get_next_activity_button_data
     *
     * @return external_function_parameters
     */
    public static function get_next_activity_button_data_parameters() {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_INT, 'Session ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get next activity data
     *
     * @param int $sessionid
     * @return array
     */
    public static function get_next_activity_button_data($sessionid) {
        global $USER;

        $params = self::validate_parameters(self::get_next_activity_button_data_parameters(), [
            'session_id' => $sessionid
        ]);
        $sessionid = $params['session_id'];

        $context = \context_system::instance();
        self::validate_context($context);

        // Session should exist and be created when user visits view.php.
        if (!$session = session::get_one_by_id($sessionid)) {
            throw new session_not_found_exception();
        }

        if ($session->get_user_id() != $USER->id) {
            throw new \Exception('You do not have permission to do this.');
        }

        $cm = get_coursemodule_from_id('videotime', $session->get_module_id(), 0, false, MUST_EXIST);
        $cm = \cm_info::create($cm);

        require_login($cm->course, false, $cm);

        $nextactivitybutton = new \mod_videotime\output\next_activity_button($cm);

        return ['data' => json_encode($nextactivitybutton->get_data())];
    }

    /**
     * Describes the get_next_activity_button_data return value.
     *
     * @return external_single_structure
     */
    public static function get_next_activity_button_data_returns() {
        return new \external_single_structure([
            'data' => new \external_value(PARAM_RAW, 'JSON encoded data for next activity button template.')
        ]);
    }

    // End region.

    // Region get_new_session.

    /**
     * Describes the parameters for get_new_session
     *
     * @return external_function_parameters
     */
    public static function get_new_session_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
            'userid' => new \external_value(PARAM_INT, 'User ID to retrieve session for. Defaults to current user',
                VALUE_DEFAULT)
        ]);
    }

    /**
     * Get new session
     *
     * @param int $cmid
     * @param int $userid
     * @return array
     */
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

    /**
     * Describes the get_new_session return value.
     *
     * @return external_single_structure
     */
    public static function get_new_session_returns() {
        return \videotimeplugin_pro\session::get_external_definition();
    }

    // End region.

    // Region get_resume_time.

    /**
     * Describes the parameters for get_resume_time
     *
     * @return external_function_parameters
     */
    public static function get_resume_time_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
            'userid' => new \external_value(PARAM_INT, 'User ID to retrieve resume time for. Defaults to current user',
                VALUE_DEFAULT)
        ]);
    }

    /**
     * Get resume time
     *
     * @param int $cmid
     * @param int $userid
     * @return array
     */
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

    /**
     * Describes the get_resume_time return value.
     *
     * @return external_single_structure
     */
    public static function get_resume_time_returns() {
        return new \external_single_structure([
            'seconds' => new \external_value(PARAM_INT, 'Resume time in seconds')
        ]);
    }

    // End region.

    // Region get_watch_percent.

    /**
     * Describes the parameters for get_watch_percent
     *
     * @return external_function_parameters
     */
    public static function get_watch_percent_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
            'userid' => new \external_value(PARAM_INT, 'User ID to retrieve resume time for. Defaults to current user',
                VALUE_DEFAULT)
        ]);
    }

    /**
     * Get watch percent
     *
     * @param int $cmid
     * @param int $userid
     * @return array
     */
    public static function get_watch_percent($cmid, $userid = null) {
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

        $sessions = \videotimeplugin_pro\module_sessions::get($cm->id, $USER->id);
        return $sessions->get_percent();
    }

    /**
     * Describes the get_watch_percent return value.
     *
     * @return external_single_structure
     */
    public static function get_watch_percent_returns() {
        return new \external_value(PARAM_FLOAT, 'Watch percent 0.0 to 1.0');
    }

    // End region.
}
