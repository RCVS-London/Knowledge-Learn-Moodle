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
 * Helper functions for admin settings and defaults.
 *
 * @package    auth_imis
 * @copyright  2021 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 */

defined('MOODLE_INTERNAL') || die;

const AUTH_IMIS = 'auth_imis';
const LOGIN_URL = '/login/index.php';
const AUTH_IMIS_LOGIN_URL = '/auth/imis/login.php';

/**
 * Config Select constructor helper
 * @param string $name unique ascii name, either 'mysetting' for settings that in config,
 * or 'myplugin/mysetting' for ones in config_plugins.
 * @param array $choices array of $value=>$label for each selection
 * @param string $default array key $choices for default value
 *
 * @return admin_setting_configselect|admin_setting_description admin setting of options or description of no options
 */
function create_configselect($name, $choices, $default) {
    $identifier = AUTH_IMIS . '_' . $name;
    $name = AUTH_IMIS . '/' . $name;

    $title = get_string($identifier, AUTH_IMIS);
    $description = get_string($identifier . '_desc', AUTH_IMIS);

    if (empty($choices)) {
        $title = get_string($identifier . '_none', AUTH_IMIS);
        $description = get_string($identifier . '_none_desc', AUTH_IMIS);
        return new admin_setting_description($name, $title, $description);
    } else {
        return new admin_setting_configselect($name, $title, $description, $default, $choices);
    }
}

/**
 * Config text constructor helper
 *
 * @param string $name unique ascii name, either 'mysetting' for settings that in config,
 * or 'myplugin/mysetting' for ones in config_plugins.
 * @param mixed $paramtype int means PARAM_XXX type, string is a allowed format in regex
 *
 * @return admin_setting_configtext admin setting of name and type
 */
function create_configtext($name, $paramtype) {
    $identifier = AUTH_IMIS . '_' . $name;
    $name = AUTH_IMIS . '/' . $name;

    $title = get_string($identifier, AUTH_IMIS);
    $description = get_string($identifier . '_desc', AUTH_IMIS);
    $default = get_string($identifier . '_default', AUTH_IMIS);
    return new admin_setting_configtext($name, $title, $description, $default, $paramtype, 80);
}

/**
 * Heading constructor helper
 * @param string $name unique ascii name, either 'mysetting' for settings that in config,
 * or 'myplugin/mysetting' for ones in config_plugins.
 *
 * @return admin_setting_heading admin setting of name title and description
 */
function create_heading($name) {
    $identifier = AUTH_IMIS . '_' . $name;
    $name = AUTH_IMIS . '/' . $name;

    $title = get_string($identifier, AUTH_IMIS);
    $description = get_string($identifier . '_desc', AUTH_IMIS);
    return new admin_setting_heading($name, $title, $description);
}

/**
 * VetGDP courseid options helper
 *
 * @return array course choices and default based on language string
 */
function get_vetgdp_courseid() {

    $choices = [];
    $courses = get_courses('all', 'c.sortorder',
        'c.id as id, c.idnumber as idnumber, c.fullname as fullname, c.visible as visible');
    $defaultcourseidnumber = get_string(AUTH_IMIS . '_vetgdpcourseid' . '_default', AUTH_IMIS);
    foreach ($courses as $course) {
        $choices[$course->id] = $course->fullname;
        if ($course->idnumber == $defaultcourseidnumber) {
            $default = $course->id;
        }
    }

    return [$choices, $default];
}

/**
 * VetGDP enrolment method options helper
 *
 * @return array enrolment method choices and default based on language string
 */
function get_vetgdp_enrolmentmethod() {
    $enrolmentplugins = [];
    $enrolmentpluginlist = core_component::get_plugin_list('enrol');
    foreach ($enrolmentpluginlist as $plugin => $fulldir) {
        if (get_string_manager()->string_exists('pluginname', 'enrol_' . $plugin)) {
            $pluginname = get_string('pluginname', 'enrol_' . $plugin);
        } else {
            $pluginname = $plugin;
        }
        $enrolmentplugins[$plugin] = $pluginname;
    }
    core_collator::asort($enrolmentplugins);
    $defaultenrolmentmethod = get_string(AUTH_IMIS . '_vetgdpenrolmentmethod' . '_default', AUTH_IMIS);
    return [$enrolmentplugins, $defaultenrolmentmethod];
}

/**
 * VetGDP enrolment roles helper
 *
 * @return array enrolment role choices and default based on language string
 */
function get_vetgdp_enrolroleid() {
    $roles = get_default_enrol_roles(context_system::instance());
    $archetyperoledefault = get_string(AUTH_IMIS . '_vetgdpenrolroleid' . '_default', AUTH_IMIS);
    $archetyperoles = get_archetype_roles($archetyperoledefault);
    $firstarchetyperole = reset($archetyperoles);
    $defaultroleid = $firstarchetyperole->id;

    return [$roles, $defaultroleid];
}

/**
 * VetGDP user themes helper
 *
 * @return array user theme choices and default based on language string
 */
function get_vetgdp_forceusertheme() {
    $themeplugins = [];
    $themelist = core_component::get_plugin_list('theme');
    foreach ($themelist as $plugin => $fulldir) {
        if (get_string_manager()->string_exists('pluginname', 'theme_' . $plugin)) {
            $pluginname = get_string('pluginname', 'theme_' . $plugin);
        } else {
            $pluginname = $plugin;
        }
        $themeplugins[$plugin] = $pluginname;
    }
    core_collator::asort($themeplugins);
    $defaulttheme = get_string(AUTH_IMIS . '_vetgdpforceusertheme' . '_default', AUTH_IMIS);
    return [$themeplugins, $defaulttheme];
}

/**
 * VetGDP post nominals user profile field helper
 *
 * @return array user profile field choices and default based on language string
 */
function get_vetgdp_postnominalsprofilefield() {
    global $DB;
    $choices = [];
    $userprofilefieldssql = '
        SELECT uif.id as id, uic.name as categoryname, uif.name as userfieldname, uif.shortname
        FROM {user_info_field} uif
        INNER JOIN {user_info_category} uic ON uic.id = uif.categoryid
        ORDER BY uic.sortorder, uif.sortorder
    ';

    $userfields = $DB->get_records_sql($userprofilefieldssql);
    $defaultuserprofilepostnominalsshortname = get_string(AUTH_IMIS . '_vetgdppostnominalsprofilefield' . '_default', AUTH_IMIS);
    foreach ($userfields as $userfield) {

        // Truncate if the concatenated fields are way too long.
        $name = mb_strimwidth($userfield->categoryname . ': ' . $userfield->userfieldname, 0, 100, '...');
        $choices[$userfield->shortname] = $name;

        if ($userfield->shortname == $defaultuserprofilepostnominalsshortname) {
            $default = $userfield->shortname;
        }
    }
    return [$choices, $default];
}

/**
 * VetGDP login url helper
 *
 * @return array loginurls and default based on language string
 */
function get_loginurl() {
    $loginurls = [
        (string)LOGIN_URL => LOGIN_URL,
        (string)AUTH_IMIS_LOGIN_URL => AUTH_IMIS_LOGIN_URL
    ];
    $defaultloginurl = get_string(AUTH_IMIS . '_loginurl' . '_default', AUTH_IMIS);
    $default = array_search($defaultloginurl, $loginurls);

    return [$loginurls, $default];
}

/**
 * VetGDP can edit profile helper
 *
 * @return array loginurls and default based on language string
 */
function get_caneditprofile() {
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $defaultcaneditprofile = get_string(AUTH_IMIS . '_caneditprofile' . '_default', AUTH_IMIS);
    $default = array_search($defaultcaneditprofile, $choices);
    return [$choices, $default];
}

/**
 * Helper function to centralise error output of login pages (deafult login or local imis login.
 *
 * @return void
 */
function error_output() {
    global $SESSION, $OUTPUT;

    if (empty($SESSION->loginerrormsg)) {
        return;
    }

    $errormsg = get_string('auth_imis_loginerrorineligible', 'auth_imis');
    $errormsghtml = get_string('auth_imis_loginerrorineligiblehtml', 'auth_imis');
    if ($SESSION->loginerrormsg == $errormsg) {
        echo $OUTPUT->box_start();
        // Cannot use constant here: \core\output\notification::NOTIFY_WARNING, as adaptable has re-writers.
        echo $OUTPUT->notification($errormsghtml, 'notifymessage');
        echo $OUTPUT->box_end();
    }

    $errormsg = get_string('auth_imis_loginerrorinaccountlocked', 'auth_imis');
    $errormsghtml = get_string('auth_imis_loginerrorinaccountlockedhtml', 'auth_imis');
    if ($SESSION->loginerrormsg == $errormsg) {
        echo $OUTPUT->box_start();
        // Cannot use constant here: \core\output\notification::NOTIFY_WARNING, as adaptable has re-writers.
        echo $OUTPUT->notification($errormsghtml, 'notifymessage');
        echo $OUTPUT->box_end();
    }
}