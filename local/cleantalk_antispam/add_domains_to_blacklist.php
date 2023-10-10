<?php 
require(__DIR__.'/../../config.php');
require_login();
if (!is_siteadmin()) {
    die;
}

$dryrunoff = optional_param('dryrunoff', 0, PARAM_INT);
$domain_blacklist = optional_param('domain_blacklist', '', PARAM_URL);
$deleted_unconfirmed = optional_param('deleted_unconfirmed', '', PARAM_URL);

echo <<<LINK
    <p><a href='{$CFG->wwwroot}/local/cleantalk_antispam/delete_users_in_domain_blacklist.php'>Delete users in blacklist</a></p>
LINK;

echo <<<DRYRUNFORM
    <form method="post" name = "dryrun">
        <label for = "dryrunoff">Turn dry run off (Update database)? </label> 
        <input type = "checkbox" id = "dryrunoff" name = "dryrunoff">
        <label for = "deleted_unconfirmed">Run for deleted and confirmed only? </label>
        <input type = "checkbox" id = "deleted_unconfirmed" name = "deleted_unconfirmed">
        <input type="submit">
    </form><br>
DRYRUNFORM;
$denyemailaddresses = $CFG->denyemailaddresses;

if ($domain_blacklist) {
    foreach ($domain_blacklist as $blacklisted_domain) {
        $denyemailaddresses .= ' '.$blacklisted_domain.' ';
    }
}
if ($dryrunoff) {
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
echo "<p>$denyemailaddresses list: {$denyemailaddresses}</p>";
echo '<form method="post" name = "add_domains_to_blacklist">';
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
    if (strstr($CFG->denyemailaddresses," {$email_domain} ") == false) {
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

