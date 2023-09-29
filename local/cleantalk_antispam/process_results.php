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
    });
    
</script>
<button id="spamButton" onclick="showOnlySpam">Show only spam</button>
<?php
$spamCounter=0; // running total
$spamEmails = array();
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
                $summary='<br>User email: '.$key;
                $spam=false;
                
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
                echo "<p ".($spam==true ? "class='spam'" : "class='nospam'").">".$summary."</p>";
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
$min_num_logs = 20;

foreach ($spamEmails as $spamEmail) {
    $user = $DB->get_record('user',array('email'=>$spamEmail));
    if (is_numeric($user->id)) {
        $sql_count = "select count(id) 
                from {$CFG->prefix}logstore_standard_log 
                where userid = {$user->id}";
        $number_of_logs = $DB->count_records_sql($sql_count);
        if ($number_of_logs > $min_num_logs) {
            echo "<p class = 'spam'>Spam user ".fullname($user).
            " (email {$user->email}) has {$min_num_logs} logs or above";
        } else {
            echo "<p>Spam user ".fullname($user)
            ." (email {$user->email}) will be deleted";
        }
    } else {
        var_dump('weird user ',$user->id);
    }
    
}
// This array can be used to bulk delete users from the Moodle database
?>