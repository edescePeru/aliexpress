$(function () {

    const $app =
        $('#tenant-role-app');

    const urlList =
        $app.data('url-list');

    const urlCreate =
        $app.data('url-create');

    const urlEdit =
        $app.data('url-edit');

    const urlToggle =
        $app.data('url-toggle');


    let currentPage = 1;


    function selectedTenantId() {

        return $('#tenantRoleTenant')
            .val();

    }


    function loadRoles(page = 1) {

        let tenantId =
            selectedTenantId();


        if (!tenantId) {
            return;
        }


        currentPage = page;


        $.ajax({

            url: urlList,

            method: 'GET',

            data: {
                tenant_id:
                tenantId,

                page:
                page,

                search:
                    $('#searchTenantRole')
                        .val(),

                per_page:
                    $('#perPageTenantRole')
                        .val()
            },

            beforeSend:
                function () {

                    $('#bodyTenantRoles')
                        .html(`
                            <tr>
                                <td
                                    colspan="9"
                                    class="text-center"
                                >
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Cargando...
                                </td>
                            </tr>
                        `);

                },

            success:
                function (response) {

                    renderRoles(
                        response.data
                    );

                    renderPagination(
                        response
                    );

                },

            error:
                function (xhr) {

                    showAjaxError(
                        xhr,
                        'No se pudieron cargar los roles.'
                    );

                }

        });

    }


    function renderRoles(roles) {

        let html = '';


        if (
            !roles ||
            roles.length === 0
        ) {

            $('#bodyTenantRoles')
                .html(`
                    <tr>
                        <td
                            colspan="9"
                            class="text-center text-muted"
                        >
                            El tenant no tiene roles.
                        </td>
                    </tr>
                `);

            return;

        }


        let tenantId =
            selectedTenantId();


        $.each(
            roles,
            function (
                index,
                role
            ) {

                let origin =
                    role.template
                        ? escapeHtml(
                        role.template.name
                        )
                        : 'Exclusivo';


                let typeBadge =
                    role.is_customized
                        ? '<span class="badge badge-warning">Personalizado</span>'
                        : '<span class="badge badge-info">Estándar</span>';


                let ownerBadge =
                    role.is_owner_assignable
                        ? '<span class="badge badge-success">Sí</span>'
                        : '<span class="badge badge-secondary">No</span>';


                let statusBadge =
                    role.is_active
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-secondary">Inactivo</span>';


                let toggleText =
                    role.is_active
                        ? 'Inhabilitar'
                        : 'Habilitar';


                let toggleClass =
                    role.is_active
                        ? 'btn-outline-danger'
                        : 'btn-outline-success';


                let editUrl =
                    urlEdit
                        .replace(
                            ':tenantId',
                            tenantId
                        )
                        .replace(
                            ':roleId',
                            role.id
                        );


                html += `

                    <tr>

                        <td>
                            ${role.id}
                        </td>

                        <td>
                            ${escapeHtml(
                    role.name
                )}
                        </td>

                        <td>
                            ${escapeHtml(
                    role.description
                )}
                        </td>

                        <td>
                            ${origin}
                        </td>

                        <td>
                            ${typeBadge}
                        </td>

                        <td>
                            <span
                                class="badge badge-info"
                            >
                                ${role.permissions_count}
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
                                href="${editUrl}"
                                class="
                                    btn
                                    btn-outline-primary
                                    btn-xs
                                "
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
                                    btn-toggle-role
                                "

                                data-id="${role.id}"

                                data-active="${
                    role.is_active
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


        $('#bodyTenantRoles')
            .html(html);

    }


    $('#tenantRoleTenant')
        .on(
            'change',
            function () {

                let hasTenant =
                    !!$(this).val();


                $('#searchTenantRole')
                    .prop(
                        'disabled',
                        !hasTenant
                    );


                $('#perPageTenantRole')
                    .prop(
                        'disabled',
                        !hasTenant
                    );


                $('#btnNewTenantRole')
                    .prop(
                        'disabled',
                        !hasTenant
                    );


                if (hasTenant) {

                    loadRoles(1);

                } else {

                    $('#bodyTenantRoles')
                        .html(`
                            <tr>
                                <td
                                    colspan="9"
                                    class="text-center text-muted"
                                >
                                    Seleccione un tenant.
                                </td>
                            </tr>
                        `);


                    $('#paginationTenantRoles')
                        .empty();

                }

            }
        );


    $('#btnNewTenantRole')
        .on(
            'click',
            function () {

                let tenantId =
                    selectedTenantId();


                if (!tenantId) {
                    return;
                }


                window.location.href =
                    urlCreate.replace(
                        ':tenantId',
                        tenantId
                    );

            }
        );


    $(document).on(
        'click',
        '.btn-toggle-role',
        function () {

            let roleId =
                $(this).data('id');

            let active =
                parseInt(
                    $(this)
                        .data('active')
                ) === 1;

            let tenantId =
                selectedTenantId();


            $.confirm({

                title:
                    active
                        ? 'Inhabilitar rol'
                        : 'Habilitar rol',

                content:
                    active
                        ? '¿Está seguro de inhabilitar este rol?'
                        : '¿Está seguro de habilitar este rol?',

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

                                let url =
                                    urlToggle
                                        .replace(
                                            ':tenantId',
                                            tenantId
                                        )
                                        .replace(
                                            ':roleId',
                                            roleId
                                        );


                                $.ajax({

                                    url:
                                    url,

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

                                            $.alert({
                                                title:
                                                    'Correcto',

                                                content:
                                                response.message,

                                                type:
                                                    'green'
                                            });


                                            loadRoles(
                                                currentPage
                                            );

                                        },

                                    error:
                                        function (
                                            xhr
                                        ) {

                                            showAjaxError(
                                                xhr,
                                                'No se pudo cambiar el estado del rol.'
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


    let searchTimeout;


    $('#searchTenantRole')
        .on(
            'keyup',
            function () {

                clearTimeout(
                    searchTimeout
                );


                searchTimeout =
                    setTimeout(
                        function () {
                            loadRoles(1);
                        },
                        400
                    );

            }
        );


    $('#perPageTenantRole')
        .on(
            'change',
            function () {

                loadRoles(1);

            }
        );


    function renderPagination(
        response
    ) {

        if (
            response.last_page <= 1
        ) {

            $('#paginationTenantRoles')
                .empty();

            return;

        }


        let html =
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
                        class="
                            page-link
                            page-tenant-role
                        "
                        data-page="${page}"
                    >
                        ${page}
                    </a>

                </li>

            `;

        }


        html += '</ul>';


        $('#paginationTenantRoles')
            .html(html);

    }


    $(document).on(
        'click',
        '.page-tenant-role',
        function (event) {

            event.preventDefault();


            loadRoles(
                $(this).data('page')
            );

        }
    );


    function showAjaxError(
        xhr,
        defaultMessage
    ) {

        let message =
            defaultMessage;


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
            type: 'orange'
        });

    }


    function escapeHtml(value) {

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

    if (
        $('#tenantRoleTenant').val()
    ) {

        $('#searchTenantRole')
            .prop(
                'disabled',
                false
            );

        $('#perPageTenantRole')
            .prop(
                'disabled',
                false
            );

        $('#btnNewTenantRole')
            .prop(
                'disabled',
                false
            );

        loadRoles(1);

    }

});