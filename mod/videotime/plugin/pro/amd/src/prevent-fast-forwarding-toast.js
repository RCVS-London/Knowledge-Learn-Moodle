import VideoTimePlugin from "videotimeplugin_pro/prevent-fast-forwarding";
import * as Toast from 'core/toast';

export default class PreventFastForwarding extends VideoTimePlugin {

    /**
     * Send notification to user interface
     *
     * @param {string} message message to send
     */
    message(message) {
        Toast.add(message);
    }
}
