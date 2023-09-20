<?php


defined('MOODLE_INTERNAL') || die();
// These are Moodle's Event_2 implementation
$observers = array(
    //Users
    array(
        'eventname' => '\core\event\user_created',
        'callback' => '\local_rcvskantispam\local_rcvskantispam_observer::user_created',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_rcvskantispam\local_rcvskantispam_observer::user_loggedin',
    ),
    
);