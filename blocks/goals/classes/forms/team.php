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
 * Goals Block
 *
 * @package    block_goals
 * @copyright  2022 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_goals\forms;
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/classes/form/persistent.php');

/**
 * Class team
 *
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class team extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'block_goals\\models\\team';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('action');

    /**
     * Define the form.
     */
    public function definition() {
        global $USER, $OUTPUT;
        $mform = $this->_form;

        $id = 0;
        if (!empty($this->_customdata['id'])) {
            $id = $this->_customdata['id'];
        }
        $isediting = false;
        if (!empty($id)) {
            $isediting = true;
        }

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance(), $USER);
        if ($managegoals) {
            $mform->addElement('html', $OUTPUT->box(get_string('managegoals', 'block_goals')));
        }

        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $mform->setConstant('action', $this->_customdata['action']);

        $mform->addElement('text', 'name', get_string('teamname', 'block_goals'), 'maxlength="64" size="64"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        /*
          TODO:
        $editoroptions = array(
            'maxfiles'  => EDITOR_UNLIMITED_FILES,
            'maxbytes'  => $CFG->maxbytes,
            'trusttext' => false,
            'context'   => \context_system::instance(),
            'subdirs'   => false,
            'autosave' => false
        );
        $editoroptions
        */
        $mform->addElement('editor', 'description', get_string('teamdescription', 'block_goals'), ['rows' => 6, 'cols' => 100], ['autosave' => false]);
        $mform->setType('description', PARAM_RAW);

        $managegoals = has_capability('block/goals:managegoals', \context_system::instance(), $USER);
        if ($managegoals) {
            $mform->addElement('selectyesno', 'hidden', get_string('hidden', 'block_goals'));
            $mform->setType('hidden', PARAM_BOOL);
        } else {
            if (!$isediting) {
                $mform->addElement('hidden', 'hidden', get_string('hidden', 'block_goals'), get_string('no'));
            }
        }

        $this->add_action_buttons(true);

    }

    function definition_after_data() {
        $mform = $this->_form;
        if (!empty($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);

        }
    }
}