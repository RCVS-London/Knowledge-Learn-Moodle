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
 * Settings for the goals block
 *
 * @package    block_goals
 * @copyright  2021 Royal College of Veterinary Surgeons
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Aylmer
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/blocks/goals/lib.php');

//global $ADMIN, $CFG, $settings;

if ($ADMIN->fulltree) {


    // BADGE_TYPE_SITE, BADGE_TYPE_COURSE
    $sitebadges = badges_get_badges(BADGE_TYPE_SITE);
    $coursebadges = badges_get_badges(BADGE_TYPE_COURSE);

    $badges = [];
    $badges[''] = 'No Badge requirement';
    foreach ($sitebadges as $sitebadge) {
        $badges[$sitebadge->id] = $sitebadge->name;
    }
    foreach ($coursebadges as $coursebadge) {
        $badges[$coursebadge->id] = $coursebadge->name;
    }

    $settings->add(new admin_setting_configselect('block_goals/requiredbadge',
        get_string('requiredbadge', 'block_goals'),
        get_string('requiredbadgedesc', 'block_goals'),
        '',
        $badges));

    $settings->add(new admin_setting_configselect('block_goals/defaultreminderfrequency',
        get_string('defaultreminderfrequency', 'block_goals'),
        get_string('defaultreminderfrequencydesc', 'block_goals'),
        '',
        get_reminderfrequencies()));

    $settings->add(new admin_setting_confightmleditor('block_goals/introcontent',
        get_string('introcontent', 'block_goals'),
        get_string('introcontentdesc', 'block_goals'),
        get_string('introcontentdefault', 'block_goals')));

    $settings->add(new admin_setting_confightmleditor('block_goals/nobadgeaccess',
        get_string('nobadgeaccess', 'block_goals'),
        get_string('nobadgeaccessdesc', 'block_goals'),
        get_string('nobadgeaccessdefault', 'block_goals')));

    global $DB;
    $userprofilefields = $DB->get_records_menu('user_info_field', null, 'categoryid, sortorder', 'id, shortname');

    $settings->add(new admin_setting_configmultiselect('block_goals/userprofilefieldsdisplay',
        get_string('userprofilefieldsdisplay', 'block_goals'), get_string('userprofilefieldsdisplaydesc', 'block_goals'),
        array_keys($userprofilefields), $userprofilefields));

    $link = new moodle_url('/admin/category.php?category=blockgoals');
    $settings->add(new admin_setting_heading('block_goals/management',
        get_string('management', 'block_goals'),
        get_string('managementdesc', 'block_goals', $link->out_as_local_url(false) )));


    /*
    require_once($CFG->dirroot . '/blocks/goals/lib.php');

    // Presentation options heading.
    $settings->add(new admin_setting_heading('block_goals/appearance',
            get_string('appearance', 'admin'),
            ''));

    // Display Course Categories on Dashboard course items (cards, lists, summary items).
    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaycategories',
            get_string('displaycategories', 'block_goals'),
            get_string('displaycategories_help', 'block_goals'),
            1));

    // Enable / Disable available layouts.
    $choices = array(BLOCK_GOALS_VIEW_CARD => get_string('card', 'block_goals'),
            BLOCK_GOALS_VIEW_LIST => get_string('list', 'block_goals'),
            BLOCK_GOALS_VIEW_SUMMARY => get_string('summary', 'block_goals'));
    $settings->add(new admin_setting_configmulticheckbox(
            'block_goals/layouts',
            get_string('layouts', 'block_goals'),
            get_string('layouts_help', 'block_goals'),
            $choices,
            $choices));
    unset ($choices);

    // Enable / Disable course filter items.
    $settings->add(new admin_setting_heading('block_goals/availablegroupings',
            get_string('availablegroupings', 'block_goals'),
            get_string('availablegroupings_desc', 'block_goals')));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingallincludinghidden',
            get_string('allincludinghidden', 'block_goals'),
            '',
            0));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingall',
            get_string('all', 'block_goals'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupinginprogress',
            get_string('inprogress', 'block_goals'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingpast',
            get_string('past', 'block_goals'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingfuture',
            get_string('future', 'block_goals'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingcustomfield',
            get_string('customfield', 'block_goals'),
            '',
            0));

    $choices = \core_customfield\api::get_fields_supporting_course_grouping();
    if ($choices) {
        $choices  = ['' => get_string('choosedots')] + $choices;
        $settings->add(new admin_setting_configselect(
                'block_goals/customfiltergrouping',
                get_string('customfiltergrouping', 'block_goals'),
                '',
                '',
                $choices));
    } else {
        $settings->add(new admin_setting_configempty(
                'block_goals/customfiltergrouping',
                get_string('customfiltergrouping', 'block_goals'),
                get_string('customfiltergrouping_nofields', 'block_goals')));
    }
    $settings->hide_if('block_goals/customfiltergrouping', 'block_goals/displaygroupingcustomfield');

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupingstarred',
            get_string('favourites', 'block_goals'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_goals/displaygroupinghidden',
            get_string('hiddencourses', 'block_goals'),
            '',
            1));


    */
    //global $CFG, $ADMIN, $settings;

}
$ADMIN->add('blocksettings', new admin_category('blockgoals', new lang_string('blockgoals', 'block_goals')));

$ADMIN->add('blockgoals', new admin_externalpage('filters', get_string('filters', 'block_goals'),
        new moodle_url('/blocks/goals/admin/filters.php'), 'block/goals:managegoals')
);

$ADMIN->add('blockgoals', new admin_externalpage('teams', get_string('teams', 'block_goals'),
        new moodle_url('/blocks/goals/admin/teams.php'), 'block/goals:managegoals')
);

$ADMIN->add('blockgoals', new admin_externalpage('goals', get_string('goals', 'block_goals'),
        new moodle_url('/blocks/goals/admin/goals.php'), 'block/goals:managegoals')
);

$ADMIN->add('blockgoals', new admin_externalpage('verbs', get_string('verbs', 'block_goals'),
        new moodle_url('/blocks/goals/admin/verbs.php'), 'block/goals:managegoals')
);

$ADMIN->add('blockgoals', new admin_externalpage('settings', get_string('settings', 'block_goals'),
        new moodle_url('/admin/settings.php?section=blocksettinggoals'), 'block/goals:managegoals')
);
