let currentPage = 1;
let searchTimeout = null;
let tenantRoutes = {};

$(function () {

    const $app =
        $('#platform-tenants-app');

    tenantRoutes = {
        data:
            $app.data('url-data'),

        show:
            $app.data('url-show')
    };


    loadTenants();


    $('#filterSearch').on(
        'keyup',
        function () {

            clearTimeout(
                searchTimeout
            );

            searchTimeout =
                setTimeout(
                    function () {

                        currentPage = 1;

                        loadTenants();

                    },
                    400
                );

        }
    );


    $(
        '#filterPlan,' +
        '#filterStatus,' +
        '#filterPerPage'
    ).on(
        'change',
        function () {

            currentPage = 1;

            loadTenants();

        }
    );


    $('#btnNewTenant').on(
        'click',
        function () {

            $.alert({
                title:
                    'Nuevo tenant',

                content:
                    'La creación de tenants se habilitará en la siguiente fase.',

                type:
                    'blue'
            });

        }
    );


    $(document).on(
        'click',
        '[data-tenant-page]',
        function () {

            const page =
                parseInt(
                    $(this).data(
                        'tenant-page'
                    ),
                    10
                );

            if (!page) {
                return;
            }

            currentPage = page;

            loadTenants();

        }
    );

});


function loadTenants() {

    $('#tenantTableBody')
        .html(
            `
                <tr>
                    <td
                        colspan="7"
                        class="text-center py-4"
                    >
                        <i
                            class="
                                fas
                                fa-spinner
                                fa-spin
                            "
                        ></i>

                        Cargando...
                    </td>
                </tr>
            `
        );


    $.ajax({

        url:
        tenantRoutes.data,

        type:
            'GET',

        dataType:
            'json',

        data: {
            page:
            currentPage,

            per_page:
                $('#filterPerPage')
                    .val(),

            search:
                $('#filterSearch')
                    .val(),

            plan_id:
                $('#filterPlan')
                    .val(),

            status:
                $('#filterStatus')
                    .val()
        },

        success:
            function (
                response
            ) {

                renderTenants(
                    response
                );

            },

        error:
            function () {

                $('#tenantTableBody')
                    .html(
                        `
                            <tr>
                                <td
                                    colspan="7"
                                    class="
                                        text-center
                                        text-danger
                                        py-4
                                    "
                                >
                                    No se pudo cargar
                                    la información.
                                </td>
                            </tr>
                        `
                    );

            }

    });

}


function renderTenants(
    response
) {

    const tenants =
        response.data || [];

    if (!tenants.length) {

        $('#tenantTableBody')
            .html('');

        $('#tenantEmpty')
            .removeClass('d-none');

        $('#tenantPaginationInfo')
            .html('');

        $('#tenantPagination')
            .html('');

        return;
    }

    $('#tenantEmpty')
        .addClass('d-none');

    let html = '';


    tenants.forEach(
        function (tenant) {

            const plan =
                tenant.plan &&
                tenant.plan.name
                    ? tenant.plan.name
                    : 'Sin plan';

            const owner =
                tenant.owner &&
                tenant.owner.name
                    ? tenant.owner.name
                    : 'Sin Owner';

            const users =
                tenant.users.active +
                ' / ' +
                tenant.users.max;

            const status =
                tenant.is_active
                    ? `
                        <span
                            class="badge badge-success"
                        >
                            Activo
                        </span>
                    `
                    : `
                        <span
                            class="badge badge-secondary"
                        >
                            Inactivo
                        </span>
                    `;


            const showUrl =
                tenantRoutes
                    .show
                    .replace(
                        ':id',
                        tenant.id
                    );


            html += `

                <tr>

                    <td>

                        <strong>
                            ${escapeHtml(
                tenant.name
            )}
                        </strong>

                        <br>

                        <small
                            class="text-muted"
                        >
                            ID #${tenant.id}
                        </small>

                    </td>


                    <td>
                        ${escapeHtml(
                plan
            )}
                    </td>


                    <td>

                        <strong>
                            ${escapeHtml(
                users
            )}
                        </strong>

                        <br>

                        <small
                            class="text-muted"
                        >
                            ${tenant.users.available}
                            disponibles
                        </small>

                    </td>


                    <td>
                        ${tenant.companies_count}
                    </td>


                    <td>
                        ${escapeHtml(
                owner
            )}
                    </td>


                    <td>
                        ${status}
                    </td>


                    <td
                        class="text-center"
                    >

                        <a
                            href="${showUrl}"
                            class="
                                btn
                                btn-outline-primary
                                btn-sm
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-eye
                                "
                            ></i>

                            Ver

                        </a>

                    </td>

                </tr>
            `;

        }
    );


    $('#tenantTableBody')
        .html(html);


    renderPagination(
        response
    );

}


function renderPagination(
    response
) {

    $('#tenantPaginationInfo')
        .text(
            'Mostrando ' +
            response.from +
            ' a ' +
            response.to +
            ' de ' +
            response.total +
            ' registros'
        );


    if (
        response.last_page <= 1
    ) {

        $('#tenantPagination')
            .html('');

        return;
    }


    let html =
        '<ul class="pagination pagination-sm mb-0">';


    const start =
        Math.max(
            1,
            response.current_page - 2
        );

    const end =
        Math.min(
            response.last_page,
            response.current_page + 2
        );


    for (
        let page = start;
        page <= end;
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

                <button
                    type="button"
                    class="page-link"
                    data-tenant-page="${page}"
                >
                    ${page}
                </button>

            </li>
        `;

    }


    html += '</ul>';


    $('#tenantPagination')
        .html(html);

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