define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry', 'core/templates',
        'core/ajax', 'core/modal_factory', 'core/modal_events', 'core/str', 'videotimeplugin_repository/select2',
        'videotimeplugin_repository/pagination'], function($, Notification, CustomEvents, Modal, ModalRegistry, Templates, Ajax,
                                                           ModalFactory, ModalEvents, str, select2, Pagination) {

        var registered = false;

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalVideoList = function(root) {
            Modal.call(this, root);
            this.contextId = null;
            this.videos = [];
            this.videoPageIndex = [];
            this.descriptionModal = null;
            this.embedsModal = null;
            this.previewModal = null;
            this.confirmModal = null;
            this.query = null;
            this.filterData = {};
            this.perPage = 10;
            this.pagination = new Pagination(this.perPage, 0, root);
            this.pagination.init();
            this.sort = null;
            this.sortdirections = {};
            this.readOnly = null;
        };

        ModalVideoList.TYPE = 'video-list';
        ModalVideoList.prototype = Object.create(Modal.prototype);
        ModalVideoList.prototype.constructor = ModalVideoList;

        /**
         * Any prerequisites to modal functionality.
         */
        ModalVideoList.prototype.init = function() {
            this.pagination.getContainer().on('pagination.changePage', function() {
                this.refreshTable();
            }.bind(this));

            // Build modals that use Promises for content. This will force it to preload.
            // There was an issue where footers would only display when the modal was opened as second time.
            this.getConfirmModal(function() {});
            this.getPreviewModal(function() {});
        };

        /**
         * Set if modal list is read only (cannot select videos for embedding).
         *
         * @param {boolean} readOnly
         */
        ModalVideoList.prototype.setReadOnly = function(readOnly) {
            this.readOnly = readOnly;
        };

        ModalVideoList.prototype.setContextId = function(contextId) {
            this.contextId = contextId;
        };

        ModalVideoList.prototype.refreshTable = function() {

            let args = {
                'contextid': this.contextId,
                'query': this.query,
                'filter_data': this.filterData,
                'limitfrom': this.pagination.getLimitFrom(),
                'limitnum': this.perPage
            };

            if (this.sort) {
                args.sort = this.sort;
                args.sortdirection = this.sortdirections[this.sort];
            }

            var promises = Ajax.call([
                {
                    methodname: 'videotimeplugin_repository_search_videos', args: args
                }
            ]);

            promises[0].done(function(response) {
                response.data.forEach(function(item) {
                    this.videos[item.uri] = item;
                    this.videoPageIndex[item.uri] = this.pagination.getCurrentPage();
                }.bind(this));
                let context = Object.assign(response, {readonly: this.readOnly});
                Templates.render('videotimeplugin_repository/video_table', context)
                    .then(function(html) {
                        this.pagination.setTotalItems(response.total);
                        this.pagination.render('#pagination-container');

                        this.getRoot().find('.video-list-table').html(html);
                    }.bind(this));
            }.bind(this));
        };

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalVideoList.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getRoot().on(ModalEvents.shown, function() {
                this.getModal().find('.select2').each(function(index, element) {
                    $(element).select2({
                        dropdownParent: this.getRoot()
                    });
                }.bind(this));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=use-video]', function(e) {
                this.showConfirmModal($(e.target).data('uri'));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=show-description]', function(e) {
                this.showDescriptionModal($(e.target).data('uri'));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=show-embeds]', function(e) {
                this.showEmbedsModal($(e.target).data('uri'));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=preview]', function(e) {
                this.showPreviewModal($(e.target).data('uri'));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=search]', function(e) {
                this.setQuery($($(e.target).data('target')).val());
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=clear-search]', function(e) {
                this.query = '';
                $($(e.target).data('target')).val('');
                this.pagination.setCurrentPage(0);
                this.refreshTable();
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=apply-filters]', function() {
                this.applyFilters();
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=clear-filters]', function(e) {
                this.filterData = [];
                $($(e.target).data('target')).val('');
                this.getModal().find('.select2').val(null).trigger('change');
                this.pagination.setCurrentPage(0);
                this.refreshTable();
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, '[data-action=sort]', function(e) {
                this.sort = $(e.target).data('field');
                let direction = 'ASC';
                if (this.sortdirections.hasOwnProperty(this.sort)) {
                    if (this.sortdirections[this.sort] == 'ASC') {
                        direction = 'DESC';
                    } else {
                        direction = 'ASC';
                    }
                }
                this.sortdirections[this.sort] = direction;
                this.refreshTable();
            }.bind(this));
        };

        /**
         * Apply filter form values.
         */
        ModalVideoList.prototype.applyFilters = function() {
            this.filterData = this.getRoot().find('form').serializeArray();
            this.pagination.setCurrentPage(0);
            this.refreshTable();
        };

        ModalVideoList.prototype.useVideo = function(videoUri, overrideSettings) {
            var video = this.videos[videoUri];

            $('#id_vimeo_url').val(video.link);
            $('#id_vimeo_url').attr('readonly', 'readonly');
            $('#id_choose_video').html(video.name);
            $('#id_choose_video').val(video.name);
            $('#id_choose_video').addClass('btn-success');
            if (overrideSettings) {
                $('#id_name').val(video.name);
                $('#id_introeditor').val(video.description);
                $('#id_introeditoreditable').html(video.description);
                $('#id_completion_on_view_time_second').val(video.duration);

                // Add tags
                // First remove any selections.
                $("#fitem_id_tags .form-autocomplete-selection").empty();
                $("#fitem_id_tags #id_tags").empty();
                $.each(video.tags, function(index, tag) {
                    $("#fitem_id_tags #id_tags").append('<option data-iscustom="true" value="' + tag.name +
                        '" selected>' + tag.name + '</option>');
                    $("#fitem_id_tags .form-autocomplete-selection").append('<span role="listitem" data-value="' +
                        tag.name + '" aria-selected="true" class="label label-info"><span aria-hidden="true">× </span>' +
                        tag.name + '</span>');
                });
            }
            this.hide();
        };

        /**
         * Display modal with full video description.
         *
         * @param {string} videoUri
         */
        ModalVideoList.prototype.showDescriptionModal = function(videoUri) {
            if (!this.videos.hasOwnProperty(videoUri)) {
                return;
            }

            var video = this.videos[videoUri];

            this.getDescriptionModal(function(modal) {
                modal.setTitle(video.name);
                modal.setBody(video.description);
                modal.show();
            }.bind(this));
        };

        /**
         * Utilize callback to receive the same instance of the description modal.
         *
         * @param {function} callback
         */
        ModalVideoList.prototype.getDescriptionModal = function(callback) {
            if (!this.descriptionModal) {
                ModalFactory.create({}).done(function(modal) {
                    this.descriptionModal = modal;
                    callback(this.descriptionModal);
                }.bind(this));
            } else {
                callback(this.descriptionModal);
            }
        };

        /**
         * Display modal with full video description.
         *
         * @param {string} videoUri
         */
        ModalVideoList.prototype.showEmbedsModal = function(videoUri) {
            if (!this.videos.hasOwnProperty(videoUri)) {
                return;
            }

            var video = this.videos[videoUri];

            str.get_string('embeds', 'videotime').done(function(string) {
                this.getEmbedsModal(function(modal) {
                    modal.setTitle(video.name + ' ' + string);
                    modal.setBody(Templates.render('videotimeplugin_repository/embeds', {video: video}));
                    modal.show();
                }.bind(this));
            }.bind(this));
        };

        /**
         * Utilize callback to receive the same instance of the embeds modal.
         *
         * @param {function} callback
         */
        ModalVideoList.prototype.getEmbedsModal = function(callback) {
            if (!this.embedsModal) {
                ModalFactory.create({}).done(function(modal) {
                    this.embedsModal = modal;
                    callback(this.embedsModal);
                }.bind(this));
            } else {
                callback(this.embedsModal);
            }
        };

        /**
         * Display modal with full video description.
         *
         * @param {string} videoUri
         */
        ModalVideoList.prototype.showPreviewModal = function(videoUri) {
            if (!this.videos.hasOwnProperty(videoUri)) {
                return;
            }

            var video = this.videos[videoUri];

            this.getPreviousAndNextVideoInSearch(videoUri).then(function(data) {
                str.get_string('preview', 'moodle').done(function(string) {
                    this.getPreviewModal(function(modal) {
                        modal.setTitle(video.name + ' ' + string);
                        modal.setBody(Templates.render('videotimeplugin_repository/preview_modal_body', {
                            video: video,
                            next_video: data.nextVideo,
                            previous_video: data.previousVideo
                        }));
                        modal.show();
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        };

        /**
         * Utilize callback to receive the same instance of the preview modal.
         *
         * @param {function} callback
         */
        ModalVideoList.prototype.getPreviewModal = function(callback) {
            if (!this.previewModal) {
                ModalFactory.create({
                    large: true,
                    footer: Templates.render('videotimeplugin_repository/preview_modal_footer', {readonly: this.readOnly})
                }).done(function(modal) {
                    this.previewModal = modal;
                    modal.getModal().on(CustomEvents.events.activate, '[data-action=use-video]', function() {
                        modal.hide();
                        this.showConfirmModal(modal.getModal().find('#vimeo-embed').data('uri'));
                    }.bind(this));
                    modal.getRoot().on(CustomEvents.events.activate, '[data-action=preview]', function(e) {
                        this.showPreviewModal($(e.target).data('uri'));
                    }.bind(this));
                    callback(this.previewModal);
                }.bind(this));
            } else {
                callback(this.previewModal);
            }
        };

        /**
         * Get previous video based on search criteria.
         *
         * @param {string} videoUri
         * @returns {Promise}
         */
        ModalVideoList.prototype.getPreviousAndNextVideoInSearch = function(videoUri) {
            let page = this.videoPageIndex[videoUri];
            let limitfrom = 0;
            if (page > 0) {
                limitfrom = page * this.perPage - 1; // Negative 1 to cover videos on previous page.
            }

            let args = {
                'contextid': this.contextId,
                'query': this.query,
                'filter_data': this.filterData,
                'limitfrom': limitfrom,
                'limitnum': this.perPage + 2 // Plus 2 to cover videos on next page.
            };

            if (this.sort) {
                args.sort = this.sort;
                args.sortdirection = this.sortdirections[this.sort];
            }

            return new Promise((resolve) => {
                Ajax.call([{methodname: 'videotimeplugin_repository_search_videos', args: args}])[0].done(function (response) {
                    let previousVideo = null;
                    let nextVideo = null;

                    let last = null;
                    let useNext = false;

                    response.data.forEach(function (item, index) {
                        if (useNext) {
                            nextVideo = item;
                            useNext = false;
                        }
                        if (item.uri === videoUri) {
                            previousVideo = last;
                            useNext = true;
                        }

                        this.videos[item.uri] = item;
                        // Calculate page of video. It may not be set yet.
                        let pageIndex = Math.floor((limitfrom + index) / this.perPage);
                        this.videoPageIndex[item.uri] = pageIndex;

                        last = item;
                    }.bind(this));

                    resolve({previousVideo: previousVideo, nextVideo: nextVideo});
                }.bind(this));
            });
        };

        /**
         * Display confirmation modal.
         *
         * @param {string} videoUri
         */
        ModalVideoList.prototype.showConfirmModal = function(videoUri) {
            if (!this.videos.hasOwnProperty(videoUri)) {
                return;
            }

            var video = this.videos[videoUri];

            str.get_strings([
                {key: 'confirmation', component: 'videotime'},
                {key: 'choose_video_confirm', component: 'videotime'}
            ]).done(function(strings) {
                this.getConfirmModal(function(modal) {
                    modal.setTitle(strings[0]);
                    modal.setBody('<p>' + strings[1] + ' "' + video.name +
                        '"?</p><input type="hidden" name="uri" value="' + video.uri + '">');
                    modal.show();
                }.bind(this));
            }.bind(this));
        };

        /**
         * Utilize callback to receive the same instance of the confirm modal.
         *
         * @param {function} callback
         */
        ModalVideoList.prototype.getConfirmModal = function(callback) {
            if (!this.confirmModal) {
                ModalFactory.create({
                    footer: Templates.render('videotimeplugin_repository/confirm_modal_footer', {readonly: this.readOnly})
                }).done(function(modal) {
                    this.confirmModal = modal;
                    modal.getModal().on(CustomEvents.events.activate, '[data-action=use-video]', function() {
                        modal.hide();
                        var uri = modal.getModal().find('[name=uri]').val();
                        var override = modal.getModal().find('[name=override]').prop('checked');
                        this.useVideo(uri, override);
                    }.bind(this));
                    callback(this.confirmModal);
                }.bind(this));
            } else {
                callback(this.confirmModal);
            }
        };

        // Automatically register with the modal registry the first time this module is imported so that you can create modals
        // of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(ModalVideoList.TYPE, ModalVideoList, 'videotimeplugin_repository/modal_video_list');
            registered = true;
        }

        /**
         * Set value on select filter.
         *
         * @param {string} filterName
         * @param {string} value
         */
        ModalVideoList.prototype.setFilterValue = function(filterName, value) {
            this.getRoot().find("[name=" + filterName + "]").val(value).trigger("change");
        };

        /**
         * Set search query value and input value.
         *
         * @param {string} query
         */
        ModalVideoList.prototype.setQuery = function(query) {
            this.getRoot().find("#search-input").val(query);
            this.query = query;
            this.pagination.setCurrentPage(0);
            this.refreshTable();
        };

        return ModalVideoList;
    });
