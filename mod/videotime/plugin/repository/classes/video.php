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
 * Video class definition
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository;

/**
 * Class representation of a video database record.
 *
 * @package videotimeplugin_repository
 */
class video implements video_interface, \JsonSerializable {
    /**
     * @var \stdClass Database record of video.
     */
    private $record;

    /**
     * @var \context
     */
    private $context;

    /**
     * Constructor
     *
     * @param \stdClass $record
     * @param \context $context Context video is currently associated with.
     */
    protected function __construct(\stdClass $record, \context $context) {
        $this->record = $record;
        $this->context = $context;
    }

    /**
     * Create new video from database record.
     *
     * @param \stdClass $record
     * @param \context $context Context video is currently associated with.
     * @return video
     * @throws \dml_exception
     */
    public static function create(\stdClass $record, \context $context = null) {
        if (is_null($context)) {
            $context = \context_system::instance();
        }
        return new video($record, $context);
    }

    /**
     * Get database record for this video.
     *
     * @return \stdClass
     */
    public function get_record() {
        return $this->record;
    }

    /**
     * Get all courses this video is used. If video is used multiple times in the same course multiple records will be
     * returned for that course.
     *
     * @return \stdClass[]
     */
    public function get_courses() {
        global $DB;

        $records = [];

        try {
            $records = $DB->get_records_sql("SELECT v.id, c.id as course_id, c.idnumber, c.fullname, c.category,
                                             v.name AS activity_name, v.id AS activity_instance_id
                                             FROM {course} c
                                             JOIN {course_categories} cc on cc.id = c.category
                                             JOIN {videotime} v ON v.course = c.id AND v.vimeo_url = :url
                                             ORDER BY cc.sortorder, c.sortorder", ['url' => $this->get_record()->link]);

            foreach ($records as &$record) {
                $category = \core_course_category::get($record->category);
                $record->category = $category->get_nested_name(false);
                $record->activity_url = (new \moodle_url('/mod/videotime/view.php',
                    ['v' => $record->activity_instance_id]))->out(false);
            }
        } catch (\dml_exception $e) {
            debugging($e->getMessage());
        } catch (\moodle_exception $e) {
            debugging($e->getMessage());
        }

        return $records;
    }

    /**
     * Get all album records this video belongs to.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_albums() {
        global $DB;

        return $DB->get_records_sql('SELECT * FROM {videotime_vimeo_album} a
                                     JOIN {videotime_vimeo_video_album} va ON va.album_id = a.id
                                     AND va.video_id = ?', [$this->get_record()->id]);
    }

    /**
     * Get URL to video thumbnail. Either stored or external.
     *
     * @return string
     * @throws \coding_exception | \dml_exception
     */
    public function get_thumbnail_url() {
        global $DB;

        $pictures = $this->get_pictures();

        $instance = null;
        if ($this->context instanceof \context_module) {
            $cm = $DB->get_record('course_modules', ['id' => $this->context->instanceid]);
            $cm = \cm_info::create($cm);
            if ($cm->modname == 'videotime') {
                $instance = $DB->get_record('videotime', ['id' => $cm->instance]);
            }
        }

        $size = 640;
        $withplay = false;

        if ($instance) {
            switch ($instance->preview_picture) {
                case video_interface::PREVIEW_PICTURE_BIG:
                    $size = 1920;
                    break;
                case video_interface::PREVIEW_PICTURE_MEDIUM:
                    $size = 640;
                    break;
                case video_interface::PREVIEW_PICTURE_BIG_WITH_PLAY:
                    $size = 1920;
                    $withplay = true;
                    break;
                case video_interface::PREVIEW_PICTURE_MEDIUM_WITH_PLAY:
                    $size = 640;
                    $withplay = true;
                    break;

            }
        }

        $link = null;
        if (isset($pictures['sizes'])) {
            foreach ($pictures['sizes'] as $availablesize) {
                if ($availablesize['width'] == $size) {
                    if ($withplay) {
                        $link = $availablesize['link_with_play_button'];
                    } else {
                        $link = $availablesize['link'];
                    }
                    break;
                }
            }
        }

        if (!$link) {
            return null;
        }

        if (get_config('videotime', 'store_pictures')) {
            // Serve image from file system. If it doesn't exist, it will be pulled in on first request.
            return \moodle_url::make_pluginfile_url(
                \context_system::instance()->id,
                'videotimeplugin_repository',
                'pictures',
                $this->get_record()->id,
                '/',
                md5($link))->out(false);
        } else {
            return $link;
        }
    }

    /**
     * Get all pictures available for video.
     *
     * @return mixed
     */
    public function get_pictures() {
        return json_decode($this->get_record()->pictures, true);
    }

    /**
     * Get shortened version of description for display.
     *
     * @param string $description
     * @param int $maxlength
     * @return string
     */
    public function get_description_excerpt($description, $maxlength = 150) {
        if (strlen($description) > $maxlength) {
            $excerpt   = substr($description, 0, $maxlength - 3);
            $lastspace = strrpos($excerpt, ' ');
            $excerpt   = substr($excerpt, 0, $lastspace);
            $excerpt  .= '...';
        } else {
            $excerpt = $description;
        }

        return $excerpt;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     * @throws \dml_exception | \coding_exception
     */
    public function jsonSerialize() {
        $video = (array)$this->record;
        if ($albums = $this->get_albums()) {
            $video['albums'] = $albums;
        } else {
            $video['albums'] = [];
        }

        $video['tags'] = [];

        // Use tags associated with course module.
        if ($this->context->contextlevel == CONTEXT_MODULE && \core_tag_tag::is_enabled('core', 'course_modules')) {
            foreach (\core_tag_tag::get_item_tags('core', 'course_modules', $this->context->instanceid) as $tag) {
                $video['tags'][] = [
                    'uri' => $tag->get_view_url(),
                    'name' => $tag->get_display_name()
                ];
            }
        } else {
            // Fallback to Vimeo tags.
            if ($tags = json_decode($this->get_record()->tags, true)) {
                $video['tags'] = $tags;
            }
        }

        if ($this->get_record()->duration >= 3600) {
            $video['duration_formatted'] = gmdate('H:i:s', $this->get_record()->duration);
        } else {
            $video['duration_formatted'] = gmdate('i:s', $this->get_record()->duration);
        }

        // Description may be "null" from Vimeo.
        if ($video['description'] == 'null') {
            $video['description'] = '';
        }

        $video['description_excerpt'] = $this->get_description_excerpt($video['description']);
        $video['show_more_button'] = $video['description_excerpt'] != $video['description'];

        if ($this->get_record()->created_time) {
            try {
                $createdtime = \DateTime::createFromFormat(
                    \DateTime::ISO8601,
                    $this->get_record()->created_time,
                    new \DateTimeZone('UTC')
                );
                $createdtime->setTimezone(\core_date::get_user_timezone_object());
                $video['date_formatted'] = $createdtime->format('Y/m/d');
            } catch (\Exception $e) {
                // Date parsing error, ignore.
                debugging($e->getMessage());
                $video['date_formatted'] = '';
            }
        }

        $video['thumbnail_url'] = $this->get_thumbnail_url();
        $video['courses'] = $this->get_courses();
        $video['has_courses'] = count($video['courses']) > 0;
        $video['notprocessed'] = $this->get_record()->state == self::STATE_NOT_PROCESSED;

        return $video;
    }

    /**
     * Get description of data returned with web services.
     *
     * @return \external_description
     */
    public static function get_external_definition() {
        return new \external_single_structure([
            'name' => new \external_value(PARAM_TEXT, 'The video\'s title.'),
            'description' => new \external_value(PARAM_RAW, 'A brief explanation of the video\'s content.', VALUE_DEFAULT),
            'description_excerpt' => new \external_value(PARAM_RAW, 'Shortened version of description.', VALUE_DEFAULT),
            'show_more_button' => new \external_value(PARAM_BOOL, 'Description has more content than excerpt', VALUE_DEFAULT),
            'uri' => new \external_value(PARAM_TEXT, 'The video\'s canonical relative URI.', VALUE_DEFAULT),
            'duration' => new \external_value(PARAM_INT, 'The video\'s duration in seconds.', VALUE_DEFAULT, 0),
            'duration_formatted' => new \external_value(PARAM_TEXT, 'Display duration.', VALUE_DEFAULT),
            'link' => new \external_value(PARAM_URL, 'The link to the video.', VALUE_DEFAULT),
            'thumbnail_url' => new \external_value(PARAM_URL, '', VALUE_DEFAULT),
            'date_formatted' => new \external_value(PARAM_TEXT, 'When video was created', VALUE_DEFAULT),
            'albums' => new \external_multiple_structure(
                new \external_single_structure([
                    'uri' => new \external_value(PARAM_TEXT, 'The album\'s URI.'),
                    'name' => new \external_value(PARAM_TEXT, 'The album\'s display name.')
                ]), 'An album is a collection of videos for public or private sharing.', VALUE_DEFAULT, []
            ),
            'tags' => new \external_multiple_structure(
                new \external_single_structure([
                    'uri' => new \external_value(PARAM_TEXT, 'The tag\'s URI.'),
                    'name' => new \external_value(PARAM_TEXT, 'The tag\'s display name.')
                ]), 'Tags are pieces of metadata for categorizing or labeling videos.', VALUE_DEFAULT, []
            ),
            'courses' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'Course ID'),
                    'idnumber' => new \external_value(PARAM_TEXT, 'Course ID number', VALUE_DEFAULT),
                    'fullname' => new \external_value(PARAM_TEXT, 'Course full name', VALUE_DEFAULT),
                    'category' => new \external_value(PARAM_TEXT, 'Course category', VALUE_DEFAULT),
                    'activity_name' => new \external_value(PARAM_TEXT, 'Video Time instance name', VALUE_DEFAULT),
                    'activity_instance_id' => new \external_value(PARAM_INT, 'Video Time instance ID', VALUE_DEFAULT),
                    'activity_url' => new \external_value(PARAM_URL, 'Video Time instance link', VALUE_DEFAULT)
                ]), 'Course/embed info where this video is used.', VALUE_DEFAULT, []
            ),
            'has_courses' => new \external_value(PARAM_BOOL, VALUE_DEFAULT, false),
            'notprocessed' => new \external_value(PARAM_BOOL)
        ]);
    }

    /**
     * Add a Vimeo video to the database to be processed.
     *
     * @param string $vimeourl
     * @throws \Vimeo\Exceptions\VimeoRequestException
     * @throws \dml_exception
     * @throws exception\api_not_authenticated
     * @throws exception\api_not_configured
     */
    public static function add_adhoc($vimeourl) {
        global $DB;

        try {
            if (!$video = $DB->get_record('videotime_vimeo_video', ['link' => $vimeourl])) {

                if ($videoid = mod_videotime_get_vimeo_id_from_link($vimeourl)) {

                    $api = new \videotimeplugin_repository\api();
                    $response = $api->request('/videos/' . $videoid);
                    if ($response['status'] == 200) {
                        $record = new \stdClass();

                        $record->name = $response['body']['name'];
                        $record->uri = $response['body']['uri'];
                        $record->link = $response['body']['link'];
                        $modifieddate = \DateTime::createFromFormat(\DateTime::ISO8601, $response['body']['modified_time']);
                        $modifieddate->setTimezone(new \DateTimeZone('UTC'));
                        $record->modified_time = $modifieddate->getTimestamp();
                        $record->state = video_interface::STATE_NOT_PROCESSED;
                        $record->source = video_interface::SOURCE_ADHOC;
                        $record->id = $DB->insert_record('videotime_vimeo_video', $record);
                    }
                }
            }
        } catch (\Exception $e) {
            if (!PHPUNIT_TEST && !defined('BEHAT_SITE_RUNNING')) {
                debugging($e->getMessage());
            }
        }
    }
}
