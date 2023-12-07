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
 * Displays a thumbnail and info about a Video Time instance.
 *
 * @package     videotimeplugin_repository
 * @copyright   2020 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository\output;

use mod_videotime\videotime_instance;
use videotimeplugin_repository\api;
use videotimeplugin_repository\video;

/**
 * Displays a thumbnail and info about a Video Time instance.
 *
 * @package videotimeplugin_repository
 */
class video_preview implements \templatable, \renderable {
    /**
     * @var videotime_instance
     */
    private $instance;

    /**
     * @var video|null
     */
    private $video;

    /**
     * @var int
     */
    private $userid;

    /**
     * Constructor
     *
     * @param videotime_instance $instance
     * @param int $userid
     */
    public function __construct(videotime_instance $instance, int $userid) {
        global $DB;

        $this->instance = $instance;
        $this->userid = $userid;

        if ($videorecord = $DB->get_record('videotime_vimeo_video', ['link' => $this->instance->vimeo_url])) {
            $this->video = video::create($videorecord, $this->instance->get_context());
        } else if ($videoid = mod_videotime_get_vimeo_id_from_link($this->instance->vimeo_url)) {
            video::add_adhoc($this->instance->vimeo_url);
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param \renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass|array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $DB;

        $sessions = \videotimeplugin_pro\module_sessions::get($this->instance->get_cm()->id, $this->userid);

        $modicons = '';
        if ($course = $DB->get_record('course', ['id' => $this->instance->get_cm()->course])) {
            $completioninfo = new \completion_info($course);
            $cm = \cm_info::create($this->instance->get_cm());
            $name = $cm->name; // Trigger internal processing of cm.
            if (!class_exists('\\core_course\\output\\activity_information')) {
                $modicons = $PAGE->get_renderer('course')->course_section_cm_completion($course, $completioninfo,
                    $cm);
            }
        }

        $context = [
            'module_sessions' => $sessions->jsonSerialize(),
            'url' => new \moodle_url('/mod/videotime/view.php', ['id' => $this->instance->get_cm()->id]),
            'instance' => $this->instance->to_record(),
            'modicons' => $modicons
        ];

        if ($this->video) {
            $context['video'] = $this->video->jsonSerialize();
        }

        return $context;
    }

    /**
     * Get component name of template when rendering.
     *
     * @return string
     */
    public function get_component_name() {
        return 'videotimeplugin_repository';
    }
}
