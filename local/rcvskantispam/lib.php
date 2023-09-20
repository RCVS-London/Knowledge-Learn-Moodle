<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
defined('MOODLE_INTERNAL') || die();

// This function is called when a user is created.
// Wont be called any more.
function local_rcvskantispam_handle_user_created(core\event\user_created $event) {
    // Get the user data from the event.
    $user = $event->get_record_snapshot('user', $event->get_data());

    // Perform your custom logic here.
    // For example, you can check the user's email or IP address against a blacklist.

    // If you want to prevent the user from being created, you can throw an exception.
    // Example:
    throw new moodle_exception('registration_denied', 'local_rcvskantispam');
}

// This function is called when a user is authenticated (logs in).
// Wont be called any more.
function local_rcvskantispam_handle_user_authenticated(core\event\user_authenticated $event) {
    // Get the user data from the event.
    $user = $event->get_record_snapshot('user', $event->get_data());
    print_r($user);
    // Perform your custom logic here.
    // For example, you can check the user's IP address against a blacklist.

    // If you want to prevent the user from authenticating, you can throw an exception.
    // Example:
    throw new moodle_exception('authentication_denied', 'local_rcvskantispam');
}
