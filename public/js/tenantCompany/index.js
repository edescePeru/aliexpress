$(document).ready(function () {

    $(document).on(
        'click',
        '[data-toggle-company-status]',
        function () {

            const button = $(this);

            const companyName =
                button.data('company-name');

            const isActive =
                parseInt(
                    button.data('is-active'),
                    10
                ) === 1;

            const url =
                button.data('url');


            const actionText =
                isActive
                    ? 'desactivar'
                    : 'activar';


            $.confirm({
                title:
                    isActive
                        ? 'Desactivar empresa'
                        : 'Activar empresa',

                content:
                    '¿Está seguro de ' +
                    actionText +
                    ' la empresa <strong>' +
                    companyName +
                    '</strong>?',

                type:
                    isActive
                        ? 'orange'
                        : 'green',

                buttons: {

                    confirm: {
                        text:
                            isActive
                                ? 'Desactivar'
                                : 'Activar',

                        btnClass:
                            isActive
                                ? 'btn-warning'
                                : 'btn-success',

                        action: function () {

                            button.prop(
                                'disabled',
                                true
                            );


                            $.ajax({
                                url: url,

                                method: 'POST',

                                headers: {
                                    'X-CSRF-TOKEN':
                                        $(
                                            'meta[name="csrf-token"]'
                                        ).attr(
                                            'content'
                                        )
                                },

                                success: function (response) {

                                    toastr.success(
                                        response.message
                                    );

                                    /*
                                     * Recargamos para actualizar:
                                     *
                                     * badge
                                     * botones
                                     * orden de Companies
                                     */
                                    window.location.reload();
                                },

                                error: function (xhr) {

                                    let message =
                                        'No se pudo cambiar el estado de la empresa.';


                                    if (
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
                                                text: 'Aceptar',
                                                btnClass: 'btn-warning',
                                            }
                                        }
                                    });
                                },

                                complete: function () {

                                    button.prop(
                                        'disabled',
                                        false
                                    );
                                }
                            });
                        }
                    },


                    cancel: {
                        text: 'Cancelar',
                    }
                }
            });
        }
    );

});