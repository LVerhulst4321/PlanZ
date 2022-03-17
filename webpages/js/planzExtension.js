$(function() {
    $.planz = $.planz || {};

    $.planz.redirectToLogin = () => {
        window.location = '/';
    };

    $.planz.clearAlerts = (alertType) => {
        if (alertType) {
            $('.alert-' + alertType).remove();
        } else {
           $('.alert').remove();
        }
    };

    $.planz.simpleAlert = (severity, text) => {
        let $alert = $('<div class="alert alert-' + severity + '" />');
        $alert.text(text);

        let $parent = $('.container');
        if ($parent.length > 0) {
            $parent.first().prepend($alert);
        } else {
            $parent = $('.navbar');
            if ($parent.length > 0) {
                $parent().first().after($alert);
            }
        }
    };
});