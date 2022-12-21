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
 * Class used to interface with session records.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro;

use videotimeplugin_pro\exception\invalid_session_state_exception;

/**
 * Class used to interface with session records.
 *
 * A session is a single viewing of a Video Time video. Each viewing
 * creates a new session. See also {@see module_sessions } for aggregating all user sessions.
 *
 * @package videotimeplugin_pro
 */
class session implements \JsonSerializable {
    /** constant string db table name */
    const TABLE = 'videotime_session';

    /** constant int */
    const STATE_INCOMPLETE = 0;

    /** constant int */
    const STATE_FINISHED = 1;

    /** @var stdClass instance db record */
    private $record;

    /**
     * Constructor
     *
     * @param \stdClass $record A session database record.
     */
    protected function __construct(\stdClass $record) {
        $this->record = $record;
    }

    /**
     * Get all possible states for a session.
     *
     * @return array
     * @throws \coding_exception
     */
    public static function get_states() {
        return [
            self::STATE_INCOMPLETE => get_string('state_incomplete', 'videotime'),
            self::STATE_FINISHED => get_string('state_finished', 'videotime')
        ];
    }

    /**
     * Create a new session for a particular module and user. Persists to database.
     *
     * @param int $moduleid Course module ID
     * @param int $userid
     * @return session
     * @throws \dml_exception
     */
    public static function create_new($moduleid, $userid) {
        $record = new \stdClass();
        $record->module_id = $moduleid;
        $record->user_id = $userid;
        $record->time = 0;
        $date = new \DateTime('now', \core_date::get_server_timezone_object());
        $record->timestarted = $date->getTimestamp();

        $session = new session($record);
        $session->persist();

        return $session;
    }

    /**
     * Create session from database record.
     *
     * @param stdClass $record
     * @return session
     */
    public static function from_record($record) {
        return new session($record);
    }

    /**
     * Get session by id
     *
     * @param int $id Session database record ID.
     * @return null|session
     * @throws \dml_exception
     */
    public static function get_one_by_id($id) {
        global $DB;

        if (!$record = $DB->get_record(self::TABLE, ['id' => $id])) {
            return null;
        }

        return new session($record);
    }

    /**
     * Update or create session in database.
     *
     * @throws \dml_exception
     */
    public function persist() {
        global $DB;

        if (isset($this->record->id)) {
            $DB->update_record(self::TABLE, $this->record);
        } else {
            $this->record->id = $DB->insert_record(self::TABLE, $this->record);
        }
    }

    /**
     * Get id
     *
     * @return int
     */
    public function get_id() {
        return $this->record->id;
    }

    /**
     * Set id
     *
     * @param int $id
     */
    public function set_id($id) {
        $this->record->id = $id;
    }

    /**
     * Get course module id
     *
     * @return int
     */
    public function get_module_id() {
        return $this->record->module_id;
    }

    /**
     * Set course module id
     *
     * @param int $moduleid
     */
    public function set_module_id($moduleid) {
        $this->record->module_id = $moduleid;
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function get_user_id() {
        return $this->record->user_id;
    }

    /**
     * Set user id
     *
     * @param int $userid
     */
    public function set_user_id($userid) {
        $this->record->user_id = $userid;
    }

    /**
     * Get time
     *
     * @return int
     */
    public function get_time() {
        return $this->record->time;
    }

    /**
     * Set time
     *
     * @param int $time
     */
    public function set_time($time) {
        $this->record->time = $time;
    }

    /**
     * Get time created
     *
     * @return int
     */
    public function get_timecreated() {
        return $this->record->timecreated;
    }

    /**
     * Get time created
     *
     * @param int $timecreated
     */
    public function set_timecreated($timecreated) {
        $this->record->timecreated = $timecreated;
    }

    /**
     * Get current state
     *
     * @return int
     */
    public function get_state() {
        return $this->record->state;
    }

    /**
     * Set current state
     *
     * @param int $state
     * @throws invalid_session_state_exception
     * @throws \coding_exception
     */
    public function set_state($state) {
        if (!array_key_exists($state, self::get_states())) {
            throw new invalid_session_state_exception();
        }
        $this->record->state = $state;
    }

    /**
     * Set percent watched
     *
     * @param float $percent
     */
    public function set_percent($percent) {
        $this->record->percent_watch = $percent;
    }

    /**
     * Get percent watched
     *
     * @return float|null
     */
    public function get_percent() {
        return $this->record->percent_watch;
    }

    /**
     * Get current watch time
     *
     * @param float $currentwatchtime
     */
    public function set_current_watch_time($currentwatchtime) {
        $this->record->current_watch_time = $currentwatchtime;
    }

    /**
     * Set current watch time
     *
     * @return float|null
     */
    public function get_current_watch_time() {
        return $this->record->current_watch_time;
    }

    /**
     * Get label for state.
     *
     * @param int $state
     * @return string
     * @throws \coding_exception
     */
    public static function get_state_label($state) {
        return self::get_states()[$state];
    }

    /**
     * Format seconds to HH:MM:SS.
     *
     * @param int $seconds
     * @return string
     */
    public static function format_time($seconds) {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    }

    /**
     * Get record to serailize
     *
     * @return array|mixed
     */
    public function jsonSerialize() {
        return (array)$this->record;
    }

    /**
     * Get structure of session for external services.
     *
     * @return \external_single_structure
     */
    public static function get_external_definition() {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT, ''),
            'module_id' => new \external_value(PARAM_INT, ''),
            'user_id' => new \external_value(PARAM_INT, ''),
            'time' => new \external_value(PARAM_INT, ''),
            'timestarted' => new \external_value(PARAM_INT, '', VALUE_OPTIONAL),
            'state' => new \external_value(PARAM_INT, '', VALUE_OPTIONAL),
            'percent' => new \external_value(PARAM_FLOAT, '', VALUE_DEFAULT, 0),
            'current_watch_time' => new \external_value(PARAM_FLOAT, '', VALUE_DEFAULT, 0),
        ]);
    }
}
