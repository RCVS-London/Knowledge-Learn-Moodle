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
 * Manage goal verbs.
 * @package block_goals
 * @copyright  2022 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_goals\controllers\verb;
use block_goals\models;

require '../../../config.php';

global $CFG, $PAGE;
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/blocks/goals/lib.php';

$PAGE->navbar->add(get_string('goalsadmin', 'block_goals'), new moodle_url('/blocks/goals/admin/index.php'));
$PAGE->navbar->add('Verbs', new moodle_url('/blocks/goals/admin/verbs.php'));
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/goals/admin/verbs.php', ['action' => $action]));
//admin_externalpage_setup('verbs');

enforce_security(true);

switch ($action) {
    case 'createverb':
        $id = optional_param('id', 0, PARAM_INT);
        verb::display($id);
        break;

    case 'editverb':
        $id = optional_param('id', 0, PARAM_INT);
        verb::process($id);
        break;

    case 'showverb':
        $id = required_param('id', PARAM_INT);
        verb::show($id);
        break;

    case 'hideverb':
        $id = required_param('id', PARAM_INT);
        verb::hide($id);
        break;
        
    case 'deleteverb':
        $id = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', '0', PARAM_INT);

        if ($confirm && confirm_sesskey()) {
            verb::delete($id);
        } else {
            global $OUTPUT;

            $verb = new models\verb($id);

            $yesno = [0 => get_string('no'), 1 => get_string('yes')];

            $output = 'Confirm delete of verb?<br>';
            $output .= '<dl>';
            $output .= '<dt>Verb</dt><dd>' . $verb->get('verb') . '</dd>';
            $output .= '<dt>Hidden</dt><dd>' . $yesno[$verb->get('hidden')] . '</dd>';
            $output .= '</dl>';

            $continue = new moodle_url('', ['action' => 'deleteverb', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
            // $OUTPUT->confirm($message, $continue, $cancel)
            $confirm = $OUTPUT->confirm($output, $continue, new moodle_url('', []));
            verb::renderpage($confirm);
        }
        break;

    case 'moveverb':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);
        verb::move($id, $dir);
        break;

    default:
        verb::renderpage();
}


