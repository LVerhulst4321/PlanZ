$(function() {

    $.planz.mySchedule = {

        timers: {},

        postData: (data) => {
            $('.alert-danger').remove();

            $.ajax({ 
                url: 'api/confirm_session_assignment.php',
                method: 'POST',
                contentType: "application/json; charset=UTF-8",
                dataType: "json",
                data: JSON.stringify(data),
                success: function(data) {
                    // nothing required
                },
                error: function(err) {
                    if (err.status < 300) {
                        // this isn't an error. Why am I in the error function? Bad jQuery; no biscuit.
                    } else if (err.status == 401) {
                        $.planz.redirectToLogin();
                    } else {
                        $.planz.simpleAlert('danger', 'There was a problem contacting the server. Your changes might not have been saved. Try again later?');
                    }
                }
            });
        },

        sendConfirmation: (sessionId, participantSessionid, value) => {
            $.planz.mySchedule.postData({
                sessionId: sessionId,
                participantSessionId: participantSessionid,
                value: value
            });
        },

        sendNotes: (sessionId, participantSessionid, notes) => {
            $.planz.mySchedule.postData({
                sessionId: sessionId,
                participantSessionId: participantSessionid,
                notes: notes
            });
        },

        sendNotesWithDelay: (e) => {
            let $field = $(e.target);
            let sessionId = $field.data('sessionid');
            let participantSessionid = $field.data("participantonsessionid");
            let name = 'notes-' + sessionId;
            let value = $field.val();

            if ($.planz.mySchedule.timers[name]) {
                clearTimeout($.planz.mySchedule.timers[name]);
                $.planz.mySchedule.timers[name] = null;
            }
            $.planz.mySchedule.timers[name] = setTimeout(() => {
                $.planz.mySchedule.sendNotes(sessionId, participantSessionid, value);
            }, 1000);
        }
    }

    $(".confirmation-select").change((e) => {
        let $select = $(e.target);
        let sessionId = $select.data("sessionid");
        let participantSessionid = $select.data("participantonsessionid");
        $.planz.mySchedule.sendConfirmation(sessionId, participantSessionid, $select.val());
    });

    $('input[type="text"]').on('keyup', (e) => {
        $.planz.mySchedule.sendNotesWithDelay(e);
    });

    $('input[type="text"]').on('paste', (e) => {
        setTimeout(function() {
            $.planz.mySchedule.sendNotesWithDelay(e);
        }, 0);
    });

    $('input[type="text"]').on('cut', (e) => {
        setTimeout(function() {
            $.planz.mySchedule.sendNotesWithDelay(e);
        }, 0);
    });

});