$(function () {
    const $container =
        $('#company-bank-account-index');

    const successMessage =
        $('#flash-success').data('message');

    if (successMessage) {
        toastr.success(successMessage);
    }

    $(document).on(
        'click',
        '[data-toggle-bank-account]',
        function () {
            const $button = $(this);

            const id =
                $button.data('id');

            const isActive =
                String($button.data('active')) === '1';

            const actionText =
                isActive
                    ? 'desactivar'
                    : 'activar';

            const title =
                isActive
                    ? 'Desactivar cuenta'
                    : 'Activar cuenta';

            $.confirm({
                title: title,
                content:
                    '¿Está seguro de ' +
                    actionText +
                    ' esta cuenta bancaria?',
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
                            let url =
                                $container.data(
                                    'toggle-url'
                                );

                            url = url.replace(
                                '__ID__',
                                id
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

                                    window.location.reload();
                                },

                                error: function (xhr) {
                                    let message =
                                        'No se pudo actualizar la cuenta bancaria.';

                                    if (
                                        xhr.responseJSON &&
                                        xhr.responseJSON.message
                                    ) {
                                        message =
                                            xhr.responseJSON.message;
                                    }

                                    toastr.error(message);
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