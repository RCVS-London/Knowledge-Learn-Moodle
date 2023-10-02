<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_cleantalk_antispam_upgrade($oldversion) {
    global $DB;
    //  database upgrade logic goes here.

    if ($oldversion < 2023100270) {
        // Rename the field "tiletopleftthistile" to "tileicon".
        // The latter is much simpler and the former was only used for legacy reasons.
        $user_info_field1 = new stdClass();
        $user_info_field1->shortname = 'cleantalk_whitelist';
        $user_info_field1->name = 'Cleantalk Whitelist';
        $user_info_field1->datatype = 'checkbox';
        $user_info_field1->description = 'Cleantalk email whitelist';
        $DB->insert_record('user_info_field',$user_info_field1);

        $user_info_field2 = new stdClass();
        $user_info_field2->shortname = 'cleantalk_checked';
        $user_info_field2->name = 'Cleantalk Checked';
        $user_info_field2->datatype = 'datetime';
        $user_info_field2->description = 'Cleantalk Checked';
        $DB->insert_record('user_info_field',$user_info_field2);
   
   ;

        
    }
    return true;
}