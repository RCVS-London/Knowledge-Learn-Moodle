<?php


defined('MOODLE_INTERNAL') || die();
// These are Moodle's Event_2 implementation
$observers = array(
    //Users
    array(
        'eventname' => '\core\event\user_created',
        'callback' => '\local_cleantalk_antispam\local_cleantalk_antispam_observer::user_created',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_cleantalk_antispam\local_cleantalk_antispam_observer::user_loggedin',
    ),
    
);