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
 * This process deletes albums and tags that are no longer used by any videos within the database.
 *
 * @package     videotimeplugin_repository
 * @copyright   2020 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository\task;

/**
 * This process deletes albums and tags that are no longer used by any videos within the database.
 *
 * @package videotimeplugin_repository
 */
class cleanup_albums_and_tags extends \core\task\scheduled_task {
    /**
     * Get name
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('cleanupalbumsandtags', 'videotime');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $DB;

        // Delete unused albums.
        if ($unused = $DB->get_fieldset_sql(
            'SELECT a.id
               FROM {videotime_vimeo_album} a
               LEFT JOIN {videotime_vimeo_video_album} va ON va.album_id = a.id
               LEFT JOIN {videotime_vimeo_video} v ON v.id = va.video_id
              WHERE v.id IS NULL')
        ) {
            list($sql, $params) = $DB->get_in_or_equal($unused);
            $DB->delete_records_select('videotime_vimeo_album', "id $sql", $params);
        }

        // Delete unused tags.
        if ($unused = $DB->get_fieldset_sql(
            'SELECT t.id
               FROM {videotime_vimeo_tag} t
               LEFT JOIN {videotime_vimeo_video_tag} va ON va.tag_id = t.id
               LEFT JOIN {videotime_vimeo_video} v ON v.id = va.video_id
              WHERE v.id IS NULL')
        ) {
            list($sql, $params) = $DB->get_in_or_equal($unused);
            $DB->delete_records_select('videotime_vimeo_tag', "id $sql", $params);
        }
    }
}
