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
 * Processes videos with state of STATE_NOT_PROCESSED or STATE_REPROCESS.
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository\task;

use dml_write_exception;
use videotimeplugin_repository\api;
use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\exception\api_not_configured;
use videotimeplugin_repository\video_interface;

/**
 * Processes videos with state of STATE_NOT_PROCESSED or STATE_REPROCESS.
 *
 * Pulls and persists all video fields (description, duration, links, etc)
 *
 * Persists video tag information.
 *
 * Saves video thumbnail image to Moodle data
 *
 * @package videotimeplugin_repository
 */
class process_videos extends \core\task\scheduled_task {
    /**
     * Get name
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('process_videos', 'videotime');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $DB;

        $context = \context_system::instance();

        $fs = get_file_storage();

        try {
            $api = new api();

            $records = $DB->get_records_sql(
                'SELECT * FROM {videotime_vimeo_video} WHERE state IN(?, ?) ORDER BY modified_time DESC',
                [video_interface::STATE_NOT_PROCESSED,
                video_interface::STATE_REPROCESS],
                0,
                50
            );

            foreach ($records as $record) {

                $response = $api->request($record->uri);

                // Continue processing next video if error is present.
                if (isset($response['body']['error'])) {
                    mtrace('API Error: ' . $response['body']['error']);
                    continue;
                }

                mtrace('Processing ' . $response['body']['name'] . ' (' . $response['body']['uri'] . ')');

                // Apply any property from API response that exists on video database record.
                foreach ($response['body'] as $key => $value) {
                    if (property_exists($record, $key)) {
                        if (!is_scalar($value)) {
                            $record->$key = json_encode($value);
                        } else {
                            $record->$key = $value;
                        }
                    }
                }
                $record->userinfo = json_encode($response['body']['user']);

                // Save modified time as unit timestamp for easier querying later.
                $modifieddate = \DateTime::createFromFormat(\DateTime::ISO8601, $response['body']['modified_time']);
                $modifieddate->setTimezone(new \DateTimeZone('UTC'));
                $record->modified_time = $modifieddate->getTimestamp();

                // Delete any tag assignments, refreshing them soon.
                $DB->delete_records('videotime_vimeo_video_tag', ['video_id' => $record->id]);

                foreach ($response['body']['tags'] as $tag) {
                    if (!$tagrecord = $DB->get_record('videotime_vimeo_tag', ['uri' => $tag['uri']])) {
                        $tagid = $DB->insert_record('videotime_vimeo_tag', (object)[
                            'uri' => $tag['uri'],
                            'name' => $tag['name']
                        ]);
                    } else {
                        $tagid = $tagrecord->id;
                    }

                    try {
                        // Cannot use insert_record() because it expects an 'id' column.
                        $DB->execute('INSERT INTO {videotime_vimeo_video_tag} VALUES(?, ?)',
                            [$record->id, $tagid]);
                    } catch (\dml_exception $e) {
                        // Likely a duplicate tag causing an error with unique index.
                        mtrace($e->getMessage());
                    }
                }

                // Delete cached images for this video. It may have been updated in Vimeo since we're
                // processing/reprocessing.
                $fs->delete_area_files($context->id, 'videotimeplugin_repository', 'pictures', $record->id);

                $record->state = video_interface::STATE_PROCESSED;
                $DB->update_record('videotime_vimeo_video', $record);
            }

        } catch (api_not_authenticated $e) {
            mtrace('Vimeo API is not authenticated. Skipping');
        } catch (api_not_configured $e) {
            mtrace('Vimeo API is not configured. Skipping');
        }
    }
}
