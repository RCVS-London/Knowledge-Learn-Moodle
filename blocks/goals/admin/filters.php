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
 * Manage goal filters.
 * @package block_goals
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_goals\controllers\category;
use block_goals\controllers\filter;
use block_goals\models;

require '../../../config.php';

global $CFG, $PAGE;
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goalsadmin', 'block_goals'), new moodle_url('/blocks/goals/admin/index.php'));
$PAGE->navbar->add('Filters', new moodle_url('/blocks/goals/admin/filters.php'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/admin/filters.php', ['action' => $action]));
///admin_externalpage_setup('filters');

enforce_security(true);

switch ($action) {
    case 'createcategory':
        $id = optional_param('id', 0, PARAM_INT);
        category::display($id);
        break;

    case 'editcategory':
        $id = optional_param('id', 0, PARAM_INT);
        category::process($id);
        break;

    case 'deletecategory':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm && confirm_sesskey()) {
            category::delete($id);
        } else {
            global $OUTPUT;

            $category = new models\category($id);
            $yesno = [0 => get_string('no'), 1 => get_string('yes')];

            $output = 'Confirm delete of category?<br>';
            $output .= '<dl>';
            $output .= '<dt>Category</dt><dd>' . $category->get('name') . '</dd>';
            $output .= '<dt>Description</dt><dd>' . format_text($category->get('description'), $category->get('descriptionformat')) . '</dd>';
            $output .= '<dt>Example</dt><dd>' . format_text($category->get('example'), $category->get('exampleformat')) . '</dd>';
            $output .= '<dt>Category Filters</dt><dd>' . $category->get_categoryfilternames() . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deletecategory', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            category::renderpage($confirm);
        }
        break;

    case 'movecategory':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);
        category::move($id, $dir);
        break;

    case 'createfilter':
        $id = optional_param('id', 0, PARAM_INT);
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        filter::display($id, $categoryid);
        break;

    case 'editfilter':
        $id = optional_param('id', 0, PARAM_INT);
        filter::process($id);
        break;

    case 'deletefilter':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm && confirm_sesskey()) {
            filter::delete($id);
        } else {
            global $OUTPUT;

            $filter = new models\filter($id);
            $output = 'Confirm delete of filter?<br>';
            $output .= '<dl>';
            $output .= '<dt>Filter</dt><dd>' . $filter->get('name') . '</dd>';
            $output .= '<dt>Icon</dt><dd>' . $filter->get('faicon') . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deletefilter', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            category::renderpage($confirm);
        }
        break;

    case 'movefilter':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);
        filter::move($id, $dir);
        break;

    default:
        category::renderpage();
}


