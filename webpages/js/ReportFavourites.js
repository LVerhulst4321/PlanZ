$(function() {
    $(document).on('click', '.report-favourite', function() {
        let $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }
        let favourite = $button.attr('data-favourite') !== 'true';
        $button.prop('disabled', true);
        $.ajax({
            url: 'SubmitReportFavourite.php',
            type: 'POST',
            data: JSON.stringify({
                "reportName": $button.attr('data-report-name'),
                "favourite": favourite
            }),
            dataType: "json",
            contentType: "application/json; charset=UTF-8",
            success: function(data) {
                $button.prop('disabled', false);
                setFavourite($button, data.favourite);
            },
            error: function(response) {
                $button.prop('disabled', false);
                if (response.status === 401) {
                    $.planz.redirectToLogin();
                } else {
                    showError(response);
                }
            }
        });
    });

    function setFavourite($button, favourite) {
        let label = favourite ? 'Remove from favourites' : 'Add to favourites';
        $button.attr('data-favourite', favourite ? 'true' : 'false');
        $button.attr('aria-pressed', favourite ? 'true' : 'false');
        $button.attr('title', label);
        $button.attr('aria-label', label);
        $button.find('i').toggleClass('bi-star', !favourite).toggleClass('bi-star-fill', favourite);
    }

    function showError(response) {
        let text = (response.responseJSON && response.responseJSON.text) ? response.responseJSON.text : 'Unable to update favourite.';
        $('#report-favourite-alert').remove();
        let $alert = $('<div id="report-favourite-alert" class="alert alert-danger alert-dismissible fade show mt-2" role="alert">' +
            '<span></span>' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '</div>');
        $alert.find('span').first().text(text);
        $('.container, .container-fluid').first().prepend($alert);
    }
});
