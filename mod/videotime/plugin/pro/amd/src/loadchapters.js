/*
 * Load chapters into tab
 *
 * @package    videotimetab_chapter
 * @module     videotimetab_chapter/loadchapters
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import VideoTimePlugin from "mod_videotime/videotime-plugin";
import Notification from "core/notification";
import Templates from "core/templates";

export default class PreventFastForwarding extends VideoTimePlugin {
    /**
     * Initialize the videotime instance with prevent fast forwarding module
     * @param {VideoTime} videotime
     * @param {object} instance Prefetched VideoTime instance object.
     */
    initialize(videotime, instance) {
        let player = videotime.getPlayer();
        player.on('loaded', () => {
            let container = document.querySelector('#chapter-' + instance.id + ' .videotimetabs_chapters');
            if (container) {
                player.getChapters().then(chapters => {
                    chapters.forEach(chapter => {
                        let s = Math.floor(Number(chapter.startTime));
                        if (s >= 3600) {
                            chapter.starttimedisplay = (Math.floor(s / 3600)).toString().padStart(2, '0') + ':' +
                                (Math.floor(s % 3600 / 60)).toString().padStart(2, '0') + ':' +
                                (Math.floor(s % 60)).toString().padStart(2, '0');
                        } else if (s >= 60) {
                            chapter.starttimedisplay = (Math.floor(s % 3600 / 60)).toString() + ':' +
                                (Math.floor(s % 60)).toString().padStart(2, '0');
                        } else {
                            chapter.starttimedisplay = (Math.floor(s % 60)).toString();
                        }
                        player.addCuePoint(chapter.startTime, {
                            starttime: chapter.startTime
                        });
                    });
                    Templates.render('videotimetab_chapter/chapters', {
                        chapters: chapters
                    }).then(Templates.replaceNodeContents.bind(Templates, container)).fail(Notification.exception);
                }).catch(Notification.exception);
            }
        });
    }
}
