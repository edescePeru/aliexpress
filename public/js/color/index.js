let colorCurrentPage = 1;
let colorSearchTimeout = null;
let colorRoutes = {};
let canUpdateColor = false;
let canDeleteColor = false;

$(function () {

    const $app =
        $('#color-app');


    colorRoutes = {

        data:
            $app.data(
                'url-data'
            ),

        edit:
            $app.data(
                'url-edit'
            ),

        delete:
            $app.data(
                'url-delete'
            ),

        deleteMultiple:
            $app.data(
                'url-delete-multiple'
            )

    };


    canUpdateColor =
        parseInt(
            $app.data(
                'can-update'
            ),
            10
        ) === 1;


    canDeleteColor =
        parseInt(
            $app.data(
                'can-delete'
            ),
            10
        ) === 1;


    loadColors();


    $('#colorSearch').on(
        'keyup',
        function () {

            clearTimeout(
                colorSearchTimeout
            );

            colorSearchTimeout =
                setTimeout(
                    function () {

                        colorCurrentPage = 1;

                        loadColors();

                    },
                    350
                );

        }
    );


    $('#colorPerPage').on(
        'change',
        function () {

            colorCurrentPage = 1;

            loadColors();

        }
    );


    $(document).on(
        'click',
        '[data-color-page]',
        function () {

            const page =
                parseInt(
                    $(this).data(
                        'color-page'
                    ),
                    10
                );

            if (!page) {
                return;
            }

            colorCurrentPage =
                page;

            loadColors();

        }
    );


    $(document).on(
        'click',
        '[data-delete-color]',
        function () {

            const id =
                $(this).data(
                    'delete-color'
                );

            deleteColor(
                id
            );

        }
    );


    $(document).on(
        'change',
        '.color-checkbox',
        function () {

            updateDeleteButton();

            updateCheckAllState();

        }
    );


    $('#checkAllColors').on(
        'change',
        function () {

            $('.color-checkbox')
                .prop(
                    'checked',
                    $(this).is(':checked')
                );

            updateDeleteButton();

        }
    );


    $('#btnDeleteSelectedColors').on(
        'click',
        function () {

            const ids =
                getSelectedColorIds();

            if (!ids.length) {
                return;
            }

            deleteMultipleColors(
                ids
            );

        }
    );

});


function loadColors() {

    const colspan =
        canDeleteColor
            ? 6
            : 5;


    $('#colorTableBody')
        .html(
            `
                <tr>
                    <td
                        colspan="${colspan}"
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
        colorRoutes.data,

        type:
            'GET',

        dataType:
            'json',

        data: {

            page:
            colorCurrentPage,

            search:
                $('#colorSearch')
                    .val(),

            per_page:
                $('#colorPerPage')
                    .val()

        },

        success:
            function (
                response
            ) {

                renderColors(
                    response
                );

            },

        error:
            function () {

                $('#colorTableBody')
                    .html(
                        `
                            <tr>
                                <td
                                    colspan="${colspan}"
                                    class="
                                        text-center
                                        text-danger
                                        py-4
                                    "
                                >
                                    No se pudieron
                                    cargar los colores.
                                </td>
                            </tr>
                        `
                    );

            }

    });

}


function renderColors(
    response
) {

    /*
     * Si tu endpoint devuelve directamente
     * un array, también lo soportamos.
     */
    const colors =
        Array.isArray(response)
            ? response
            : (
                response.data || []
            );


    if (!colors.length) {

        $('#colorTableBody')
            .html('');

        $('#colorEmpty')
            .removeClass(
                'd-none'
            );

        $('#colorPaginationInfo')
            .html('');

        $('#colorPagination')
            .html('');

        return;

    }


    $('#colorEmpty')
        .addClass(
            'd-none'
        );


    let html = '';


    colors.forEach(
        function (
            color
        ) {

            const editUrl =
                colorRoutes
                    .edit
                    .replace(
                        ':id',
                        color.id
                    );


            let actions = '';


            if (canUpdateColor) {

                actions += `
                    <a
                        href="${editUrl}"
                        class="
                            btn
                            btn-outline-warning
                            btn-sm
                            mr-1
                        "
                    >
                        <i
                            class="
                                fas
                                fa-pencil-alt
                            "
                        ></i>

                        Editar
                    </a>
                `;

            }


            if (canDeleteColor) {

                actions += `
                    <button
                        type="button"
                        class="
                            btn
                            btn-outline-danger
                            btn-sm
                        "
                        data-delete-color="${
                    color.id
                    }"
                    >
                        <i
                            class="
                                fas
                                fa-trash
                            "
                        ></i>
                    </button>
                `;

            }


            const colorBox =
                buildColorPreview(
                    color.code
                );


            html += `
                <tr>

                    ${
                canDeleteColor
                    ? `
                                <td
                                    class="text-center"
                                >
                                    <input
                                        type="checkbox"
                                        class="color-checkbox"
                                        value="${
                        color.id
                        }"
                                    >
                                </td>
                            `
                    : ''
                }


                    <td
                        class="text-center"
                    >
                        ${colorBox}
                    </td>


                    <td>
                        ${escapeHtml(
                color.name || '-'
            )}
                    </td>


                    <td>
                        ${
                color.code
                    ? `
                                    <code>
                                        ${escapeHtml(
                    color.code
                    )}
                                    </code>
                                `
                    : '-'
                }
                    </td>


                    <td>
                        ${escapeHtml(
                color.short_name
                || '-'
            )}
                    </td>


                    <td>
                        ${actions || '-'}
                    </td>

                </tr>
            `;

        }
    );


    $('#colorTableBody')
        .html(
            html
        );


    $('#checkAllColors')
        .prop(
            'checked',
            false
        )
        .prop(
            'indeterminate',
            false
        );


    updateDeleteButton();


    if (
        !Array.isArray(
            response
        )
    ) {
        renderColorPagination(
            response
        );
    }

}


function buildColorPreview(
    code
) {

    if (
        !code ||
        !isValidHexColor(
            code
        )
    ) {

        return `
            <span
                class="
                    badge
                    badge-light
                    border
                "
            >
                N/A
            </span>
        `;

    }


    return `
        <span
            title="${escapeHtml(code)}"
            style="
                display:inline-block;
                width:32px;
                height:32px;
                border-radius:6px;
                border:1px solid #adb5bd;
                background-color:${
        escapeHtml(code)
        };
                vertical-align:middle;
            "
        ></span>
    `;

}


function isValidHexColor(
    value
) {

    return /^#[0-9A-Fa-f]{6}$/
        .test(
            String(value)
        );

}


function deleteColor(
    id
) {

    $.confirm({

        title:
            'Eliminar color',

        content:
            '¿Está seguro de eliminar este color?',

        type:
            'red',

        buttons: {

            confirm: {

                text:
                    'Sí, eliminar',

                btnClass:
                    'btn-danger',

                action:
                    function () {

                        sendDeleteColor(
                            id
                        );

                    }

            },

            cancel: {

                text:
                    'Cancelar'

            }

        }

    });

}


function sendDeleteColor(
    id
) {

    $.ajax({

        url:
        colorRoutes.delete,

        type:
            'POST',

        data: {

            _token:
                $('meta[name="csrf-token"]')
                    .attr('content'),

            color_id:
            id

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

                                    loadColors();

                                }

                        }

                    }

                });

            },

        error:
            function (
                xhr
            ) {

                showColorAjaxError(
                    xhr,
                    'No se pudo eliminar el color.'
                );

            }

    });

}


function getSelectedColorIds() {

    const ids = [];


    $('.color-checkbox:checked')
        .each(
            function () {

                ids.push(
                    $(this).val()
                );

            }
        );


    return ids;

}


function deleteMultipleColors(
    ids
) {

    $.confirm({

        title:
            'Eliminar colores',

        content:
            '¿Está seguro de eliminar los colores seleccionados?',

        type:
            'red',

        buttons: {

            confirm: {

                text:
                    'Sí, eliminar',

                btnClass:
                    'btn-danger',

                action:
                    function () {

                        $.ajax({

                            url:
                            colorRoutes
                                .deleteMultiple,

                            type:
                                'POST',

                            data: {

                                _token:
                                    $('meta[name="csrf-token"]')
                                        .attr('content'),

                                ids:
                                ids

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

                                                        loadColors();

                                                    }

                                            }

                                        }

                                    });

                                },

                            error:
                                function (
                                    xhr
                                ) {

                                    showColorAjaxError(
                                        xhr,
                                        'No se pudieron eliminar los colores.'
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


function updateDeleteButton() {

    const selected =
        $('.color-checkbox:checked')
            .length;

    $('#btnDeleteSelectedColors')
        .prop(
            'disabled',
            selected === 0
        );

}


function updateCheckAllState() {

    const total =
        $('.color-checkbox')
            .length;

    const selected =
        $('.color-checkbox:checked')
            .length;


    $('#checkAllColors')
        .prop(
            'checked',
            total > 0 &&
            selected === total
        )
        .prop(
            'indeterminate',
            selected > 0 &&
            selected < total
        );

}


function renderColorPagination(
    response
) {

    if (
        response.total === undefined
    ) {
        return;
    }


    $('#colorPaginationInfo')
        .text(
            response.total
                ? (
                    'Mostrando ' +
                    response.from +
                    ' a ' +
                    response.to +
                    ' de ' +
                    response.total +
                    ' registros'
                )
                : ''
        );


    if (
        response.last_page <= 1
    ) {

        $('#colorPagination')
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
                    data-color-page="${
            page
            }"
                >
                    ${page}
                </button>

            </li>
        `;

    }


    html += '</ul>';


    $('#colorPagination')
        .html(
            html
        );

}


function showColorAjaxError(
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
        .text(
            value
        )
        .html();

}