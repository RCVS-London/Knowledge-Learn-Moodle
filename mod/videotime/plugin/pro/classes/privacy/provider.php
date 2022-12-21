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
 * Plugin version and other meta-data are defined here.
 *
 * @package     videotimeplugin_pro
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The videotimeplugin_pro module privacy provider
 *
 * @package     videotimeplugin_pro
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'videotime_session',
            [
                'user_id' => 'privacy:metadata:videotime_session:user_id',
                'module_id' => 'privacy:metadata:videotime_session:module_id',
                'time' => 'privacy:metadata:videotime_session:time',
                'timestarted' => 'privacy:metadata:videotime_session:timestarted',
                'state' => 'privacy:metadata:videotime_session:state',
                'percent_watch' => 'privacy:metadata:videotime_session:percent_watch',
                'current_watch_time' => 'privacy:metadata:videotime_session:current_watch_time',
            ],
            'privacy:metadata:videotime_session'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Fetch all choice answers.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {videotime} v ON v.id = cm.instance
            INNER JOIN {videotime_session} vs ON vs.module_id = cm.id
                 WHERE vs.user_id = :userid";

        $params = [
            'modname'       => 'videotime',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all users with a videotime session.
        $sql = "SELECT vs.user_id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videotime} v ON v.id = cm.instance
                  JOIN {videotime_session} vs ON vs.module_id = cm.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'videotime',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       vs.time,
                       vs.timestarted,
                       vs.state,
                       vs.percent_watch,
                       vs.current_watch_time
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {videotime} v ON v.id = cm.instance
            INNER JOIN {videotime_session} vs ON vs.module_id = cm.id
                 WHERE c.id {$contextsql}
                       AND vs.user_id = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'videotime', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Reference to the videotime activity seen in the last iteration of the loop. By comparing this with the current
        // record, and because we know the results are ordered, we know when we've moved to the sessions to a new videotime.
        // when we can export the complete data for the last activity.
        $lastcmid = null;

        $sessions = $DB->get_recordset_sql($sql, $params);
        foreach ($sessions as $session) {
            // If we've moved to a new sesseion, then write the last session data and reinit the session data array.
            if ($lastcmid != $session->cmid) {
                if (!empty($sessiondata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_session_data_for_user($sessiondata, $context, $user);
                }
                $sessiondata = [
                    'sessions' => [],
                    'cmid' => $session->cmid,
                ];
            }
            $sessiondata['sessions'][] = [
                'timestarted' => \core_privacy\local\request\transform::datetime($session->timestarted),
                'time' => $session->time,
                'state' => $session->state,
                'percent_watch' => $session->percent_watch,
                'current_watch_time' => $session->current_watch_time,
            ];
            $lastcmid = $session->cmid;
        }
        $sessions->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($sessiondata)) {
            $context = \context_module::instance($lastcmid);
            self::export_session_data_for_user($sessiondata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single videotime activity, along with any generic data or area files.
     *
     * @param array $sessiondata the personal data to export for the videotime activity.
     * @param \context_module $context the context of the choice.
     * @param \stdClass $user the user record
     */
    protected static function export_session_data_for_user(array $sessiondata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the videotiome activity.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with videotime data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $sessiondata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('videotime', $context->instanceid)) {
            $DB->delete_records('videotime_session', ['module_id' => $cm->id]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }
            $DB->delete_records('videotime_session', ['module_id' => $instanceid, 'user_id' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('videotime', $context->instanceid);

        if (!$cm) {
            // Only choice module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "module_id = :videotimeid AND user_id $usersql";
        $params = ['videotimeid' => $cm->instance] + $userparams;
        $DB->delete_records_select('videotime_session', $select, $params);
    }
}
