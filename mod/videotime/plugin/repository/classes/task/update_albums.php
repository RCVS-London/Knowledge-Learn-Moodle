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
 * Class update_albums
 *
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
 *
 * @package videotimeplugin_repository
 */
class update_albums extends \core\task\scheduled_task {
    /**
     * Get name
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('update_albums', 'videotime');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $DB;

        try {
            $api = new api();
            $recordids = [];
            $next = '/me/albums?per_page=100';
            while (true) {
                $allalbumsresponse = $api->request($next);

                if (isset($allalbumsresponse['body']['error'])) {
                    mtrace('Album API Error: ' . $allalbumsresponse['body']['error']);
                    return;
                }

                foreach ($allalbumsresponse['body']['data'] as $album) {
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

                    $recordids[] = $record->id;
                }

                if ($allalbumsresponse['body']['paging']['next']) {
                    $next = $allalbumsresponse['body']['paging']['next'];
                } else {
                    break;
                }
            }

            // Now build album associations with videos.
            foreach ($DB->get_records('videotime_vimeo_album') as $albumrecord) {
                $videouris = [];
                $next = $albumrecord->uri . '/videos?per_page=100&fields=uri';
                while (true) {
                    $videoresponse = $api->request($next);

                    if (isset($videoresponse['body']['error'])) {
                        mtrace('Album (video) API Error: ' . $videoresponse['body']['error']);
                        return;
                    }

                    foreach ($videoresponse['body']['data'] as $video) {
                        $videouris[] = $video['uri'];
                    }

                    if ($videoresponse['body']['paging']['next']) {
                        $next = $videoresponse['body']['paging']['next'];
                    } else {
                        break;
                    }
                }

                if ($videouris) {
                    list($sql, $params) = $DB->get_in_or_equal($videouris);
                    array_unshift($params, $albumrecord->id);

                    $DB->delete_records('videotime_vimeo_video_album', ['album_id' => $albumrecord->id]);
                    $DB->execute('INSERT INTO {videotime_vimeo_video_album} (video_id, album_id)
                                  SELECT v.id, ? AS album_id
                                  FROM {videotime_vimeo_video} v WHERE v.uri ' . $sql, $params);
                }
            }

        } catch (api_not_authenticated $e) {
            mtrace('Vimeo API is not authenticated. Skipping');
        } catch (api_not_configured $e) {
            mtrace('Vimeo API is not configured. Skipping');
        }
    }
}
