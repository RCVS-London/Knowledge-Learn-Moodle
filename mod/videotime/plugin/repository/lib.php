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
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use videotimeplugin_repository\video;

defined('MOODLE_INTERNAL') || die();

/**
 * File serving callback
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file was not found, just send the file otherwise and do not return anything
 */
function videotimeplugin_repository_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    // Vimeo thumbnails. Try serving from file system if thumbnails are stored. Otherwise pull from Vimeo.
    if ($filearea == 'pictures') {

        if (!isset($args[0])) {
            return false;
        }

        $video = video::create($DB->get_record('videotime_vimeo_video', ['id' => $args[0]], '*', MUST_EXIST));

        $relativepath = implode('/', $args);

        $fullpath = "/$context->id/videotimeplugin_repository/$filearea/$relativepath";

        $fs = get_file_storage();
        $hash = sha1($fullpath);
        if (!$file = $fs->get_file_by_hash($hash) or $file->is_directory()) {
            // File has not been stored yet, pull from Vimeo.
            if (!$pictures = $video->get_pictures()) {
                return false;
            }

            $link = null;
            foreach ($pictures['sizes'] as $size) {
                if (md5($size['link']) == $args[1]) {
                    $link = $size['link'];
                    break;
                } else if (md5($size['link_with_play_button']) == $args[1]) {
                    $link = $size['link_with_play_button'];
                    break;
                }
            }

            if ($link) {
                // Prepare file record object.
                $fileinfo = [
                    'contextid' => $context->id,
                    'component' => 'videotimeplugin_repository',
                    'filearea' => 'pictures',
                    'itemid' => $video->get_record()->id,
                    'filepath' => '/',
                    'filename' => md5($link)
                ];

                $file = $fs->create_file_from_string($fileinfo, file_get_contents($link));
            } else {
                return false;
            }
        }

        send_stored_file($file, null, 0, $forcedownload, $options);
    }

    return false;
}
