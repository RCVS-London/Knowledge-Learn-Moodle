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
 * iMIS webservices installer upgrade code
 *
 * @package    auth_imis
 * @copyright  2021 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade auth_imis.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_imis_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2021042207) {

        $imisauth = get_auth_plugin('imis');
        $emailprefix = $imisauth->get_emailprefix();

        // Convert imis_webservices users to imis users
        // Update email addresses to add vetgdp_ prefix
        $sql = "
            UPDATE {user}
            SET auth = :imis, email = concat('{$emailprefix}', email)
            WHERE auth = :imis_webservices
            ";

        
        $params = [
            'imis' => 'imis',
            'imis_webservices' => 'imis_webservices'
        ];

        $result = $DB->execute($sql, $params);
        if (!$result) {
            return false;
        }

        // Disable imis_webservices authentication plugin if enabled.
        get_enabled_auth_plugins(true); // fix the list of enabled auths
        if (empty($CFG->auth)) {
            $authsenabled = array();
        } else {
            $authsenabled = explode(',', $CFG->auth);
        }
        // remove from enabled list
        $key = array_search('imis_webservices', $authsenabled);
        if ($key !== false) {
            unset($authsenabled[$key]);
            set_config('auth', implode(',', $authsenabled));
        }

        upgrade_plugin_savepoint(true, 2021042207, 'auth', 'imis');
    }

    return true;
}
