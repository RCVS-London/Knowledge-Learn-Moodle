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
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_videotimeplugin_repository_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019090801) {

        // Define table videotime_vimeo_video to be created.
        $table = new xmldb_table('videotime_vimeo_video');

        // Adding fields to table videotime_vimeo_video.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('categories', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('config_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('content_rating', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('context', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('created_time', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('embed', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('height', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('last_user_action_event_date', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('license', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('link', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('modified_time', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '500', null, null, null, null);
        $table->add_field('parent_folder', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('pictures', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('privacy', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('release_time', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('resource_key', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('stats', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('transcode', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('upload', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('uri', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('user', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('width', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table videotime_vimeo_video.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for videotime_vimeo_video.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019090801, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2019091500) {

        // Changing type of field modified_time on table videotime_vimeo_video to int.
        $table = new xmldb_table('videotime_vimeo_video');
        $field = new xmldb_field('modified_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'metadata');

        // Launch change of type for field modified_time.
        $dbman->change_field_type($table, $field);

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019091500, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2019091602) {

        // Define field state to be added to videotime_vimeo_video.
        $table = new xmldb_table('videotime_vimeo_video');
        $field = new xmldb_field('state', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'width');

        // Conditionally launch add field state.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019091602, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2019091700) {

        // Define field albums to be added to videotime_vimeo_video.
        $table = new xmldb_table('videotime_vimeo_video');
        $field = new xmldb_field('albums', XMLDB_TYPE_TEXT, null, null, null, null, null, 'state');

        // Conditionally launch add field albums.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019091700, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2019092100) {

        // Define table videotime_vimeo_album to be created.
        $table = new xmldb_table('videotime_vimeo_album');

        // Adding fields to table videotime_vimeo_album.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('uri', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table videotime_vimeo_album.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table videotime_vimeo_album.
        $table->add_index('uri', XMLDB_INDEX_UNIQUE, array('uri'));

        // Conditionally launch create table for videotime_vimeo_album.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table videotime_vimeo_video_album to be created.
        $table = new xmldb_table('videotime_vimeo_video_album');

        // Adding fields to table videotime_vimeo_video_album.
        $table->add_field('video_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('album_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table videotime_vimeo_video_album.
        $table->add_key('video_id', XMLDB_KEY_FOREIGN, array('video_id'), 'videotime_vimeo_video', array('id'));
        $table->add_key('album_id', XMLDB_KEY_FOREIGN, array('album_id'), 'videotime_vimeo_album', array('id'));

        // Adding indexes to table videotime_vimeo_video_album.
        $table->add_index('video_album_assignment', XMLDB_INDEX_UNIQUE, array('video_id', 'album_id'));

        // Conditionally launch create table for videotime_vimeo_video_album.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019092100, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2019092200) {

        // Define table videotime_vimeo_tag to be created.
        $table = new xmldb_table('videotime_vimeo_tag');

        // Adding fields to table videotime_vimeo_tag.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('uri', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table videotime_vimeo_tag.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table videotime_vimeo_tag.
        $table->add_index('uri', XMLDB_INDEX_UNIQUE, array('uri'));

        // Conditionally launch create table for videotime_vimeo_tag.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table videotime_vimeo_video_tag to be created.
        $table = new xmldb_table('videotime_vimeo_video_tag');

        // Adding fields to table videotime_vimeo_video_tag.
        $table->add_field('video_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tag_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table videotime_vimeo_video_tag.
        $table->add_key('video_id', XMLDB_KEY_FOREIGN, array('video_id'), 'videotime_vimeo_video', array('id'));
        $table->add_key('tag_id', XMLDB_KEY_FOREIGN, array('tag_id'), 'videotime_vimeo_tag', array('id'));

        // Adding indexes to table videotime_vimeo_video_tag.
        $table->add_index('video_tag_assignment', XMLDB_INDEX_UNIQUE, array('video_id', 'tag_id'));

        // Conditionally launch create table for videotime_vimeo_video_tag.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2019092200, 'videotimeplugin', 'repository');
    }

    if ($oldversion < 2020041200) {

        // Define field source to be added to videotime_vimeo_video.
        $table = new xmldb_table('videotime_vimeo_video');
        $field = new xmldb_field('source', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'id');

        // Conditionally launch add field source.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Repository savepoint reached.
        upgrade_plugin_savepoint(true, 2020041200, 'videotimeplugin', 'repository');
    }

    return true;
}
