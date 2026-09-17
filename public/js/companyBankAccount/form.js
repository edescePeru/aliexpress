$(function () {

    function formatBank(bank) {
        if (!bank.id) {
            return bank.text;
        }

        const image = $(bank.element).data('image');

        if (!image) {
            return $('<span></span>').text(bank.text);
        }

        const $container = $(
            '<span class="d-flex align-items-center"></span>'
        );

        const $image = $('<img>')
            .attr('src', image)
            .attr('alt', bank.text)
            .css({
                width: '26px',
                height: '26px',
                objectFit: 'contain',
                marginRight: '8px'
            });

        const $text = $('<span></span>')
            .text(bank.text);

        $container.append($image);
        $container.append($text);

        return $container;
    }


    /*
     * ============================================================
     * SELECT2 DE BANCOS
     * ============================================================
     */

    $('.select2-bank').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un banco',
        allowClear: true,
        templateResult: formatBank,
        templateSelection: formatBank
    });


    /*
     * ============================================================
     * GUARDAR CREATE / EDIT
     * ============================================================
     */

    const $form =
        $('#company-bank-account-form');

    const $button =
        $('#btn-save-bank-account');

    if (!$form.length || !$button.length) {
        return;
    }

    const originalButtonHtml =
        $button.html();


    $form.on('submit', function (e) {
        e.preventDefault();

        /*
         * Evita doble click.
         */
        if ($button.prop('disabled')) {
            return;
        }


        /*
         * Estado guardando.
         */
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

            /*
             * Siempre POST.
             *
             * En Edit Laravel recibe:
             * _method = PUT
             *
             * gracias a @method('PUT').
             */
            method: 'POST',

            data: $form.serialize(),


            success: function (response) {

                restoreButton();

                $.alert({
                    title: 'Correcto',

                    content:
                        response.message ||
                        'La cuenta bancaria fue guardada correctamente.',

                    type: 'green',

                    buttons: {
                        ok: {
                            text: 'ACEPTAR',
                            btnClass: 'btn-success',

                            action: function () {
                                if (response.url) {
                                    window.location.href =
                                        response.url;
                                }
                            }
                        }
                    }
                });
            },


            error: function (xhr) {

                restoreButton();

                let message =
                    'No se pudo guardar la cuenta bancaria.';


                /*
                 * Errores de validación Laravel.
                 */
                if (
                    xhr.status === 422 &&
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {
                    const messages = [];

                    $.each(
                        xhr.responseJSON.errors,
                        function (field, errors) {
                            $.each(
                                errors,
                                function (index, error) {
                                    messages.push(error);
                                }
                            );
                        }
                    );

                    if (messages.length) {
                        message =
                            messages.join('<br>');
                    }

                } else if (
                    xhr.responseJSON &&
                    xhr.responseJSON.message
                ) {
                    message =
                        xhr.responseJSON.message;
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


    function restoreButton() {
        $button
            .prop('disabled', false)
            .removeClass('btn-success')
            .addClass('btn-primary')
            .html(originalButtonHtml);
    }

});