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
 * Authenticate through iMIS webservices.
 *
 * @package    auth_imis
 * @copyright  2021 Felix Michaux, David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// @codingStandardsIgnoreLine. This form of include is correct for language construct. Moodle sniffer incorrectly reports.
require_once $CFG->libdir . '/authlib.php';

// @codingStandardsIgnoreLine. This form of include is correct for language construct. Moodle sniffer incorrectly reports.
require_once $CFG->dirroot . '/auth/imis/locallib.php';

/**
 * Plugin for no authentication.
 */
class auth_plugin_imis extends auth_plugin_base {

    // Member variables.

    // All value declarations are overridden in constructor.
    // It is possible for config loading to fail in the constructor, (during install/upgrade) so it is better to use
    // member variables rather than rely on the config property bag from the base class existing.

    private $webservicevalidatelogin = 'https://webservices.rcvs.org.uk/imisservices.asmx/validateLogin';
    private $webservicevalidateloginex = 'https://webservices.rcvs.org.uk/imisservices.asmx/validateLoginEx';
    private $webserviceselectdata = 'https://webservices.rcvs.org.uk/imisservices.asmx/SelectData';

    private $changepasswordurl = 'https://myaccount.rcvs.org.uk/';
    private $loginurl = AUTH_IMIS_LOGIN_URL;

    private $vetgdpcourseid = 0;
    private $vetgdpenrolmentmethod = 'manual';
    private $vetgdpenrolroleid = 0;
    private $vetgdpforceusertheme = 'adaptable';
    private $vetgdppostnominalsprofilefield = 'postnoms';

    private $emailprefix = 'vetgdp_';
    private $caneditprofile = false;

    // Constructor.
    public function __construct() {

        // Override parent member variables.
        $this->authtype = 'imis';

        $this->config = get_config('auth_imis');
        $this->errorlogtag = '[AUTH IMIS] ';

        // If config cannot be loaded from cache.
        if (count((array)$this->config) < 2) {
            global $DB;
            $this->config = (object)$DB->get_records_menu('config_plugins', array('plugin' => 'auth_imis'), '', 'name,value');
        }

        // Note: These config settings can be overridden in config.php as per normal.
        // ($CFG->forced_plugin_settings[$plugin];).

        if (isset($this->config->webservicevalidatelogin)) {
            $this->webservicevalidatelogin = $this->config->webservicevalidatelogin;
        }
        if (isset($this->config->webservicevalidateloginex)) {
            $this->webservicevalidateloginex = $this->config->webservicevalidateloginex;
        }
        if (isset($this->config->webserviceselectdata)) {
            $this->webserviceselectdata = $this->config->webserviceselectdata;
        }
        if (isset($this->config->changepasswordurl)) {
            $this->changepasswordurl = $this->config->changepasswordurl;
        }
        if (isset($this->config->loginurl)) {
            $this->loginurl = $this->config->loginurl;
        }
        if (isset($this->config->vetgdpcourseid)) {
            $this->vetgdpcourseid = $this->config->vetgdpcourseid;
        }
        if (isset($this->config->vetgdpenrolmentmethod)) {
            $this->vetgdpenrolmentmethod = $this->config->vetgdpenrolmentmethod;
        }
        if (isset($this->config->vetgdpenrolroleid)) {
            $this->vetgdpenrolroleid = $this->config->vetgdpenrolroleid;
        }
        if (isset($this->config->vetgdpforceusertheme)) {
            $this->vetgdpforceusertheme = $this->config->vetgdpforceusertheme;
        }
        if (isset($this->config->vetgdppostnominalsprofilefield)) {
            $this->vetgdppostnominalsprofilefield = $this->config->vetgdppostnominalsprofilefield;
        }
        if (isset($this->config->emailprefix)) {
            $this->emailprefix = $this->config->emailprefix;
        }
        if (isset($this->config->caneditprofile)) {
            $this->caneditprofile = $this->config->caneditprofile;
        }
    }

    // Public functions.
    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_imis() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * This is the primary method that is used by the authenticate_user_login()
     * function in moodlelib.php. This method overrides the base class method.
     *
     * This method should return a boolean indicating
     * whether or not the username and password authenticate successfully.
     *
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {

        // Webservice endpoint that takes a login id and password and returns an iMIS id and a validation key.
        $response = $this->validate_stage1($username, $password);
        if ($response == false || $response->result == false) {
            return false;
        }
        $validateimisid = $response->data['validateimisid'];
        $validatekey = $response->data['validatekey'];

        // Webservice endpoint that takes an encoded validation key, and returns an iMIS id and username.
        $response = $this->validate_stage2($username, $validateimisid, $validatekey);
        if ($response == false || $response->result == false) {
            return false;
        }

        $response = $this->check_eligibility($username);
        if ($response == false || $response->result == false) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * Parent class returns false by default so it is not necessary to override this method.
     *
     * @return bool
     */
    // REF: public function can_change_password.

    /**
     * Returns the URL for changing the users' passwords, or empty if the default
     * URL can be used.
     *
     * This method is used if can_change_password() returns true.
     * This method is called only when user is logged in, it may use global $USER.
     * If you are using a plugin config variable in this method, please make sure it is set before using it,
     * as this method can be called even if the plugin is disabled, in which case the config values won't be set.
     *
     * @return moodle_url url of the profile page or null if standard used
     */
    public function change_password_url() {
        return $this->changepasswordurl;
    }

    /**
     * Returns the prefix for prepending to the users' email
     *
     * To prevent collisions on existing usernames using the same email address, but to keep valid emails.
     * Use blank to use original email.
     *
     * @return string string of the email prefix
     */
    public function get_emailprefix() {
        return $this->emailprefix;
    }

    /**
     * Returns true if this authentication plugin can edit the users'
     * profile.
     *
     * @return bool
     */
    public function can_edit_profile() {
        return $this->caneditprofile;
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Internal plugins use password hashes from Moodle user table for authentication.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns false if this plugin is enabled but not configured.
     *
     * @return bool
     */
    public function is_configured() {

        global $DB;

        // Ensure all settings are set.
        // PHP function empty() evaluates both empty string '', literal zero '0', and 0 value as true.
        if (empty($this->config)) {
            debugging('Configuration object not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->changepasswordurl) || empty($this->changepasswordurl)) {
            debugging('Configuration value: changepasswordurl not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->loginurl) || empty($this->loginurl)) {
            debugging('Configuration value: loginurl not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->webservicevalidatelogin) || empty($this->webservicevalidatelogin)) {
            debugging('Configuration value: webservicevalidatelogin not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->webservicevalidateloginex) || empty($this->webservicevalidateloginex)) {
            debugging('Configuration value: webservicevalidateloginex not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->webserviceselectdata) || empty($this->webserviceselectdata)) {
            debugging('Configuration value: webserviceselectdata not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->vetgdpcourseid) || empty($this->vetgdpcourseid)) {
            debugging('Configuration value: vetgdpcourseid not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->vetgdpenrolmentmethod) || empty($this->vetgdpenrolmentmethod)) {
            debugging('Configuration value: vetgdpenrolmentmethod not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->vetgdpenrolroleid) || empty($this->vetgdpenrolroleid)) {
            debugging('Configuration value: vetgdpenrolroleid not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->vetgdpforceusertheme) || empty($this->vetgdpforceusertheme)) {
            debugging('Configuration value: vetgdpforceusertheme not set', DEBUG_DEVELOPER);
            return false;
        }
        if (empty($this->config->vetgdppostnominalsprofilefield) || empty($this->vetgdppostnominalsprofilefield)) {
            debugging('Configuration value: vetgdppostnominalsprofilefield not set', DEBUG_DEVELOPER);
            return false;
        }
        if (!isset($this->config->emailprefix) || !isset($this->emailprefix)) {
            debugging('Configuration value: emailprefix not set', DEBUG_DEVELOPER);
            return false;
        }
        if (!isset($this->config->caneditprofile) || !isset($this->caneditprofile)) {
            debugging('Configuration value: caneditprofile not set', DEBUG_DEVELOPER);
            return false;
        }
        // Name of enrolment plugin to target.
        $enrolmentmethod = $this->vetgdpenrolmentmethod;

        // Ensure enrolment method is enabled.
        if (!enrol_is_enabled($enrolmentmethod)) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Configured enrolment method: '{$this->vetgdpenrolmentmethod}' is not enabled");
            return false;
        }

        // Ensure course exists based on idnumber.
        $courseid = $DB->get_field('course', 'id', array('id' => $this->vetgdpcourseid));
        if (!$courseid) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Configured course id: '{$this->vetgdpcourseid}' not found");
            return false;
        }

        // Ensure enrolment method instance exists for course.
        $enrolmentinstance = $DB->get_record('enrol', ['enrol' => $enrolmentmethod, 'courseid' => $courseid]);
        if (!$enrolmentinstance) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Enrolment instance for enrolment method: '{$enrolmentmethod}' and courseid: '{$courseid}' not found.");
            return false;
        }

        // Ensure enrolment instance is enabled. Constant defined in lib/enrollib.php.
        if ($enrolmentinstance->status == ENROL_INSTANCE_DISABLED) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Enrolment instance for enrolment method: '{$enrolmentmethod}' and courseid: '{$courseid}' is not enabled.");
            return false;
        }

        // Ensure roleid exists.
        $roleid = $DB->get_field('role', 'id', array('id' => $this->vetgdpenrolroleid));
        if (!$roleid) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Enrolment roleid: '{$this->vetgdpenrolroleid}' not found.");
            return false;
        }

        [$themeplugins, $defaulttheme] = get_vetgdp_forceusertheme();
        if (!array_key_exists($this->vetgdpforceusertheme, $themeplugins)) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Force user theme: '{$this->vetgdpforceusertheme}' not found.");
            return false;
        }

        [$profilefields, $postnomfield] = get_vetgdp_postnominalsprofilefield();
        if (!array_key_exists($this->vetgdppostnominalsprofilefield , $profilefields)) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Post nominals user profile field: '{$this->vetgdppostnominalsprofilefield }' not found.");
            return false;
        }

        if (!function_exists('curl_init') ) {
            debugging("Authentication: {AUTH_IMIS}: " .
                "Function curl_init not found.");
            return false;
        }

        return true;
    }

    /**
     * Indicates if password hashes should be stored in local moodle database.
     *
     * Parent class returns the inverse of internal passwords which is explicityly set above,
     * so it is not necessary to override this method.
     *
     * @return bool true means md5 password hash stored in user table, false means flag 'not_cached' stored there instead
     */
    // REF: public function prevent_local_passwords.

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * Parent class returns false by default so it is not necessary to override this method.
     *
     * @return bool
     */
    // REF: public function can_reset_password.

    /**
     * Returns whether or not this authentication plugin can be manually set
     * for users, for example, when bulk uploading users.
     *
     * This should be overriden by authentication plugins where setting the
     * authentication method manually is allowed.
     *
     * Parent class returns false by default so it is not necessary to override this method.
     *
     * @return bool
     * @since Moodle 2.6
     */
    // REF public function can_be_manually_set.

    /**
     * Read user information from external database and returns it as array().
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honour synchronisation flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    public function get_userinfo($username) {
        global $CFG;

        $querystring = "
            SELECT category, email, forenames, CONVERT(VARCHAR, graduation_date, 23) AS graduation_date,
                   surname, member_type, major_key, CONVERT(VARCHAR, RCVS_1CPD_LastRegDate.LastRegDate, 23) AS LastRegDate,
                   RCVS_VS_postnominals.postnoms as postnominals
            FROM ud_regaddress
            INNER JOIN NAME ON NAME.id = ud_regaddress.id
            LEFT JOIN rcvs_mem_to_uni ON rcvs_mem_to_uni.id = ud_regaddress.id
            LEFT JOIN rcvs_1cpd_admin ON rcvs_1cpd_admin.id = ud_regaddress.id
            LEFT JOIN RCVS_AdvancedPrac_Combined ON NAME.id = RCVS_AdvancedPrac_Combined.id
            LEFT JOIN RCVS_1CPD_LastRegDate on RCVS_1CPD_LastRegDate.id = Name.id
            LEFT JOIN RCVS_VS_postnominals on NAME.id = RCVS_VS_postnominals.ID
            WHERE NAME.id = (SELECT dbo.usernameToID('{$username}'))
            ";

        $endpoint = $this->webserviceselectdata;

        $payload = [
            'SQLSelect' => $querystring,
        ];

        $pattern = "/(?:<string xmlns=\"http:\/\/tempuri\.org\/\">|[|])([^|^<]+)/i";

        $offsets = [
                'email' => 1,
                'firstname' => 2,
                'lastname' => 4,
                'idnumber' => 6,
                'profile_field_postnoms' => 8,
        ];

        $response = $this->imis_webservice($endpoint, $payload, $pattern, $offsets);

        // Webservice call fails.
        if (!$response->result) {
            return false;
        }

        // Return false if authentication fails. Ignore empty post noms.
        if (empty($response->data['email']) || empty($response->data['firstname']) ||
            empty($response->data['lastname']) || empty($response->data['idnumber'])) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' failed authentication get_userinfo");
            return false;
        }
        // Use emailprefix if it exists.
        $response->data['email'] = $this->emailprefix . $response->data['email'];

        $response->data['country'] = 'GB';
        $response->data['calendartype'] = $CFG->calendartype;
        $response->data['timezone'] = get_string('europe/london', 'timezones');
        $response->data['lang'] = $CFG->lang;
        $response->data['maildisplay'] = core_user::MAILDISPLAY_HIDE;

        return $response->data;
    }

    /**
     * Hook for overriding behaviour of login page.
     * This method is called from login/index.php page for all enabled auth plugins.
     *
     * Override normal error setting behaviour to include html friendly error feedback.
     *
     */
    public function loginpage_hook() {
        error_output();
    }

    /**
     * Post authentication hook.
     * This method is called from authenticate_user_login() for all enabled auth plugins.
     *
     * Overridden from base class. This function acheives:
     *  1. Ensuring user is enrolled onto $this->vetgdpcourseid (VETGDP AT) course as a $this->vetgdpenrolroleid (student)
     *  2. Ensuring user theme is forced to $this->>vetgdpforceusertheme (adaptable).
     *  3. Redirects user to $this->vetgdpcourseid (VETGDP AT) course page
     *
     * @param object $user user object, later used for $USER
     * @param string $username (with system magic quotes)
     * @param string $password plain text password (with system magic quotes)
     */
    public function user_authenticated_hook(&$user, $username, $password) {

        // Defensive programming.
        if ($user->auth != $this->authtype) {
            return;
        }

        global $DB;
        global $SESSION;

        // Enrol user onto vetGDP course as a student.
        $success = $this->enrol_user($user->id);
        if (!$success) {
            debugging("AUTH: {$this->authtype}: Unable to enrol user: '{$user->id}' into course: '{$this->vetgdpcourseid}'");
        }

        // Enforce user theme to adaptable.
        $success = $DB->set_field('user', 'theme', $this->vetgdpforceusertheme, ['id' => $user->id]);
        if (!$success) {
            debugging("AUTH: {$this->authtype}: Unable to set user theme to: '{$this->vetgdpforceusertheme}' for : '{$user->id}'");
        }

        // Set delayed redirection of user to course page.
        $SESSION->wantsurl = (string)new moodle_url('/course/view.php?id=' . urlencode($this->vetgdpcourseid));

    }

    // Private functions.

    /**
     * Webservice endpoint that takes a login id and password and returns an iMIS id and a validation key.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return mixed response object with result boolean and data array properties or false on error
     */
    private function validate_stage1($username, $password) {

        $endpoint = $this->webservicevalidatelogin;

        $payload = [
            'LoginId' => $username,
            'Password' => $password,
        ];

        $pattern = "/<string>(.+?)<\/string>/i";

        $offsets = [
            'validateimisid' => 0,
            'validatekey' => 1
        ];

        $response = $this->imis_webservice($endpoint, $payload, $pattern, $offsets);

        // Webservice call fails.
        if (!$response->result) {
            return false;
        }
        if (!array_key_exists('validateimisid', $response->data)) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication: validateimisid does not exist in response");
            return false;
        }
        if ($response->data['validateimisid'] == 'Account is locked out') {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication: Account is locked out");
            // Trigger login failed event.
            $failurereason = AUTH_LOGIN_UNAUTHORISED;;
            $event = \core\event\user_login_failed::create(['other' => ['username' => $username,
                'reason' => $failurereason]]);
            $event->trigger();

            global $CFG, $SESSION;
            // Avoid redirect is coming from $this->>test_settings().
            if (isset($CFG->imistesting)) {
                return false;
            }
            $errormsg = get_string('auth_imis_loginerrorinaccountlocked', 'auth_imis');
            $SESSION->loginerrormsg = $errormsg;
            redirect(new moodle_url($this->loginurl));

            // Defensive.
            return false;
        }
        if (!array_key_exists('validatekey', $response->data)) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication: validatekey does not exist in response");
            return false;
        }
        // Return false if authentication fails.
        if (empty($response->data['validateimisid']) || empty($response->data['validatekey'])) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' failed authentication _initial_validation");
            return false;
        }

        return $response;
    }

    /**
     * Webservice endpoint that takes an encoded validation key, and returns an iMIS id and username.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return mixed response object with result boolean and data array properties or false on error
     */

    private function validate_stage2($username, $imisid, $key) {

        $endpoint = $this->webservicevalidateloginex;

        // REF: php.net: curl_escape vs urlencode vs rawurlencode.
        // REF: https://tools.ietf.org/html/rfc1738 https://tools.ietf.org/html/rfc3986 .
        $encodedkey = rawurlencode($key);

        $payload = [
            'Encrypt' => $encodedkey,
        ];

        $pattern = "/<string>(.+?)<\/string>/i";

        $offsets = [
            'imisidex' => 0,
            'usernameex' => 1
        ];

        $response = $this->imis_webservice($endpoint, $payload, $pattern, $offsets);

        // Webservice call fails.
        if (!$response->result) {
            return false;
        }

        $imisidex = $response->data['imisidex'];
        $usernameex = $response->data['usernameex'];

        if ($imisid != $imisidex || strtoupper($username) != strtoupper($usernameex)) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication. validate_stage2 ids or usernames to not match validate_stage1");
            return false;
        }

        $imisidpattern = "/^[a-zA-Z0-9]{1,7}$/i";
        if (!preg_match($imisidpattern, $imisidex)) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication. validate_stage2 imisid does not match pattern");
            return false;
        }

        // Short circuit if authentication fails.
        if ($imisidex == null || $usernameex == null) {
            debugging("AUTH: {$this->authtype}: username: '{$username}' " .
                "failed authentication validatestage2 id/username is null");
            return false;
        }

        return $response;
    }

    /**
     * Webservice endpoint that takes a username and if true, returns a response object or redirects to an eligibility error page.
     *
     * @param string $username The username to check validity of.
     *
     * @return mixed response object with result boolean and data array properties, false on error, or redirects if ineligible.
     */
    private function check_eligibility($username) {

        $endpoint = $this->webserviceselectdata;

        $querystring = "
            SELECT *
            FROM RCVS_VetGDP_training_eligibility
            WHERE id = (SELECT dbo.usernameToID('{$username}') as id);
        ";

        $payload = [
            'SQLSelect' => $querystring,
        ];

        $pattern = "/(?:<string xmlns=\"http:\/\/tempuri\.org\/\">|[|])([^|^<]+)/i";

        $offsets = [
            'eligible' => 2,
            'override' => 3
        ];

        $response = $this->imis_webservice($endpoint, $payload, $pattern, $offsets);

        // Webservice call fails.
        if (!$response->result) {
            return false;
        }

        $eligible = $response->data['eligible'];
        $override = $response->data['override'];

        if (strtoupper($eligible) == 'TRUE' || strtoupper($override) == 'TRUE') {
            return $response;
        } else {

            // Trigger login failed event.
            $failurereason = AUTH_LOGIN_UNAUTHORISED;;
            $event = \core\event\user_login_failed::create(['other' => ['username' => $username,
                'reason' => $failurereason]]);
            $event->trigger();

            global $CFG, $SESSION;
            // Avoid redirect is coming from $this->>test_settings().
            if (isset($CFG->imistesting)) {
                return false;
            }
            $errormsg = get_string('auth_imis_loginerrorineligible', 'auth_imis');
            $SESSION->loginerrormsg = $errormsg;
            redirect(new moodle_url($this->loginurl));

            // Defensive.
            return false;
        }
    }

    /**
     * Webservice endpoint that takes an encoded validation key, and returns an iMIS id and username.
     *
     * @param string $endpoint The webservice endpoint
     * @param array $payload The data to send in the cURL postfield request
     * @param string $pattern The regular expression to separate the cURL response data into the offsets
     * @param array $objectoffsets associative array of named expected values, with their match grouping offset
     *
     * @return mixed response object with result boolean and data array properties or false on error
     */
    private function imis_webservice($endpoint, $payload, $pattern, $objectoffsets) {
        $returnobject = [];
        $result = true;

        if (!function_exists('curl_init')) {
            debugging(AUTH_IMIS . ': cURL is required to be enabled to use this moodle iMIS authentication plugin.');
            return false;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($payload, '', '&'),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        if (preg_match_all($pattern, $response, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $key = array_search($i, $objectoffsets);
                if ($key !== false) {
                    if (isset($matches[1][$objectoffsets[$key]])) {
                        $returnobject[$key] = $matches[1][$objectoffsets[$key]];
                    } else {
                        // Index was found as a key in offsets, but no data was present.
                        $result = false;
                    }
                }

            }
        } else {
            $result = false;
        }

        return (object) array_merge(['result' => $result], ['data' => $returnobject]);
    }

    /**
     *
     * Enrol given user id into configured course as configured role using configured enrolment method.
     *
     * @param int $userid The user id
     *
     * @return bool success or failure of enrolment.
     */
    private function enrol_user($userid) {

        // Need config.php and database calls.
        global $CFG, $DB;

        // Defensive programming.
        if (empty($userid)) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol blank userid");
            return false;
        }

        // Name of enrolment plugin to target.
        $enrolmentmethod = $this->vetgdpenrolmentmethod;

        // Ensure enrolment method is enabled.
        if (!enrol_is_enabled($enrolmentmethod)) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol userid: '{$userid}'. " .
                "Configured enrolment method: '{$this->vetgdpenrolmentmethod}' is not enabled");
            return false;
        }

        // Ensure course exists based on idnumber.
        $courseid = $DB->get_field('course', 'id', array('id' => $this->vetgdpcourseid));
        if (!$courseid) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol userid: '{$userid}'. " .
                "Configured course id: '{$this->vetgdpcourseid}' not found");
            return false;
        }

        // Ensure enrolment method instance exists for course.
        $enrolmentinstance = $DB->get_record('enrol', ['enrol' => $enrolmentmethod, 'courseid' => $courseid]);
        if (!$enrolmentinstance) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol userid: '{$userid}'. " .
                "Enrolment instance for enrolment method: '{$enrolmentmethod}' and courseid: '{$courseid}' not found.");
            return false;
        }

        // Ensure enrolment instance is enabled. Constant defined in lib/enrollib.php.
        if ($enrolmentinstance->status == ENROL_INSTANCE_DISABLED) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol userid: '{$userid}'. " .
                "Enrolment instance for enrolment method: '{$enrolmentmethod}' and courseid: '{$courseid}' is not enabled.");
            return false;
        }

        // Ensure roleid exists.
        $roleid = $DB->get_field('role', 'id', array('id' => $this->vetgdpenrolroleid));
        if (!$roleid) {
            debugging("Authentication: {AUTH_IMIS}: Attempting to enrol userid: '{$userid}'. " .
                "Enrolment roleid: '{$this->vetgdpenrolroleid}' not found.");
            return false;
        }

        // Include the enrolment plugin class defs.
        // @codingStandardsIgnoreLine. This form of include is correct for language construct. Moodle sniffer incorrectly reports.
        require_once $CFG->dirroot . '/enrol/' . $enrolmentmethod . '/locallib.php';

        // Create an instance of the enrolment plugin.
        $enrolmentplugininstance = enrol_get_plugin($enrolmentmethod);

        // Is user already enrolled?
        if (is_enrolled(context_course::instance($courseid), $userid)) {
            return true;
        }

        // Add user_enrolments and role_assignments for user. No return value.
        $enrolmentplugininstance->enrol_user($enrolmentinstance, $userid, $roleid);

        return true;
    }

    /**
     *
     * Test if settings are ok, print info to output.
     *
     * @return void
     */
    public function test_settings() {
        global $CFG, $OUTPUT;

        $testdata = (object)[
            'validuser' => (object)[
                'name' => '1. Valid user/password test',
                'username' => 'dscully',
                'password' => 'TE123456',
                'user_login' => true,
                'validate_stage1' => true,
                'validate_stage2' => true,
                'check_eligibility' => true,
            ],
            'ineligibleuser' => (object)[
                'name' => '2. Valid user/password, but ineligible test',
                'username' => 'felix',
                'password' => 'TE123456',
                'user_login' => false,
                'validate_stage1' => true,
                'validate_stage2' => true,
                'check_eligibility' => false,
            ],
            'badpassword' => (object)[
                'name' => '3. Bad password test',
                'username' => 'dscully',
                'password' => '****',
                'user_login' => false,
                'validate_stage1' => false,
                'validate_stage2' => false,
                'check_eligibility' => true,
            ],
            'baduser' => (object)[
                'name' => '4. Bad user/password test',
                'username' => '****',
                'password' => '****',
                'user_login' => false,
                'validate_stage1' => false,
                'validate_stage2' => false,
                'check_eligibility' => false,
            ],
        ];

        raise_memory_limit(MEMORY_HUGE);
        $olddebug = $CFG->debug;
        $olddisplay = ini_get('display_errors');
        ini_set('display_errors', '1');
        $CFG->debug = DEBUG_DEVELOPER;
        error_reporting($CFG->debug);
        $CFG->imistesting = true;

        if (!$this->is_configured()) {
            $url = (new moodle_url('/admin/settings.php', ['section' => 'authsettingimis']))->out();
            echo $OUTPUT->notification(get_string('auth_imis_configuredincorrectly', 'auth_imis', $url), 'notifyproblem');
        } else {
            echo $OUTPUT->notification(get_string('auth_imis_configuredcorrectly', 'auth_imis'), 'notifysuccess');
            // Test user_login.
            $result = $this->test_case1($testdata->validuser);
            $result = $this->test_case1($testdata->ineligibleuser);
            $result = $this->test_case1($testdata->badpassword);
            $result = $this->test_case1($testdata->baduser);

            // Specific validation for private methods.
            $result = $this->test_case2($testdata->validuser);
            $result = $this->test_case2($testdata->ineligibleuser);
            $result = $this->test_case2($testdata->badpassword);
            $result = $this->test_case2($testdata->baduser);

        }

        $CFG->debug = $olddebug;
        ini_set('display_errors', $olddisplay);
        error_reporting($CFG->debug);
        ob_end_flush();
    }

    /**
     *
     * Output a success/failure notification based on condition
     *
     * @param object $testcase The testcase object
     * @param bool $condition The condition to test
     * @param string $step The step description
     *
     * @return bool success or failure of assertion condition
     */
    private function test_assert($testcase, $condition, $step) {
        global $OUTPUT;
        if ($condition) {
            echo $OUTPUT->notification("Test Case: <em>{$testcase->name}</em>: <strong>{$step}</strong>", 'notifysuccess');
            return true;
        } else {
            echo $OUTPUT->notification("Test Case: <em>{$testcase->name}</em>: <strong>{$step}</strong>", 'notifyproblem');
            return false;
        }
    }

    /**
     *
     * Output a success/failure notification based on condition
     *
     * @param object $testcase The testcase object
     *
     * @return bool success or failure of assertion condition
     */
    private function test_case1($testcase) {
        $result = $this->user_login($testcase->username, $testcase->password);
        return $this->test_assert($testcase, $result == $testcase->user_login, 'user_login');
    }

    /**
     *
     * Output a success/failure notification based on condition
     *
     * @param object $testcase The testcase object
     *
     * @return bool success or failure of assertion condition
     */
    private function test_case2($testcase) {
        $response = $this->validate_stage1($testcase->username, $testcase->password);

        if ($this->test_assert($testcase,
            $testcase->validate_stage1 == !($response == false || $response->result == false),
            'validate_stage1')) {
            $validateimisid = null;
            $validatekey = null;
            if (isset($response->data)) {
                if (isset($response->data['validateimisid'])) {
                    $validateimisid = $response->data['validateimisid'];
                }
                if (isset($response->data['validateimisid'])) {
                    $validatekey = $response->data['validatekey'];
                }
            }

            // Webservice endpoint that takes an encoded validation key, and returns an iMIS id and username.
            $response = $this->validate_stage2($testcase->username, $validateimisid, $validatekey);
            if ($this->test_assert($testcase,
                $testcase->validate_stage2 == !($response == false || $response->result == false),
                'validate_stage2')) {
                $response = $this->check_eligibility($testcase->username);
                $this->test_assert($testcase,
                    $testcase->check_eligibility == !($response == false || $response->result == false),
                    'check_eligibility'
                );
                return true;
            }
        }
    }
}
