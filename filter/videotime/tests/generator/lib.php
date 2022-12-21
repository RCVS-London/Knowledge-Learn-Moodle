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
 * Plugin version and other meta-data are defined here.
 *
 * @package     filter_videotime
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/label/tests/generator/lib.php');

/**
 * VideoTime module data generator class
 *
 * @package     filter_videotime
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_videotime_generator extends component_generator_base {

    /** Creates an instance of label with video time tag testing purposes.
     *
     * @param array|stdClass $record data for module being generated. Requires 'name'
     *     of a Video Time instance
     * @param null|array $options general options for course module. Since 2.6 it is
     *     possible to omit this argument by merging options into $record
     * @return stdClass record from module-defined table with additional field
     *     cmid (corresponding id in course_modules table)
     */
    public function create_label($record, $options = []) {
        global $DB;

        $instance = $DB->get_record('videotime', ['name' => $record['name']]);
        $cm = get_coursemodule_from_instance('videotime', $instance->id);

        $newrecord = [
           'intro' => '[videotime cmid="' . $cm->id . '"]',
           'course' => $cm->course,
           'showdescription' => 1,
           'section' => $record['section'] ?? 1
        ];
        $options = [];

        $labelgenerator = new mod_label_generator($this->datagenerator);
        return $labelgenerator->create_instance($newrecord, $options);
    }
}
