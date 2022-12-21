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
 * Delete session data task tests.
 *
 * @package   videotimeplugin_pro
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro;

use advanced_testcase;
use mod_videotime\videotime_instance;
use videotimeplugin_pro\task\delete_session_data;

/**
 * Class task_delete_session_data_test
 *
 * @group videotime
 * @group videotimeplugin_pro
 * @group task_delete_session_data_test
 * @covers \videotimeplugin_pro\task\delete_session_data
 */
class task_delete_session_data_test extends advanced_testcase {

    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $instancerecord;

    /** @var stdClass */
    private $user1;

    /** @var stdClass */
    private $user2;

    /**
     * @var videotime_instance
     */
    private $videotimeinstance;

    public function setUp(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course->id);
        $this->instancerecord = $this->getDataGenerator()->create_module('videotime', [
            'course' => $this->course->id,
        ]);
        $this->videotimeinstance = videotime_instance::instance_by_id($this->instancerecord->id);

        external::get_new_session($this->videotimeinstance->get_cm()->id, $this->user1->id);
        external::get_new_session($this->videotimeinstance->get_cm()->id, $this->user2->id);
    }

    protected function tearDown(): void {
        $this->course = null;
        $this->instancerecord = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->videotimeinstance = null;
    }

    public function test_execute(): void {
        $task = new delete_session_data();
        $task->set_custom_data(['user_id' => $this->user1->id, 'module_id' => $this->videotimeinstance->get_cm()->id]);
        $task->execute();

        $sessions1 = module_sessions::get($this->videotimeinstance->get_cm()->id, $this->user1->id);
        $sessions2 = module_sessions::get($this->videotimeinstance->get_cm()->id, $this->user2->id);

        $this->assertEquals(0, $sessions1->get_views(), 'Ensure user1 session was deleted.');
        $this->assertEquals(1, $sessions2->get_views(), 'Ensure user2 session NOT was deleted.');
    }
}
