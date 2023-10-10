<?php 
require(__DIR__.'/../../config.php');
require_login();
if (!is_siteadmin()) {
    die;
}
$timeStamp=date("Ymdhis");
echo "The timestamp for this run is " . $timeStamp;
$dryrunoff = optional_param('dryrunoff', 0, PARAM_INT);
$delete_users = optional_param('delete_users', 0, PARAM_INT);



$denyemailaddresses = $CFG->denyemailaddresses;


$count = 0;
foreach ($delete_users as $delete_user) {
    $deleted_email_array[]=delete_user_records($delete_users[$count],$dryrunoff);
    $count ++;
}
$folder = './blacklist/'.$timeStamp.'/';
if (!file_exists($folder)) {    
    mkdir($folder, 0777, true);
}
$file = fopen($folder.'spam_emails.csv', 'w'); 
fputcsv($file,$deleted_email_array);
fclose($file);

$get_blacklisted_emails_sql = <<<SQL
    SELECT u.id, u.email, u.firstname, u.lastname
    FROM mdl_user u
    WHERE STRPOS('{$denyemailaddresses}', CONCAT(' ',SUBSTRING(u.email, STRPOS(u.email, '@') + 1),' ')) > 0
SQL;

$get_blacklisted_emails = $DB->get_records_sql($get_blacklisted_emails_sql);
echo "<p>Current blacklist: {$denyemailaddresses}</p>";
?>
<script>
        function checkAll() {
            var checkboxes = document.getElementsByClassName('deleteCheck');
            // loop through the HTMLCollection (it's not an array!)
            for (let checkbox of checkboxes) {
                console.log(checkbox.id);
                checkbox.checked = true;
            }
        }

        function uncheckAll() {
            var checkboxes = document.getElementsByClassName('deleteCheck');
            for (let checkbox of checkboxes) {
                console.log(checkbox.id);
                checkbox.checked = false;
            }
        }
</script>

<form method="post" name = "deleted_blacklisted_emails">
<input type="submit" value = "Delete user record permanently">
    <p>
    <label for = "dryrunoff">Turn dry run off (Update database)? </label> 
    <input type = "checkbox" id = "dryrunoff" name = "dryrunoff" value = 1>
    </p>
    <table>
        <tr>
            <td>User id</td>
            <td>Email</td>
            <td>Firstname</td>
            <td>lastname</td>
            <td>Delete permanently</td>
        </tr>  
        <input type="button" id="select" value="Select All" onclick="checkAll();">
        <input type="button" id="deselect" value="Deselect All" onclick="uncheckAll();">
        <br><br>   
<?php
foreach ($get_blacklisted_emails as $get_blacklisted_email) {
    ?>
        <tr>
            <td><?php echo $get_blacklisted_email->id; ?></td>
            <td><?php echo $get_blacklisted_email->email; ?></td>
            <td><?php echo $get_blacklisted_email->firstname; ?></td>
            <td><?php echo $get_blacklisted_email->lastname; ?></td>
            <td><input class="deleteCheck" type = "checkbox" id = "<?php echo $get_blacklisted_email->id; ?>" name = "delete_users[]" value="<?php echo $get_blacklisted_email->id; ?>"></td>
        </tr>
<?php
}
?>
<table>
<input type="submit" value = "Delete user record permanently">
</form>
<?php

function delete_user_records($userid,$dryrunoff) {
    global $DB;
    $user = $DB->get_record('user',array('id'=>$userid));
    if ($dryrunoff) {
       if ($DB->delete_records('user',array('id'=>$userid))) {
            $delete_user_success = 1;
       }
       if ($DB->delete_records('user_info_data', array('userid' => $userid))){
            $delete_user_info_data_success = 1;
        }
    }
    if (isset($delete_user_success) || !$dryrunoff) {
        echo "<p>Spam user ".fullname($user)
        ." ({$user->email}) has been deleted from user table";
    }
    if (isset($delete_user_info_data_success) || !$dryrunoff) {
        echo "<p>Unconfirmed user ".fullname($user)." ({$user->email}) removed from user_info_data table.</p><hr>";
    }
    return $user->email;
}

?>

