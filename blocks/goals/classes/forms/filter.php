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
 * Class filter
 *
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'block_goals\\models\\filter';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('action');

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $mform->setConstant('action', $this->_customdata['action']);

        global $DB;
        $categories = $DB->get_records_menu('goal_filtercategories', null, 'sortorder ASC', 'id, name');
        $choices = array_map('format_string', $categories);
        $mform->addElement('select', 'categoryid', get_string('category', 'block_goals'), $choices);

        $mform->addElement('text', 'name', get_string('filtername', 'block_goals'), 'maxlength="64" size="64"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'faicon', get_string('faicon', 'block_goals'), 'maxlength="64" size="64"');
        $mform->setType('faicon', PARAM_TEXT);

        $this->add_action_buttons(true);
    }

    function definition_after_data() {
        $mform = $this->_form;

        if (!empty($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }
        if (!empty($this->_customdata['sortorder'])) {
            $mform->addElement('hidden', 'sortorder', $this->_customdata['sortorder']);
            $mform->setType('sortorder', PARAM_INT);
        }

        if (!empty($this->_customdata['categoryid'])) {
            $mform->getElement('categoryid')->setSelected($this->_customdata['categoryid']);
        }
    }

}