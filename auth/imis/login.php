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
 * Login page that uses the current moodle site login page as the form action
 *
 * @package    auth_imis
 * @copyright  2021 David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreStart
require __DIR__ . '../../../config.php';

// @codingStandardsIgnoreLine. Login page needs CFG.
global $CFG, $PAGE;

// @codingStandardsIgnoreLine. This form of include is correct for language construct. Moodle sniffer incorrectly reports.
require_once $CFG->dirroot . '/auth/imis/locallib.php';

$customcssurl = (new moodle_url('/auth/imis/custom.css'))->out();
$formactionurl = (new moodle_url('/login/index.php'))->out();

$PAGE->set_url('/auth/imis/login.php'); // Defined here to avoid notices on errors etc

// Prevent caching of this page to stop confusion when changing page after making AJAX changes
$PAGE->set_cacheable(false);
$PAGE->set_context(context_system::instance());

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta
            name="viewport"
            content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <title>VetGDP Adviser training login</title>
    <link
            rel="stylesheet"
            href="<?php echo $customcssurl; ?>"
    />
</head>

<body>
<div id="top_banner">
    <a href="/" name="home">
        <img
                src="https://vetgdptraining.rcvs.org.uk/VetGDP%2BRGB%5B3%5D.jpg"
                class="logo"
                style="float: right; height: 75px; margin-top: -0.8em"
                alt="VetGDP logo"
        />
        <img
                src="https://onecpd.rcvs.org.uk/static/images/logo.svg"
                class="logo"
                style="float: left"
                alt="RCVS logo"
        />
    </a>
</div>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div id="navbarNav">
        <span class="navbar-brand">&nbsp;</span>
    </div>
</nav>
<main style="flex: 1">
    <?php
        error_output();
    ?>
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-12 col-md-8">
                <h3>VetGDP Adviser training</h3>
                <br />
                <p>
                    Please login below using your RCVS My Account credentials to
                    access the VetGDP Adviser training.
                </p>
                <p>
                    Please note that this training is only available to veterinary surgeons who have already started the course. I you wish to start the course please visit
                    <a href="https://academy.rcvs.org.uk/">RCVS Academy</a>
                    if you have any queries.
                </p>
                <br />
                <form
                        action="<?php echo $formactionurl; ?>"
                        method="post"
                        name="form"
                        id="form"
                >
                    <div class="form-group">
                        <label for="usernameInput">My Account username</label>
                        <input
                                type="text"
                                class="form-control"
                                name="username"
                                id="usernameInput"
                                aria-describedby="usernameHelp"
                                placeholder="My Account username"
                        />
                        <small id="usernameHelp" class="form-text text-muted">
                            If you've forgotten your RCVS My Account credentials, you can
                            <a href="https://myaccount.rcvs.org.uk/" target="_blank"
                            >reset your username or password</a
                            >.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="passwordInput">Password</label>
                        <input
                                type="password"
                                name="password"
                                class="form-control"
                                id="passwordInput"
                                placeholder="Password"
                        />
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <br />
                    <br />

                    <!-- <p><input type="text" name="username" size="15" /></p>
                              <p><input type="password" name="password" size="15" /></p>
                              <p><input type="submit" name="Submit" class="btn btn-primary" value="Login" /></p> -->
                </form>
            </div>
        </div>
    </div>
</main>

<footer>
    <div class="row">
        <div class="col-12 ml-auto" style="padding-top: 1.5em">
          <span class="float-right" style="padding-right: 1em">
            © Copyright
            <a href="https://rcvs.org.uk/">RCVS</a>
            2021
          </span>
        </div>
    </div>
</footer>
</body>

<script
        src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js"
        integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k"
        crossorigin="anonymous"
></script>
</html>
