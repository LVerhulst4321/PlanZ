$(function() {

    $.zambia.newAccount = {

        validateFormFields: ($input) => {

            let name = $('input#name').val();
            let password = $('input#password').val();
            let cpassword = $('input#cpassword').val();

            $input.removeClass('is-invalid');
            $input.closest('.form-group').find('.form-text').hide();
            if (name !== '' && password !== '' && cpassword !== '' 
                && password.length >= 8 && cpassword === password) {

                $('button').prop('disabled', false);
            } else {
                $('button').prop('disabled', true);

                if ($input.prop('name') === 'name' && name === '') {
                    $input.addClass('is-invalid');
                    $input.closest('.form-group').find('.form-text').show();
                } else if ($input.prop('name') === 'password') {
                    if (password === '' || password.length < 8) {
                        $input.addClass('is-invalid');
                        $input.closest('.form-group').find('.form-text').show();
                    }

                    if (cpassword !== '' && cpassword !== password) {
                        $('input#cpassword').addClass('is-invalid');
                        $('input#cpassword').closest('.form-group').find('.form-text').show();
                    }
                } else if ($input.prop('name') === 'cpassword' && cpassword !== password) {
                    $input.addClass('is-invalid');
                    $input.closest('.form-group').find('.form-text').show();
                }
            }
        },

        create: (badgeName, password, control, controliv) => {
            $.zambia.clearAlerts('danger');
            $.ajax({ 
                url: 'api/create_new_account.php',
                data: JSON.stringify({
                    "badgeName": badgeName,
                    "password": password,
                    "control": control,
                    "controliv": controliv
                }),
                dataType: "json",
                contentType: "application/json; charset=UTF-8",
                method: 'POST',
                success: function(data) {
                    $.zambia.redirectToLogin();
                },
                error: function(err) {
                    if (err.status >= 200 && err.status < 300) {
                        $.zambia.redirectToLogin();
                    } else {
                        $.zambia.simpleAlert('danger', 'There was a problem contacting the server. Your changes might not have been saved');
                        $('html,body').animate({ scrollTop: 0 }, 'slow');
                    }
                }
            });
        }
    };

    $('input').on('change keyup paste cut', (e) => {        
        setTimeout(function() {
            $.zambia.newAccount.validateFormFields($(e.currentTarget));
        }, 0);
    })

    $('form').submit((e) => {
        e.preventDefault();
        e.stopPropagation();

        $.zambia.newAccount.create( $('input#name').val(),  $('input#password').val(), $('input#control').val(), $('input#controliv').val());
    });
});
