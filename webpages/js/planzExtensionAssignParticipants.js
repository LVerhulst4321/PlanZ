$(function() {

    $.planz.assignParticipants = {

        fetchParticipant: (id) => {
            $.ajax({ 
                url: 'api/fetch_participant.php?badgeid=' + encodeURIComponent(id),
                method: 'GET',
                success: function(data) {
                    $.planz.assignParticipants.showBio(data);
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

        showBio: (data) => {
            console.log("okay");
            if (data.participant && data.participant.name && data.participant.bio) {
                let bio = data.participant.bio.text || "";
                let name = data.participant.name.badgeName;

                $('#BioBtn').button('reset');
                setTimeout(function() {
                        $("#BioBtn").button().prop("disabled", true);
                    },
                0);

                let $popover = $("#popover-target");
                $popover.attr("title", 'Bio for ' + name + "&nbsp;<i id='popoverClose' class='icon-remove-sign pull-right'></i>");
                $popover.data("content", bio);

                $("#popover-target").popover('show');
            }
        }
    }

    $("#BioBtn").click((e) => {
        let badgeid = $('#partDropdown').val();
        $.planz.assignParticipants.fetchParticipant(badgeid);
    });
})