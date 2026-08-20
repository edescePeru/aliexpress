$(function () {

    const $app =
        $('#color-form-app');

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


    initializeColorPreview();


    $('#colorPicker').on(
        'input change',
        function () {

            const color =
                $(this).val();

            $('#code')
                .val(
                    color.toUpperCase()
                );

            updateColorPreview(
                color
            );

        }
    );


    $('#code').on(
        'input',
        function () {

            let value =
                $(this)
                    .val()
                    .trim();


            if (
                value &&
                value.charAt(0)
                !== '#'
            ) {

                value =
                    '#' + value;

            }


            value =
                value.toUpperCase();


            $(this).val(
                value
            );


            if (
                isValidHexColor(
                    value
                )
            ) {

                $('#colorPicker')
                    .val(
                        value
                    );

                updateColorPreview(
                    value
                );

            } else {

                updateColorPreview(
                    null
                );

            }

        }
    );


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


    $('#formColor').on(
        'submit',
        function (event) {

            event.preventDefault();


            const code =
                $('#code')
                    .val()
                    .trim();


            /*
             * Permitimos null porque ya existen
             * colores históricos sin code.
             */
            if (
                code &&
                !isValidHexColor(
                    code
                )
            ) {

                $.alert({

                    title:
                        'Atención',

                    content:
                        'El código del color debe tener formato hexadecimal, por ejemplo #FFFFFF.',

                    type:
                        'orange'

                });

                return;
            }


            const url =
                urlUpdate ||
                urlStore;


            const $button =
                $('#btnSaveColor');


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

                        showFormColorError(
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
                                        : 'Guardar color'
                                )
                            );

                    }

            });

        }
    );


    function initializeColorPreview() {

        const current =
            $('#code')
                .val();

        if (
            isValidHexColor(
                current
            )
        ) {

            $('#colorPicker')
                .val(
                    current
                );

            updateColorPreview(
                current
            );

            return;
        }


        const initial =
            $('#colorPreview')
                .data(
                    'initial-color'
                );


        if (
            isValidHexColor(
                initial
            )
        ) {

            updateColorPreview(
                initial
            );

            return;
        }


        updateColorPreview(
            null
        );

    }


    function updateColorPreview(
        color
    ) {

        if (
            color &&
            isValidHexColor(
                color
            )
        ) {

            $('#colorPreview')
                .css(
                    'background-color',
                    color
                );

        } else {

            $('#colorPreview')
                .css(
                    'background-color',
                    '#f4f6f9'
                );

        }

    }


    function isValidHexColor(
        value
    ) {

        return /^#[0-9A-Fa-f]{6}$/
            .test(
                String(
                    value || ''
                )
            );

    }


    function showFormColorError(
        xhr
    ) {

        let message =
            'No se pudo guardar el color.';


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