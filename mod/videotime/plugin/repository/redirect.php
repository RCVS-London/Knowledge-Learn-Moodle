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
 * Redirect after setting code
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once("$CFG->dirroot/mod/videotime/lib.php");
require_once("$CFG->dirroot/mod/videotime/plugin/repository/lib/vimeo-api/src/Vimeo/Vimeo.php");

require_login();
if (!is_siteadmin()) {
    throw new coding_exception('Only admins should ever reach this page.');
}

$code = required_param('code', PARAM_TEXT);

set_config('vimeo_authorization_code', $code, 'videotime');

$lib = new \Vimeo\Vimeo(get_config('videotime', 'client_id'), get_config('videotime', 'client_secret'));

$redirecturi = $CFG->wwwroot . '/mod/videotime/plugin/repository/redirect.php';

$token = $lib->accessToken($code, $redirecturi);

if (isset($token['body']['error'])) {
    \core\notification::error($token['body']['error_description']);
} else if (isset($token['body']['access_token'])) {
    set_config('vimeo_access_token', $token['body']['access_token'], 'videotime');
    \core\notification::success(get_string('authenticate_vimeo_success', 'videotime'));
    \core\notification::info(get_string('run_discovery_task', 'videotime'));
    if (!videotime_is_totara()) {
        redirect(new moodle_url('/admin/tool/task/schedule_task.php', [
            'task' => 'videotimeplugin_repository\task\discover_videos'
        ]));
    } else {
        redirect(new moodle_url('/mod/videotime/plugin/repository/overview.php'));
    }
}

redirect(new moodle_url('/mod/videotime/plugin/repository/authenticate.php'));
