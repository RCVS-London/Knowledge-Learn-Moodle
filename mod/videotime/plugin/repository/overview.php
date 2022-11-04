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
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use videotimeplugin_repository\video_interface;
use videotimeplugin_repository\exception\api_not_configured;
use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\api;

require_once(__DIR__.'/../../../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->dirroot/mod/videotime/plugin/pro/vendor/autoload.php");

global $CFG, $OUTPUT;

admin_externalpage_setup('overview');

$authentication_status = get_string('done', 'videotime');
$authentication_class = null;
$configuration_status = get_string('done', 'videotime');
$settings_status = get_string('done', 'videotime');

try {
    $lib = new api();

    if ($access_token = get_config('videotime', 'vimeo_access_token')) {
        $lib->setToken($access_token);
        $response = $lib->request('/me/videos', [], 'GET');
        if ($response['status'] == 200) {
            $authentication_status = get_string('authenticated', 'videotime');
            $authentication_class = 'success';
        } else {
            $authentication_status = get_string('needs_authentication', 'videotime') . ' (' . $response['status'] . ')';
            $authentication_class = 'danger';
        }
    }
} catch(api_not_authenticated $e) {
    $authentication_status = get_string('todo', 'videotime');
} catch(api_not_configured $e) {
    $configuration_status = get_string('configure_vimeo_help', 'videotime', [
        'redirect_url' => (new moodle_url('/mod/videotime/plugin/repository/redirect.php'))->out(false),
        'configure_url' => (new moodle_url('/admin/settings.php?section=modsettingvideotime'))->out(false)
    ]);
    $settings_status = get_string('todo', 'videotime');
    $authentication_status = get_string('todo', 'videotime');
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('vimeo_overview', 'videotime'));

echo $OUTPUT->render_from_template('videotimeplugin_repository/overview', [
    'configuration_status' => $configuration_status,
    'settings_status' => $settings_status,
    'settings_url' => new moodle_url('/admin/settings.php?section=modsettingvideotime'),
    'authentication_status' => $authentication_status,
    'authentication_class' => $authentication_class,
    'authenticate_url' => new moodle_url('/mod/videotime/plugin/repository/authenticate.php'),
    'videos_discovered' => $DB->count_records('videotime_vimeo_video'),
    'video_discovery_task' => new moodle_url('/admin/tool/task/schedule_task.php', [
        'task' => 'videotimeplugin_repository\task\discover_videos'
    ]),
    'videos_processed' => $DB->count_records_sql('SELECT COUNT(*) FROM {videotime_vimeo_video} WHERE state IN (?, ?)', [
        video_interface::STATE_PROCESSED,
        video_interface::STATE_REPROCESS // Include reprocess since the video is still available albeit stale.
    ]),
    'is_totara' => videotime_is_totara(),
    'dirroot' => $CFG->dirroot
]);

echo $OUTPUT->footer();
