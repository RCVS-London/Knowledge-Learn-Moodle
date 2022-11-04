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
 * Legacy Cron Quiz Reports Task
 *
 * @package    block_goals
 * @copyright  2020 David Aylmer
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

/*
    At 09:00 on Monday = 0 9 * * 1
    At 09:00 on day-of-month 1 = 0 9 1 * *
    At 00:00 on day-of-month 31 and on Sunday in February (never) = 0 0 31 2 0
 */

$tasks = [
    [
        'classname' => 'block_goals\task\goal_reminder_weekly',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '9',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1',
        'disabled' => 1
    ],
    [
        'classname' => 'block_goals\task\goal_reminder_monthly',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '9',
        'day' => '1',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 1
    ],
    [
        'classname' => 'block_goals\task\goal_reminder_testing',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '31',
        'month' => '2',
        'dayofweek' => '2',
        'disabled' => 1
    ]
];
