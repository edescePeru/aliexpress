$(document).ready(function () {

    const $page =
        $('#workforcePage');

    if (!$page.length) {
        return;
    }

    let currentPage = 1;

    let searchTimer = null;


    /*
     * ============================================================
     * URLS
     * ============================================================
     */

    const urlData =
        $page.data(
            'url-data'
        );

    const urlStore =
        $page.data(
            'url-store'
        );

    const urlEditTemplate =
        $page.data(
            'url-edit'
        );

    const urlUpdateTemplate =
        $page.data(
            'url-update'
        );

    const urlDeleteTemplate =
        $page.data(
            'url-delete'
        );


    /*
     * ============================================================
     * HELPERS
     * ============================================================
     */

    function routeWithId(
        template,
        id
    ) {

        return String(
            template
        ).replace(
            ':id',
            id
        );
    }


    function csrfToken()
    {

        return $(
            'meta[name="csrf-token"]'
        ).attr(
            'content'
        );
    }


    /*
     * ============================================================
     * LISTADO
     * ============================================================
     */

    function loadWorkforces(
        page
    ) {

        currentPage =
            page || 1;

        $('#workforceTableContainer')
            .html(
                '<div class="text-center py-4">' +
                '<i class="fas fa-spinner fa-spin"></i> ' +
                'Cargando...' +
                '</div>'
            );

        $.ajax({

            url:
            urlData,

            method:
                'GET',

            data: {
                page:
                currentPage,

                search:
                    $('#workforceSearch')
                        .val()
                        .trim()
            },

            success: function (
                response
            ) {

                $('#workforceTableContainer')
                    .html(
                        response.html
                    );
            },

            error: function () {

                $('#workforceTableContainer')
                    .html(
                        '<div class="alert alert-danger">' +
                        'No se pudieron cargar los servicios.' +
                        '</div>'
                    );
            }
        });
    }


    /*
     * ============================================================
     * BUSCADOR
     * ============================================================
     */

    $('#workforceSearch')
        .on(
            'input',
            function () {

                clearTimeout(
                    searchTimer
                );

                searchTimer =
                    setTimeout(
                        function () {

                            loadWorkforces(
                                1
                            );

                        },
                        350
                    );
            }
        );


    /*
     * ============================================================
     * PAGINACIÓN
     * ============================================================
     */

    $(document)
        .on(
            'click',
            '[data-workforce-page]',
            function () {

                const page =
                    parseInt(
                        $(this).data(
                            'workforce-page'
                        )
                    );

                loadWorkforces(
                    page
                );
            }
        );


    /*
     * ============================================================
     * CREAR
     * ============================================================
     */

    $('#btnCreateWorkforce')
        .on(
            'click',
            function () {

                $('#workforceId')
                    .val('');

                $('#workforceDescription')
                    .val('');

                $('#workforceUnitMeasure')
                    .val('')
                    .trigger(
                        'change'
                    );

                $('#workforceUnitPrice')
                    .val(
                        '0'
                    );

                $('#workforceBillable')
                    .prop(
                        'checked',
                        true
                    );

                $('#modalWorkforceTitle')
                    .text(
                        'Nuevo servicio'
                    );

                $('#modalWorkforce')
                    .modal(
                        'show'
                    );
            }
        );


    /*
     * ============================================================
     * EDITAR
     * ============================================================
     */

    $(document)
        .on(
            'click',
            '[data-edit-workforce]',
            function () {

                const id =
                    parseInt(
                        $(this).data(
                            'edit-workforce'
                        )
                    );

                $.ajax({

                    url:
                        routeWithId(
                            urlEditTemplate,
                            id
                        ),

                    method:
                        'GET',

                    success: function (
                        response
                    ) {

                        const item =
                            response.workforce;

                        $('#workforceId')
                            .val(
                                item.id
                            );

                        $('#workforceDescription')
                            .val(
                                item.description
                            );

                        $('#workforceUnitMeasure')
                            .val(
                                item.unit_measure_id
                            )
                            .trigger(
                                'change'
                            );

                        $('#workforceUnitPrice')
                            .val(
                                parseFloat(
                                    item.unit_price || 0
                                ).toFixed(
                                    2
                                )
                            );

                        $('#workforceBillable')
                            .prop(
                                'checked',
                                !!item.billable
                            );

                        $('#modalWorkforceTitle')
                            .text(
                                'Editar servicio'
                            );

                        $('#modalWorkforce')
                            .modal(
                                'show'
                            );
                    },

                    error: function (
                        xhr
                    ) {

                        toastr.error(
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'No se pudo cargar el servicio.',
                            'Error'
                        );
                    }
                });
            }
        );


    /*
     * ============================================================
     * GUARDAR
     * ============================================================
     */

    $('#btnSaveWorkforce')
        .on(
            'click',
            function () {

                const $button =
                    $(this);

                const id =
                    parseInt(
                        $('#workforceId')
                            .val()
                        || 0
                    );

                const description =
                    $('#workforceDescription')
                        .val()
                        .trim();

                const unitMeasureId =
                    $('#workforceUnitMeasure')
                        .val();

                const unitPrice =
                    parseFloat(
                        $('#workforceUnitPrice')
                            .val()
                        || 0
                    );

                const billable =
                    $('#workforceBillable')
                        .is(
                            ':checked'
                        )
                        ? 1
                        : 0;


                if (!description) {

                    toastr.warning(
                        'Ingrese la descripción del servicio.'
                    );

                    return;
                }


                if (!unitMeasureId) {

                    toastr.warning(
                        'Seleccione una unidad de medida.'
                    );

                    return;
                }


                if (
                    isNaN(
                        unitPrice
                    ) ||
                    unitPrice < 0
                ) {

                    toastr.warning(
                        'Ingrese un precio válido.'
                    );

                    return;
                }


                const isEdit =
                    id > 0;

                const url =
                    isEdit
                        ? routeWithId(
                        urlUpdateTemplate,
                        id
                        )
                        : urlStore;


                const data = {
                    _token:
                        csrfToken(),

                    description:
                    description,

                    unit_measure_id:
                    unitMeasureId,

                    unit_price:
                    unitPrice,

                    billable:
                    billable
                };


                if (isEdit) {
                    data._method =
                        'PUT';
                }


                $button
                    .prop(
                        'disabled',
                        true
                    )
                    .text(
                        'Guardando...'
                    );


                $.ajax({

                    url:
                    url,

                    method:
                        'POST',

                    data:
                    data,

                    success: function (
                        response
                    ) {

                        $('#modalWorkforce')
                            .modal(
                                'hide'
                            );


                        $.alert({

                            title:
                                'Proceso completado',

                            content:
                            response.message,

                            type:
                                'green',

                            buttons: {

                                ok: {
                                    text:
                                        'Aceptar',

                                    btnClass:
                                        'btn-success'
                                }
                            }
                        });


                        loadWorkforces(
                            currentPage
                        );
                    },

                    error: function (
                        xhr
                    ) {

                        const response =
                            xhr.responseJSON
                            || {};

                        if (
                            response.errors
                        ) {

                            Object.keys(
                                response.errors
                            )
                                .forEach(
                                    function (
                                        key
                                    ) {

                                        const messages =
                                            response.errors[
                                                key
                                                ];

                                        (
                                            Array.isArray(
                                                messages
                                            )
                                                ? messages
                                                : [
                                                    messages
                                                ]
                                        )
                                            .forEach(
                                                function (
                                                    message
                                                ) {

                                                    toastr.error(
                                                        message,
                                                        'Error'
                                                    );
                                                }
                                            );
                                    }
                                );

                        } else {

                            toastr.error(
                                response.message
                                || 'No se pudo guardar el servicio.',
                                'Error'
                            );
                        }
                    },

                    complete: function () {

                        $button
                            .prop(
                                'disabled',
                                false
                            )
                            .text(
                                'Guardar'
                            );
                    }
                });
            }
        );


    /*
     * ============================================================
     * ELIMINAR
     * ============================================================
     */

    $(document)
        .on(
            'click',
            '[data-delete-workforce]',
            function () {

                const id =
                    parseInt(
                        $(this).data(
                            'delete-workforce'
                        )
                    );

                $.confirm({

                    title:
                        'Eliminar servicio',

                    content:
                        '¿Está seguro de eliminar este servicio predeterminado?',

                    type:
                        'red',

                    buttons: {

                        confirm: {

                            text:
                                'Eliminar',

                            btnClass:
                                'btn-danger',

                            action:
                                function () {

                                    $.ajax({

                                        url:
                                            routeWithId(
                                                urlDeleteTemplate,
                                                id
                                            ),

                                        method:
                                            'POST',

                                        data: {
                                            _token:
                                                csrfToken(),

                                            _method:
                                                'DELETE'
                                        },

                                        success:
                                            function (
                                                response
                                            ) {

                                                toastr.success(
                                                    response.message
                                                );

                                                loadWorkforces(
                                                    currentPage
                                                );
                                            },

                                        error:
                                            function (
                                                xhr
                                            ) {

                                                toastr.error(
                                                    xhr.responseJSON &&
                                                    xhr.responseJSON.message
                                                        ? xhr.responseJSON.message
                                                        : 'No se pudo eliminar el servicio.',
                                                    'Error'
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


    /*
     * ============================================================
     * INICIO
     * ============================================================
     */

    $('#workforceUnitMeasure')
        .select2({
            theme:
                'bootstrap4',

            dropdownParent:
                $('#modalWorkforce'),

            width:
                '100%'
        });


    loadWorkforces(
        1
    );

});