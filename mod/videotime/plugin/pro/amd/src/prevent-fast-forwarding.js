
import VideoTimePlugin from "mod_videotime/videotime-plugin";
import Ajax from "core/ajax";
import Notification from "core/notification";
import Log from "core/log";
import {get_string as getString} from "core/str";

export default class PreventFastForwarding extends VideoTimePlugin {
    /**
     * Constructor
     */
    constructor() {
        super('preventfastforwarding');
        this.watchTime = 0;
        this.seeking = false;
        this.duration = 0;
        this.playbackRate = 1;
        this.fastForwardBuffer = 2.5; // Seconds.
        this.lastNotice = 0;
    }

    /**
     * Initialize the videotime instance with prevent fast forwarding module
     * @param {VideoTime} videotime
     * @param {object} instance Prefetched VideoTime instance object.
     */
    initialize(videotime, instance) {
        if (!instance.preventfastforwarding) {
            // Prevent fast forwarding not enabled for this cm.
            super.initialize(videotime);
            return;
        }

        this.getWatchPercent(videotime.getCmId()).then((percent) => {
            videotime.getPlayer().getDuration().then((duration) => {
                this.duration = duration;
                this.watchTime = percent * duration;
                Log.debug("PREVENT FF: duration " + duration);
                Log.debug("PREVENT FF: watch time " + this.watchTime);
            }).catch(Notification.exception);
        }).fail(Notification.exception);

        // Keep track of playback rate.
        videotime.getPlayer().getPlaybackRate().then((playbackRate) => {
            this.playbackRate = playbackRate;
        });
        videotime.getPlayer().on('playbackratechange', (data) => {
            this.playbackRate = data.playbackRate;
        });

        videotime.getPlayer().on('timeupdate', (data) => {
            Log.debug("PREVENT FF: watch time " + data.seconds);

            if (data.seconds > this.watchTime + this.fastForwardBuffer * this.playbackRate) {
                // Seeked too far forward.
                Log.debug("PREVENT FF: Preventing...");
                videotime.getPlayer().setCurrentTime(this.watchTime);
                if (Date.now() > this.lastNotice + 5000) {
                    this.lastNotice = Date.now();
                    getString('preventfastforwardingmessage', 'mod_videotime', {
                        percent: Math.round(this.watchTime * 100 / this.duration)
                    }).then(this.message).fail(Notification.exception);
                }
            } else if (data.seconds > this.watchTime) {
                this.watchTime = data.seconds;
                Log.debug("PREVENT FF: setting watch time " + data.seconds);
            }
        });

        super.initialize(videotime);
    }

    /**
     * Send notification to user interface
     *
     * @param {string} message message to send
     */
    message(message) {
        Notification.addNotification({
            message: message,
            type: 'info'
        });
    }

    /**
     * Get how far (in percent 0.0 - 1.0) someone has watched a video.
     *
     * @param {int} cmId
     * @returns {Promise}
     */
    getWatchPercent(cmId) {
        return Ajax.call([{
            methodname: 'videotimeplugin_pro_get_watch_percent',
            args: {cmid: cmId}
        }])[0];
    }
}
