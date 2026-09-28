$(function () {

    const $form =
        $('#formEdit');

    const $button =
        $('#btn-submit');

    const $buttonText =
        $button.find(
            '[data-button-text]'
        );

    $button.on(
        'click',
        function () {

            const value =
                $.trim(
                    $form
                        .find('[name="value"]')
                        .val()
                );

            if (value === '') {
                toastr.warning(
                    'Ingrese un valor.'
                );

                return;
            }

            savePercentageWorker();
        }
    );

    function savePercentageWorker()
    {
        const url =
            $form.data('url');

        const formData =
            new FormData(
                $form[0]
            );

        /*
         * Estado guardando.
         */
        $button
            .prop('disabled', true);

        $buttonText.text(
            'Guardando...'
        );

        $.ajax({
            url:
            url,

            method:
                'POST',

            data:
            formData,

            processData:
                false,

            contentType:
                false,

            success:
                function () {

                    $.alert({
                        icon:
                            'fas fa-check-circle',

                        theme:
                            'modern',

                        animation:
                            'zoom',

                        type:
                            'green',

                        title:
                            'Actualización correcta',

                        content:
                            'El parámetro laboral se actualizó correctamente.',

                        buttons: {

                            ok: {
                                text:
                                    'Aceptar',

                                btnClass:
                                    'btn-success',

                                action:
                                    function () {

                                        window.location.href =
                                            '/dashboard/plataforma/parametros-laborales';
                                    }
                            }
                        }
                    });
                },

            error:
                function (xhr) {

                    let message =
                        'No se pudo actualizar el parámetro.';

                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {
                        message =
                            xhr.responseJSON.message;
                    }

                    $.alert({
                        icon:
                            'fas fa-exclamation-triangle',

                        theme:
                            'modern',

                        animation:
                            'zoom',

                        type:
                            'orange',

                        title:
                            'No se pudo actualizar',

                        content:
                            $('<div>')
                                .text(message)
                                .html(),

                        buttons: {

                            ok: {
                                text:
                                    'Aceptar',

                                btnClass:
                                    'btn-warning'
                            }
                        }
                    });
                },

            complete:
                function () {

                    $button
                        .prop(
                            'disabled',
                            false
                        );

                    $buttonText.text(
                        'Guardar cambios'
                    );
                }
        });
    }
});