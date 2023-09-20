<?php

namespace local_rcvskantispam;

use stdClass;

class local_rcvskantispam_observer
{
    // Users observers
    // Get the data from the $_POST object as the event data doesn't have any user details from the DB in it at this time 
    public static function user_created(\core\event\user_created $event)
    {
        //error_log("user created - event picked up.",0);
        $sender_email = $_POST['email'];
        $sender_ip = $_SERVER['REMOTE_ADDR'];
        $api_key = self::getApiURL($sender_email, $sender_ip);
        // check if api key is not set- show error if not, or die?
        $api_results = self::callAPI("GET", $api_key, null);
        $isSpam = self::checkApiResults($api_results, $sender_ip, $sender_email);

        if ($isSpam) {
            error_log("user " . $sender_email . " was recognised as spam on IP: " . $sender_ip, 0);
            echo ("<BR><h1>SPAM</h1>");
            // throw new moodle_exception('authentication_denied', 'local_rcvskantispam');
            die();
        } else {
            echo ("<BR><h1>NOT SPAM</h1>");
        }
    }

    public static function user_loggedin(\core\event\user_loggedin $event)
    {
        //echo("user_authenticated");
        $event_data = $event->get_data();
        error_log("user logged in - event picked up.", 0);
        //echo("Username:".$event_data['other']['username']);
        $sender_email = $event_data['other']['username'];
        $sender_ip = $_SERVER['REMOTE_ADDR'];
        //echo("<br>IP:".$sender_ip);

        error_log(json_encode($event_data), 0);
        $api_key = self::getApiURL($sender_email, $sender_ip);
        // check if api key is not set- show error if not, or die?
        $api_results = self::callAPI("GET", $api_key, null);
        $isSpam = self::checkApiResults($api_results, $sender_ip, $sender_email);

        if ($isSpam) {
            echo ("<BR><h1>SPAM</h1>");
            error_log("user " . $sender_email . " was recognised as spam on IP: " . $sender_ip, 0);

            die();
        } else {
            echo ("<BR><h1>NOT SPAM</h1>");
            die(); // remove this (and the echo message when finished.)
        }
    }

    public static function checkApiResults($api_results, $sender_ip, $sender_email)
    {
        global $DB, $CFG;
        $response = json_decode($api_results, true);
        //echo("<br>Result<br>");
        //print_r($response);

        $data = $response['data'];
        $is_spam = false;
        if ($sender_ip != '') {
            $ip_data = $data[$sender_ip];

            // popular spam conditions for IP addresses
            if ($ip_data['spam_rate'] == 1) {
                echo ("<br><b>IP has a 100% spam rate.</b>");
                $is_spam = true;
            }
            if ($ip_data['appears'] == 1) {
                echo ("<br><b>IP appears in the spam blacklist.</b>");
                $is_spam = true;
            }
            if ($ip_data['in_antispam'] > 0) {
                echo ("<br><b>IP appears in the antispam blacklist.</b>");
                if ($ip_data['in_antispam_previous'] > 0) {
                    echo ("<br><b>IP has appeared previously in the antispam blacklist.</b>");
                } else {
                    echo ("<br><b>This is the first time this IP has been spotted in the antispam blacklist.</b>");
                }

                $is_spam = true;
            }

            if ($is_spam) {
                echo ("<br><h1>Spam IP.</h1>");
                //$blocked_ip_obj = $DB->get_record('config','name','blockedip');
                $blocked_ip_obj = $DB->get_record('config', array('name' => 'blockedip'));
                $update_blocked_ip = new \stdClass;
                $update_blocked_ip->id = $blocked_ip_obj->id;
                $update_blocked_ip->blocked_ip_list = $blocked_ip_obj->blocked_ip_list . '\n' . $sender_ip;
                if ($DB->update_record('config', $update_blocked_ip)) {
                    echo "Blocked IPs list update succeeded.\n";
                    error_log("IP:" . $sender_ip . "added to block list");
                } else {
                    error_log("IP:" . $sender_ip . " faled to add to block list");
                    echo "Blocked IPs list update failed.\n";
                }
            } else {
                echo ("<br><h1>IP is OK.</h1>");
            }
        }
        // just testing the blocked email list ... is it populated?  Can we avoid using $CFG?
        $blocked_email_obj = $DB->get_record('config', array('name' => 'denyemailaddresses'));
        print_r($blocked_email_obj);
        // end of debug

        // Check denied email addresses from the $CFG global
        $email_denied = false;
        if (!empty($CFG->denyemailaddresses)) {
            $denied = explode(' ', $CFG->denyemailaddresses);
            print_r($denied);

            foreach ($denied as $deniedpattern) {
                $deniedpattern = trim($deniedpattern);
                if (!$deniedpattern) {
                    continue;
                }
                if (strpos($deniedpattern, '.') === 0) {
                    if (strpos(strrev($sender_email), strrev($deniedpattern)) === 0) {
                        // Subdomains are in a form ".example.com" - matches "xxx@anything.example.com".
                        echo get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                        $email_denied = true;
                        //return get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                    }
                } else if (strpos(strrev($sender_email), strrev('@' . $deniedpattern)) === 0) {
                    echo get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                    $email_denied = true;
                    //return get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                }
            }
        } else {
            echo ("Denied email list is not defined.");
        }
        // The above block needs to set flags instead of returning a string.
        $is_spam_email = false || $email_denied;
        if ($sender_email != '' && $is_spam_email == false) {
            $email_data = $data[$sender_email];

            // popular spam conditions for email addresses
            if ($email_data['frequency'] > 0) {
                echo ('<br><b>Email has been spotted previously as spam ' . $email_data['frequency'] . ' times</b>');
                $is_spam_email = true;
            }
            if ($email_data['spam_rate'] == 1) {
                echo ("<br><b>Email appears in the spam blacklist.</b>");
                $is_spam_email = true;
            }
            if (($email_data['exists'] !== null) && ($email_data['exists'] == 0)) {
                echo ("<br><b>Email does not exist.  Invalid email.</b>");
                $is_spam_email = true;
            }
            if ($email_data['disposable_email'] == 1) {
                echo ("<br><b>Email is disposable - probably spam.</b>");
                $is_spam_email = true;
            }


            if ($is_spam_email) {

                
               // $blocked_email_obj = $DB->get_record('config', array('name' => 'denyemailaddresses'));
                //print_r($blocked_email_obj);
               // $CFG->denyemailaddresses .= ' ' . $sender_email;
                //echo ("Spam email added.  New CFG value - " . $CFG->denyemailaddresses);
                //$update_blocked_email = new \stdClass;
                //$update_blocked_email->id = $blocked_email_obj->id;
                //$update_blocked_email->blocked_emails_list = $blocked_email_obj->blocked_email_list . ' ' . $sender_email;

                $denyemailaddresses = get_config('core','denyemailaddresses','');

                $update_denyemailaddresses = $denyemailaddresses.' '.$sender_email;

                if (set_config('denyemailaddresses',$update_denyemailaddresses)) {
        
                

               
                //if ($DB->update_record('config', $update_blocked_email)) {
                    error_log("Email:" . $sender_email . " added to email block list");
                    echo "Blocked IPs list update succeeded.\n";
                } else {
                    error_log("Email:" . $sender_email . " failed to add to email block list");
                    echo "Blocked IPs list update failed.\n";
                }
            } else {
                echo ("<br><h1>Email is OK.</h1>");
            }
        }
        if ($is_spam || $is_spam_email) {
            echo ("<br>Results = SPAM.<br>");
            return true; // Not OK - spam
        } else {
            return false; // OK - not spam
        }
    }

    public static function getApiURL($sender_email, $sender_ip)
    {
        $api_key = get_config('local_rcvskantispam', 'apikey');
        $apiURL = 'https://api.cleantalk.org/?method_name=spam_check&auth_key=' . $api_key;
        if ($sender_email != '') $apiURL .= '&email=' . $sender_email;
        if ($sender_ip != '') $apiURL .= '&ip=' . $sender_ip;
        return $apiURL;
    }

    /**
     * Performs a generic API call using cURL
     * 
     * @param mixed $method GET (can be PUT, POST, DELETE)
     * @param mixed $url    
     * @param mixed $data   for a cURL GET, we can just set $data to false because we are not passing any data with a GET call.
     * 
     * @return json
     * 
     * example URLs:
     * IP call
     *       https://api.cleantalk.org/?method_name=spam_check&auth_key=123456&email=stop_email@example.com&ip=127.0.0.1
     * email call (hashed email)
     *       https://api.cleantalk.org/?method_name=spam_check&auth_key=123456&email=email_08c2495014d7f072fbe0bc10a909fa9dca83c17f2452b93afbfef6fe7c663631
     * IP call (IPV4 hash)
     *       https://api.cleantalk.org/?method_name=spam_check&auth_key=12345&ip=ip4_f46604ded89bbd0e8e478172a9a650f4825a763053ad2e3582c8286864ec4074
     *
     */
    public static function callAPI($method, $url, $data)
    {
        $curl = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'APIKEY: jamu4y7uvyta8yq',
            'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        if (!$result) {
            die("Connection Failure");
        }
        curl_close($curl);
        return $result;
    }
}
