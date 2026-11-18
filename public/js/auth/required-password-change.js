$(function () {

    function fieldError(field, message) {
        $('#' + field).addClass('is-invalid').attr('aria-invalid', 'true');
        $('#' + field + '-error').text(message).addClass('d-block');
    }

    function errorSummary(message) {
        $('#password-change-error').text(message).removeClass('d-none').trigger('focus');
    }

    /*
     * Mostrar / ocultar contraseña.
     */
    $('.btn-show-password').on(
        'click',
        function () {

            const target =
                $(this)
                    .data('target');

            const $input =
                $(target);

            const $icon =
                $(this)
                    .find('i');


            if (
                $input.attr('type')
                ===
                'password'
            ) {

                $input.attr(
                    'type',
                    'text'
                );

                $icon
                    .removeClass(
                        'fa-eye'
                    )
                    .addClass(
                        'fa-eye-slash'
                    );

            } else {

                $input.attr(
                    'type',
                    'password'
                );

                $icon
                    .removeClass(
                        'fa-eye-slash'
                    )
                    .addClass(
                        'fa-eye'
                    );

            }

            const visible = $input.attr('type') === 'text';
            $(this).attr('aria-pressed', String(visible)).attr('aria-label',
                (visible ? 'Ocultar ' : 'Mostrar ') + $('label[for="' + $input.attr('id') + '"]').text().toLowerCase());

        }
    );


    $('#formRequiredPasswordChange').on(
        'submit',
        function (event) {

            event.preventDefault();

            $('#password, #password_confirmation').removeClass('is-invalid').attr('aria-invalid', 'false');
            $('#password-error, #password_confirmation-error').empty().removeClass('d-block');
            $('#password-change-error').empty().addClass('d-none');


            const $form =
                $(this);

            const url =
                $form.data('url');

            const password =
                $('#password').val();

            const confirmation =
                $('#password_confirmation')
                    .val();


            if (
                !password ||
                password.length < 8
            ) {

                fieldError('password', 'La contraseña debe tener al menos 8 caracteres.');

                showWarning(
                    'La contraseña debe tener al menos 8 caracteres.'
                );

                return;
            }


            if (
                password !== confirmation
            ) {

                fieldError('password_confirmation', 'Las contraseñas no coinciden.');

                showWarning(
                    'Las contraseñas no coinciden.'
                );

                return;
            }


            const $button =
                $('#btnSavePassword');


            $button
                .prop(
                    'disabled',
                    true
                )
                .html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                    'Guardando...'
                );


            $.ajax({

                url:
                url,

                method:
                    'POST',

                data:
                    $form.serialize(),

                success:
                    function (
                        response
                    ) {

                        $.alert({

                            title:
                                'Contraseña actualizada',

                            content:
                            response.message,

                            type:
                                'green',

                            buttons: {

                                ok: {

                                    text:
                                        'Continuar',

                                    btnClass:
                                        'btn-success',

                                    action:
                                        function () {

                                            window.location.href =
                                                response.redirect;

                                        }

                                }

                            }

                        });

                    },

                error:
                    function (
                        xhr
                    ) {

                        showAjaxError(
                            xhr,
                            'No se pudo actualizar la contraseña.'
                        );

                    },

                complete:
                    function () {

                        $button
                            .prop(
                                'disabled',
                                false
                            )
                            .html(
                                '<i class="fas fa-save mr-1"></i>' +
                                'Actualizar contraseña'
                            );

                    }

            });

        }
    );


    function showWarning(
        message
    ) {

        errorSummary(message);

    }


    function showAjaxError(
        xhr,
        defaultMessage
    ) {

        let message =
            defaultMessage;


        if (
            xhr.status === 422 &&
            xhr.responseJSON &&
            xhr.responseJSON.errors
        ) {

            ['password', 'password_confirmation'].forEach(function (field) {
                const errors = xhr.responseJSON.errors[field];
                if (errors && errors[0]) fieldError(field, errors[0]);
            });

            const first =
                Object.values(
                    xhr.responseJSON.errors
                )[0];


            if (
                first &&
                first[0]
            ) {

                message =
                    first[0];

            }

        } else if (
            xhr.responseJSON &&
            xhr.responseJSON.message
        ) {

            message =
                xhr.responseJSON.message;

        }


        if (xhr.status === 419) message = 'La sesión ha expirado. Recarga la página para continuar.';
        if (xhr.status === 401) message = 'La sesión ha expirado. Inicia sesión para continuar.';
        errorSummary(message);

    }

});
