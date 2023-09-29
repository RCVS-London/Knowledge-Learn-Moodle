<?php

/**
 * This script allows you to reset any local user password.
 *
 * @package    plugin
 * @subpackage cli
 * @copyright  2023 Anthony Forshaw (anthony@rcvsknowledge.org), Paolo Oprandi (paolo@rcvsknowledge.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
//require_once($CFG->libdir.'/clilib.php');      // cli only functions

?>
<script>
    window.addEventListener("load", function(event) {
        // toggles the GET parameter 1 or 0 (creates if not exist, or updates if exists)
        function dryRunToggle() {
            let checkbox = document.getElementById('dryrun');
            var url = new URL(window.location.href);
            var search_params = url.searchParams;
            search_params.set('dryrun', checkbox.checked ? '1' : '0');
            url.search = search_params.toString();
            var new_url = url.toString();
            window.location.href = new_url;
        }
        document.getElementById ("dryrun").addEventListener ("click", dryRunToggle, false);
    });
    
</script>
<?php

//get all unconfirmed users flagged as deleted and remove from database
$user = array('confirmed'=>0,'deleted'=>1);
$unconfirmed_users = $DB->get_records('user',$user);
$i=0;
echo "<h1>Unconfirmed users</h1>";
foreach ($unconfirmed_users as $unconfirmed_user) {
    //delete unconfirmed user
    if ($DB->delete_records('user',array('id'=>$user->id)) {
        echo "<p>{$i} Unconfirmed user ".fullname($unconfirmed_user)." ({$unconfirmed_user->email}) removed from database.</p>";
    } else {
        echo "<p>{$i} Failed to remove from database unconfirmed user: ".fullname($unconfirmed_user)." ({$unconfirmed_user->email})</p>";
    }
    $i++;
}


//get all users and check against cleantalk blacklist
// Construct the calling string for a bulk spam check wget URL
// example:  wget -O- --post-data='data=stop_email@example.com,10.0.0.1,10.0.0.2' https://api.cleantalk.org/?method_name=spam_check&auth_key=123456
$all_users = array('deleted'=>0);
$all_users = $DB->get_records('user',$all_users);

$dryRun=1;
if(isset($_GET["dryrun"])) {
    if ($_GET['dryrun']==0) $dryRun=0;
}
?>
<label for = "dryrun"> Perform a dry run (No updates)? </label> 
<input type = "checkbox" id = "dryrun" value = "<?php echo $dryRun;?>" onclick = "dryRun();" <?php echo ($dryRun==1) ? ("checked") : ("");?>>
<?php
echo("<h1>Dry Run: ".($dryRun==1 ? "yes" : "no"));
echo "<h1>The rest</h1>";
$i=1;
$fileNum=1;
$maxApiCall=200;
$postData='';
$api_key = get_config('local_rcvskantispam', 'apikey');
foreach ($all_users as $user) {
    echo "<p>{$i} {$user->email}</p>";
    $sender_email = $user->email;
    $postData.=$sender_email.",";
    $i++;
    if ($i % $maxApiCall==0) {
        $sortedNum=substr(1000+$fileNum, 1);
        $fileName="api_results".$sortedNum.".txt";
        // create file
        if ($dryRun==0) {
            //file_put_contents("./results/".$fileName, '');
        }
        $wgetCall="wget -O ./results/".$fileName." --post-data='data=".substr($postData, 0, -1)."&method_name=spam_check&auth_key=".$api_key."' https://api.cleantalk.org/";
        echo("<br>WGET call string (counter = ".$i.")");
        echo("<br>".$wgetCall);
        // perform the wget call and dump details to a text file
        if(function_exists('exec')) {
            echo "<br>exec is enabled<br>";
            // Uncomment the next line to re-enable the API call.  (It performs close to 9000 calls, so dont run this unless necessary)
            if ($dryRun==0) {
                echo("<br>Calling API");
                //exec($wgetCall);
            } else {
                echo("Dry Run - not processed.");
            }
        } else {
            echo "<br>exec is disabled<br>";
        }
        
        // a new file should now appear:  api_results<X>.txt, where <X> is an incremental number
        echo file_get_contents('./results/'.$fileName);
        // new postdata after 200 records
        $postData='';
        $fileNum++;
    }
}

//QUESTION: Ant, do we need this function?
function getApiURL($sender_email, $sender_ip)
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

//QUESTION: Ant, do we still need this function?
    function callAPI($method, $url, $data)
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

//QUESTION: Ant, do we need this function?
function checkApiResults($api_results, $sender_ip, $sender_email)
    {
        global $DB, $CFG;
        $response = json_decode($api_results, true);

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
                $blockedip = get_config('core','blockedip','');
                if (!strstr($blockedip,$sender_ip)) {
                    $blockedip = $sender_email.'\n '.$blockedip;
                    if (set_config('blockedip',$blockedip)) {
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
        }

        // just testing the blocked email list ... is it populated?  Can we avoid using $CFG?
       // $blocked_email_obj = $DB->get_record('config', array('name' => 'denyemailaddresses'));
       // print_r($blocked_email_obj);
        // end of debug

        // Check denied email addresses from the $CFG global
        $email_denied = false;
        error_log("Denied email addresses: ".$CFG->denyemailaddresses);
        if (!empty($CFG->denyemailaddresses)) {
            $denied = explode(' ', $CFG->denyemailaddresses);
            //echo("Denied email addresses:");
            //print_r($denied);

            foreach ($denied as $deniedpattern) {
               // if ($email_denied) continue;
                $deniedpattern = trim($deniedpattern);
                if (!$deniedpattern) {
                    continue;
                }
                // For debugging:
                //error_log("Denied pattern:".$deniedpattern);
                //error_log("strpos(deniedpattern,.):".strpos($deniedpattern, '.'));
                //error_log("strrev($sender_email):".strrev($sender_email));
                //error_log("strrev($deniedpattern):".strrev($deniedpattern));
                //Examples:
                //Denied pattern:stop_email@example.com
                //strpos(deniedpattern,.):18
                //strrev(anthony@rcvsknowledge.org):gro.egdelwonksvcr@ynohtna
                //strrev(stop_email@example.com):moc.elpmaxe@liame_pots
                if (strpos($deniedpattern, '.') === 0) { // if this is a full domain with no email@ section, i.e. ".example.com"
                    if (strpos(strrev($sender_email), strrev($deniedpattern)) === 0) { // If the sender email is from the same domain as the blocked email, i.e. "example.com"
                        // Subdomains are in a form ".example.com" - matches "xxx@anything.example.com".
                        echo get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                        error_log("Email ".$sender_email." is part of a blocked email domain");
                        $email_denied = true;
                    }
                } else if (strpos(strrev($sender_email), strrev('@' . $deniedpattern)) === 0) {
                    error_log("strrev(@ . deniedpattern):".strrev('@' . $deniedpattern));
                    error_log("Email ".$sender_email." is a blocked email.");
                    echo get_string('emailnotallowed', '', $CFG->denyemailaddresses);
                    $email_denied = true;
                }
            }
        } else {
            echo ("Denied email list is not defined.");
        }
        // If $email_denied==true then there is no need to add the email to the block list - it is already there...
        $is_spam_email = false || $email_denied;
        if ($is_spam_email) {
            error_log("Email is blocked.  On the denied email list.");
        } else {
            // Check for spamminess if this email is NOT on the blocked list.
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
                $denyemailaddresses = get_config('core','denyemailaddresses','');
                if (!strstr($denyemailaddresses,$sender_email)) {
                    $denyemailaddresses = $sender_email.' '.$denyemailaddresses;
                    if (set_config('denyemailaddresses',$denyemailaddresses)) {
                        error_log("Email:" . $sender_email . " added to email block list");
                        echo "Blocked email list update succeeded.\n";
                    } else {
                        error_log("Email:" . $sender_email . " failed to add to email block list");
                        echo "Blocked email list update failed.\n";
                    }
                }
            } else {
                echo ("<br><h1>Email is OK.</h1>");
            }
        }
        // If either the IP or email is regarded as spam then return true
        if ($is_spam || $is_spam_email) {
            error_log("Email:".$sender_email." is regarded as spam.");
            echo ("<br>Results = SPAM.<br>");
            return true; // Not OK - spam
        } else {
            error_log("Email:".$sender_email." is NOT regarded as spam.");
            return false; // OK - not spam
        }
    }
