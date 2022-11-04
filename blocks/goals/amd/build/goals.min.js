/* jshint noempty: false  */
/* jshint latedef: nofunc */
/* jshint unused: false   */
/* jshint esnext: true    */
/* jshint loopfunc:true   */

/*
define(['jquery', 'core/str', 'core/ajax', 'core/templates', 'core/notification'], function($, str, ajax, templates, notification) {
    'use strict';

    var blockconfig = null;
    var strings = null;

    return {
        init: function(config, s) {
            blockconfig = config;
            strings = s;

            var courseLink = $('.brand').attr("href");
            var link = '<p class="home_link">' +
                '   <a class="course_return_link"href="' + courseLink + '">' + strings.returntocourse + '</a>' +
                '</p>';
            $(link).insertBefore('.block_rcgp_quiz_summary.block');

            renderVerticalScoreBars();

            $(document).ready(function () {
                initialsetup();
                animateprogressbars();
                lazyload();
            });

            function initialsetup() {
                // Initial screen setup.
                $('.hideCat').hide();
                $('.accordion').hide();
                $('.hideReflections').hide();

                $('.block_rcgp_quiz_summary .content > .loading').remove();
                $('.current_quiz_results_container').hide().slideDown({queue: true, duration: 'medium'});
                $('.user_reflections_container').hide().slideDown({queue: true, duration: 'medium'});
                $('.all_current_quiz_results_container').hide().slideDown({queue: true, duration: 'medium'});

                $('.current_quiz_results_container').show();
                $('.user_reflections_container').show();
                $('.all_current_quiz_results_container').show();
            }

            function animateprogressbars() {

                if (blockconfig === undefined) {
                    return false;
                }

                if (blockconfig.animateprogressbars === undefined) {
                    return false;
                }

                if (blockconfig.animateprogressbars !== 1) {
                    return false;
                }

                // Animate the progress bars of the category results.
                $(function () {
                    $('.bar').each(function () {
                        var bar_value = $(this).attr('aria-valuenow') + '%';
                        $(this).css({"width": bar_value, "transition": "5s"});
                        $(this).prop('Counter', 0).animate({
                            Counter: $(this).attr('aria-valuetext')
                        }, {
                            duration: 5000,
                            easing: 'swing',
                            step: function (now) {
                                $(this).html('<span>' + Math.ceil(now) + '%</span>');
                            }
                        });

                    });
                });
            }

            function lazyload() {

                if (blockconfig === undefined) {
                    return false;
                }
                if (blockconfig.lazyload === undefined) {
                    return false;
                }
                switch (blockconfig.lazyload.value) {
                    case blockconfig.lazyload.upfront:
                        return false;
                    case blockconfig.lazyload.noquestions:
                        return false;
                    case blockconfig.lazyload.allquestions:
                        return lazyloadquestions();
                    case blockconfig.lazyload.percategory:
                        return lazyloadquestionspercategory(true);
                    case blockconfig.lazyload.percategorypertemplate:
                        return lazyloadquestionspercategory(false);
                    default:
                        break;
                }
            }

            function lazyloadquestions() {
                var url = new URL(window.location.href);
                var attemptid = url.searchParams.get("attempt");
                var promises = ajax.call([{
                    methodname: 'blocks_rcgp_quiz_summary_get_categories_and_questions',
                    args: {attemptid: attemptid}
                }], true);
                $('.statusfooter').slideDown({queue: true, duration: 'slow'});
                $('.statusfooter .progress .bar').css('width', 10 + '%').attr('aria-valuenow', 10 + '%');
                $('.statusfooter .container p em').html(strings.loadingquestions);

                $.when.apply($, promises)
                    .done(function (categories) {
                        $('.statusfooter .container p em').html(strings.processingquestions);

                        categories.forEach(function(category) {
                            templates.render('block_rcgp_quiz_summary/questions', category).done(function (html) {
                                var categoryid = category.categoryid;
                                var categoryname = category.categoryname;
                                rewritecategoryquestions(
                                    categoryid, categoryname, categories.length, html, strings.writingquestionsfor, true
                                );
                            });
                        });
                    })
                    .fail(notification.exception);
            }

            function lazyloadquestionspercategory(templateonserver) {
                var promises = [];
                var categories = [];

                categories = $('input[type="hidden"].get_category').toArray();

                var url = new URL(window.location.href);
                var attemptid = url.searchParams.get("attempt");

                $('.statusfooter').slideDown({queue: true, duration: 'slow'});

                categories.forEach(function(category) {

                    var categoryid = $(category).val();
                    var categoryname = $(category).next().find('p.showCat').text().trim();

                    promises.push(
                        ajax.call([{
                            methodname: 'blocks_rcgp_quiz_summary_get_questions_for_category',
                            args: {
                                attemptid: attemptid,
                                categoryid: categoryid,
                                template: templateonserver,
                                recategorise: blockconfig.notifyofrecategorisation
                            }
                        }])[0].done(function (questions) {
                            if (templateonserver) {
                                rewritecategoryquestions(
                                    categoryid, categoryname, categories.length, questions.html, strings.writingquestionsfor, true
                                );
                            } else {
                                rewritecategoryquestions(
                                    categoryid, categoryname, categories.length, '', strings.processingquestionsfor, false
                                );
                                templates.render('block_rcgp_quiz_summary/questions', questions).done(function (html) {
                                    rewritecategoryquestions(
                                        categoryid, categoryname, categories.length, html, strings.writingquestionsfor, true
                                    );
                                }).fail(notification.exception);
                            }

                        }).fail(notification.exception)
                    );
                });
            }

            function rewritecategoryquestions(categoryid, categoryname, categorycount, html, message) {

                var loaded = 0;
                var width = 100;
                if (message == strings.processingquestionsfor) {
                    $("#accordion-" + categoryid).attr('data-loaded', true);
                    loaded = $(".accordion.questions[data-loaded=true]").length;
                    width = loaded / categorycount * 100;
                    if (width == 100) {
                        message = strings.loadingquestions;
                        categoryname = '';
                        width = 0;
                    }

                }
                if (message == strings.writingquestionsfor) {
                    // Write the HTML.
                    $("#accordion-" + categoryid).html(html);
                    $("#accordion-" + categoryid).attr('data-written', true);
                    loaded = $(".accordion.questions[data-written=true]").length;
                    width = loaded / categorycount * 100;
                    if (width == 100) {
                        $('.statusfooter').slideUp({queue: true, duration: 'slow'});
                    }
                }

                $('.statusfooter .progress .bar').removeClass('active');
                var prewidth = $('.statusfooter .progress .bar').width();
                $('.statusfooter .progress .bar').stop();
                $('.statusfooter .progress .bar').hide();
                $('.statusfooter .progress .bar').width(prewidth + "%");
                $('.statusfooter .progress .bar').show();

                $('.statusfooter .progress .bar').css('width', width + '%').attr('aria-valuenow', width + '%');
                $('.statusfooter .container p em').html(message + categoryname + '  ');
            }

            $('.quiz_category_viewer').click(function (e) {
                e.preventDefault();
                $('.quiz_record_row').removeClass("selected");
                $('.accordion').hide();

                var category_id = ($(this).prev().val());
                var areSelectedVisible = $('.showCat[data-role=' + category_id + ']').is(":hidden");

                $('.showCat').show();
                $('.hideCat').hide();

                if (areSelectedVisible) {
                    $(this).parents().eq(2).removeClass("selected");
                    $('.showCat').show();
                    $('.hideCat').hide();
                    $('.hideCat[data-role=' + category_id + ']').hide();
                    $('.showCat[data-role=' + category_id + ']').show();
                    $(this).attr({"title": "Show questions"});
                    $('.question_container[data-role=' + category_id + ']').hide();
                }
                else if ($('.showCat[data-role=' + category_id + ']').is(":visible")) {
                    var parents = $(this).parents();
                    parents.eq(2).addClass("selected");
                    $('.showCat[data-role=' + category_id + ']').hide();
                    $('.hideCat[data-role=' + category_id + ']').show();
                    $('.question_container[data-role=' + category_id + ']').show();
                    $('#accordion-' + category_id).hide().slideDown({queue: true, duration: 'fast'});
                    animateTo($('#accordion-' + category_id).parent());
                }
            });

            function renderVerticalScoreBars() {
                var score_canvas = document.getElementById("user_peer_chart");

                if (score_canvas === null) {
                    return false;
                }

                var graphx = 30,
                    graphy = 0,
                    scalex = 1.3,
                    scaley = 1.3;

                var ctx = score_canvas.getContext("2d"),
                    user_score = $('#user_summ_score').val(),
                    peer_score = $('#peer_avg_score').val(),
                    user_start = 100 - user_score,
                    peer_start = 100 - peer_score;

                ctx.scale(scalex, scaley);

                // Set background color.
                ctx.fillStyle = "#eee";
                ctx.fillRect(graphx, graphy, 50, graphy + 100 - user_score);

                ctx.fillStyle = "white";
                ctx.font = '12px Arial';
                ctx.fillText(strings.yourresult, graphx - 10, 115);

                // Set foreground color.
                ctx.fillStyle = "#f75f00";
                ctx.fillRect(graphx, graphy + user_start, 50, graphy + user_score);
                ctx.font = '20px Arial';

                var rcgppass = $('#rcgppass').val();
                if ((typeof rcgppass == "undefined" || rcgppass === null) && rcgppass !== 0) {
                    if (user_score >= 20) {
                        ctx.fillStyle = "white";
                    } else {
                        ctx.fillStyle = "black";
                    }
                } else {
                    ctx.shadowColor = "white";
                    ctx.shadowOffsetX = 2;
                    ctx.shadowOffsetY = 2;
                    ctx.shadowBlur = 8;

                    if (user_score >= rcgppass) {
                        ctx.fillStyle = "green";
                    } else {
                        ctx.fillStyle = "#8A0007";
                    }
                }

                // Update: Temporarily ignore shadowing from above.
                ctx.shadowColor = '';
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 0;
                ctx.shadowBlur = 0;

                if (user_score >= 20) {
                    // Ignore rcgp pass colours from above.
                    ctx.fillStyle = "white";
                    if (user_score == 100) {
                        ctx.fillText(user_score + '%', graphx, graphy + user_start + 20);
                    } else {
                        ctx.fillText(user_score + '%', graphx + 5, graphy + user_start + 20);
                    }
                } else {
                    // Ignore rcgp pass colours from above.
                    ctx.fillStyle = "black";
                    ctx.fillText(user_score + '%', graphx + 5, graphy + user_start - 20);
                }

                ctx.shadowColor = "";
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 0;
                ctx.shadowBlur = 0;

                // Set background color.
                ctx.fillStyle = "#eee";
                ctx.fillRect(graphx + 60, graphy, 50, graphy + 100 - peer_score);

                // Set foreground color.
                ctx.fillStyle = "#8A0007";
                ctx.fillRect(graphx + 60, graphy + peer_start, 50, graphy + peer_score);
                ctx.font = '20px Arial';
                if (peer_score >= 20) {
                    ctx.fillStyle = "white";
                    if (peer_score == 100) {
                        ctx.fillText(peer_score + '%', graphx + 60, graphy + peer_start + 20);
                    } else {
                        ctx.fillText(peer_score + '%', graphx + 65, graphy + peer_start + 20);
                    }
                } else {
                    ctx.fillStyle = "black";
                    ctx.fillText(peer_score + '%', graphx + 65, graphy + peer_start - 20);
                }

                ctx.fillStyle = "white";
                ctx.font = '12px Arial';
                ctx.fillText(strings.peerresult, graphx + 60 - 2, 115);
            }

            $('.reflections_header').click(function (e) {
                e.preventDefault();
                if ($('.showReflections').is(":visible")) {
                    $('.showReflections').hide();
                    $('.hideReflections').show();
                    animateTo('.user_reflections_container');
                } else {
                    $('.showReflections').show();
                    $('.showReflections').show();
                    $('.hideReflections').hide();
                }
                $('.user_reflections_div').toggle();
            });

            $('.save_reflection').click(function (e) {
                e.preventDefault();

                $('.save_reflection').attr('disabled', 'disabled');

                var reflectiontext = $('.reflectiontext').val(),
                    reflectionid = $('.reflectionid').val(),
                    attemptid = $('.attemptid').val(),
                    cm_id = $('.cm_id').val();

                // If no reflectionid then assume this is new.
                if (reflectionid === '' || reflectionid === null) {
                    var addReflectionData = {
                        attemptid: attemptid,
                        comments: reflectiontext,
                        comment_type: 'user reflection',
                        task: 'create_record'
                    };

                    $.post('ajax/user_reflections.php', addReflectionData, function (data, status) {
                        var result = JSON.parse(data);
                        if ((status === "success") && (result.result === 'OK') && (result.record)) {
                            reflectionid = result.record.id;
                            $('.reflectionid').val(reflectionid);

                            var new_url = window.location.href.substr(0, window.location.href.indexOf('blocks'));
                            new_url += "mod/certificate/view.php?action=get&id=" + cm_id;
                            new_url += "&reflectionid=" + reflectionid + "&attempt=" + attemptid;
                            $('#reflectiondownload').attr('href', new_url);
                            $('#reflectiondownload').parent().parent().show();
                            displayupdatemessage(strings.yourreflectionshavebeensaved);
                        } else {
                            displayupdatemessage(result);
                        }
                        $('.save_reflection').removeAttr('disabled');
                    });

                } else {
                    var updateReflectionData = {
                        id: reflectionid,
                        comments: reflectiontext,
                        task: 'update_record'
                    };

                    $.post('ajax/user_reflections.php', updateReflectionData, function (data, status) {
                        var result = JSON.parse(data);
                        if ((status === "success") && (result.result === 'OK') && (result.record)) {
                            displayupdatemessage(strings.yourreflectionshavebeensaved);
                        } else {
                            displayupdatemessage(result);
                        }
                        $('.save_reflection').removeAttr('disabled');
                    });
                }
            });

            function displayupdatemessage(message) {
                var updatetext = '<div class="row-fluid update-status">';
                updatetext += '<div class="span12">';
                updatetext += '<div class="alert alert-info">';
                updatetext += ' <p>' + message + '</p>';
                updatetext += '</div>';
                updatetext += '</div>';
                updatetext += '</div>';

                $(updatetext).insertAfter('.save_reflection').parent().parent();
                $('.update-status').fadeOut(3000);

            }

            function animateTo(element) {

                var target = $(element);

                $("html, body").on("scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove", function () {
                    $("html, body").stop();
                });

                $('html, body').animate({scrollTop: $(target).position().top}, 1000, function () {
                    $('html, body').off("scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove");
                });
                return false;
            }
        }
    };
});

 */