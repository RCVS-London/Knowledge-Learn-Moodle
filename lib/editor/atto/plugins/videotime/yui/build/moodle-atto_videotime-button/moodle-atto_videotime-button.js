YUI.add('moodle-atto_videotime-button', function (Y, NAME) {

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

/*
 * @package    atto_bold
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_videotime-button
 */

/**
 * Atto text editor video time plugin.
 *
 * @namespace M.atto_videotime
 * @class button
 * @extends M.atto_videotime.EditorPlugin
 */

var COMPONENTNAME = 'atto_videotime',
    CSS = {
        BUTTON: 'atto_videotime_embed'
    };

Y.namespace('M.atto_videotime').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    initializer: function() {
        // And then the unlink button.
        this.addButton({
            buttonName: 'videotime_embed',
            callback: this._displayDialogue,
            icon: 'icon',
            iconComponent: 'atto_videotime',
            title: 'pluginname'
        });
    },

    /**
     * Display the embed selector.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();

        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('pluginname', COMPONENTNAME),
            width: '800px',
            focusAfterHide: true
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent());

        dialogue.show();
    },

    /**
     * Return the dialogue content for the tool.
     *
     * @method _getDialogueContent
     * @private
     * @return {Node} The content to place in the dialogue.
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile('' +
            '<table class="table">' +
                '<tbody>' +
                    '{{#each instances}}' +
                        '<tr>' +
                            '<td>{{name}}</td>' +
                            '<td><button class="{{../CSS.BUTTON}} btn btn-primary" data-cmid="{{cmid}}">' +
                                '{{{get_string "embed" ../component}}}' +
                            '</button></td>' +
                        '</tr>' +
                    '{{/each}}' +
                '</tbody>' +
            '</table>' +
        '');

        var content = Y.Node.create(template({
            component: COMPONENTNAME,
            CSS: CSS,
            instances: this.get('instances')
        }));

        content.delegate('click', this._insertShortcode, '.' + CSS.BUTTON, this);
        return content;
    },

    /**
     * Insert the picked video time embed into the editor.
     *
     * @method _insertShortcode
     * @param {EventFacade} e
     * @private
     */
    _insertShortcode: function(e) {
        var cmid = e.target.getData('cmid');

        // Hide the dialogue.
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        var host = this.get('host');

        // Focus on the last point.
        host.setSelection(this._currentSelection);

        // And add the character.
        host.insertContentAtFocusPoint('[videotime cmid="' + cmid + '"]');

        // And mark the text area as updated.
        this.markUpdated();
    }

}, {
    ATTRS: {
        /**
         * The content of the example library.
         *
         * @attribute library
         * @type object
         */
        instances: {
            value: {}
        },

    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
