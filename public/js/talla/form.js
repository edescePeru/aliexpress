$(function () {

    const $app =
        $('#talla-form-app');

    const urlStore =
        $app.data(
            'url-store'
        );

    const urlUpdate =
        $app.data(
            'url-update'
        );

    const urlIndex =
        $app.data(
            'url-index'
        );


    /*
     * Normalizamos nombre corto.
     */
    $('#short_name').on(
        'input',
        function () {

            const value =
                $(this)
                    .val()
                    .toUpperCase();

            $(this).val(
                value
            );

        }
    );


    $('#formTalla').on(
        'submit',
        function (event) {

            event.preventDefault();


            const url =
                urlUpdate ||
                urlStore;


            const $button =
                $('#btnSaveTalla');


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

                type:
                    'POST',

                data:
                    $(this)
                        .serialize(),

                success:
                    function (
                        response
                    ) {

                        $.alert({

                            title:
                                'Correcto',

                            content:
                            response.message,

                            type:
                                'green',

                            buttons: {

                                ok: {

                                    text:
                                        'Aceptar',

                                    btnClass:
                                        'btn-success',

                                    action:
                                        function () {

                                            window.location.href =
                                                response.url ||
                                                urlIndex;

                                        }

                                }

                            }

                        });

                    },

                error:
                    function (
                        xhr
                    ) {

                        showTallaFormError(
                            xhr
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
                                (
                                    urlUpdate
                                        ? 'Guardar cambios'
                                        : 'Guardar talla'
                                )
                            );

                    }

            });

        }
    );


    function showTallaFormError(
        xhr
    ) {

        let message =
            'No se pudo guardar la talla.';


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