<?php 
require(__DIR__.'/../../config.php');
require_login();
if (!is_siteadmin()) {
    die;
}
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_secondary_navigation(false);
$PAGE->set_url('/local/cleantalk_antispam/add_domains_to_blacklist.php');
$PAGE->set_title('Blacklist domains');
$PAGE->set_heading('Blacklist domains');
echo $OUTPUT->header();

echo <<<LINK
    <p>
        <a href='{$CFG->wwwroot}/local/cleantalk_antispam/add_domains_to_blacklist.php'>
            Add domains to blacklist
        </a> | 
        <a href='{$CFG->wwwroot}/local/cleantalk_antispam/delete_users_in_domain_blacklist.php'>
            Delete users in blacklist
        </a> | 
        <a href='{$CFG->wwwroot}/local/cleantalk_antispam/bulk_create_antispam_list.php'>
            Bulk create antispam list
        </a> | 
        <a href='{$CFG->wwwroot}/local/cleantalk_antispam/bulk_process_antispam_list.php'>
            Bulk process antispam list
        </a>
    </p>  
LINK;

$dryrunoff = optional_param('dryrunoff', 0, PARAM_INT);
$domain_blacklist = optional_param('domain_blacklist', '', PARAM_URL);
$deleted_unconfirmed = optional_param('deleted_unconfirmed', '', PARAM_INT);


$deleted_unconfirmed_check_status = '';
if ($deleted_unconfirmed) $deleted_unconfirmed_check_status = 'checked'; 
echo <<<DELETEUNCONFIRMEDFORM
    <form method="post" name = "deleted_unconfirmed_form">
        <label for = "deleted_unconfirmed">Run for deleted and confirmed only? </label>
        <input type = "checkbox" id = "deleted_unconfirmed" name = "deleted_unconfirmed" value = 1 {$deleted_unconfirmed_check_status}>
        <input type="submit">
    </form><br>
DELETEUNCONFIRMEDFORM;
$denyemailaddresses = get_config('core','denyemailaddresses');

if ($domain_blacklist) {
    foreach ($domain_blacklist as $blacklisted_domain) {
        $denyemailaddresses .= ' '.$blacklisted_domain.' ';
    }
}

if ($dryrunoff) {
    echo '<p><strong>Setting now</p></strong>';
    set_config('denyemailaddresses',$denyemailaddresses);
}

$email = 'u.email';
$deleted_unconfirmed_sql = 'u.confirmed = 1 AND u.deleted = 0';
if ($deleted_unconfirmed) {
    $email = "substring(u.username FROM '^(.*)(\.\d+)$')";
    $deleted_unconfirmed_sql = 'u.confirmed = 0 AND u.deleted = 1';
}


$get_domainnames_sql = <<<SQL
WITH UserCounts AS (
    SELECT
        SUBSTRING({$email}, STRPOS({$email}, '@') + 1) AS email_domain,
        COUNT(u.id) AS domain_count
    FROM
        {$CFG->prefix}user u
    WHERE
        {$deleted_unconfirmed_sql}
        AND u.id != 1
    GROUP BY
        email_domain
),

LogCounts AS (
    SELECT
        SUBSTRING({$email}, STRPOS({$email}, '@') + 1) AS email_domain,
        COUNT(lsl.id) AS log_count
    FROM
        {$CFG->prefix}user u
    LEFT JOIN
        {$CFG->prefix}logstore_standard_log lsl ON lsl.userid = u.id
    WHERE
        {$deleted_unconfirmed_sql}
        AND u.id != 1
    GROUP BY
        email_domain
)

SELECT
    UC.email_domain AS email_domain,
    COALESCE(UC.domain_count, 0) AS domain_count,
    COALESCE(LC.log_count, 0) AS log_count,
    CASE
        WHEN UC.domain_count = 0 THEN NULL
        ELSE LC.log_count::float / UC.domain_count
    END AS log_per_user
FROM
    UserCounts UC
FULL OUTER JOIN
    LogCounts LC ON UC.email_domain = LC.email_domain
ORDER BY
    log_per_user ASC, email_domain ASC
SQL;
//echo  $get_domainnames_sql;
$domain_name_records = $DB->get_records_sql($get_domainnames_sql);
echo "<p>Deny email addresses list: {$denyemailaddresses}</p>";
echo '<form method="post" name = "add_domains_to_blacklist">';
if ($dryrunoff) $dryrunoff_check_status = 'checked'; 
echo <<<DRYRUNFORM
    <label for = "dryrunoff">Turn dry run off (Update database)? </label> 
    <input type = "checkbox" id = "dryrunoff" name = "dryrunoff" value = 1 {$dryrunoff_check_status}>
    <input type = "hidden" id = "deleted_unconfirmed" name = "deleted_unconfirmed" value = "{$deleted_unconfirmed}">
    <br>
DRYRUNFORM;
echo '<input type="submit" value = "Add emails">';
echo <<<BLACKLISTTABLE
    <table>
        <tr>
            <td>Email</td>
            <td>Domain name count</td><td>Logs by domain domain</td>
            <td>Average logs by domain</td>
            <td>Add to blacklist</td>
        </tr>     
BLACKLISTTABLE;

foreach ($domain_name_records as $domain_name_record) {
    $email_domain = $domain_name_record->email_domain;
    if (strstr($denyemailaddresses," {$email_domain} ") == false) {
    echo <<<DELETECHECKBOX
        <tr>
            <td>{$email_domain}</td>
            <td>{$domain_name_record->domain_count}</td>
            <td>{$domain_name_record->log_count}</td>
            <td>{$domain_name_record->log_per_user}</td>
            <td><input type = "checkbox" id = "{$email_domain}" name = "domain_blacklist[]" value="{$email_domain}"></td>
        </tr>
DELETECHECKBOX;
    }
}
echo '<table>';
echo '<input type="submit" value = "Add emails">';
echo '</form>';

?>

