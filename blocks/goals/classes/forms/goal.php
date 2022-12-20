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
use block_goals\models;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/classes/form/persistent.php');

/**
 * Class category
 *
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class goal extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'block_goals\\models\\goal';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('action');

    /**
     * Define the form.
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        //if ($this->isadding) {
        //    $submitlabal = get_string('addnewgoal', 'block_goals');
        //}


        // Add some extra hidden fields.

        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $mform->setConstant('action', $this->_customdata['action']);

        // type
        $choices = [
            models\goal::TYPE_INDIVIDUAL => get_string('individual', 'block_goals'),
            models\goal::TYPE_TEAM => get_string('team', 'block_goals'),
            models\goal::TYPE_TEMPLATE => get_string('template', 'block_goals')
        ];
        $mform->addElement('select', 'type', get_string('type', 'block_goals'), $choices);

        // userid
        $usertotal = $DB->count_records('user',
            array('deleted' => 0, 'suspended' => 0, 'confirmed' => 1));

        if ($usertotal < 5000) {
            list($sort, $params) = users_order_by_sql('u');
            // User searchable selector - return users who are confirmed, not deleted, not suspended and not a guest.
            $sql = 'SELECT u.id, ' . get_all_user_name_fields(true, 'u') . '
                        FROM {user} u
                        WHERE u.deleted = 0
                        AND u.confirmed = 1
                        AND u.suspended = 0
                        AND u.id != :siteguestid
                        ORDER BY ' . $sort;
            $params['siteguestid'] = $CFG->siteguest;
            // LEFT JOIN {goal_teammembers} gt ON gt.userid = u.id
            // $params['teamid'] = $CFG->siteguest;
            //                        -- AND (gt.teamid IS NULL OR gt.teamid != :teamid)
            $users = $DB->get_records_sql($sql, $params);
            $options = array();
            foreach ($users as $userid => $user) {
                $options[$userid] = fullname($user);
            }
            $mform->addElement('searchableselector', 'userid', get_string('user'), $options);
            $mform->setType('userid', PARAM_INT);
        } else {
            //simple text box for username or user id (if two username exists, a form error is displayed)
            $mform->addElement('text', 'userid', get_string('usernameorid', 'webservice'));
            $mform->setType('userid', PARAM_RAW_TRIMMED);
        }

        // teamid
        $teams = $DB->get_records_menu('goal_team', null, 'id ASC', 'id, name');
        // Concatenate arrays, maintaining index.
        $choices = [0 => 'None'] + $teams;
        $mform->addElement('select', 'teamid', get_string('team', 'block_goals'), $choices);
        $mform->setType('teamid', PARAM_INT);

        // verbs
        $verbs = $DB->get_records_menu('goal_verbs', null, 'sortorder', 'id, verb');
        $choices = array_map('format_string', $verbs);
        $mform->addElement('select', 'verbid', get_string('verb', 'block_goals'), $choices);
        $mform->setType('verbid', PARAM_INT);

        // amount
        $mform->addElement('text', 'amount', get_string('amount', 'block_goals'), 'maxlength="4" size="4"');
        $mform->setType('amount', PARAM_INT);

        // percentage flag
        $mform->addElement('selectyesno', 'percentageflag', get_string('percentageflag', 'block_goals'));
        $mform->setType('percentageflag', PARAM_BOOL);

        // objective
        $mform->addElement('text', 'objective', get_string('objective', 'block_goals'), array('size' => 60));
        $mform->setType('objective', PARAM_RAW);
        //$mform->addRule('objective', null, 'required');

        //due date
        $mform->addElement('date_selector', 'duedate', get_string('duedate', 'block_goals'), array('optional' => false));

        // template flag
        // $mform->addElement('selectyesno', 'template', get_string('template', 'block_goals'));
        // $mform->setType('template', PARAM_BOOL);

        // Goal Text
        $mform->addElement('textarea', 'goaltext', get_string('goaltext', 'block_goals'), array('rows' => 6, 'cols' => 80, 'class' => 'smalltext'));
        $mform->setType('goaltext', PARAM_RAW);
        //$mform->addRule('goaltext', null, 'required');

        // progress
        $mform->addElement('text', 'progress', get_string('progress', 'block_goals'), 'maxlength="4" size="4"');
        $mform->setType('progress', PARAM_INT);
        //$mform->addRule('progress', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'description', get_string('goaldescription', 'block_goals'), ['rows' => 6, 'cols' => 100], ['autosave' => false]);
        $mform->setType('description', PARAM_RAW);
        //$mform->addRule('description', get_string('required'), 'required', null, 'client');

        $goalfilters = null;
        if (!empty($this->_customdata['goalfilters'])) {
            $goalfilters = $this->_customdata['goalfilters'];
        }

        $mform->addElement('selectyesno', 'hidden', get_string('hidden', 'block_goals'));
        $mform->setType('hidden', PARAM_BOOL);

        // Add filters
        if (!empty($this->_customdata['categories'])) {
            $categories = $this->_customdata['categories'];
            foreach ($categories as $category) {
                $mform->addElement('header', 'category_' . $category->get('id'), $category->get('name'));
                $filters = $category->get_filters();
                foreach ($filters as $filter) {
                    $mform->addElement('advcheckbox', 'filter_' . $filter->get('id'), $filter->get('name'), $filter->get('name'), null, [0, 1]);

                    if (!empty($goalfilters)) {
                        foreach ($goalfilters as $goalfilter) {
                            if ($goalfilter->get('filterid') == $filter->get('id')) {
                                $mform->setDefault('filter_' . $filter->get('id'), $goalfilter->get('booleanvalue'));
                            }
                        }
                    }
                    //$this->foreignfields[] = 'filter_' . $filter->get('id');
                    //parent::$foreignfields[] = 'filter_' . $filter->get('id');
                    static::$foreignfields[] = 'filter_' . $filter->get('id');
                }
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