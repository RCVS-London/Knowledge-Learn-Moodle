<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin settings and defaults.
 *
 * @package auth_imis
 * @copyright  2021 Felix Michaux, David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Variables present due to this file being included/required by method load_settings on parent auth class.
 * (lib/classes/plugininfo/auth.php)
 * Typically from:
 *     - admin/index.php, lib/adminlib.php, admin/settings/plugins.php
 *
 * Declaration here prevents linting issues and documents code better.
 * @var part_of_admin_tree $ADMIN;
 * @var admin_settingpage $settings;
 *
 */

defined('MOODLE_INTERNAL') || die;

// @codingStandardsIgnoreLine. This form of include is correct for language construct. Moodle sniffer incorrectly reports.
require_once 'locallib.php';

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_imis/pluginname', '', new lang_string('auth_imis_description', 'auth_imis')));

    // Imis Webservice Endpoints.
    $settings->add(create_heading('webservices'));
    $settings->add(create_configtext('webservicevalidatelogin', PARAM_URL));
    $settings->add(create_configtext('webservicevalidateloginex', PARAM_URL));
    $settings->add(create_configtext('webserviceselectdata', PARAM_URL));

    // Other Settings heading.
    $settings->add(create_heading('othersettings'));

    // Change password URL.
    $settings->add(create_configtext('changepasswordurl', PARAM_URL));

    [$choices, $default] = get_loginurl();
    $settings->add(create_configselect('loginurl', $choices, $default));

    [$choices, $default] = get_vetgdp_courseid();
    $settings->add(create_configselect('vetgdpcourseid', $choices, $default));

    [$choices, $default] = get_vetgdp_enrolmentmethod();
    $settings->add(create_configselect('vetgdpenrolmentmethod', $choices, $default));

    [$choices, $default] = get_vetgdp_enrolroleid();
    $settings->add(create_configselect('vetgdpenrolroleid', $choices, $default));

    [$choices, $default] = get_vetgdp_forceusertheme();
    $settings->add(create_configselect('vetgdpforceusertheme', $choices, $default));

    [$choices, $default] = get_vetgdp_postnominalsprofilefield();
    $settings->add(create_configselect('vetgdppostnominalsprofilefield', $choices, $default));

    [$choices, $default] = get_caneditprofile();
    $settings->add(create_configselect('caneditprofile', $choices, $default));

    // Email prefix.
    $settings->add(create_configtext('emailprefix', PARAM_RAW_TRIMMED));

    // All values should be locked by default:
    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('imis');

    $userprofilelockfields = [];
    global $DB;
    $userprofilefields = $DB->get_fieldset_select('user_info_field', 'shortname', '');
    foreach ($userprofilefields as $userprofilefield) {
        $userprofilelockfields[] = 'profile_field_' . $userprofilefield;
    }

    display_auth_lock_options(
        $settings, $authplugin->authtype, $authplugin->userfields,
        get_string('auth_fieldlocks_help', 'auth'),
        false, false, $userprofilelockfields
    );

    // Reset all field lock auth locks defaults to lock.
    foreach ($settings->settings as $setting) {
        if ($setting->plugin == 'auth_imis' && strpos($setting->name, 'field_lock_') !== false) {
            $setting->defaultsetting = 'locked';
        }
    }
}