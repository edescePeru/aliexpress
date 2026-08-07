$(function () {

    const $app =
        $('#role-template-app');

    const urlList =
        $app.data('url-list');

    const urlEdit =
        $app.data('url-edit');

    const urlToggle =
        $app.data('url-toggle');

    let currentPage = 1;

    function loadTemplates(page = 1) {

        currentPage = page;


        $.ajax({

            url: urlList,

            method: 'GET',

            data: {
                page: page,

                search:
                    $('#searchRoleTemplate')
                        .val(),

                per_page:
                    $('#perPageRoleTemplate')
                        .val()
            },

            beforeSend: function () {

                $('#bodyRoleTemplates')
                    .html(`
                        <tr>
                            <td
                                colspan="7"
                                class="text-center"
                            >
                                <i class="fas fa-spinner fa-spin"></i>
                                Cargando...
                            </td>
                        </tr>
                    `);

            },

            success: function (response) {

                renderTemplates(
                    response.data
                );

                renderPagination(
                    response
                );

            },

            error: function (xhr) {

                showAjaxError(
                    xhr,
                    'No se pudieron cargar las plantillas.'
                );

            }

        });

    }

    function renderTemplates( templates ) {

        let html = '';


        if (
            !templates ||
            templates.length === 0
        ) {

            $('#bodyRoleTemplates')
                .html(`
                    <tr>
                        <td
                            colspan="7"
                            class="text-center text-muted"
                        >
                            No existen plantillas.
                        </td>
                    </tr>
                `);

            return;

        }


        $.each(
            templates,
            function (
                index,
                template
            ) {

                let statusBadge =
                    template.is_active
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-secondary">Inactivo</span>';


                let ownerBadge =
                    template.is_owner_assignable
                        ? '<span class="badge badge-primary">Sí</span>'
                        : '<span class="badge badge-warning">No</span>';


                let toggleText =
                    template.is_active
                        ? 'Inhabilitar'
                        : 'Habilitar';


                let toggleClass =
                    template.is_active
                        ? 'btn-outline-danger'
                        : 'btn-outline-success';


                html += `
                    <tr>

                        <td>
                            ${template.id}
                        </td>

                        <td>
                            ${escapeHtml(
                    template.code
                )}
                        </td>

                        <td>
                            ${escapeHtml(
                    template.name
                )}
                        </td>

                        <td>
                            <span
                                class="badge badge-info"
                            >
                                ${template.permissions_count}
                            </span>
                        </td>

                        <td>
                            ${ownerBadge}
                        </td>

                        <td>
                            ${statusBadge}
                        </td>

                        <td>

                            <a
                                href="${urlEdit.replace(':id', template.id)}"
                                class="btn btn-outline-primary btn-xs"
                            >
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>

                            <button
                                type="button"
                                class="
                                    btn
                                    ${toggleClass}
                                    btn-xs
                                    btn-toggle-template
                                "
                                data-id="${template.id}"
                                data-active="${
                    template.is_active
                        ? 1
                        : 0
                    }"
                            >
                                ${toggleText}
                            </button>

                        </td>

                    </tr>
                `;

            }
        );


        $('#bodyRoleTemplates')
            .html(html);

    }

    function refreshPermissionStatus( prefix ) {

        const $container =
            $('#' +
                prefix +
                '_permissions_container');


        let total =
            $container
                .find(
                    '.permission-checkbox'
                )
                .length;


        let selected =
            $container
                .find(
                    '.permission-checkbox:checked'
                )
                .length;


        $('.permission-counter' +
            '[data-prefix="' +
            prefix +
            '"]'
        ).text(
            selected +
            ' / ' +
            total +
            ' seleccionados'
        );


        /*
         * Actualizamos checkbox de cada módulo.
         */
        $container
            .find(
                '.permission-module-checkbox'
            )
            .each(
                function () {

                    const $moduleCheckbox =
                        $(this);

                    const module =
                        $moduleCheckbox
                            .data('module');


                    const $permissions =
                        $container.find(
                            '.permission-checkbox' +
                            '[data-module="' +
                            module +
                            '"]'
                        );


                    const moduleTotal =
                        $permissions.length;


                    const moduleSelected =
                        $permissions
                            .filter(
                                ':checked'
                            )
                            .length;


                    $moduleCheckbox
                        .prop(
                            'checked',
                            moduleTotal > 0 &&
                            moduleSelected ===
                            moduleTotal
                        );


                    /*
                     * Estado visual:
                     *
                     * [-] módulo parcialmente seleccionado
                     */
                    $moduleCheckbox
                        .prop(
                            'indeterminate',
                            moduleSelected > 0 &&
                            moduleSelected <
                            moduleTotal
                        );

                }
            );


        const $selectAll =
            $('.permission-select-all' +
                '[data-prefix="' +
                prefix +
                '"]'
            );


        $selectAll.prop(
            'checked',
            total > 0 &&
            selected === total
        );


        $selectAll.prop(
            'indeterminate',
            selected > 0 &&
            selected < total
        );

    }

    function resetPermissionSelection( prefix ) {

        const $container =
            $('#' +
                prefix +
                '_permissions_container');


        $container
            .find(
                '.permission-checkbox'
            )
            .prop(
                'checked',
                false
            );


        $('.permission-search' +
            '[data-prefix="' +
            prefix +
            '"]'
        ).val('');


        $container
            .find(
                '.permission-module'
            )
            .show();


        $container
            .find(
                '.permission-item'
            )
            .show();


        refreshPermissionStatus(
            prefix
        );

    }

    $(document).on(
        'click',
        '.btn-toggle-template',
        function () {

            let id =
                $(this).data('id');

            let active =
                parseInt(
                    $(this).data('active')
                ) === 1;


            $.confirm({

                title:
                    active
                        ? 'Inhabilitar plantilla'
                        : 'Habilitar plantilla',

                content:
                    active
                        ? '¿Está seguro de inhabilitar esta plantilla? Los roles ya creados para tenants no serán eliminados.'
                        : '¿Está seguro de habilitar esta plantilla?',

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

                                $.ajax({

                                    url:
                                        urlToggle.replace(
                                            ':id',
                                            id
                                        ),

                                    method:
                                        'POST',

                                    data: {
                                        _token:
                                            $(
                                                'meta[name="csrf-token"]'
                                            )
                                                .attr(
                                                    'content'
                                                )
                                    },

                                    success:
                                        function (
                                            response
                                        ) {

                                            showSuccess(
                                                response.message
                                            );

                                            loadTemplates(
                                                currentPage
                                            );

                                        },

                                    error:
                                        function (
                                            xhr
                                        ) {

                                            showAjaxError(
                                                xhr,
                                                'No se pudo cambiar el estado.'
                                            );

                                        }

                                });

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

    function renderPagination(
        response
    ) {

        let html = '';


        if (
            response.last_page <= 1
        ) {

            $('#paginationRoleTemplates')
                .html('');

            return;

        }


        html +=
            '<ul class="pagination pagination-sm">';


        for (
            let page = 1;
            page <= response.last_page;
            page++
        ) {

            html += `
                <li
                    class="
                        page-item
                        ${
                page ===
                response.current_page
                    ? 'active'
                    : ''
                }
                    "
                >
                    <a
                        href="#"
                        class="page-link page-role-template"
                        data-page="${page}"
                    >
                        ${page}
                    </a>
                </li>
            `;

        }


        html += '</ul>';


        $('#paginationRoleTemplates')
            .html(html);

    }

    $(document).on(
        'click',
        '.page-role-template',
        function (event) {

            event.preventDefault();

            loadTemplates(
                $(this).data('page')
            );

        }
    );

    let searchTimeout;

    $('#searchRoleTemplate')
        .on(
            'keyup',
            function () {

                clearTimeout(
                    searchTimeout
                );


                searchTimeout =
                    setTimeout(
                        function () {
                            loadTemplates(
                                1
                            );
                        },
                        400
                    );

            }
        );

    $('#perPageRoleTemplate')
        .on(
            'change',
            function () {

                loadTemplates(1);

            }
        );

    function showSuccess(
        message
    ) {

        $.alert({
            title: 'Correcto',
            content: message,
            type: 'green',
            buttons: {
                ok: {
                    text: 'Aceptar',
                    btnClass:
                        'btn-success'
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

            let errors =
                xhr.responseJSON.errors;

            let first =
                Object.values(
                    errors
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
            title: 'Aviso',
            content: message,
            type: 'orange'
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

    loadTemplates();

});