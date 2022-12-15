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
 * Exception class
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository\exception;

/**
 * Exception class
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_not_authenticated extends \moodle_exception {
    /**
     * Constructor
     *
     * @param string $errorcode
     * @param string $module
     * @param string $link
     * @param array $a
     * @param array $debuginfo
     */
    public function __construct(
        $errorcode = 'api_not_authenticated',
        $module = 'videotime',
        $link = '',
        $a = null,
        $debuginfo = null
    ) {
        parent::__construct($errorcode, $module, $link, $a, $debuginfo);
    }
}
