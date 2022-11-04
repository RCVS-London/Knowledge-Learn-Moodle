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
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use videotimeplugin_pro\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/webservice/tests/helpers.php");

/**
 * Class external_test
 *
 * @group videotime
 */
class external_test extends externallib_advanced_testcase {

    private $course;
    private $videotimeinstance;
    private $student;

    public function setUp()
    {
        $this->course = $this->getDataGenerator()->create_course();
        $this->videotimeinstance = $this->getDataGenerator()->create_module('videotime', [
            'course' => $this->course->id,
            'resume_playback' => true
        ]);
        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id);

        parent::setUp();
    }

    public function tearDown()
    {
        $this->course = null;
        $this->videotimeinstance = null;
        $this->student = null;
    }

    public function test_get_new_session() {
        $this->resetAfterTest();
        $this->assertTrue(true);

        $this->setUser($this->student);

        $sessiondata = external::get_new_session($this->videotimeinstance->cmid);

        $this->assertEquals($this->student->id, $sessiondata['user_id']);
        $this->assertEquals($this->videotimeinstance->cmid, $sessiondata['module_id']);
    }

    public function test_get_resume_time() {
        $this->resetAfterTest();
        $this->assertTrue(true);

        $this->setUser($this->student);

        $session = external::get_new_session($this->videotimeinstance->cmid);
        external::set_session_current_time($session['id'], 60);

        $this->assertEquals(60, external::get_resume_time($this->videotimeinstance->cmid)['seconds']);
    }

}