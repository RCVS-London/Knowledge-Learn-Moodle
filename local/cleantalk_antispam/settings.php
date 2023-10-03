<?php
/**
 * Plugin settings for the local_cleantalk_antispam plugin.
 *
 * @package   local_cleantalk_antispam
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Ensure the configurations for this site are set
if ($hassiteconfig) {

    // Create the new settings page
    // - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
    // $settings will be null
    $settings = new admin_settingpage('local_cleantalk_antispam', 'Cleantalk Antispam Settings');

    // Create
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
        // This is the reference you will use to your configuration
        'local_cleantalk_antispam/apikey',

        // This is the friendly title for the config, which will be displayed
        'Cleantalk Blacklist API: Key',

        // This is helper text for this config field
        'This is the key used to access the Cleantalk Blacklist API.  <a href="https://cleantalk.org/my/">Dashboard</a>',

        // This is the default value
        'No Key Defined',

        // This is the type of Parameter this config is
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(

        'local_cleantalk_antispam/whitelist',

        // This is the friendly title for the whitelist, which will be displayed
        'Cleantalk Whitelist',
        // This is helper text for this whitelist field
        'Emails should be added to the whitelist seperated by comma',
    ''));
}