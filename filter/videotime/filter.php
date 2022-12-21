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
 * Video Time filter.
 *
 * @package   filter_videotime
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videotime\videotime_instance;

/**
 * Allows embedding of Video Time activities.
 *
 * @package   filter_videotime
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_videotime extends moodle_text_filter {

    /**
     * Function filter replaces Video Time shortcodes with vimeo embeds.
     *
     * @param  string $text    HTML content to process
     * @param  array  $options options passed to the filters
     * @return string
     */
    public function filter($text, array $options = array()) {
        global $PAGE, $USER, $OUTPUT;

        if (!is_string($text) or empty($text)) {
            // Non string data can not be filtered anyway.
            return $text;
        }

        // Improve performance: check if any codes exist in the text before parsing.
        if (strpos($text, '[videotime') === false) {
            return $text;
        }

        $pattern = '/\[videotime(.*?)\]/';

        try {
            preg_match_all($pattern, $text, $matches);

            $videotimetags = [];

            if ($matches[0]) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    if (isset($matches[1][$i])) {
                        // Parse attribute.
                        $x = new SimpleXMLElement('<element ' . $matches[1][$i] . '/>');
                        $cmid = (string)$x->attributes()['cmid'];

                        $videotimetags[] = [
                            'replace' => $matches[0][$i],
                            'cmid' => $cmid
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \core\notification::error(get_string('parsingerror', 'filter_videotime') . '<br>' . $e->getMessage());
        }

        $renderer = $PAGE->get_renderer('mod_videotime');

        foreach ($videotimetags as $key => &$videotimetag) {
            try {
                if (!$cm = get_coursemodule_from_id('videotime', $videotimetag['cmid'])) {
                    $content = $OUTPUT->notification(get_string('vimeo_url_missing', 'videotime'));
                } else {
                    $context = context_course::instance($cm->course);

                    $canviewcourse = has_capability('moodle/course:view', $context) ||
                        is_enrolled(context_course::instance($cm->course), $USER, '', true);

                    // Check if user can access activity.
                    if (!$canviewcourse || !cm_info::create($cm)->uservisible) {
                        $content = null;
                    } else if (
                        get_config('filter_videotime', 'disableifediting')
                        && $PAGE->user_is_editing()
                    ) {
                        $content = $OUTPUT->notification(
                            get_string('videodisabled', 'filter_videotime', $cm->name),
                            \core\output\notification::NOTIFY_SUCCESS
                        );
                    } else {
                        $instance = videotime_instance::instance_by_id($cm->instance);
                        $instance->set_embed(true);

                        $defaultrenderer = $renderer;

                        // Allow any subplugin to override video time instance output.
                        foreach (\core_component::get_component_classes_in_namespace(
                            null,
                            'videotime\\instance'
                        ) as $fullclassname => $classpath) {
                            if (is_subclass_of($fullclassname, videotime_instance::class)) {
                                if ($override = $fullclassname::get_instance($instance->id)) {
                                    $instance = $override;
                                }
                                if ($override = $fullclassname::get_renderer($instance->id)) {
                                    $renderer = $override;
                                }
                            }
                        }

                        $content = $renderer->render($instance);

                        // Set renderer back to default if override was used.
                        $renderer = $defaultrenderer;
                    }
                }
            } catch (\Exception $e) {
                $content = $OUTPUT->notification(get_string('parsingerror', 'filter_videotime') . '<br>' . $e->getMessage());
            }

            // Replace the shortcode with rendering.
            $pos = strpos($text, $videotimetag['replace']);
            if ($pos !== false) {
                $text = substr_replace($text, $content, $pos, strlen($videotimetag['replace']));
            }
        }

        return $text;
    }
}
