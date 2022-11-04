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

use videotimeplugin_repository\exception\api_not_configured;
use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\api;

require_once(__DIR__.'/../../../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->dirroot/mod/videotime/plugin/pro/vendor/autoload.php");

global $CFG, $OUTPUT;

admin_externalpage_setup('authenticate');

if (empty(get_config('videotime', 'client_id')) || empty(get_config('videotime', 'client_secret'))) {
    \core\notification::error(get_string('configure_vimeo_first', 'videotime'));
    redirect(new moodle_url('/mod/videotime/plugin/repository/overview.php'));
}

$lib = new \Vimeo\Vimeo(get_config('videotime', 'client_id'), get_config('videotime', 'client_secret'));

$redirect_uri = $CFG->wwwroot . '/mod/videotime/plugin/repository/redirect.php';

$url = $lib->buildAuthorizationEndpoint($redirect_uri, 'public private', null);

$url = str_replace('&amp;', '&', $url);

$status = get_string('not_authenticated', 'videotime');
$class = 'warning';

if ($access_token = get_config('videotime', 'vimeo_access_token')) {
    $lib->setToken($access_token);
    $response = $lib->request('/me/videos', [], 'GET');
    if ($response['status'] == 200) {
        $status = get_string('authenticated', 'videotime');
        $class = 'success';
    } else {
        $status = get_string('needs_authentication', 'videotime') . ' (' . $response['status'] . ')';
        $class = 'danger';
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('authenticate_vimeo', 'videotime'));

echo html_writer::tag('p', get_string('status') . ": <b class=\"text-$class\">$status</b>");

echo $OUTPUT->single_button($url, get_string('authenticate_vimeo', 'videotime'), 'GET');

echo $OUTPUT->footer();
