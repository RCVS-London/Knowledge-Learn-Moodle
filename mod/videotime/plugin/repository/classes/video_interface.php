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

namespace videotimeplugin_repository;

/**
 * Class representation of a video database record.
 *
 * @package videotimeplugin_repository
 */
interface video_interface
{
    /**
     * Fields that must be included with every video.
     */
    const REQUIRED_FIELDS = ['name', 'uri', 'link'];

    /**
     * Video has been discovered from Vimeo but all data has not been pulled yet.
     */
    const STATE_NOT_PROCESSED = 0;

    /**
     * Video data is complete. Extra data has been pulled (all fields, image(s), tags, etc).
     */
    const STATE_PROCESSED = 1;

    /**
     * Video was modified on Vimeo and needs processing again. Video is still valid in the interim.
     */
    const STATE_REPROCESS = 2;

    const PREVIEW_PICTURE_BIG = 1;
    const PREVIEW_PICTURE_MEDIUM = 2;
    const PREVIEW_PICTURE_BIG_WITH_PLAY = 3;
    const PREVIEW_PICTURE_MEDIUM_WITH_PLAY = 4;

    /**
     * Video originated from the authorized Vimeo account connected to Moodle.
     */
    const SOURCE_ACCOUNT = 1;

    /**
     * Video was added as a public video or does not belong to the connected Vimeo account.
     */
    const SOURCE_ADHOC = 2;

    /**
     * Create new video from database record.
     *
     * @param \stdClass $record
     * @return mixed
     */
    public static function create(\stdClass $record);

    /**
     * Get database record for this video.
     *
     * @return \stdClass
     */
    public function get_record();

    /**
     * Get all courses this video is used.
     *
     * @return \stdClass[]
     */
    public function get_courses();

    /**
     * Get URL to video thumbnail. Either stored or external.
     *
     * @return string
     */
    public function get_thumbnail_url();

    /**
     * Get shortened version of description for display.
     *
     * @param string $description
     * @param int $max_length
     * @return string
     */
    public function get_description_excerpt($description, $max_length = 200);

    /**
     * Get description of data returned with web services.
     *
     * @return \external_description
     */
    public static function get_external_definition();
}
