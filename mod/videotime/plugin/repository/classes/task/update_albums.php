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

namespace videotimeplugin_repository\task;

use videotimeplugin_repository\api;
use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\exception\api_not_configured;

/**
 * Class update_albums
 * @package videotimeplugin_repository\task
 */
class update_albums extends \core\task\scheduled_task
{
    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('update_albums', 'videotime');
    }

    public function execute()
    {
        global $DB;

        try {
            $api = new api();
            $record_ids = [];
            $next = '/me/albums?per_page=100';
            while (true) {
                $all_albums_response = $api->request($next);

                if (isset($all_albums_response['body']['error'])) {
                    mtrace('Album API Error: ' . $all_albums_response['body']['error']);
                    return;
                }

                foreach ($all_albums_response['body']['data'] as $album) {
                    if (!$record = $DB->get_record('videotime_vimeo_album', ['uri' => $album['uri']])) {
                        $record = new \stdClass();
                    }

                    $record->name = $album['name'];
                    $record->uri = $album['uri'];

                    if (isset($record->id)) {
                        $DB->update_record('videotime_vimeo_album', $record);
                    } else {
                        $record->id = $DB->insert_record('videotime_vimeo_album', $record);
                    }

                    $record_ids[] = $record->id;
                }

                if ($all_albums_response['body']['paging']['next']) {
                    $next = $all_albums_response['body']['paging']['next'];
                } else {
                    break;
                }
            }

            // Now build album associations with videos.
            foreach ($DB->get_records('videotime_vimeo_album') as $album_record) {
                $video_uris = [];
                $next = $album_record->uri . '/videos?per_page=100&fields=uri';
                while (true) {
                    $video_response = $api->request($next);

                    if (isset($video_response['body']['error'])) {
                        mtrace('Album (video) API Error: ' . $video_response['body']['error']);
                        return;
                    }

                    foreach ($video_response['body']['data'] as $video) {
                        $video_uris[] = $video['uri'];
                    }

                    if ($video_response['body']['paging']['next']) {
                        $next = $video_response['body']['paging']['next'];
                    } else {
                        break;
                    }
                }

                if ($video_uris) {
                    list($sql, $params) = $DB->get_in_or_equal($video_uris);
                    array_unshift($params, $album_record->id);

                    $DB->execute('DELETE FROM {videotime_vimeo_video_album} WHERE album_id = ?', [$album_record->id]);
                    $DB->execute('INSERT INTO {videotime_vimeo_video_album} (video_id, album_id)
                                  SELECT v.id, ? AS album_id
                                  FROM {videotime_vimeo_video} v WHERE v.uri ' . $sql, $params);
                }
            }

        } catch(api_not_authenticated $e) {
            mtrace('Vimeo API is not authenticated. Skipping');
        } catch(api_not_configured $e) {
            mtrace('Vimeo API is not configured. Skipping');
        }
    }
}
