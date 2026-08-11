$(function () {

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

        }
    );


    $('#formRequiredPasswordChange').on(
        'submit',
        function (event) {

            event.preventDefault();


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

                showWarning(
                    'La contraseña debe tener al menos 8 caracteres.'
                );

                return;
            }


            if (
                password !== confirmation
            ) {

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
                                'Guardar nueva contraseña'
                            );

                    }

            });

        }
    );


    function showWarning(
        message
    ) {

        $.alert({
            title:
                'Atención',

            content:
            message,

            type:
                'orange'
        });

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


        $.alert({

            title:
                'Aviso',

            content:
            message,

            type:
                'orange'

        });

    }

});