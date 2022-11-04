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
 * Upgrade script for the Video Time Pro.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_videotimeplugin_pro_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018080204) {

        // Define table videotime_session to be created.
        $table = new xmldb_table('videotime_session');

        // Adding fields to table videotime_session.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('module_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table videotime_session.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table videotime_session.
        $table->add_index('module_user', XMLDB_INDEX_NOTUNIQUE, array('module_id', 'user_id'));

        // Conditionally launch create table for videotime_session.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080204, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2018080205) {

        // Define field state to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('state', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timestarted');

        // Conditionally launch add field state.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080205, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2018080209) {

        // Define field percent to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('percent', XMLDB_TYPE_NUMBER, '5, 3', null, null, null, null, 'state');

        // Conditionally launch add field percent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080209, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2019081903) {

        // Define field current_time to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('current_watch_time', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'percent');

        // Conditionally launch add field current_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2019081903, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2020071200) {

        // Rename field percent on table videotime_session to percent_watch.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('percent', XMLDB_TYPE_NUMBER, '5, 3', null, null, null, null, 'state');

        // Launch rename field percent_watch.
        $dbman->rename_field($table, $field, 'percent_watch');

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2020071200, 'videotimeplugin', 'pro');
    }

    return true;
}
