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
 * @package     videotimeplugin_pro
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use videotimeplugin_repository\video_collection;
use videotimeplugin_repository\video;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/videotime/lib.php');

class videotimeplugin_repository_external extends \external_api
{
    #region search_videos

    public static function search_videos_parameters() {
        return new \external_function_parameters([
            'contextid' => new \external_value(PARAM_INT),
            'query' => new \external_value(PARAM_TEXT, '', VALUE_DEFAULT),
            'filter_data' => new \external_multiple_structure(new external_single_structure([
                'name' => new \external_value(PARAM_TEXT, 'Field name of filter'),
                'value' => new \external_value(PARAM_TEXT, 'Value to filter by', VALUE_DEFAULT)
            ]), '', VALUE_DEFAULT),
            'limitfrom' => new \external_value(PARAM_INT, '', VALUE_DEFAULT, 0),
            'limitnum' => new \external_value(PARAM_INT, '', VALUE_DEFAULT, 10),
            'sort' => new \external_value(PARAM_TEXT, 'Field to sort by.', VALUE_DEFAULT, 'v.created_time'),
            'sortdirection' => new \external_value(PARAM_TEXT, 'Field to sort by.', VALUE_DEFAULT, 'DESC')
        ]);
    }

    public static function search_videos($contextid, $query, $filter_data, $limitfrom, $limitnum, $sort, $sortdirection) {
        $params = self::validate_parameters(self::search_videos_parameters(), [
            'contextid' => $contextid,
            'query' => $query,
            'filter_data' => $filter_data,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum,
            'sort' => $sort,
            'sortdirection' => $sortdirection
        ]);

        $context = context::instance_by_id($params['contextid']);

        require_login();
        require_capability('mod/videotime:addinstance', $context);

        $filters = [];
        if ($params['filter_data']) {
            foreach ($params['filter_data'] as $val) {
                if (array_key_exists('name', $val)) {
                    $filters[$val['name']][] = $val['value'];
                }
            }
        }

        $album_ids = isset($filters['albums']) ? $filters['albums'] : [];
        $tag_ids = isset($filters['tags']) ? $filters['tags'] : [];

        $video_collection = new video_collection($params['query'], $album_ids, $tag_ids, $params['limitfrom'],
            $params['limitnum'], $params['sort'], $params['sortdirection']);

        $data = json_decode(json_encode($video_collection), true);

        return $data;
    }

    public static function search_videos_returns() {
        return video_collection::get_external_definition();
    }

    #endregion

    #region api_request

    public static function api_request_parameters() {
        return new \external_function_parameters([
            'url' => new \external_value(PARAM_TEXT),
            'contextid' => new \external_value(PARAM_INT)
        ]);
    }

    public static function api_request($url, $contextid) {
        $params = self::validate_parameters(self::api_request_parameters(), [
            'url' => $url,
            'contextid' => $contextid
        ]);

        $context = context::instance_by_id($params['contextid']);

        require_login();
        require_capability('mod/videotime:addinstance', $context);

        $api = new \videotimeplugin_repository\api();

        $response = $api->request($params['url']);

        return ['data' => json_encode($response)];
    }

    public static function api_request_returns() {
        return new external_single_structure([
            'data' => new \external_value(PARAM_RAW)
        ]);
    }

    #endregion

    #region get_filter_options

    public static function get_filter_options_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_filter_options() {
        global $DB;

        $albums = array_values($DB->get_records('videotime_vimeo_album', null, 'name'));
        $tags = array_values($DB->get_records('videotime_vimeo_tag', null, 'name'));


        return ['albums' => $albums, 'tags' => $tags];
    }

    public static function get_filter_options_returns() {
        return new external_single_structure([
            'albums' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_TEXT),
                'name' => new external_value(PARAM_TEXT)
            ]), '', VALUE_DEFAULT, []),
            'tags' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_TEXT),
                'name' => new external_value(PARAM_TEXT)
            ]), '', VALUE_DEFAULT, []),
        ]);
    }

    #endregion
}
