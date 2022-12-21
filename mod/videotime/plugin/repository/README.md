# Video Time #

The Video Time product family is a set of plugins to integrate Vimeo videos into moodle. The base activity is free to use and makes embedding videos into moodle super easy. The pro add-on provides a lot of features like tracking, resume, in-activity, app support, privacy support, next activity and many more. Business connects moodle to Vimeo, allowing the teacher to simply choose the video from within moodle.

# Video Time Repository #

Video Time Repository is an extension to Video Time Pro (i.e. it depends on pro). Repository provides
- a direct integration with vimeo (choose the video directly within the activity)
- metadata synchronisation from vimeo to moodle/totara
- preview mode (show a preview image for each activity)

# Installation #

> **Video Time must be installed prior to installing Video Time Pro. Download it here: <https://moodle.org/plugins/pluginversions.php?plugin=mod_videotime>**
> **This plugin should also be bundled with Video Time Pro: <https://bdecent.de/product/video-time-pro/>**
> You can find the documentation here: <a href="https://docs.moodle.org/en/VideoTime">https://docs.moodle.org/en/VideoTime</a>

## Configuring Vimeo API (Required)

#### Step 1: Creating a Vimeo App 

1. Go to <https://developer.vimeo.com/apps/new> and login with your Vimeo account
2. Enter a name and description for your app. Example: Video Time Repository API
3. Ensure the checkbox "No. The only Vimeo accounts that will have access to the app are my own" is checked
4. Agree to Vimeo's Terms of Service and click "Create App"
5. You should now be taken to your new app
6. Click "Edit settings"
7. Enter an App description, this will be displayed to admins when authenticating with Vimeo
8. Enter App URL, it **must** be set to {{wwwroot}}/mod/videotime/plugin/repository/redirect.php
9. 
* Example: If your Moodle site is at https://learning.com/moodle then...
* App URL is: https://learning.com/moodle/mod/videotime/plugin/repository/redirect.php

Also add {{wwwroot}}/mod/videotime/plugin/repository/redirect.php to "Your callback URLs" in your App

Click "Update" to finish creating a Vimeo App

#### Step 2: Entering Vimeo Client ID and Secret

Creating an App will generate a unique Client ID and Secret. This must be entered in Moodle before authenticating.

1. Go to <https://developer.vimeo.com/apps>
2. Click on your App
3. Copy down the Client Identifier (near the top) and the Client Secret (Manage App Secrets)
4. In Moodle, go to Site administration / Plugins / Activity modules / Video Time / Video Time settings
5. Enter Vimeo Client ID and Vimeo Client Secret
6. Click "Save changes"
7. Got to Site administration / Plugins / Activity modules / Video Time / Authenticate with Vimeo
8. Click "Authenticate with Vimeo", you will be redirected to Vimeo to authorize the App
9. After successful authorization you will be redirected back to Moodle

#### Step 3: Wait for video discovery

After authorization Moodle will work behind the scenes to pull in your Vimeo account's video information. For larger 
accounts you may not see your videos populate immediately.


# License #

2021 bdecent gmbh <https://bdecent.de>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
