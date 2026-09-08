$(document).ready(function () {

    $(document).on(
        'click',
        '[data-toggle-branch-status]',
        function () {

            const button = $(this);

            const branchName =
                button.data('branch-name');

            const isActive =
                parseInt(
                    button.data('is-active'),
                    10
                ) === 1;

            const isMain =
                parseInt(
                    button.data('is-main'),
                    10
                ) === 1;

            const url =
                button.data('url');


            /*
             * Podemos dar feedback inmediato para la principal,
             * aunque backend mantiene igualmente la protección.
             */
            if (isActive && isMain) {

                $.alert({
                    title: 'Aviso',
                    content:
                        'La sucursal principal no puede ser desactivada.',
                    type: 'orange',

                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-warning'
                        }
                    }
                });

                return;
            }


            const actionText =
                isActive
                    ? 'desactivar'
                    : 'activar';


            $.confirm({
                title:
                    isActive
                        ? 'Desactivar sucursal'
                        : 'Activar sucursal',

                content:
                    '¿Está seguro de ' +
                    actionText +
                    ' la sucursal <strong>' +
                    branchName +
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
                                        ).attr('content')
                                },

                                success: function (response) {

                                    toastr.success(
                                        response.message
                                    );

                                    window.location.reload();
                                },

                                error: function (xhr) {

                                    let message =
                                        'No se pudo cambiar el estado de la sucursal.';


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
                                                btnClass: 'btn-warning'
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
                        text: 'Cancelar'
                    }
                }
            });
        }
    );

});