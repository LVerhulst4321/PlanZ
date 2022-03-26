$(function() {

    $.planz.editSession = {

        getHistory: (id) => {
            $.ajax({ 
                url: 'api/view_session_history.php?id=' + encodeURIComponent(id),
                method: 'GET',
                success: function(data) {
                    $.planz.editSession.renderHistory(data);
                    $("#historyModal").modal('show');
                },
                error: function(err) {
                    if (err.status == 401) {
                        $.planz.redirectToLogin();
                    } else if (err.status == 403) {
                        $.planz.simpleAlert('danger', 'You do not have access to perform this function');
                    } else {
                        $.planz.simpleAlert('danger', 'There was a problem contacting the server. Try again later?');
                    }
                }
            });
        },

        geAssignments: (id) => {
            $.ajax({ 
                url: 'api/view_session_participants.php?id=' + encodeURIComponent(id),
                method: 'GET',
                success: function(data) {
                    $.planz.editSession.renderAssignments(data);
                    $("#assignmentModal").modal('show');
                },
                error: function(err) {
                    if (err.status == 401) {
                        $.planz.redirectToLogin();
                    } else if (err.status == 403) {
                        $.planz.simpleAlert('danger', 'You do not have access to perform this function');
                    } else {
                        $.planz.simpleAlert('danger', 'There was a problem contacting the server. Try again later?');
                    }
                }
            });
        },

        renderHistory: (data) => {
            let $table = $('<table class="table" />');
            let $thead = $('<thead><tr><th>Date</th><th>Who</th><th>Description</th><th>Status</th></tr></thead>')
            $table.append($thead);
            let $tbody = $('<tbody />');
            for (let i = 0; i < data.history.length; i++) {
                let $tr = $('<tr />');
                let $time = $('<td />');
                let day = dayjs(data.history[i].timestamp);
                $time.text(day.format('MMM D, YYYY h:mm a'));
                $tr.append($time);

                let $name = $('<td />');
                let $a = $('<a />');
                $a.attr('href', "./AdminParticipants.php?badgeid=" + data.history[i].badgeid);
                $a.text(data.history[i].name ? data.history[i].name : data.history[i].badgeid);
                $name.append($a);
                $tr.append($name);

                let $description = $('<td />');
                $description.text(data.history[i].codedescription);
                $tr.append($description);

                let $status = $('<td />');
                $status.text(data.history[i].status);
                $tr.append($status);

                $tbody.append($tr);
            }

            $table.append($tbody);
            $(".history-content").empty();
            $(".history-content").append($table);
        },

        renderAssignments: (data) => {
            if (data.assignments.length > 0) {
                let $table = $('<table class="table" />');
                let $thead = $('<thead><tr><th>Name</th><th>Mod?</th></tr></thead>')
                $table.append($thead);
                let $tbody = $('<tbody />');
                for (let i = 0; i < data.assignments.length; i++) {
                    let $tr = $('<tr />');
                    let $name = $('<td />');
                    let $a = $('<a />');
                    $a.attr('href', "./AdminParticipants.php?badgeid=" + data.assignments[i].badgeid);
                    $a.text(data.assignments[i].name ? data.assignments[i].name : data.assignments[i].badgeid);
                    $name.append($a);
                    $tr.append($name);

                    let $mod = $('<td />');
                    $mod.text(data.assignments[i].moderator ? 'Yes' : 'No');
                    $tr.append($mod);

                    $tbody.append($tr);
                }

                $table.append($tbody);
                $(".assignment-content").empty();
                $(".assignment-content").append($table);
            } else {
                let $message = $('<p class="text-info">No participants are currently assigned to this session.</p>');
                $(".assignment-content").empty();
                $(".assignment-content").append($message);
            }
        }
    };

    $(".session-history").click((e) => {
        let id = $("#sessionid").val();
        $.planz.editSession.getHistory(id);
    });

    $(".session-assignments").click((e) => {
        let id = $("#sessionid").val();
        $.planz.editSession.geAssignments(id);
    });
});