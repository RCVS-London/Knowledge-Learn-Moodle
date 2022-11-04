# Video Time Pro #

Video Time Pro has advanced features to
- track the user’s viewing time using activity completion
- sync the viewing percentage to the gradebook NEW (1.0)
- insert a video directly on the course page (label mode) NEW (1.0)
- resume the video where you left of NEW (1.1)
- automatically load the next activity when the student completes video NEW (1.1)
- get insights about each user’s viewing time
- set default embed options for the plugin
- and override the instances' embed options globally. 

## How to install

> **Video Time must be installed prior to installing Video Time Pro. Download it here: <https://moodle.org/plugins/pluginversions.php?plugin=mod_videotime>**

#### Option 1: Install from zip package (recommended)
1. Download Video Time Pro
2. Login to your Moodle site as an admin and go to Administration > Site administration > Plugins > Install plugins.
3. Upload the ZIP file. You should only be prompted to add extra details (in the Show more section) if the plugin is not automatically detected.
4. If your target directory is not writeable, you will see a warning message.
5. Check the plugin validation report

#### Option 2: Install manually on server
1. Download Video Time Pro
2. Upload or copy it to your Moodle server.
Unzip it in the `/mod/videotime/plugin` directory.
3. In your Moodle site (as admin) go to Settings > Site administration > Notifications (you should, for most plugin types, get a message saying the plugin is installed).

For more detailed info, visit <https://docs.moodle.org/35/en/Installing_plugins>

## License ##

2019 bdecent gmbh <https://bdecent.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Testing 'free' features with Pro installed

Set `$CFG->disable_videotime_pro = true` in your `config.php` file.

## Changes
