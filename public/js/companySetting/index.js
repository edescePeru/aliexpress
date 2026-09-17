$(function () {

    const $form =
        $('#company-setting-form');

    const $button =
        $('#btn-save-settings');

    const originalButtonHtml =
        $button.html();

    $form.on('submit', function (e) {
        e.preventDefault();

        if ($button.prop('disabled')) {
            return;
        }

        $button
            .prop('disabled', true)
            .removeClass('btn-primary')
            .addClass('btn-success')
            .html(
                '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                'Guardando...'
            );

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),

            success: function (response) {

                $button
                    .prop('disabled', false)
                    .removeClass('btn-success')
                    .addClass('btn-primary')
                    .html(originalButtonHtml);

                $.alert({
                    title: 'Correcto',
                    content:
                        response.message ||
                        'Configuraciones actualizadas correctamente.',
                    type: 'green',

                    buttons: {
                        ok: {
                            text: 'ACEPTAR',
                            btnClass: 'btn-success'
                        }
                    }
                });
            },

            error: function (xhr) {

                $button
                    .prop('disabled', false)
                    .removeClass('btn-success')
                    .addClass('btn-primary')
                    .html(originalButtonHtml);

                let message =
                    'No se pudieron guardar las configuraciones.';

                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.message
                ) {
                    message =
                        xhr.responseJSON.message;
                }

                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {
                    const errors =
                        xhr.responseJSON.errors;

                    const messages = [];

                    Object.keys(errors).forEach(
                        function (key) {
                            errors[key].forEach(
                                function (error) {
                                    messages.push(error);
                                }
                            );
                        }
                    );

                    if (messages.length) {
                        message =
                            messages.join('<br>');
                    }
                }

                $.alert({
                    title: 'Aviso',
                    content: message,
                    type: 'orange',

                    buttons: {
                        ok: {
                            text: 'ACEPTAR',
                            btnClass: 'btn-warning'
                        }
                    }
                });
            }
        });
    });

    $(document).on(
        'change',
        '.custom-control-input[type="checkbox"]',
        function () {
            const $input = $(this);

            const $label = $(
                'label[for="' +
                $input.attr('id') +
                '"]'
            );

            $label.text(
                $input.is(':checked')
                    ? 'Activado'
                    : 'Desactivado'
            );
        }
    );

});