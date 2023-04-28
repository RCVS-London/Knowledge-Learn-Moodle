<?php

require_once('../config.php');
require_login();
$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('login');
$PAGE->set_title("Notice for VetGDP users");
$PAGE->set_heading("Notice for VetGDP users");
$PAGE->set_url($CFG->wwwroot.'/local/imis_user_notice.php');

echo $OUTPUT->header();
$fullname = fullname($USER);
$email = $USER->email;
$sesskey = sesskey();
if (str_contains($USER->email,'vetgdp_')) {
    $email = substr($USER->email,7);
}
echo <<<MESSAGE
            <h3>Important information</h3>
            <p>Dear {$fullname}</p>
            <p>Welcome to RCVS Knowledge's Learn platform!</p>
            <p>When you first accessed Learn it was through the RCVS
            authentication system. Learn is delivered by 
            by RCVS Knowledge, the charity partner of the RCVS. 
            We need to change the way in which you authenticate.
            In order to do this we must reset your password.<p>
            <p>You have been given a temporary password of <em>changeme</em></p>
            <p>Please log in again using this password and change your password.</p>
            <p>Your account will use the email: {$email}</p>
            <p>Apologies for the inconvenience caused.</p>
            <p><a href=
                "{$CFG->wwwroot}/login/logout.php?sesskey={$sesskey}" 
                title="logout" class="btn btn-info white ">
                Login & reset password</a>
            </p>

    
MESSAGE;

$user_update['id'] = $USER->id;
$user_update['auth'] = 'learn';
$user_update['password'] = md5('changeme');
$user_update['email'] = $email;
$DB->update_record('user', $user_update);

$user_preferences_update_array = array('userid' => $USER->id,
    'name' =>'auth_forcepasswordchange');
$user_preferences_update_id = 
    $DB->get_field('user_preferences','id',
    $user_preferences_update_array);
if (!empty($user_preferences_update_id)) {
    $DB->set_field('user_preferences','value',1,['id' => $user_preferences_update_id]);
} else {
    $user_preferences_update_array['value'] = 1; 
    $DB->insert_record('user_preferences',$user_preferences_update_array);
}

echo $OUTPUT->footer();
?>