<?php
include '../config.php';
echo '<p>start</p>';
$sender_email = 'baba@baba.org';
$denyemailaddresses = get_config('core','denyemailaddresses','');
if (!strstr($denyemailaddresses,$sender_email)) {
    $update_denyemailaddresses = $denyemailaddresses.' '.$sender_email;
    if (set_config('denyemailaddresses',$update_denyemailaddresses)) {
        echo '<h1>complete</h1>';
    }
}