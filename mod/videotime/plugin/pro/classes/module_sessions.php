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
 * Group of session records for a specific module and user.
 *
 * Used for summary and aggregate information about user
 * progress and activity for a Video Time instance. Helpful for activity completion.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro;

/**
 * Group of session records for a specific module and user.
 *
 * @package videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_sessions implements \JsonSerializable {
    /**
     * @var session[]
     */
    private $sessions = [];

    /**
     * @var \stdClass User watching Video Time instance.
     */
    private $userid;

    /**
     * @var int Course module ID
     */
    private $moduleid;

    /**
     * Constructor
     *
     * @param int $moduleid
     * @param int $userid
     * @throws \dml_exception
     */
    protected function __construct($moduleid, $userid) {
        global $DB;

        $this->moduleid = $moduleid;
        $this->userid = $userid;

        foreach ($DB->get_records(session::TABLE, ['module_id' => $moduleid, 'user_id' => $userid]) as $record) {
            $this->sessions[] = session::from_record($record);
        }
    }

    /**
     * Get instance from course module ID and user.
     *
     * @param int $moduleid
     * @param int $userid
     * @return module_sessions
     * @throws \dml_exception
     */
    public static function get($moduleid, $userid) {
        return new module_sessions($moduleid, $userid);
    }

    /**
     * Get total watch time of all sessions for this user and module.
     *
     * @return int
     */
    public function get_total_time() {
        $time = 0;

        foreach ($this->sessions as $session) {
            $time += $session->get_time();
        }

        return $time;
    }

    /**
     * Check if video is finished.
     *
     * @return bool
     */
    public function is_finished() {
        foreach ($this->sessions as $session) {
            if ($session->get_state() == session::STATE_FINISHED) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get overall state of all sessions.
     *
     * @return int
     */
    public function get_state() {
        $state = session::STATE_INCOMPLETE;

        if ($this->is_finished()) {
            $state = session::STATE_FINISHED;
        }

        return $state;
    }

    /**
     * Get percentage of video watched. Highest percentage of all sessions is returned.
     *
     * @return float|int|null
     */
    public function get_percent() {
        $highestpercent = 0;

        foreach ($this->sessions as $session) {
            if ($session->get_percent() > $highestpercent) {
                $highestpercent = $session->get_percent();
            }
        }

        return $highestpercent;
    }

    /**
     * Get current watch time
     *
     * @return float
     */
    public function get_current_watch_time() {
        if (count($this->sessions) > 0) {
            foreach (array_reverse($this->sessions) as $session) {
                if ($watchtime = $session->get_current_watch_time()) {
                    return $watchtime;
                }
            }
        }

        return 0;
    }

    /**
     * Get number of times user viewed video.
     *
     * @return int
     */
    public function get_views() {
        return count($this->sessions);
    }

    /**
     * Serialize data
     *
     * @return array
     */
    public function jsonSerialize() {
        return [
            'total_time' => $this->get_total_time(),
            'is_finished' => $this->is_finished(),
            'state' => $this->get_state(),
            'percent' => $this->get_percent(),
            'percent_formatted' => (int)($this->get_percent() * 100),
            'current_watch_time' => $this->get_current_watch_time(),
        ];
    }
}
