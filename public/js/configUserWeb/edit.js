$(function () {

    const $app =
        $('#config-user-edit-app');

    const urlIndex =
        $app.data('url-index');

    const urlUpdate =
        $app.data('url-update');

    const initialCompanyId =
        String(
            $app.data(
                'default-company-id'
            ) || ''
        );

    const initialBranchId =
        String(
            $app.data(
                'default-branch-id'
            ) || ''
        );


    $(document).on(
        'change',
        '.company-checkbox',
        function () {

            const companyId =
                String(
                    $(this).val()
                );

            const checked =
                $(this).is(
                    ':checked'
                );

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


    $(document).on(
        'change',
        '.branch-checkbox',
        function () {

            refreshDefaultBranches();

        }
    );


    $('#default_company_id').on(
        'change',
        function () {

            refreshDefaultBranches();

        }
    );


    function refreshDefaultCompanies(
        preferredValue = null
    ) {

        let currentValue =
            preferredValue !== null
                ? String(
                preferredValue
                )
                : String(
                $('#default_company_id')
                    .val()
                || ''
                );


        let html =
            '<option value="">' +
            'Seleccione una empresa' +
            '</option>';


        $('.company-checkbox:checked')
            .each(
                function () {

                    const id =
                        String(
                            $(this).val()
                        );

                    const label =
                        $('label[for="' +
                            $(this)
                                .attr('id') +
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

        } else if (
            $('.company-checkbox:checked')
                .length === 1
        ) {

            $('#default_company_id')
                .val(
                    $('.company-checkbox:checked')
                        .first()
                        .val()
                );

        }

    }


    function refreshDefaultBranches(
        preferredValue = null
    ) {

        const companyId =
            String(
                $('#default_company_id')
                    .val()
                || ''
            );


        let currentValue =
            preferredValue !== null
                ? String(
                preferredValue
                )
                : String(
                $('#default_branch_id')
                    .val()
                || ''
                );


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
                            String(
                                $(this).val()
                            );

                        const label =
                            $('label[for="' +
                                $(this)
                                    .attr('id') +
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

            const $options =
                $('#default_branch_id option')
                    .not(
                        '[value=""]'
                    );


            if ($options.length === 1) {

                $('#default_branch_id')
                    .val(
                        $options
                            .first()
                            .val()
                    );

            }

        }

    }


    /*
     * Imagen preview.
     */
    $('#image').on(
        'change',
        function () {

            const file =
                this.files[0];

            if (!file) {
                return;
            }

            const reader =
                new FileReader();

            reader.onload =
                function (event) {

                    $('#imagePreview')
                        .attr(
                            'src',
                            event.target.result
                        );

                };

            reader.readAsDataURL(
                file
            );

        }
    );


    /*
     * Guardar.
     */
    $('#formEditTenantUser').on(
        'submit',
        function (event) {

            event.preventDefault();


            /*
             * Si es Tenant Owner estos
             * elementos no existirán.
             */
            if (
                $('.company-checkbox')
                    .length > 0
            ) {

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

            }


            const formData =
                new FormData(
                    this
                );


            const $button =
                $('#btnSaveTenantUser');


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
                urlUpdate,

                method:
                    'POST',

                data:
                formData,

                processData:
                    false,

                contentType:
                    false,

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

                        showAjaxError(
                            xhr,
                            'No se pudo actualizar el usuario.'
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
                                'Guardar cambios'
                            );

                    }

            });

        }
    );


    function initializeDefaults() {

        if (
            $('.company-checkbox')
                .length === 0
        ) {
            return;
        }


        refreshDefaultCompanies(
            initialCompanyId
        );


        refreshDefaultBranches(
            initialBranchId
        );

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


    initializeDefaults();

});