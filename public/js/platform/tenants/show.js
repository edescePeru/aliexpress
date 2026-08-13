$(function () {

    const $app =
        $('#platform-tenant-show-app');

    const urlEdit =
        $app.data('url-edit');

    const urlUpdate =
        $app.data('url-update');

    const urlToggleStatus =
        $app.data(
            'url-toggle-status'
        );


    $('#btnEditTenant').on(
        'click',
        function () {

            $.ajax({

                url:
                urlEdit,

                type:
                    'GET',

                dataType:
                    'json',

                success:
                    function (
                        tenant
                    ) {

                        showEditTenant(
                            tenant
                        );

                    },

                error:
                    function () {

                        showError(
                            'No se pudo cargar el tenant.'
                        );

                    }

            });

        }
    );


    $('#btnToggleTenant').on(
        'click',
        function () {

            const active =
                parseInt(
                    $(this).data(
                        'current-status'
                    ),
                    10
                ) === 1;

            const action =
                active
                    ? 'inhabilitar'
                    : 'habilitar';


            $.confirm({

                title:
                    active
                        ? 'Inhabilitar tenant'
                        : 'Habilitar tenant',

                content:
                    '¿Está seguro de ' +
                    action +
                    ' este tenant?',

                type:
                    active
                        ? 'red'
                        : 'green',

                buttons: {

                    confirm: {

                        text:
                            active
                                ? 'Sí, inhabilitar'
                                : 'Sí, habilitar',

                        btnClass:
                            active
                                ? 'btn-danger'
                                : 'btn-success',

                        action:
                            function () {

                                toggleTenant();

                            }

                    },

                    cancel: {

                        text:
                            'Cancelar'

                    }

                }

            });

        }
    );


    function showEditTenant(
        tenant
    ) {

        let planOptions = '';

        $('#tenantAvailablePlans option')
            .each(
                function () {

                    const value =
                        $(this).val();

                    const selected =
                        String(value) ===
                        String(
                            tenant.plan_id
                        )
                            ? 'selected'
                            : '';

                    planOptions +=
                        '<option value="' +
                        escapeHtml(
                            value
                        ) +
                        '" ' +
                        selected +
                        '>' +
                        escapeHtml(
                            $(this).text()
                        ) +
                        '</option>';

                }
            );


        $.confirm({

            title:
                'Editar tenant',

            columnClass:
                'col-md-6',

            content: `

                <form
                    id="formEditTenant"
                >

                    <div
                        class="form-group"
                    >

                        <label>
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="${
                escapeHtml(
                    tenant.name
                    || ''
                )
                }"
                            required
                        >

                    </div>


                    <div
                        class="form-group"
                    >

                        <label>
                            Plan
                        </label>

                        <select
                            name="plan_id"
                            class="form-control"
                            required
                        >
                            ${planOptions}
                        </select>

                    </div>

                </form>

            `,

            buttons: {

                save: {

                    text:
                        'Guardar',

                    btnClass:
                        'btn-success',

                    action:
                        function () {

                            saveTenant();

                            return false;

                        }

                },

                cancel: {

                    text:
                        'Cancelar'

                }

            }

        });

    }


    function saveTenant() {

        $.ajax({

            url:
            urlUpdate,

            type:
                'POST',

            data:
                $('#formEditTenant')
                    .serialize(),

            headers: {
                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]')
                        .attr('content')
            },

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

                                action:
                                    function () {

                                        location.reload();

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
                        'No se pudo actualizar el tenant.'
                    );

                }

        });

    }


    function toggleTenant() {

        $.ajax({

            url:
            urlToggleStatus,

            type:
                'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]')
                        .attr('content')
            },

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

                                action:
                                    function () {

                                        location.reload();

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
                        'No se pudo cambiar el estado del tenant.'
                    );

                }

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


        showError(
            message
        );

    }


    function showError(
        message
    ) {

        $.alert({

            title:
                'Aviso',

            content:
            message,

            type:
                'orange'

        });

    }


    function escapeHtml(
        value
    ) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }

        return $('<div>')
            .text(value)
            .html();

    }

});