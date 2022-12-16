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
 * Authentication Plugin: Learn RCVS Knowledge Email Authentication
 *
 * @author 2021 David Aylmer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth_learn
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Email authentication plugin.
 */
class auth_plugin_learn extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'learn';
        $this->config = get_config('auth_learn');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_learn() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    public function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    public function can_signup() {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    public function user_signup($user, $notify=true) {
        // Standard signup, without custom confirmatinurl.

        // TODO: hack alert
        // Moodle 3.8 introduced new callbacks: extend_signup_form($mform) and validate_extend_signup_form($data).
        // Unfortunately, while $mform above is malleable, $data is passed by value and not reference, so removing.
        // the authlib requirement to enter email address twice.
        // (OR ask for username and email separately) isn't possible. Double email and username are still required.
        // The callbacks cannot prevent that.

        $user->username = core_text::strtolower($user->email);

        return $this->user_signup_with_confirmation($user, $notify);
    }

    /**
     * Sign up a new user ready for confirmation.
     *
     * Password is passed in plaintext.
     * A custom confirmationurl could be used.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     * @param string $confirmationurl user confirmation URL
     * @return boolean true if everything well ok and $notify is set to true
     * @throws moodle_exception
     * @since Moodle 3.8
     */
    public function user_signup_with_confirmation($user, $notify=true, $confirmationurl = null) {
        global $CFG, $DB, $SESSION;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }

        $user->id = user_create_user($user, false, false);

        user_add_password_history($user->id, $plainpassword);

        // Save any custom profile field information.
        profile_save_data($user);

        // Save wantsurl against user's profile, so we can return them there upon confirmation.
        if (!empty($SESSION->wantsurl)) {
            set_user_preference('auth_learn_wantsurl', $SESSION->wantsurl, $user);
        }

        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        if (! send_confirmation_email($user, $confirmationurl)) {
            print_error('auth_learnnoemail', 'auth_learn');
        }

        if ($notify) {
            global $CFG, $PAGE, $OUTPUT;
            $emailconfirm = get_string('emailconfirm');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($PAGE->course->fullname);
            echo $OUTPUT->header();
            notice(get_string('emailconfirmsent', '', $user->email), "$CFG->wwwroot/index.php");
        } else {
            return true;
        }
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    public function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    public function user_confirm($username, $confirmsecret) {
        global $DB, $SESSION;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->auth != $this->authtype) {
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret == $confirmsecret && $user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->secret == $confirmsecret) {
                $DB->set_field("user", "confirmed", 1, array("id" => $user->id));

                if ($wantsurl = get_user_preferences('auth_learn_wantsurl', false, $user)) {
                    // Ensure user gets returned to page they were trying to access before signing up.
                    $SESSION->wantsurl = $wantsurl;
                    unset_user_preference('auth_learn_wantsurl', $user);
                }

                return AUTH_CONFIRM_OK;
            }
        } else {
            return AUTH_CONFIRM_ERROR;
        }
    }

    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled.
     * @return bool
     */
    public function is_captcha_enabled() {
        return get_config("auth_{$this->authtype}", 'recaptcha');
    }

    public function signup_form() {
        global $CFG;
        echo "<script>console.log('hello');</script>";
        require_once($CFG->dirroot.'/auth/'.$this->authtype.'/signup_form.php');
        return new login_signup_form(null, null, 'post', '', array('autocomplete' => 'on'));
    }

}


