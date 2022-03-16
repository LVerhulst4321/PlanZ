$(function() {

    $.zambia.feedback = {

        filterTimer: null,
        commentTimers: {},

        fetch: function(term) {
            $('#load-spinner').show();
            $.ajax({ 
                url: 'api/session_feedback_list.php' + (term ? '?q=' + encodeURIComponent(term) : ''),
                method: 'GET',
                success: function(data) {
                    $('#load-spinner').hide();
                    $.zambia.feedback.render(data, term);
                },
                error: function(err) {
                    if (err.status == 401) {
                        $.zambia.redirectToLogin();
                    } else {
                        $.zambia.simpleAlert('danger', 'There was a problem contacting the server. Try again later?');
                    }
                }
            });
        },

        filter: (term, immediate) => {
            if ($.zambia.feedback.filterTimer) {
                clearTimeout($.zambia.feedback.filterTimer);
                $.zambia.feedback.filterTimer = null;
            }
            $.zambia.feedback.filterTimer = setTimeout(function() {
                $.zambia.feedback.fetch(term);
            }, immediate ? 10 : 1000);
        },

        sendToServer: (e) => {
            let $select = $(e.target);
            let sessionId = $select.closest('.session-block').data('sessionid');
            let name = $select.attr('name');
            let value = $select.prop("tagName") === 'SELECT' ? $select.val() : $select.prop('checked'); 
            $.zambia.feedback.sendToUpdateServer(sessionId, name, value);
        },

        sendToServerWithDelay: (e) => {
            let $field = $(e.target);
            let sessionId = $field.closest('.session-block').data('sessionid');
            let name = $field.attr('name');
            let value = $field.val();

            if ($.zambia.feedback.commentTimers[name]) {
                clearTimeout($.zambia.feedback.commentTimers[name]);
                $.zambia.feedback.commentTimers[name] = null;
            }
            $.zambia.feedback.commentTimers[name] = setTimeout(() => {
                console.log("comment changed... " +  sessionId + " " + name + " " + value);
                $.zambia.feedback.sendToUpdateServer(sessionId, name, value);
            }, 1000);
        },

        sendToUpdateServer: (sessionId, name, value) => {
            $.zambia.clearAlerts('danger');
            $.ajax({ 
                url: 'api/session_feedback_update.php',
                data: JSON.stringify({
                    "sessionId": sessionId,
                    "name": name,
                    "value": value
                }),
                dataType: "json",
                contentType: "application/json; charset=UTF-8",
                method: 'POST',
                success: function(data) {
                },
                error: function(err) {
                    if (err.status >= 200 && err.status < 300) {
                        // JQuery sucks
                    } else if (err.status == 401) {
                        $.zambia.redirectToLogin();
                    } else {
                        $.zambia.simpleAlert('danger', 'There was a problem contacting the server. Your changes might not have been saved');
                        $('html,body').animate({ scrollTop: 0 }, 'slow');
                    }
                }
            });
        },

        render: function(data, term) {
            let $sessionList = $('#session-list');
            $sessionList.empty();
            if (data && data.categories) {
                for (let i = 0; i < data.categories.length; i++) {
                    let name = '<h4 class="mt-4">' + data.categories[i].name + '</h4>';
                    $sessionList.append($(name));
                    if (data.categories[i].sessions) {
                        for (let j = 0; j < data.categories[i].sessions.length; j++) {
                            let session = data.categories[i].sessions[j];
                            let $wrapper = $('<div class="ml-2 mb-5 session-block" data-sessionid="' + session.sessionId + '"/>');
                            let $p = $('<p class="mt-0 mb-2"/>');
                            if (term) {
                                term = term.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&"); // escape any special characters
                                let title = '<b>' + session.title.replace(new RegExp("(" + term + ")", "gi"), "<mark>$1</mark>") + "</b><br />";
                                let text = '<span>' + session.description.replace(new RegExp("(" + term + ")", "gi"), "<mark>$1</mark>") + '</span>';
                                $p.html(title + text);
                            } else {
                                let title = '<b>' + session.title + "</b><br />";
                                let text = '<span>' + session.description + '</span>';
                                $p.html(title + text);
                            }
                            $wrapper.append($p);
                            let $row1 = $('<div class="row"></row>');
                            let $attendQuestion = $('<div class="form-row col-lg-6 align-items-baseline"><label class="col-auto small" for="attend-question">Are you likely to attend this session?</label>' +
                                '<div class="col-auto">' +
                                '<select id="attend-question" class="form-control form-control-sm col-auto" name="attend">' + 
                                    '<option value=""></option><option value="1">Very likely</option><option value="2">Likely</option><option value="3">Maybe</option><option value="4">Unlikely</option><option value="5">Very unlikely</option></select>' 
                                    + '</div></div>');
                            let $virtualQuestion = $('<div class="form-row col-lg-6 align-items-baseline">' 
                                    + '<label class="col-auto small" for="attend-question-' + session.sessionId + '">If so, how will you attend this session?</label>'
                                    + '<div class="col-auto" name="attendance-type">'
                                    + '<select id="attend-question-' + session.sessionId + '" class="form-control form-control-sm col-auto" name="attend-type">' + 
                                        '<option value=""></option><option value="1">In-person</option><option value="2">Virtually</option><option value="3">Either works</option></select>' 
                                        + '</div></div>');
                            let $row2 = $('<div class="row"></row>');
                            let $assignmentQuestion = $('<div class="form-row col-lg-6 align-items-baseline"><label class="col-auto small" for="attend-question">Do you want to be assigned to this session?</label>' +
                                '<div class="col-auto">' +
                                '<select id="attend-question" class="form-control form-control-sm col-auto" name="interest">' + 
                                    '<option value=""></option><option value="1">So much yes!</option><option value="2">Yes</option><option value="3">Maybe</option><option value="4">Only as a last resort</option><option value="5">Nope nope nope nope!</option></select>' 
                                    + '</div></div>');
                            let $moderatorQuestion = $('<div class="form-row col-lg-6 align-items-baseline">' 
                                                        + '<div class="form-check mb-2">'
                                                        +   '<input class="form-check-input" type="checkbox" id="moderate-' + session.sessionId + '"  name="moderate" />'
                                                        +   '<label class="form-check-label small" for="moderate-' + session.sessionId + '">'
                                                        +      'I volunteer to moderate this panel'
                                                        +   '</label></div></div>');
                            let $row3 = $('<div class="row"><div class="col-12 form-group">' 
                                + '<label for="reason-' + session.sessionId + '" class="small sr-only">Panel qualifications</label>'
                                + '<input type="text" class="form-control form-control-sm" id="reason-' + session.sessionId + '" placeholder="Tell us why you\'d be great on this session..." name="comments" />'
                            + '</div></div>');
                            if (session.feedback) {
                                if (session.feedback.comments) {
                                    $row3.find('input').val(session.feedback.comments);
                                }
                                if (session.feedback.attend) {
                                    $attendQuestion.find('select').val(session.feedback.attend);
                                }
                                if (session.feedback.attendType) {
                                    $virtualQuestion.find('select').val(session.feedback.attendType);
                                }
                                if (session.feedback.interest) {
                                    $assignmentQuestion.find('select').val(session.feedback.interest);
                                }
                                if (session.feedback.moderate) {
                                    $moderatorQuestion.find('input').prop('checked', session.feedback.moderate);
                                }
                            }
                            $row1.append($attendQuestion);
                            $row1.append($virtualQuestion);
                            $row2.append($assignmentQuestion);
                            $row2.append($moderatorQuestion);
                            $wrapper.append($row1);
                            if (data.interest && !session.inviteOnly) {
                                $wrapper.append($row2);
                                $wrapper.append($row3);
                            }
                            $sessionList.append($wrapper);
                        }
                    }
                }
            }
        }
    };

    $('#load-spinner').show();

    $("#filter").keyup((e) => {
        $.zambia.feedback.filter(e.target.value);
    });

    $("#clearFilter").click((e) => {
        $("#filter").val('');
        $.zambia.feedback.filter('', true);
    });
    $.zambia.feedback.fetch()

    $('#session-list').on('change', 'select, input[type="checkbox"]', (e) => {
         $.zambia.feedback.sendToServer(e)
    });

    $('#session-list').on('keyup', 'input[type="text"]', (e) => {
        $.zambia.feedback.sendToServerWithDelay(e, e.target.val);
    });

    $('#session-list').on('paste', 'input[type="text"]', (e) => {
        setTimeout(function() {
            $.zambia.feedback.sendToServerWithDelay(e);
        }, 0);
    });

    $('#session-list').on('cut', 'input[type="text"]', (e) => {
        setTimeout(function() {
            $.zambia.feedback.sendToServerWithDelay(e);
        }, 0);
    });

});