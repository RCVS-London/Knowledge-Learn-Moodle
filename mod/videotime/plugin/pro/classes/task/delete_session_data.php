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
 * Delete user session data for a particular module.
 *
 * @package     videotimeplugin_pro
 * @copyright   2020 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_pro\task;

use videotimeplugin_pro\session;

/**
 * Delete user session data for a particular module.
 *
 * @copyright   2020 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_session_data extends \core\task\adhoc_task {
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        if (!isset($data->user_id) || !isset($data->module_id)) {
            debug('Task expects user_id and module_id in custom data.');
            return;
        }

        $DB->execute('DELETE FROM {' . session::TABLE . '} where user_id = :user_id AND module_id = :module_id', [
            'user_id' => $data->user_id,
            'module_id' => $data->module_id
        ]);
    }
}
