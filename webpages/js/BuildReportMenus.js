
$(function() {
    $('#build-report-btn').click(function() {
        let sortOrder = $('#ordering').val();
        let url = $('#build-report-btn').closest('form').attr("action");
        $('.alert').remove();
        $('#build-report-btn .spinner-border').show();        
        $.ajax({
            url: url,
            type: 'POST',
            data: JSON.stringify({
                "sortOrder": sortOrder
            }),
            dataType: "json",
            contentType: "application/json; charset=UTF-8",
            success: function(data, response) {
                $('#build-report-btn .spinner-border').hide();
                showMessage(data);
            },
            error: function(response) {
                if (response.status === 401) {
                    $.planz.redirectToLogin();
                } else {
                    $('#build-report-btn .spinner-border').hide();
                    showMessage(response.responseJSON);
                }
            }
        });
    });

    function showMessage(data) {
        if (typeof user === 'string') {
            try {
                data = JSON.parse(data);
            } catch {
                if (data.indexOf('<div') == 0) {
                    data = $(data).text();
                }
                data = { "severity": "danger", "text": data};
            }
        }
        $.planz.simpleAlert(data.severity, data.text);
    }
});