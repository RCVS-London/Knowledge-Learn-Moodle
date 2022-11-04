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
 * External functions and service definitions.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'videotimeplugin_pro_record_watch_time' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'record_watch_time',
        'description'   => 'Record watch time to user video session.',
        'type'          => 'write',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_set_percent' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'set_percent',
        'description'   => 'Set percentage completed on user video session.',
        'type'          => 'write',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_set_session_state' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'set_session_state',
        'description'   => 'Set or change the state of a user video session.',
        'type'          => 'write',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_set_session_current_time' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'set_session_current_time',
        'description'   => 'Set or change the current time of a user video session.',
        'type'          => 'write',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_get_next_activity_button_data' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'get_next_activity_button_data',
        'description'   => 'Get data for next activity button template.',
        'type'          => 'read',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_get_new_session' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'get_new_session',
        'description'   => 'Get new video session for user in a course module.',
        'type'          => 'read',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
    'videotimeplugin_pro_get_resume_time' => [
        'classpath'     => '',
        'classname'     => 'videotimeplugin_pro\external',
        'methodname'    => 'get_resume_time',
        'description'   => 'Get time for video to resume.',
        'type'          => 'read',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ]
];
