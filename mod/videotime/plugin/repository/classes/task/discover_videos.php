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
 * Queries all videos and persist minimum video data (URI, name, modified time, and status).
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository\task;

use videotimeplugin_repository\api;
use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\exception\api_not_configured;
use videotimeplugin_repository\video_interface;

/**
 * Queries all videos and persist minimum video data (URI, name, modified time, and status).
 *
 * Prepares videos for processing.
 *
 * @package videotimeplugin_repository
 */
class discover_videos extends \core\task\scheduled_task {
    /**
     * Get name
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('discover_videos', 'videotime');
    }

    /**
     * Check mysql unicode support
     *
     * @return bool
     */
    public function check_mysql_incomplete_unicode_support() {
        global $DB;

        if ($DB->get_dbfamily() == 'mysql') {
            $collation = $DB->get_dbcollation();
            $collationinfo = explode('_', $collation);
            $charset = reset($collationinfo);

            if ($charset == 'utf8') {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute task
     */
    public function execute() {
        global $CFG, $DB;

        require_once("$CFG->dirroot/mod/videotime/plugin/repository/lib/vimeo-api/src/Vimeo/Vimeo.php");
        require_once("$CFG->dirroot/lib/upgradelib.php");

        // Warn users if their database does not support unicode. An emoji in a video title or description will causes
        // issues without full unicode support.
        if (!$this->check_mysql_incomplete_unicode_support()) {
            mtrace(get_string('incompleteunicodesupport', 'admin'));
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            $recordids = [];
            $firstrequest = true;

            $api = new api();
            $next = '/me/videos?page=1&per_page=100&fields=uri,name,modified_time,status';

            // Request videos in chunks of 100.
            // Calling API endpoint for limited number of fields is much faster and doubles request quota.
            while (true) {
                $timestart = microtime(true);
                $response = $api->request($next);

                // Stop requesting videos if any error is present.
                if (isset($response['body']['error'])) {
                    mtrace('API Error: ' . $response['body']['error']);
                    break;
                }

                // Display helpful information to user. This will be important for manually running this task.
                if ($firstrequest) {
                    mtrace(get_string('rate_limit', 'videotime') . ': ' . $response['headers']['X-RateLimit-Limit']);
                    if ($response['headers']['X-RateLimit-Limit'] < $response['body']['total'] / 100) {
                        mtrace(get_string('upgrade_vimeo_account', 'videotime'));
                    }
                    mtrace(get_string('discovering_videos', 'videotime', ['count' => $response['body']['total']]));
                }

                foreach ($response['body']['data'] as $video) {

                    // Video may not be ready on Vimeo (still uploading or unavailable).
                    if (!isset($video['status']) || $video['status'] != 'available') {
                        continue;
                    }

                    if (!$record = $DB->get_record('videotime_vimeo_video', ['uri' => $video['uri']])) {
                        $record = new \stdClass();
                    }

                    $record->name = $video['name'];
                    $record->uri = $video['uri'];
                    $record->source = video_interface::SOURCE_ACCOUNT;
                    $modifieddata = \DateTime::createFromFormat(\DateTime::ISO8601, $video['modified_time']);
                    $modifieddata->setTimezone(new \DateTimeZone('UTC'));

                    // Reprocess video if it was modified.
                    if (isset($record->id) && $record->modified_time != $modifieddata->getTimestamp()) {
                        $record->state = video_interface::STATE_REPROCESS;
                    }

                    $record->modified_time = $modifieddata->getTimestamp();

                    if (isset($record->id)) {
                        $DB->update_record('videotime_vimeo_video', $record);
                    } else {
                        $record->id = $DB->insert_record('videotime_vimeo_video', $record);
                    }

                    $recordids[] = $record->id;
                }

                if ($response['body']['paging']['next']) {
                    $next = $response['body']['paging']['next'];
                } else {
                    break;
                }

                // Predict time remaining based on latency and script duration.
                $timeend = microtime(true);
                $executiontime = ($timeend - $timestart);
                $processed = ($response['body']['page'] - 1) * 100;
                $remaining = $response['body']['total'] - $processed;
                if ($remaining > 0) {
                    $seconds = $executiontime * ($remaining / 100);
                } else {
                    $seconds = 0;
                }
                mtrace(get_string('estimated_request_time', 'videotime') . ': ' . gmdate('H:i:s', $seconds));

                $firstrequest = false;
            }

            if ($recordids) {
                list($sql, $params) = $DB->get_in_or_equal($recordids);
                // Delete cached videos that no longer exist on the Vimeo account.
                $DB->delete_records_select(
                    'videotime_vimeo_video',
                    "source = ?  AND NOT id $sql",
                    array_merge([video_interface::SOURCE_ACCOUNT], $params)
                );
                $DB->delete_records_select('videotime_vimeo_video_album', "NOT video_id $sql", $params);
                $DB->delete_records_select('videotime_vimeo_video_tag', "NOT video_id $sql", $params);
            } else {
                // No videos exist, delete anything cached in database.
                $DB->delete_records('videotime_vimeo_video', array_merge([video_interface::SOURCE_ACCOUNT], $params));
                $DB->delete_records('videotime_vimeo_video_album');
                $DB->delete_records('videotime_vimeo_video_tag');
            }

            $transaction->allow_commit();
        } catch (api_not_authenticated $e) {
            mtrace('Vimeo API is not authenticated. Skipping');
        } catch (api_not_configured $e) {
            mtrace('Vimeo API is not configured. Skipping');
        }
    }
}
