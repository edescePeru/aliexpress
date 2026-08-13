$(function () {

    const $app =
        $('#platform-tenant-create-app');

    const urlStore =
        $app.data('url-store');

    const urlIndex =
        $app.data('url-index');


    $('#plan_id').on(
        'change',
        function () {

            const $option =
                $(this)
                    .find(
                        'option:selected'
                    );

            const maxUsers =
                $option.data(
                    'max-users'
                );

            if (!maxUsers) {

                $('#planSummary')
                    .addClass(
                        'd-none'
                    )
                    .html('');

                return;
            }

            $('#planSummary')
                .removeClass(
                    'd-none'
                )
                .html(
                    '<strong>' +
                    escapeHtml(
                        $option.text().trim()
                    ) +
                    '</strong><br>' +
                    'El Owner consumirá 1 de los ' +
                    escapeHtml(
                        maxUsers
                    ) +
                    ' usuarios disponibles.'
                );

        }
    );


    $('#formCreateTenant').on(
        'submit',
        function (event) {

            event.preventDefault();

            const $form =
                $(this);

            const $button =
                $('#btnCreateTenant');


            $button
                .prop(
                    'disabled',
                    true
                )
                .html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                    'Creando...'
                );


            $.ajax({

                url:
                urlStore,

                type:
                    'POST',

                data:
                    $form.serialize(),

                success:
                    function (
                        response
                    ) {

                        showTenantCreated(
                            response
                        );

                    },

                error:
                    function (
                        xhr
                    ) {

                        showAjaxError(
                            xhr,
                            'No se pudo crear el Tenant.'
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
                                'Crear Tenant'
                            );

                    }

            });

        }
    );


    function showTenantCreated(
        response
    ) {

        const tenant =
            response.tenant || {};

        const owner =
            response.owner || {};

        const password =
            response.temporary_password
            || '';


        $.alert({

            title:
                'Tenant creado',

            type:
                'green',

            columnClass:
                'col-md-7',

            content: `

                <p>
                    El nuevo cliente fue registrado
                    correctamente.
                </p>

                <div
                    class="
                        alert
                        alert-light
                        border
                    "
                >

                    <strong>
                        Tenant:
                    </strong>

                    ${escapeHtml(
                tenant.name || ''
            )}

                    <br>

                    <strong>
                        Owner:
                    </strong>

                    ${escapeHtml(
                owner.name || ''
            )}

                    <br>

                    <strong>
                        Correo:
                    </strong>

                    ${escapeHtml(
                owner.email || ''
            )}

                </div>


                <div
                    class="
                        alert
                        alert-warning
                        text-center
                    "
                >

                    <small>
                        Contraseña temporal del Owner
                    </small>

                    <div
                        class="
                            h4
                            font-weight-bold
                            mt-2
                            mb-1
                        "
                    >
                        ${escapeHtml(
                password
            )}
                    </div>

                    <small>
                        Esta contraseña solo se mostrará
                        en esta ocasión.
                    </small>

                </div>


                <p class="text-muted mb-0">

                    El propietario deberá utilizar esta
                    contraseña para ingresar y Venti360
                    le solicitará crear una nueva.

                </p>

            `,

            buttons: {

                ok: {

                    text:
                        'Entendido',

                    btnClass:
                        'btn-success',

                    action:
                        function () {

                            window.location.href =
                                urlIndex;

                        }

                }

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