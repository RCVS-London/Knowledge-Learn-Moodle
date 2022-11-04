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
 * Version information
 *
 * @package    auth_imis
 * @copyright  2021 Felix Michaux, David Aylmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Declaration here prevents linting issues and documents code better.
 * @var \core\plugininfo\auth $plugin;
 *
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2021042207;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2019111200;        // Requires this Moodle version.

// @codingStandardsIgnoreLine.
$plugin->component = 'auth_imis';         // Full name of the plugin (used for diagnostics).


