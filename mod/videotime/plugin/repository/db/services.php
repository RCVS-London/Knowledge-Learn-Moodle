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
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'videotimeplugin_repository_search_videos' => [
        'classpath'     => 'mod/videotime/plugin/repository/external.php',
        'classname'     => '\videotimeplugin_repository_external',
        'methodname'    => 'search_videos',
        'description'   => 'Search for Vimeo videos.',
        'type'          => 'read',
        'ajax'          => true
    ],
    'videotimeplugin_repository_api_request' => [
        'classpath'     => 'mod/videotime/plugin/repository/external.php',
        'classname'     => '\videotimeplugin_repository_external',
        'methodname'    => 'api_request',
        'description'   => 'Make a Vimeo API request.',
        'type'          => 'read',
        'ajax'          => true
    ],
    'videotimeplugin_repository_get_filter_options' => [
        'classpath'     => 'mod/videotime/plugin/repository/external.php',
        'classname'     => '\videotimeplugin_repository_external',
        'methodname'    => 'get_filter_options',
        'description'   => 'Get filter options for video list.',
        'type'          => 'read',
        'ajax'          => true
    ]
];
