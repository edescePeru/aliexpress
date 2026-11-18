$(document).ready(function () {

    const $app =
        $('#company-stock-items-app');

    const dataUrl =
        $app.data('url-data');

    const statusUrl =
        $app.data('url-status');


    let currentPage = 1;

    let searchTimer = null;
    let pendingRead = null;
    let readVersion = 0;


    function loadData(page = 1) {

        currentPage = page;
        const version = ++readVersion;
        if (pendingRead) pendingRead.abort();
        $('#stock-items-body').empty();
        $('#pagination-container').empty();
        if (!$('#company_id').val()) {
            $('#catalog-summary').text('Sin empresas autorizadas.');
            return;
        }
        $('#catalog-summary').text('Cargando catálogo…');

        pendingRead = $.ajax({

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
                if (version !== readVersion) return;
                $('#catalog-summary').text(`${response.total} productos · Mostrando ${response.from || 0} a ${response.to || 0}`);

                renderRows(
                    response.data
                );

                renderPagination(
                    response
                );
            },

            error: function (xhr, status) {
                if (status === 'abort' || version !== readVersion) return;
                $('#catalog-summary').text('No se pudo cargar el catálogo.');

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
                        class="next-table-empty"
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

                    <td class="text-center">
                        ${escapeHtml(row.sku || '')}
                    </td>

                    <td class="text-center">
                        ${escapeHtml(row.barcode || '')}
                    </td>

                    <td class="text-center">

                        <label class="mb-0 position-relative">
                        <input
                            type="checkbox"
                            class="company-stock-toggle"
                            data-stock-item-id="${row.id}"
                            ${checked}
                        >
                        <span class="company-stock-state">${row.enabled ? 'Habilitado' : 'No habilitado'}</span>
                        <span class="sr-only">SKU ${escapeHtml(row.sku || '')}</span>
                        </label>

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
            $('<ul class="pagination pagination-sm flex-wrap mb-0"></ul>');


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
                    $checkbox.closest('label').find('.company-stock-state').text(response.enabled ? 'Habilitado' : 'No habilitado');

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