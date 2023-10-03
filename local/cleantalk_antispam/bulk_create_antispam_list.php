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
$checkbefore = optional_param('checkbefore', null, PARAM_ALPHANUMEXT);
$dryrunoff = optional_param('dryrunoff', 0, PARAM_INT);

$dryRun=1;
if($dryrunoff==1) {
    $dryRun=0;
}

//get all unconfirmed users flagged as deleted and remove from database
$user = array('confirmed'=>0,'deleted'=>1);
//$unconfirmed_users = $DB->get_records('user',$user);
$i=0;
echo "<h1>Unconfirmed users</h1>";
if ($dryRun==0) {
    foreach ($unconfirmed_users as $unconfirmed_user) {
        //delete unconfirmed user
        if ($DB->delete_records('user',array('id'=>$unconfirmed_user->id))) {
            echo "<p>{$i} Unconfirmed user ".fullname($unconfirmed_user)." ({$unconfirmed_user->email}) removed from database.</p>";
        } else {
            echo "<p>{$i} Failed to remove from database unconfirmed user: ".fullname($unconfirmed_user)." ({$unconfirmed_user->email})</p>";
        }
        $i++;
    }
}


//get all users and check against cleantalk blacklist
// Construct the calling string for a bulk spam check wget URL
// example:  wget -O- --post-data='data=stop_email@example.com,10.0.0.1,10.0.0.2' https://api.cleantalk.org/?method_name=spam_check&auth_key=123456

if ($checkbefore) {
    $sql = "select u.* from mdl_user u 
                where exists (select uid.id from mdl_user_info_data uid
                join mdl_user_info_field uif on shortname = 'cleantalk_checked' 
                and uid.fieldid = uif.id
                where 
                uid.userid = u.id and 
                uid.data < '{$checkbefore}')
                or not exists (select uid.id from mdl_user_info_data uid
                join mdl_user_info_field uif on shortname = 'cleantalk_checked' and 
                uid.fieldid = uif.id where u.deleted = 0 and 
                uid.userid = u.id) and 
                u.deleted = 0";     
    $all_users = $DB->get_records_sql($sql);
} else {
    $user = array('deleted'=>0);
    $all_users = $DB->get_records('user',$all_users);
}
?>
<form method="post">
    <label for = "dryrunoff">Turn dry run off (Update database)? </label> 
    <input type = "checkbox" id = "dryrunoff" value = "<?php echo $dryrunoff;?>">
    <label for = "checkbefore"> Check before: </label> 
    <input type = "date" id = "checkbefore" name = "checkbefore" value = "<?php echo $checkbefore;?>">
    <input type="submit">
</form>

<?php
echo("<h1>Dry Run: ".($dryRun==1 ? "yes" : "no"));
echo "<h1>The rest</h1>";
$i=1;
$fileNum=1;
$maxApiCall=200;
$postData='';
$api_key = get_config('local_cleantalk_antispam', 'apikey');

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
    if ($dryRun==0) {
        set_processed_data($user);
    }
}



function set_processed_data($user) {
    global $DB;
    $cleantalk_check_fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'cleantalk_checked'));
    $date_today = date("Y-m-d");
    if ($user_info_data = $DB->get_record('user_info_data',array('userid'=>$user->id,'fieldid'=>$cleantalk_check_fieldid))) {
        $user_info_data->data =  $date_today;
        if ($DB->update_record('user_info_data',$user_info_data)) {
            echo ("<br>Successfully updated new user_info_data date for {$user->username} (id $user->id)");
        } else {
            echo ("<br>Failed to update new user_info_data date for {$user->username} (id $user->id)");
        }
    } else {
        $user_info_data = new stdClass();
        $user_info_data->userid = $user->id;
        $user_info_data->fieldid =$cleantalk_check_fieldid;
        $user_info_data->data = $date_today;
        $user_info_data->format = 0;
        if ($DB->insert_record('user_info_data',$user_info_data)) {
            echo ("<br>Successfully inserted record for new user_info_data record  for {$user->username} (id $user->id)");
        } else {
            echo ("<br>Failed to insert record for new user_info_data date for {$user->username} (id $user->id)");
        }
    }
}