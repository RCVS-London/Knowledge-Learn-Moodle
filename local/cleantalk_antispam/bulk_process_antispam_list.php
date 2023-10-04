<?php
/**
 * This script allows you to reset any local user password.
 *
 * @package    plugin
 * @subpackage cli
 * @copyright  2023 Anthony Forshaw (anthony@rcvsknowledge.org), Paolo Oprandi (paolo@rcvsknowledge.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// https://cleantalk.org/help/api-spam-check#multiple_records_check

require(__DIR__.'/../../config.php');
?>
<style>
    p {
        padding: 0;
        margin: 0;
        line-height:0.75em;
    }
    p.spam {color:#ff0000;}
    p.loggedinrecently {color:#ffcc00;}
    p.haslogs {color:#ff9966;}
    p.deletionfail {color:#b642f5;}

    .result:hover .tooltip {
        display: block;
    }


    .tooltip {
        display: none;
        background: #C8C8C8;
        margin-left: 28px;
        padding: 10px;
        position: absolute;
        z-index: 1000;
        width:auto;
        height:auto;
    }

    .result {
        margin:10px;
    }
</style>
<script>
    window.addEventListener("load", function(event) {
        // toggles the GET parameter 1 or 0 (creates if not exist, or updates if exists)
        function showOnlySpam() {
            let theButton = document.getElementById('spamButton');
            elements=document.getElementsByClassName("nospam");
            if (theButton.innerHTML=="Show only spam") {
                theButton.innerHTML="Show all records";
                displayMode="none";
                for (var i = 0; i < elements.length; i++){
                    elements[i].style.display = 'none';
                }
            } else {
                theButton.innerHTML="Show only spam"
                displayMode="block";
                
            }
            for (var i = 0; i < elements.length; i++){
                    elements[i].style.display = displayMode;
            }
        }
        document.getElementById ("spamButton").addEventListener ("click", showOnlySpam, false);

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
$dryRun=1;
if(isset($_GET["dryrun"])) {
    if ($_GET['dryrun']==0) $dryRun=0;
}
echo("<h1>Dry Run: ".($dryRun==1 ? "yes" : "no")."</h1>");
?>
<label for = "dryrun"> Perform a dry run (No updates)? </label> 
<input type = "checkbox" id = "dryrun" value = "<?php echo $dryRun;?>" onclick = "dryRun();" <?php echo ($dryRun==1) ? ("checked") : ("");?>>
<hr>
<button id="spamButton" onclick="showOnlySpam">Show only spam</button>
<hr>
<?php
$whitelist = get_config('local_cleantalk_antispam', 'whitelist');
echo("<h3>Whitelist: ".$whitelist."</h3>");
$whitelistArr = explode( ",", $whitelist);
print_r($whitelistArr);
$spamCounter=0; // running total
$spamEmails = array();
$checkCounter = 0;
// Get an array of all .txt files in the directory using glob()
$files = glob('./results/*.txt');

// Loop through the array of files
foreach($files as $file) {
    // Output each file name on a new line
    echo '<p class="nospam"><b>'.$file . "</b></p>";
    $json = file_get_contents($file);

    $obj = json_decode($json,true);
    if( isset( $obj['data'] )) {
        $data=$obj['data'];
        foreach($data as $key => $results) 
        {
            // display/check result conditions here
            if ($key !== "") {
                $spam=false;
                $whitelisted=false;
                $summary='<br>User email: '.$key;
                if (in_array($key, $whitelistArr)) {
                    $whitelisted=true;
                    $summary.=" <b>Email is whitelisted.</b>";
                    echo "<p ".($spam==true ? "class='spam result'" : "class='nospam result'").">".$summary."<span class='tooltip'>".json_encode($results)."</span></p>";
                
                };
                if ($whitelisted) continue;

                
                
                if ($results['disposable_email']>0) $summary.="  <b>Disposable email</b>";
                
                if ($results['appears']>0) {
                    $summary.= "  Appears:".$results['appears'];
                    $spam=true;
                }
                
                if ($results['frequency']>0) $summary.= "  Frequency:".$results['frequency'];
                
                if ($results['submitted']!="") $summary.= "  Submitted:".$results['submitted'];
                
                if ($results['updated']!="") $summary.= "  Updated:".$results['updated'];
                
                if ($results['spam_rate']>0) {
                    $summary.= "  Spam rate:".$results['spam_rate'];
                    $spam=true;
                }
                
                if ($results['exists']>0) $summary.= "  Email Exists.";
                
                if ($results['in_antispam_updated']!="") $summary.= "  in_antispam_updated:".$results['in_antispam_updated'];
                //echo '<br>sha256:'.$results['sha256'];
                
                echo "<p ".($spam==true ? "class='spam result'" : "class='nospam result'").">".$summary."<span class='tooltip'>".json_encode($results)."</span></p>";
                // Add known spam records to the $spamEmails array.
                if ($spam) array_push($spamEmails,$key);

            } else {
                if (isset($results['error'])) {
                    echo("<p class='nospam'>");
                    print_r($results);
                    echo ("</p>");
                } else {
                    echo("Check keys...");
                }
                
            }
        }
    } else {
        // The data isn't in the format that was expected, so maybe an error was thrown 
        // from the API and that needs to be handled here.
        echo '<p class="nospam">data does not exist.</p>';
    }
    
    
}

$arrlength = count($spamEmails);
echo("<br><h1>Spam records: ".$arrlength."</h1>");
echo("<h2>Dry Run: ".($dryRun==1 ? "yes" : "no")."</h2>");
//I don't think we need this as we have the whitelist
$min_num_logs = 5;
$min_days = 0;
foreach ($spamEmails as $spamEmail) {
    if ($user = $DB->get_record('user',array('email'=>$spamEmail))) {
    $sql_count = "select count(id) 
    from {$CFG->prefix}logstore_standard_log 
    where userid = {$user->id}";
    $sql_last_log = "select to_timestamp(lastaccess) 
    from {$CFG->prefix}user 
    where id = {$user->id}";
    if (is_numeric($user->id)) {
        $number_of_logs = $DB->count_records_sql($sql_count);
        $last_log = $DB->get_field_sql($sql_last_log);
        $spam_users_by_logs_array[] = 
                array(
                    'number_of_logs' => $number_of_logs,
                    'last_log'=>$last_log,
                    'spam_email'=>$user->username,
                    'spam_user'=>fullname($user)
                );
        if (!$skip) {
            //echo "<p>Spam user ".fullname($user)
            //." (email {$user->email}) will be deleted";
            if (!$dryRun) {
                //if (delete_records('user',array('email'=>$user->email))) {
                //    echo "<p>Spam user ".fullname($user)
                //    ." (email {$user->email}) has been deleted";
                //} else {
                //    echo "<p>Spam user ".fullname($user)
                //    ." (email {$user->email}) has failed to be deleted";
                //}
            }
        }
    }
        
    } else {
        echo "<p class = 'spam'>No user in Learn DB exists for {$spamEmail}";
    }
}

// The array will now be sorted by number_of_logs in ascending order

usort($spam_users_by_logs_array, 'sortByNumberOfLogsDesc');

echo "
    <table>
        <tr>
            <td>Logs</td>
            <td>Last Logged in</td>
            <td>Email</td>
            <td>Spam user</td>
        </tr>";
foreach ($spam_users_by_logs_array as $spam_users_by_log) {
    echo "<tr>
                <td>
                    {$spam_users_by_log['number_of_logs']}
                </td>
                <td>
                    {$spam_users_by_log['last_log']}
                </td>
                <td>
                    {$spam_users_by_log['spam_email']}
                </td>
                <td>
                    {$spam_users_by_log['spam_user']}
                </td>
            </tr>";
    }
echo "</table>";


// Custom sorting function based on the number_of_logs of the subarrays in descending order
function sortByNumberOfLogsDesc($a, $b) {
    if ($a["number_of_logs"] == $b["number_of_logs"]) {
        return 0;
    }
    return ($a["number_of_logs"] > $b["number_of_logs"]) ? -1 : 1;
}

   

?>