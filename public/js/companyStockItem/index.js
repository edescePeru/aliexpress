$(document).ready(function () {

    const $app =
        $('#company-stock-items-app');

    const dataUrl =
        $app.data('url-data');

    const statusUrl =
        $app.data('url-status');


    let currentPage = 1;

    let searchTimer = null;


    function loadData(page = 1) {

        currentPage = page;

        $.ajax({

            url: dataUrl,

            method: 'GET',

            data: {
                company_id:
                    $('#company_id').val(),

                brand_id:
                    $('#brand_id').val(),

                category_id:
                    $('#category_id').val(),

                status:
                    $('#status').val(),

                search:
                    $('#search').val(),

                page:
                page
            },

            success: function (response) {

                renderRows(
                    response.data
                );

                renderPagination(
                    response
                );
            },

            error: function (xhr) {

                let message =
                    'No se pudo cargar el catálogo.';

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
        });
    }


    function renderRows(rows) {

        const $body =
            $('#stock-items-body');

        $body.empty();


        if (!rows.length) {

            $body.append(`
                <tr>
                    <td
                        colspan="7"
                        class="text-center text-muted"
                    >
                        No se encontraron productos.
                    </td>
                </tr>
            `);

            return;
        }


        rows.forEach(function (row) {

            const checked =
                row.enabled
                    ? 'checked'
                    : '';

            $body.append(`
                <tr>

                    <td>
                        ${escapeHtml(row.material || '')}
                    </td>

                    <td>
                        ${escapeHtml(row.brand || '')}
                    </td>

                    <td>
                        ${escapeHtml(row.category || '')}
                    </td>

                    <td>
                        ${escapeHtml(row.variant || '')}
                    </td>

                    <td>
                        ${escapeHtml(row.sku || '')}
                    </td>

                    <td>
                        ${escapeHtml(row.barcode || '')}
                    </td>

                    <td class="text-center">

                        <input
                            type="checkbox"
                            class="company-stock-toggle"
                            data-stock-item-id="${row.id}"
                            ${checked}
                        >

                    </td>

                </tr>
            `);
        });
    }


    function renderPagination(response) {

        const $container =
            $('#pagination-container');

        $container.empty();


        if (response.last_page <= 1) {
            return;
        }


        const $pagination =
            $('<ul class="pagination"></ul>');


        for (
            let page = 1;
            page <= response.last_page;
            page++
        ) {

            const active =
                page === response.current_page
                    ? 'active'
                    : '';

            $pagination.append(`
                <li class="page-item ${active}">
                    <a
                        href="#"
                        class="page-link"
                        data-page="${page}"
                    >
                        ${page}
                    </a>
                </li>
            `);
        }


        $container.append(
            $pagination
        );
    }


    $(document).on(
        'click',
        '#pagination-container .page-link',
        function (event) {

            event.preventDefault();

            loadData(
                $(this).data('page')
            );
        }
    );


    $('#company_id, #brand_id, #category_id, #status')
        .on(
            'change',
            function () {
                loadData(1);
            }
        );


    $('#search')
        .on(
            'keyup',
            function () {

                clearTimeout(
                    searchTimer
                );

                searchTimer =
                    setTimeout(
                        function () {
                            loadData(1);
                        },
                        400
                    );
            }
        );


    $(document).on(
        'change',
        '.company-stock-toggle',
        function () {

            const $checkbox =
                $(this);

            const previousState =
                !$checkbox.is(':checked');

            const stockItemId =
                $checkbox.data(
                    'stock-item-id'
                );

            const enabled =
                $checkbox.is(':checked')
                    ? 1
                    : 0;


            $checkbox.prop(
                'disabled',
                true
            );


            $.ajax({

                url: statusUrl,

                method: 'POST',

                headers: {
                    'X-CSRF-TOKEN':
                        $('meta[name="csrf-token"]')
                            .attr('content')
                },

                data: {
                    company_id:
                        $('#company_id').val(),

                    stock_item_id:
                    stockItemId,

                    enabled:
                    enabled
                },

                success: function (response) {

                    toastr.success(
                        response.message
                    );

                    $checkbox.prop(
                        'disabled',
                        false
                    );


                    /*
                     * Si estamos filtrando
                     * habilitados/no habilitados,
                     * recargamos porque el registro
                     * ya no corresponde al filtro.
                     */
                    if (
                        $('#status').val() !==
                        'all'
                    ) {
                        loadData(
                            currentPage
                        );
                    }
                },

                error: function (xhr) {

                    $checkbox.prop(
                        'checked',
                        previousState
                    );

                    $checkbox.prop(
                        'disabled',
                        false
                    );


                    let message =
                        'No se pudo actualizar el producto.';

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
            });
        }
    );


    function escapeHtml(value) {

        return $('<div>')
            .text(value)
            .html();
    }


    loadData(1);
});