define([
    'jquery',
    'core/modal_factory',
    'videotimeplugin_repository/modal_video_list',
    'core/str',
    'core/ajax'
], function(
    $,
    ModalFactory,
    ModalVideoList,
    str,
    Ajax
) {

    var init = function(isTotara, contextId) {
        var trigger = $('#id_choose_video');

        var requiredstrings = [];
        requiredstrings.push({key: 'choose_video', component: 'videotime'});
        requiredstrings.push({key: 'pull_from_vimeo_loading', component: 'videotime'});
        requiredstrings.push({key: 'pull_from_vimeo_invalid_videoid', component: 'videotime'});
        requiredstrings.push({key: 'pull_from_vimeo_success', component: 'videotime'});

        str.get_strings(requiredstrings).done(function(strings) {
            Ajax.call([{
                methodname: 'videotimeplugin_repository_get_filter_options',
                args: {}
            }])[0].then(function(response) {
                response.is_totara = isTotara;
                ModalFactory.create({
                    type: ModalVideoList.TYPE,
                    title: strings[0],
                    templateContext: response // Moodle template context.
                }, trigger, response).done(function (modal) { // Totara template context.
                    modal.setContextId(contextId);
                    modal.setReadOnly(false);
                    modal.init();
                    modal.refreshTable();
                });
            });

            $("#id_pull_from_vimeo").on('click', function(e) {
                var originalLabel = $(e.target).val();
                $(e.target).attr('value', strings[1]);
                $(e.target).prop('disabled', true);

                var link = $("#id_vimeo_url").val();
                var videoId = link.split('/')[3];

                if (isNaN(videoId)) {
                    alert(strings[2]);
                    $(e.target).attr('value', originalLabel);
                    $(e.target).prop('disabled', false);
                    return;
                }

                var promises = Ajax.call([
                    {
                        methodname: 'videotimeplugin_repository_api_request', args: {
                            'url': '/videos/' + videoId,
                            'contextid': contextId
                        }
                    }
                ]);

                promises[0].done(function(response) {
                    var apiResponse = JSON.parse(response.data);
                    if (apiResponse.status != 200) {
                        alert('API error: ' + apiResponse.body.error);
                        $(e.target).attr('value', originalLabel);
                        $(e.target).prop('disabled', false);
                        return;
                    }

                    var video = apiResponse.body;

                    alert(strings[3]);

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

                    $(e.target).attr('value', originalLabel);
                    $(e.target).prop('disabled', false);
                }.bind(this));
            });
        }.bind(this));
    };

    return {
        init: init
    };
});
