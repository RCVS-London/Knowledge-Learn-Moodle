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
 * Browse video pop up
 *
 * @package     videotimeplugin_repository
 * @copyright   2020 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');

global $CFG, $OUTPUT;

$album = optional_param('album', null, PARAM_TEXT);
$tag = optional_param('tag', null, PARAM_TEXT);
$query = optional_param('query', '', PARAM_TEXT);

require_login();

require_capability('videotimeplugin/repository:browsevideos', \context_system::instance());

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/mod/videotime/plugin/repository/browsevideos.php');
$PAGE->set_title(get_string('browsevideos', 'videotime'));
$PAGE->set_heading(get_string('browsevideos', 'videotime'));
$PAGE->navbar->add(get_string('browsevideos', 'videotime'));
$PAGE->set_pagelayout('standard');

$albums = array_values($DB->get_records('videotime_vimeo_album', null, 'name'));
$tags = array_values($DB->get_records('videotime_vimeo_tag', null, 'name'));

$albumid = null;
if ($album) {
    foreach ($albums as $albumrec) {
        if (strtolower($albumrec->name) == strtolower($album)) {
            $albumid = $albumrec->id;
        }
    }
}

$tagid = null;
if ($tag) {
    foreach ($tags as $tagrec) {
        if (strtolower($tagrec->name) == strtolower($tag)) {
            $tagid = $tagrec->id;
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('videotimeplugin_repository/browse_videos_button', [
    'contextid' => $PAGE->context->id,
    'query' => $query,
    'albumid' => $albumid,
    'tagid' => $tagid
]);
echo $OUTPUT->footer();
