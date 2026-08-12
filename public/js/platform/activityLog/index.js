let currentPage = 1;
let searchTimeout = null;
let activityRoutes = {};

$(function () {

    const $app =
        $('#platform-activity-app');

    activityRoutes = {
        data:
            $app.data(
                'url-data'
            ),

        show:
            $app.data(
                'url-show'
            )
    };


    loadActivities();


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

                        loadActivities();

                    },
                    400
                );

        }
    );


    $(
        '#filterAction,' +
        '#filterTenant,' +
        '#filterCauser,' +
        '#filterStartDate,' +
        '#filterEndDate,' +
        '#filterPerPage'
    ).on(
        'change',
        function () {

            currentPage = 1;

            loadActivities();

        }
    );


    $('#btnClearFilters').on(
        'click',
        function () {

            $('#filterSearch')
                .val('');

            $('#filterAction')
                .val('');

            $('#filterTenant')
                .val('');

            $('#filterCauser')
                .val('');

            $('#filterStartDate')
                .val('');

            $('#filterEndDate')
                .val('');

            $('#filterPerPage')
                .val('10');

            currentPage = 1;

            loadActivities();

        }
    );


    $(document).on(
        'click',
        '[data-activity-page]',
        function () {

            const page =
                parseInt(
                    $(this).data(
                        'activity-page'
                    ),
                    10
                );

            if (
                !page ||
                page === currentPage
            ) {
                return;
            }

            currentPage = page;

            loadActivities();

        }
    );


    $(document).on(
        'click',
        '[data-view-activity]',
        function () {

            const id =
                $(this).data(
                    'view-activity'
                );

            viewActivity(
                id
            );

        }
    );

});


function loadActivities() {

    $('#activityTableBody')
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
        activityRoutes.data,

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

            action:
                $('#filterAction')
                    .val(),

            tenant_id:
                $('#filterTenant')
                    .val(),

            causer_id:
                $('#filterCauser')
                    .val(),

            start_date:
                $('#filterStartDate')
                    .val(),

            end_date:
                $('#filterEndDate')
                    .val()
        },

        success:
            function (
                response
            ) {

                renderActivities(
                    response
                );

            },

        error:
            function () {

                $('#activityTableBody')
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
                                    la auditoría.
                                </td>
                            </tr>
                        `
                    );

            }

    });

}


function renderActivities(
    response
) {

    const rows =
        response.data || [];

    let html = '';


    if (rows.length === 0) {

        $('#activityTableBody')
            .html('');

        $('#activityEmpty')
            .removeClass(
                'd-none'
            );

        $('#activityPaginationInfo')
            .html('');

        $('#activityPagination')
            .html('');

        return;
    }


    $('#activityEmpty')
        .addClass(
            'd-none'
        );


    rows.forEach(
        function (
            activity
        ) {

            const causerName =
                activity.causer &&
                activity.causer.name
                    ? activity.causer.name
                    : '-';

            const tenant =
                activity.tenant_name
                    ? activity.tenant_name
                    : (
                        activity.tenant_id
                            ? 'Tenant #' +
                            activity.tenant_id
                            : 'Global'
                    );

            const subject =
                buildSubjectLabel(
                    activity.subject
                );


            html += `
                <tr>

                    <td>
                        ${escapeHtml(
                activity.id
            )}
                    </td>

                    <td>
                        ${escapeHtml(
                activity.created_at
                || '-'
            )}
                    </td>

                    <td>
                        ${renderActionBadge(
                activity.action
            )}
                    </td>

                    <td>
                        ${escapeHtml(
                tenant
            )}
                    </td>

                    <td>
                        ${escapeHtml(
                causerName
            )}
                    </td>

                    <td>
                        ${subject}
                    </td>

                    <td
                        class="text-center"
                    >

                        <button
                            type="button"
                            class="
                                btn
                                btn-outline-info
                                btn-sm
                            "
                            data-view-activity="${
                activity.id
                }"
                            title="Ver detalle"
                        >

                            <i
                                class="
                                    fas
                                    fa-eye
                                "
                            ></i>

                        </button>

                    </td>

                </tr>
            `;

        }
    );


    $('#activityTableBody')
        .html(
            html
        );


    renderPagination(
        response
    );

}


function buildSubjectLabel(
    subject
) {

    if (!subject) {
        return '-';
    }


    const type =
        subject.type || '';

    const id =
        subject.id || '';

    const name =
        subject.name || '';


    let text =
        type || 'Entidad';


    if (name) {

        text +=
            ': ' +
            name;

    } else if (id) {

        text +=
            ' #' +
            id;

    }


    return escapeHtml(
        text
    );

}


function renderActionBadge(
    action
) {

    const text =
        action || '-';

    let badge =
        'badge-secondary';


    if (
        text.indexOf(
            '.created'
        ) !== -1
    ) {

        badge =
            'badge-success';

    } else if (
        text.indexOf(
            '.updated'
        ) !== -1
    ) {

        badge =
            'badge-primary';

    } else if (
        text.indexOf(
            '.status_changed'
        ) !== -1
    ) {

        badge =
            'badge-warning';

    }


    return `
        <span
            class="
                badge
                ${badge}
            "
        >
            ${escapeHtml(
        text
    )}
        </span>
    `;

}


function renderPagination(
    response
) {

    $('#activityPaginationInfo')
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

        $('#activityPagination')
            .html('');

        return;
    }


    let html =
        '<ul class="pagination pagination-sm mb-0">';


    html +=
        buildPageItem(
            response.current_page - 1,
            'Anterior',
            response.current_page === 1
        );


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
                    data-activity-page="${
            page
            }"
                >
                    ${page}
                </button>

            </li>
        `;

    }


    html +=
        buildPageItem(
            response.current_page + 1,
            'Siguiente',
            response.current_page ===
            response.last_page
        );


    html += '</ul>';


    $('#activityPagination')
        .html(
            html
        );

}


function buildPageItem(
    page,
    text,
    disabled
) {

    return `
        <li
            class="
                page-item
                ${
        disabled
            ? 'disabled'
            : ''
        }
            "
        >

            <button
                type="button"
                class="page-link"
                data-activity-page="${
        page
        }"
                ${
        disabled
            ? 'disabled'
            : ''
        }
            >
                ${text}
            </button>

        </li>
    `;

}


function viewActivity(
    id
) {

    const url =
        activityRoutes
            .show
            .replace(
                ':id',
                id
            );


    $.ajax({

        url:
        url,

        type:
            'GET',

        dataType:
            'json',

        success:
            function (
                activity
            ) {

                showActivityDetail(
                    activity
                );

            },

        error:
            function () {

                $.alert({

                    title:
                        'Aviso',

                    content:
                        'No se pudo cargar el detalle de auditoría.',

                    type:
                        'orange'

                });

            }

    });

}


function showActivityDetail(
    activity
) {

    const tenant =
        activity.tenant_name
            ? activity.tenant_name
            : (
                activity.tenant_id
                    ? 'Tenant #' +
                    activity.tenant_id
                    : 'Global'
            );


    const causer =
        activity.causer &&
        activity.causer.name
            ? (
                escapeHtml(
                    activity.causer.name
                ) +
                (
                    activity.causer.email
                        ? '<br><small>' +
                        escapeHtml(
                            activity.causer.email
                        ) +
                        '</small>'
                        : ''
                )
            )
            : '-';


    const subject =
        buildSubjectLabel(
            activity.subject
        );


    let content = `

        <div class="mb-3">

            <strong>
                Acción:
            </strong>

            <br>

            ${renderActionBadge(
        activity.action
    )}

        </div>


        <div class="row">

            <div class="col-md-6 mb-3">

                <strong>
                    Fecha
                </strong>

                <br>

                ${escapeHtml(
        activity.created_at
        || '-'
    )}

            </div>


            <div class="col-md-6 mb-3">

                <strong>
                    Tenant
                </strong>

                <br>

                ${escapeHtml(
        tenant
    )}

            </div>


            <div class="col-md-6 mb-3">

                <strong>
                    Administrador
                </strong>

                <br>

                ${causer}

            </div>


            <div class="col-md-6 mb-3">

                <strong>
                    Entidad
                </strong>

                <br>

                ${subject}

            </div>

        </div>
    `;


    if (activity.old) {

        content +=
            buildJsonBlock(
                'Valores anteriores',
                activity.old
            );

    }


    if (activity.new) {

        content +=
            buildJsonBlock(
                'Valores nuevos',
                activity.new
            );

    }


    if (
        activity.permissions_added &&
        activity.permissions_added.length
    ) {

        content +=
            buildPermissionBlock(
                'Permisos agregados',
                activity.permissions_added,
                'success'
            );

    }


    if (
        activity.permissions_removed &&
        activity.permissions_removed.length
    ) {

        content +=
            buildPermissionBlock(
                'Permisos eliminados',
                activity.permissions_removed,
                'danger'
            );

    }


    content += `

        <hr>

        <div class="row">

            <div class="col-md-4">

                <strong>
                    IP
                </strong>

                <br>

                ${escapeHtml(
        activity.ip || '-'
    )}

            </div>


            <div class="col-md-8">

                <strong>
                    Navegador / dispositivo
                </strong>

                <br>

                <small>
                    ${escapeHtml(
        activity.user_agent
        || '-'
    )}
                </small>

            </div>

        </div>
    `;


    $.alert({

        title:
            'Detalle de auditoría #' +
            activity.id,

        content:
        content,

        columnClass:
            'col-md-10 col-lg-8',

        type:
            'blue',

        buttons: {

            close: {

                text:
                    'Cerrar',

                btnClass:
                    'btn-secondary'

            }

        }

    });

}


function buildJsonBlock(
    title,
    data
) {

    const formatted =
        JSON.stringify(
            data,
            null,
            2
        );


    return `

        <div class="mb-3">

            <strong>
                ${escapeHtml(
        title
    )}
            </strong>

            <pre
                class="
                    bg-light
                    border
                    rounded
                    p-2
                    mt-1
                    mb-0
                "
                style="
                    max-height:300px;
                    overflow:auto;
                    white-space:pre-wrap;
                "
            >${escapeHtml(
        formatted
    )}</pre>

        </div>
    `;

}


function buildPermissionBlock(
    title,
    permissions,
    type
) {

    let html = '';

    permissions.forEach(
        function (
            permission
        ) {

            html += `
                <span
                    class="
                        badge
                        badge-${type}
                        mr-1
                        mb-1
                    "
                >
                    ${escapeHtml(
                permission
            )}
                </span>
            `;

        }
    );


    return `

        <div class="mb-3">

            <strong>
                ${escapeHtml(
        title
    )}
            </strong>

            <div class="mt-1">
                ${html}
            </div>

        </div>
    `;

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
        .text(
            value
        )
        .html();

}