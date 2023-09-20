<?php
defined('MOODLE_INTERNAL') || die();

class local_rcvskantispam {
    global $DB;
    // Define your plugin's functionality here.
    //echo("<br><h1>rcvskantispam running.  In rcvskantispam.php</h1>.");
/*
    //Add blocked ip to list
    if ($user_ip_is_spam) {
        $blocked_ip_obj = $DB->get_record('config','name','blockedip');
        $update_blocked_ip = new \stdClass;
        $update_blocked_ip->id = $blocked_ip_obj->id;
        $update_blocked_ip->blocked_ip_list = $blocked_ip_obj->blocked_ip_list.'\n'.$blocked_ip;
        if ($update_success = $DB->update_record('',$update_blocked_ip)) {
            echo "Blocked IPs list update succeeded.\n";
        } else {
            echo "Blocked IPs list update failed.\n";
        }
    }
    
    //Add blocked ip to list
     if ($user_email_is_spam == true) {
        $blocked_ip_obj = $DB->get_record('config','name','denyemailaddresses');
        $update_blocked_email = new \stdClass;
        $update_blocked_email->id = $blocked_email_obj->id;
        $update_blocked_email->blocked_emails_list = $blocked_email_obj->blocked_email_list.','.$email;
        if ($update_success = $DB->update_record('',$update_blocked_email)) {
            echo "Blocked IPs list update succeeded.\n";
        } else {
            echo "Blocked IPs list update failed.\n";
        }
    }
    */
}
