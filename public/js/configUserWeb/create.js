$(function () {

    const $app =
        $('#config-user-create-app');

    const urlIndex =
        $app.data('url-index');

    const urlStore =
        $app.data('url-store');


    /*
     * --------------------------------------------------
     * COMPANY
     * --------------------------------------------------
     */
    $(document).on(
        'change',
        '.company-checkbox',
        function () {

            const companyId =
                $(this).val();

            const checked =
                $(this).is(':checked');

            const $branches =
                $('.branch-checkbox' +
                    '[data-company-id="' +
                    companyId +
                    '"]'
                );


            if (checked) {

                $branches.prop(
                    'disabled',
                    false
                );

            } else {

                $branches
                    .prop(
                        'checked',
                        false
                    )
                    .prop(
                        'disabled',
                        true
                    );

            }


            refreshDefaultCompanies();

            refreshDefaultBranches();

        }
    );


    /*
     * --------------------------------------------------
     * BRANCH
     * --------------------------------------------------
     */
    $(document).on(
        'change',
        '.branch-checkbox',
        function () {

            refreshDefaultBranches();

        }
    );


    /*
     * --------------------------------------------------
     * COMPANY DEFAULT
     * --------------------------------------------------
     */
    $('#default_company_id').on(
        'change',
        function () {

            refreshDefaultBranches();

        }
    );


    function refreshDefaultCompanies() {

        const currentValue =
            $('#default_company_id')
                .val();

        let html =
            '<option value="">' +
            'Seleccione una empresa' +
            '</option>';


        $('.company-checkbox:checked')
            .each(
                function () {

                    const id =
                        $(this).val();

                    const label =
                        $('label[for="' +
                            $(this).attr('id') +
                            '"]'
                        )
                            .clone()
                            .children()
                            .remove()
                            .end()
                            .text()
                            .trim();


                    html +=
                        '<option value="' +
                        escapeHtml(id) +
                        '">' +
                        escapeHtml(label) +
                        '</option>';

                }
            );


        $('#default_company_id')
            .html(html);


        if (
            currentValue &&
            $('#default_company_id' +
                ' option[value="' +
                currentValue +
                '"]'
            ).length
        ) {

            $('#default_company_id')
                .val(
                    currentValue
                );

        } else {

            const companyCount =
                $('.company-checkbox:checked')
                    .length;


            /*
             * Si solo existe una empresa seleccionada,
             * la hacemos default automáticamente.
             */
            if (companyCount === 1) {

                $('#default_company_id')
                    .val(
                        $('.company-checkbox:checked')
                            .first()
                            .val()
                    );

            }

        }

    }


    function refreshDefaultBranches() {

        const companyId =
            $('#default_company_id')
                .val();

        const currentValue =
            $('#default_branch_id')
                .val();


        let html =
            '<option value="">' +
            'Seleccione un local' +
            '</option>';


        if (companyId) {

            $('.branch-checkbox' +
                '[data-company-id="' +
                companyId +
                '"]:checked'
            )
                .each(
                    function () {

                        const id =
                            $(this).val();

                        const label =
                            $('label[for="' +
                                $(this).attr('id') +
                                '"]'
                            )
                                .clone()
                                .children()
                                .remove()
                                .end()
                                .text()
                                .trim();


                        html +=
                            '<option value="' +
                            escapeHtml(id) +
                            '">' +
                            escapeHtml(label) +
                            '</option>';

                    }
                );

        }


        $('#default_branch_id')
            .html(html);


        if (
            currentValue &&
            $('#default_branch_id' +
                ' option[value="' +
                currentValue +
                '"]'
            ).length
        ) {

            $('#default_branch_id')
                .val(
                    currentValue
                );

        } else {

            const $available =
                $('#default_branch_id option')
                    .not(
                        '[value=""]'
                    );


            /*
             * Si solo hay un local disponible,
             * se selecciona automáticamente.
             */
            if ($available.length === 1) {

                $('#default_branch_id')
                    .val(
                        $available
                            .first()
                            .val()
                    );

            }

        }

    }


    /*
     * --------------------------------------------------
     * GUARDAR
     * --------------------------------------------------
     */
    $('#formCreateTenantUser').on(
        'submit',
        function (event) {

            event.preventDefault();


            if (
                $('.company-checkbox:checked')
                    .length === 0
            ) {

                showWarning(
                    'Seleccione al menos una empresa.'
                );

                return;
            }


            if (
                $('.branch-checkbox:checked')
                    .length === 0
            ) {

                showWarning(
                    'Seleccione al menos un local.'
                );

                return;
            }


            const $form =
                $(this);


            const $button =
                $('#btnSaveTenantUser');


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

                method:
                    'POST',

                data:
                    $form.serialize(),

                success:
                    function (
                        response
                    ) {

                        showCreatedUser(
                            response
                        );

                    },

                error:
                    function (
                        xhr
                    ) {

                        showAjaxError(
                            xhr,
                            'No se pudo crear el usuario.'
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
                                'Crear usuario'
                            );

                    }

            });

        }
    );


    function showCreatedUser(
        response
    ) {

        const user =
            response.user || {};

        const password =
            response.temporary_password
            || '';


        $.alert({

            title:
                'Usuario creado',

            type:
                'green',

            content: `

                <p>
                    El usuario fue creado correctamente.
                </p>

                <div class="alert alert-light border">

                    <strong>
                        Usuario:
                    </strong>

                    ${escapeHtml(
                user.name || ''
            )}

                    <br>

                    <strong>
                        Correo:
                    </strong>

                    ${escapeHtml(
                user.email || ''
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
                        Contraseña temporal
                    </small>

                    <div
                        class="
                            h4
                            font-weight-bold
                            mb-0
                            mt-1
                        "
                    >
                        ${escapeHtml(
                password
            )}
                    </div>

                </div>


                <p class="text-muted mb-0">

                    Copie esta contraseña antes
                    de cerrar esta ventana.

                    El usuario deberá cambiarla
                    al iniciar sesión.

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


    function showWarning(
        message
    ) {

        $.alert({

            title:
                'Atención',

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