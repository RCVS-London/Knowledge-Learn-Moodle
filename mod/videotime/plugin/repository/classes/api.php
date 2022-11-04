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
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository;

use videotimeplugin_repository\exception\api_not_authenticated;
use videotimeplugin_repository\exception\api_not_configured;

require_once("$CFG->dirroot/mod/videotime/plugin/pro/vendor/autoload.php");

class api extends \Vimeo\Vimeo
{
    public function __construct()
    {
        if (!$client_id = get_config('videotime', 'client_id')) {
            throw new api_not_configured();
        }

        if (!$client_secret = get_config('videotime', 'client_secret')) {
            throw new api_not_configured();
        }

        if (!$access_token = get_config('videotime', 'vimeo_access_token')) {
            throw new api_not_authenticated();
        }

        parent::__construct($client_id, $client_secret, $access_token);
    }
}
