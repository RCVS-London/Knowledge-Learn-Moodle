<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_cleantalk_antispam_upgrade($oldversion) {
    global $DB;
    //  database upgrade logic goes here.

    if ($oldversion < 2023100400) {
        // Rename the field "tiletopleftthistile" to "tileicon".
        // The latter is much simpler and the former was only used for legacy reasons.
        $user_info_field = new stdClass();
        $user_info_field->shortname = 'cleantalk_checked';
        $user_info_field->name = 'Cleantalk Checked';
        $user_info_field->datatype = 'datetime';
        $user_info_field->description = 'Cleantalk Checked';
        $DB->insert_record('user_info_field',$user_info_field);

    }
    return true;
}