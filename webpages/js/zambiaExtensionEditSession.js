$(function() {

    $.zambia.editSession = {

        getHistory: (id) => {
            console.log("Session id: " + id);
            $.ajax({ 
                url: 'api/view_session_history.php?id=' + encodeURIComponent(id),
                method: 'GET',
                success: function(data) {
                    $.zambia.editSession.render(data);
                    $("#historyModal").modal('show');
                },
                error: function(err) {
                    if (err.status == 401) {
                        $.zambia.redirectToLogin();
                    } else if (err.status == 403) {
                        $.zambia.simpleAlert('danger', 'You do not have access to perform this function');
                    } else {
                        $.zambia.simpleAlert('danger', 'There was a problem contacting the server. Try again later?');
                    }
                }
            });
        },

        render: (data) => {
            let $table = $('<table class="table" />');
            let $thead = $('<thead><tr><th>Date</th><th>Who</th><th>Description</th><th>Status</th></tr></thead>')
            $table.append($thead);
            let $tbody = $('<tbody />');
            for (let i = 0; i < data.history.length; i++) {
                console.log("History: " + data.history[i].timestamp);
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
        }
    };

    $(".session-history").click((e) => {
        let id = $("#sessionid").val();
        $.zambia.editSession.getHistory(id);
    });

});