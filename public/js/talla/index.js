let tallaCurrentPage = 1;
let tallaSearchTimeout = null;
let tallaRoutes = {};
let canUpdateTalla = false;
let canDeleteTalla = false;

$(function () {

    const $app =
        $('#talla-app');


    tallaRoutes = {

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


    canUpdateTalla =
        parseInt(
            $app.data(
                'can-update'
            ),
            10
        ) === 1;


    canDeleteTalla =
        parseInt(
            $app.data(
                'can-delete'
            ),
            10
        ) === 1;


    loadTallas();


    $('#tallaSearch').on(
        'keyup',
        function () {

            clearTimeout(
                tallaSearchTimeout
            );

            tallaSearchTimeout =
                setTimeout(
                    function () {

                        tallaCurrentPage = 1;

                        loadTallas();

                    },
                    350
                );

        }
    );


    $('#tallaPerPage').on(
        'change',
        function () {

            tallaCurrentPage = 1;

            loadTallas();

        }
    );


    $(document).on(
        'click',
        '[data-talla-page]',
        function () {

            const page =
                parseInt(
                    $(this).data(
                        'talla-page'
                    ),
                    10
                );

            if (!page) {
                return;
            }

            tallaCurrentPage =
                page;

            loadTallas();

        }
    );


    $(document).on(
        'click',
        '[data-delete-talla]',
        function () {

            const id =
                $(this).data(
                    'delete-talla'
                );

            confirmDeleteTalla(
                id
            );

        }
    );


    $(document).on(
        'change',
        '.talla-checkbox',
        function () {

            updateDeleteTallaButton();
            updateCheckAllTallasState();

        }
    );


    $('#checkAllTallas').on(
        'change',
        function () {

            $('.talla-checkbox')
                .prop(
                    'checked',
                    $(this).is(
                        ':checked'
                    )
                );

            updateDeleteTallaButton();

        }
    );


    $('#btnDeleteSelectedTallas').on(
        'click',
        function () {

            const ids =
                getSelectedTallaIds();

            if (!ids.length) {
                return;
            }

            confirmDeleteMultipleTallas(
                ids
            );

        }
    );

});


function loadTallas() {

    const colspan =
        canDeleteTalla
            ? 5
            : 4;


    $('#tallaTableBody')
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
        tallaRoutes.data,

        type:
            'GET',

        dataType:
            'json',

        data: {

            page:
            tallaCurrentPage,

            search:
                $('#tallaSearch')
                    .val(),

            per_page:
                $('#tallaPerPage')
                    .val()

        },

        success:
            function (
                response
            ) {

                renderTallas(
                    response
                );

            },

        error:
            function () {

                $('#tallaTableBody')
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
                                    No se pudieron cargar
                                    las tallas.
                                </td>
                            </tr>
                        `
                    );

            }

    });

}


function renderTallas(
    response
) {

    const tallas =
        response.data || [];


    if (!tallas.length) {

        $('#tallaTableBody')
            .html('');

        $('#tallaEmpty')
            .removeClass(
                'd-none'
            );

        $('#tallaPaginationInfo')
            .html('');

        $('#tallaPagination')
            .html('');

        return;
    }


    $('#tallaEmpty')
        .addClass(
            'd-none'
        );


    let html = '';


    tallas.forEach(
        function (
            talla
        ) {

            const editUrl =
                tallaRoutes
                    .edit
                    .replace(
                        ':id',
                        talla.id
                    );


            let actions = '';


            if (canUpdateTalla) {

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


            if (canDeleteTalla) {

                actions += `
                    <button
                        type="button"
                        class="
                            btn
                            btn-outline-danger
                            btn-sm
                        "
                        data-delete-talla="${
                    talla.id
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


            html += `
                <tr>

                    ${
                canDeleteTalla
                    ? `
                                <td
                                    class="text-center"
                                >

                                    <input
                                        type="checkbox"
                                        class="talla-checkbox"
                                        value="${
                        talla.id
                        }"
                                    >

                                </td>
                            `
                    : ''
                }


                    <td>

                        <strong>
                            ${escapeTallaHtml(
                talla.name ||
                '-'
            )}
                        </strong>

                    </td>


                    <td>

                        ${
                talla.short_name
                    ? `
                                    <span
                                        class="
                                            badge
                                            badge-info
                                        "
                                    >
                                        ${escapeTallaHtml(
                    talla.short_name
                    )}
                                    </span>
                                `
                    : '-'
                }

                    </td>


                    <td>

                        ${escapeTallaHtml(
                talla.description ||
                '-'
            )}

                    </td>


                    <td>
                        ${actions || '-'}
                    </td>

                </tr>
            `;

        }
    );


    $('#tallaTableBody')
        .html(
            html
        );


    $('#checkAllTallas')
        .prop(
            'checked',
            false
        )
        .prop(
            'indeterminate',
            false
        );


    updateDeleteTallaButton();

    renderTallaPagination(
        response
    );

}


function confirmDeleteTalla(
    id
) {

    $.confirm({

        title:
            'Eliminar talla',

        content:
            '¿Está seguro de eliminar esta talla?',

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

                        sendDeleteTalla(
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


function sendDeleteTalla(
    id
) {

    $.ajax({

        url:
        tallaRoutes.delete,

        type:
            'POST',

        data: {

            _token:
                $('meta[name="csrf-token"]')
                    .attr('content'),

            talla_id:
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

                                    loadTallas();

                                }

                        }

                    }

                });

            },

        error:
            function (
                xhr
            ) {

                showTallaIndexError(
                    xhr,
                    'No se pudo eliminar la talla.'
                );

            }

    });

}


function getSelectedTallaIds() {

    const ids = [];


    $('.talla-checkbox:checked')
        .each(
            function () {

                ids.push(
                    $(this).val()
                );

            }
        );


    return ids;

}


function confirmDeleteMultipleTallas(
    ids
) {

    $.confirm({

        title:
            'Eliminar tallas',

        content:
            '¿Está seguro de eliminar las tallas seleccionadas?',

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

                        sendDeleteMultipleTallas(
                            ids
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


function sendDeleteMultipleTallas(
    ids
) {

    $.ajax({

        url:
        tallaRoutes
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

                                    loadTallas();

                                }

                        }

                    }

                });

            },

        error:
            function (
                xhr
            ) {

                showTallaIndexError(
                    xhr,
                    'No se pudieron eliminar las tallas.'
                );

            }

    });

}


function updateDeleteTallaButton() {

    const selected =
        $('.talla-checkbox:checked')
            .length;


    $('#btnDeleteSelectedTallas')
        .prop(
            'disabled',
            selected === 0
        );

}


function updateCheckAllTallasState() {

    const total =
        $('.talla-checkbox')
            .length;

    const selected =
        $('.talla-checkbox:checked')
            .length;


    $('#checkAllTallas')
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


function renderTallaPagination(
    response
) {

    $('#tallaPaginationInfo')
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

        $('#tallaPagination')
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
                    data-talla-page="${page}"
                >

                    ${page}

                </button>

            </li>
        `;

    }


    html += '</ul>';


    $('#tallaPagination')
        .html(
            html
        );

}


function showTallaIndexError(
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


function escapeTallaHtml(
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