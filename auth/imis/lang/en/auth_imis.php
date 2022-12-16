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
 * Strings for component 'auth_imis', language 'en'.
 *
 * @package   auth_imis
 * @copyright 2021 David Aylmer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['auth_imis_caneditprofile'] = 'Can Edit Profile';
$string['auth_imis_caneditprofile_default'] = 'No';
$string['auth_imis_caneditprofile_desc'] = 'Determines whether users can edit their profile';
$string['auth_imis_changepasswordurl'] = 'Change Password URL';
$string['auth_imis_changepasswordurl_default'] = 'https://myaccount.rcvs.org.uk/';
$string['auth_imis_changepasswordurl_desc'] = 'How users can change their password';
$string['auth_imis_configuredcorrectly'] = 'Auth iMIS plugin is <strong>correctly configured</strong>.';
$string['auth_imis_configuredincorrectly'] = 'Auth iMIS plugin is <strong>not correctly configured</strong>. Please visit <a href="{$a}">settings page</a>.';
$string['auth_imis_emailprefix'] = 'User email prefix';
$string['auth_imis_emailprefix_default'] = 'vetgdp_';
$string['auth_imis_emailprefix_desc'] = 'To prevent collisions on existing usernames using the same email address, but to keep valid emails. Use blank to use original email.';
$string['auth_imis_loginerrorinaccountlocked'] = 'Your account is locked out, please email the Education team at vetgdp@rcvs.org.uk';
$string['auth_imis_loginerrorinaccountlockedhtml'] = 'Your account is locked out, please email the Education team at <a href="mailto:vetgdp@rcvs.org.uk">vetgdp@rcvs.org.uk</a>';
$string['auth_imis_loginerrorineligible'] = 'Thank you for your interest in the VetGDP Adviser role. It appears that you don’t currently meet the minimum requirements to become a VetGDP Adviser. You can find out more about the VetGDP Adviser role by visiting our website [ https://www.rcvs.org.uk/lifelong-learning/vetgdp/could-you-become-a-vetgdp-adviser/ ]. Alternatively, if you feel your experience would qualify you for the role, please email the Education team at vetgdp@rcvs.org.uk';
$string['auth_imis_loginerrorineligiblehtml'] = 'Thank you for your interest in the VetGDP Adviser role. It appears that you don’t currently meet the minimum requirements to become a VetGDP Adviser. You can find out more about the VetGDP Adviser role by visiting our <a href="https://www.rcvs.org.uk/lifelong-learning/vetgdp/could-you-become-a-vetgdp-adviser/">website</a>. Alternatively, if you feel your experience would qualify you for the role, please email the Education team at <a href="mailto:vetgdp@rcvs.org.uk">vetgdp@rcvs.org.uk</a>';
$string['auth_imis_loginurl'] = 'VetGDP Preferred login URL';
$string['auth_imis_loginurl_default'] = '/auth/imis/login.php';
$string['auth_imis_loginurl_desc'] = 'VetGDP Preferred login URL for error redirection. Only used when vetgdp username is established. Default value is the VetGDP styled login.';
$string['auth_imis_othersettings'] = 'Other Settings';
$string['auth_imis_othersettings_desc'] = 'Other Settings for iMIS authentication plugin';
$string['auth_imis_description'] = '<h3>Authenticate iMIS users via webservices.</h3><img src="./../auth/imis/pix/imis.png"/>';
$string['auth_imis_vetgdpcourseid'] = 'VetGDP Course';
$string['auth_imis_vetgdpcourseid_default'] = 'VetGDP AT';
$string['auth_imis_vetgdpcourseid_desc'] = 'VetGDP Course to automatically enrol in (and forward to) on every login. Default value is the id number.';
$string['auth_imis_vetgdpcourseid_none'] = 'None';
$string['auth_imis_vetgdpcourseid_none_desc'] = 'No Courses available';
$string['auth_imis_vetgdpenrolmentmethod'] = 'VetGDP Course Enrolment Method';
$string['auth_imis_vetgdpenrolmentmethod_default'] = 'manual';
$string['auth_imis_vetgdpenrolmentmethod_desc'] = 'VetGDP Course Enrolment Method to automatically enrol user. Default value is the enrolment method plugin name.';
$string['auth_imis_vetgdpenrolmentmethod_none'] = 'None';
$string['auth_imis_vetgdpenrolmentmethod_none_desc'] = 'No enrolment methods available';
$string['auth_imis_vetgdpenrolroleid'] = 'VetGDP Enrol Role';
$string['auth_imis_vetgdpenrolroleid_default'] = 'student';
$string['auth_imis_vetgdpenrolroleid_desc'] = 'The role to enrol VetGDP authenticated users to on authentication. Default value is the shortname.';
$string['auth_imis_vetgdpenrolroleid_none'] = 'None';
$string['auth_imis_vetgdpenrolroleid_none_desc'] = 'No Roles available';
$string['auth_imis_vetgdpforceusertheme'] = 'VetGDP Force User Theme';
$string['auth_imis_vetgdpforceusertheme_default'] = 'adaptable';
$string['auth_imis_vetgdpforceusertheme_desc'] = 'The theme to set on users when their are created via successful authentication. Default value is the plugin name.';
$string['auth_imis_vetgdpforceusertheme_none'] = 'None';
$string['auth_imis_vetgdpforceusertheme_none_desc'] = 'No themes available';
$string['auth_imis_vetgdppostnominalsprofilefield'] = 'VetGDP Post Nominals Profile Field';
$string['auth_imis_vetgdppostnominalsprofilefield_default'] = 'postnoms';
$string['auth_imis_vetgdppostnominalsprofilefield_desc'] = 'The custom user profile field to store post nominals into on authentication. Default value is the shortname.';
$string['auth_imis_vetgdppostnominalsprofilefield_none'] = 'None';
$string['auth_imis_vetgdppostnominalsprofilefield_none_desc'] = 'No User profile fields available';
$string['auth_imis_webservices'] = 'iMIS Webservice Endpoints';
$string['auth_imis_webservices_desc'] = 'iMIS Webservice Endpoints Description';
$string['auth_imis_webservicevalidatelogin'] = 'Validate Login Webservice';
$string['auth_imis_webservicevalidatelogin_default'] = 'https://webservices.rcvs.org.uk/imisservices.asmx/validateLogin';
$string['auth_imis_webservicevalidatelogin_desc'] = 'Webservice endpoint that takes a login id and password and returns an iMIS id and a validation key';
$string['auth_imis_webservicevalidateloginex'] = 'Validate Login Extended Webservice';
$string['auth_imis_webservicevalidateloginex_default'] = 'https://webservices.rcvs.org.uk/imisservices.asmx/validateLoginEx';
$string['auth_imis_webservicevalidateloginex_desc'] = 'Webservice endpoint that takes an encrypted validation key, and returns an iMIS id and username.';
$string['auth_imis_webserviceselectdata'] = 'Select Data Webservice';
$string['auth_imis_webserviceselectdata_default'] = 'https://webservices.rcvs.org.uk/imisservices.asmx/SelectData';
$string['auth_imis_webserviceselectdata_desc'] = 'Webservice that takes a SQL query and returns iMIS data for population and update of moodle user data handled by this authentication method.';
$string['pluginname'] = 'iMIS Webservices authentication';
$string['privacy:metadata'] = 'The iMIS webservices authentication plugin does not store any personal data.';
