<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023091460; // Replace with your version number. Note this needs to be incremented after every change.
$plugin->requires = 2022041900;

$plugin->component = 'local_cleantalk_antispam'; // Replace with your plugin's name.

// Plugin maturity level and release notes URL (if applicable).
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1.0';
$plugin->release_notes = 'https://example.com/moodle/plugins/local/yourpluginname';

// Supported Moodle versions (in case your plugin is compatible with multiple versions).
$plugin->supported = [400, 400];

// Minimum PHP version required for your plugin.
$plugin->php = '7.3.0';

// Plugin dependencies (if any).
$plugin->dependencies = array(
    //'local_myotherplugin' => 2023091400, // Add dependencies if necessary.
);

// Other information about your plugin.
//$plugin->component = 'local_rcvsksantispam';
$plugin->cron = 0; // Set to 1 if your plugin has scheduled tasks.

