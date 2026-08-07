$(function () {

    const $app = $('#plan-app');

    const urlList =
        $app.data('url-list');

    const urlStore =
        $app.data('url-store');

    const urlUpdate =
        $app.data('url-update');

    const urlToggle =
        $app.data('url-toggle');


    let currentPage = 1;


    function loadPlans(page = 1) {

        currentPage = page;

        $.ajax({

            url: urlList,

            method: 'GET',

            data: {
                page: page,
                search:
                    $('#searchPlan').val(),
                per_page:
                    $('#perPagePlan').val()
            },

            beforeSend: function () {

                $('#bodyPlans').html(`
                    <tr>
                        <td
                            colspan="7"
                            class="text-center"
                        >
                            <i
                                class="fas fa-spinner fa-spin"
                            ></i>
                            Cargando...
                        </td>
                    </tr>
                `);

            },

            success: function (response) {

                renderPlans(
                    response.data
                );

                renderPagination(
                    response
                );

            },

            error: function (xhr) {

                showAjaxError(
                    xhr,
                    'No se pudieron cargar los planes.'
                );

            }

        });

    }


    function renderPlans(plans) {

        let html = '';

        if (
            !plans ||
            plans.length === 0
        ) {

            html = `
                <tr>
                    <td
                        colspan="7"
                        class="text-center text-muted"
                    >
                        No se encontraron planes.
                    </td>
                </tr>
            `;

            $('#bodyPlans').html(
                html
            );

            return;

        }


        $.each(
            plans,
            function (index, plan) {

                const badge =
                    plan.is_active
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-secondary">Inactivo</span>';


                const toggleText =
                    plan.is_active
                        ? 'Inhabilitar'
                        : 'Habilitar';


                const toggleClass =
                    plan.is_active
                        ? 'btn-outline-danger'
                        : 'btn-outline-success';


                html += `
                    <tr>

                        <td>
                            ${plan.id}
                        </td>

                        <td>
                            ${escapeHtml(plan.code)}
                        </td>

                        <td>
                            ${escapeHtml(plan.name)}
                        </td>

                        <td>
                            ${plan.max_active_users}
                        </td>

                        <td>
                            ${plan.tenants_count}
                        </td>

                        <td>
                            ${badge}
                        </td>

                        <td>

                            <button
                                type="button"
                                class="
                                    btn
                                    btn-outline-primary
                                    btn-xs
                                    btn-edit-plan
                                "
                                data-plan='${encodeURIComponent(
                    JSON.stringify(plan)
                )}'
                            >
                                <i class="fas fa-edit"></i>
                            </button>

                            <button
                                type="button"
                                class="
                                    btn
                                    ${toggleClass}
                                    btn-xs
                                    btn-toggle-plan
                                "
                                data-id="${plan.id}"
                                data-active="${plan.is_active ? 1 : 0}"
                            >
                                ${toggleText}
                            </button>

                        </td>

                    </tr>
                `;

            }
        );


        $('#bodyPlans').html(
            html
        );

    }


    function renderPagination(response) {

        let html = '';

        if (
            response.last_page <= 1
        ) {

            $('#paginationPlans').html('');

            return;

        }


        html += `
            <ul class="pagination pagination-sm">
        `;


        html += `
            <li class="
                page-item
                ${response.current_page === 1
            ? 'disabled'
            : ''}
            ">
                <a
                    href="#"
                    class="page-link page-plan"
                    data-page="${response.current_page - 1}"
                >
                    &laquo;
                </a>
            </li>
        `;


        for (
            let page = 1;
            page <= response.last_page;
            page++
        ) {

            html += `
                <li class="
                    page-item
                    ${page === response.current_page
                ? 'active'
                : ''}
                ">
                    <a
                        href="#"
                        class="page-link page-plan"
                        data-page="${page}"
                    >
                        ${page}
                    </a>
                </li>
            `;

        }


        html += `
            <li class="
                page-item
                ${response.current_page === response.last_page
            ? 'disabled'
            : ''}
            ">
                <a
                    href="#"
                    class="page-link page-plan"
                    data-page="${response.current_page + 1}"
                >
                    &raquo;
                </a>
            </li>
        `;


        html += '</ul>';


        $('#paginationPlans').html(
            html
        );

    }


    $('#btnNewPlan').on(
        'click',
        function () {

            $('#formCreatePlan')[0]
                .reset();

            $('#modalCreatePlan')
                .modal('show');

        }
    );


    $('#formCreatePlan').on(
        'submit',
        function (event) {

            event.preventDefault();

            let form = $(this);

            let button =
                form.find(
                    'button[type="submit"]'
                );


            button.prop(
                'disabled',
                true
            );


            $.ajax({

                url: urlStore,

                method: 'POST',

                data: form.serialize(),

                success: function (
                    response
                ) {

                    $('#modalCreatePlan')
                        .modal('hide');

                    $.alert({
                        title: 'Correcto',
                        content:
                        response.message,
                        type: 'green',
                        buttons: {
                            ok: {
                                text: 'Aceptar',
                                btnClass:
                                    'btn-success'
                            }
                        }
                    });

                    loadPlans(1);

                },

                error: function (xhr) {

                    showAjaxError(
                        xhr,
                        'No se pudo registrar el plan.'
                    );

                },

                complete: function () {

                    button.prop(
                        'disabled',
                        false
                    );

                }

            });

        }
    );


    $(document).on(
        'click',
        '.btn-edit-plan',
        function () {

            const encoded =
                $(this).attr(
                    'data-plan'
                );

            const plan =
                JSON.parse(
                    decodeURIComponent(
                        encoded
                    )
                );


            $('#edit_plan_id')
                .val(plan.id);

            $('#edit_code')
                .val(plan.code);

            $('#edit_name')
                .val(plan.name);

            $('#edit_max_active_users')
                .val(
                    plan.max_active_users
                );

            $('#edit_description')
                .val(
                    plan.description || ''
                );


            $('#modalEditPlan')
                .modal('show');

        }
    );


    $('#formEditPlan').on(
        'submit',
        function (event) {

            event.preventDefault();

            let planId =
                $('#edit_plan_id').val();

            let url =
                urlUpdate.replace(
                    ':id',
                    planId
                );

            let form =
                $(this);

            let button =
                form.find(
                    'button[type="submit"]'
                );


            button.prop(
                'disabled',
                true
            );


            $.ajax({

                url: url,

                method: 'POST',

                data: form.serialize(),

                success: function (
                    response
                ) {

                    $('#modalEditPlan')
                        .modal('hide');

                    $.alert({
                        title: 'Correcto',
                        content:
                        response.message,
                        type: 'green',
                        buttons: {
                            ok: {
                                text: 'Aceptar',
                                btnClass:
                                    'btn-success'
                            }
                        }
                    });

                    loadPlans(
                        currentPage
                    );

                },

                error: function (xhr) {

                    showAjaxError(
                        xhr,
                        'No se pudo actualizar el plan.'
                    );

                },

                complete: function () {

                    button.prop(
                        'disabled',
                        false
                    );

                }

            });

        }
    );


    $(document).on(
        'click',
        '.btn-toggle-plan',
        function () {

            let planId =
                $(this).data('id');

            let active =
                parseInt(
                    $(this).data('active')
                ) === 1;

            let actionText =
                active
                    ? 'inhabilitar'
                    : 'habilitar';


            $.confirm({

                title:
                    active
                        ? 'Inhabilitar plan'
                        : 'Habilitar plan',

                content:
                    `¿Está seguro de ${actionText} este plan?`,

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

                        action: function () {

                            let url =
                                urlToggle.replace(
                                    ':id',
                                    planId
                                );


                            $.ajax({

                                url: url,

                                method: 'POST',

                                data: {
                                    _token:
                                        $(
                                            'meta[name="csrf-token"]'
                                        )
                                            .attr(
                                                'content'
                                            )
                                },

                                success: function (
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

                                    loadPlans(
                                        currentPage
                                    );

                                },

                                error: function (
                                    xhr
                                ) {

                                    showAjaxError(
                                        xhr,
                                        'No se pudo cambiar el estado del plan.'
                                    );

                                }

                            });

                        }

                    },

                    cancel: {
                        text: 'Cancelar'
                    }

                }

            });

        }
    );


    $(document).on(
        'click',
        '.page-plan',
        function (event) {

            event.preventDefault();

            if (
                $(this)
                    .closest('.page-item')
                    .hasClass('disabled')
            ) {
                return;
            }

            loadPlans(
                $(this).data('page')
            );

        }
    );


    let searchTimeout;

    $('#searchPlan').on(
        'keyup',
        function () {

            clearTimeout(
                searchTimeout
            );

            searchTimeout =
                setTimeout(
                    function () {
                        loadPlans(1);
                    },
                    400
                );

        }
    );


    $('#perPagePlan').on(
        'change',
        function () {
            loadPlans(1);
        }
    );


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

            const errors =
                xhr.responseJSON.errors;

            const firstError =
                Object.values(
                    errors
                )[0];

            if (
                firstError &&
                firstError[0]
            ) {
                message =
                    firstError[0];
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
            type: 'orange',
            buttons: {
                ok: {
                    text: 'Aceptar'
                }
            }
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


    loadPlans();

});