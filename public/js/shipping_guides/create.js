(function ($) {

    // =========================
    // Helpers
    // =========================
    function csrfHeader() {
        var token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
        }
    }

    function guessDocType(docNumber) {
        if (!docNumber) return '6';
        var digits = String(docNumber).replace(/\D/g, '');
        if (digits.length === 8) return '1';   // DNI
        if (digits.length === 11) return '6';  // RUC
        return '6';
    }

    function fillCustomerFields(customer) {
        var docNumber = customer.doc_number || customer.RUC || customer.ruc || '';
        var email = customer.email || customer.email_cliente || '';
        var address = customer.address || customer.direccion || customer.customer_address || '';

        $('#customer_doc_number').val(docNumber);
        $('#customer_doc_type').val(guessDocType(docNumber)).trigger('change');

        $('input[name="customer_address"]').val(address);
        $('input[name="customer_email"]').val(email);
    }

    function lookupError(xhr) {
        var message = xhr.status === 403 ? 'No tienes permiso para consultar estos datos.' :
            xhr.status === 422 ? 'Revisa los filtros de la consulta.' :
            xhr.status === 404 ? 'El registro no está disponible en el contexto actual.' :
            [401, 419].indexOf(xhr.status) !== -1 ? 'La sesión ha vencido. Vuelve a ingresar.' :
            'No se pudo completar la consulta. Intenta nuevamente.';
        toastr.error(message, null, { escapeHtml: true });
    }

    function lookupTransport(params, success, failure) {
        var request = $.ajax(params);
        request.then(success);
        request.fail(function (xhr) {
            if (xhr.statusText !== 'abort') lookupError(xhr);
            failure(xhr);
        });
        return request;
    }

    function clearGuideValidation() {
        $('#guideValidation').prop('hidden', true).empty();
        $('#frmGuide .is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $('#frmGuide [data-guide-error]').remove();
    }

    function openValidationSections(key, $field) {
        // Hidden input and aggregate errors must also reveal their owning section.
        var $panels = $field.closest('[data-guide-section]');
        if (!$panels.length) {
            var section = key === 'items' || key.indexOf('items.') === 0 ? '07' :
                key === 'driver_primary' || key.indexOf('driver_primary.') === 0 ? '06' : null;
            if (section) $panels = $('#guide-panel-' + section);
        }
        $panels.collapse('show');
    }

    function renderGuideValidation(errors, message) {
        clearGuideValidation();
        var $summary = $('#guideValidation').prop('hidden', false);
        $('<strong>').text(message || 'Revisa los datos de la guía.').appendTo($summary);
        var $list = $('<ul class="mb-0">').appendTo($summary);
        Object.keys(errors).forEach(function (key) {
            var text = (errors[key] || [])[0] || '';
            $('<li>').text(text).appendTo($list);
            var name = key.replace(/\.([^.[\]]+)/g, '[$1]');
            var $field = $('#frmGuide [name]').filter(function () { return this.name === name; });
            openValidationSections(key, $field);
            if ($field.length && $field.attr('type') !== 'hidden') {
                $field.addClass('is-invalid').attr('aria-invalid', 'true');
                var $anchor = $field.next('.select2').length ? $field.next('.select2') : $field;
                $('<div class="invalid-feedback d-block" data-guide-error>').text(text).insertAfter($anchor);
            }
        });
        $summary.trigger('focus');
    }

    function select2Common() {
        return {
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: 'Seleccionar...',
            dropdownParent: $('#frmGuide'),
            language: { noResults: function () { return 'Sin resultados para esta búsqueda.'; }, errorLoading: function () { return 'No se pudo cargar la consulta.'; } },
            ajax: { transport: lookupTransport }
        };
    }

    // =========================
    // Transport sections
    // =========================
    function toggleTransportSections() {
        var t = $('#tipo_transporte').val();

        $('#cardPublicTransport').toggle(t === '01');
        $('#cardPrivateDriver').toggle(t === '02');

        // Sections stay open; only the existing mode-specific parent is toggled.
    }

    // =========================
    // Items mode (SALE/MANUAL)
    // =========================
    function toggleItemsMode() {
        var mode = $('#items_mode').val();
        $('#saleItemsHint').toggle(mode === 'SALE');

        if (mode === 'SALE') {
            $('#boxSaleMode').show();
            $('#manualItemsBox').hide();
        } else {
            $('#boxSaleMode').hide();
            $('#saleItemsPreview').hide();
            $('#manualItemsBox').show();
        }
    }

    function clearSalePreview() {
        $('#saleMeta').hide();
        $('#saleMetaText').text('');
        $('#tbodySaleItems').html('');
        $('#saleItemsCount').text('');
        $('#saleItemsPreview').hide();
        $('#saleItemsHint').toggle($('#items_mode').val() === 'SALE');
    }

    function renderSaleItemsPreview(res) {
        $('#saleMeta').show();
        $('#saleMetaText').text(
            'Venta: ' + (res.sale && res.sale.ref ? res.sale.ref : '') +
            ' | ' + (res.sale && res.sale.tipo ? res.sale.tipo : '') +
            ' | Cliente: ' + (res.sale && res.sale.cliente ? res.sale.cliente : '-') +
            ' | Total: ' + (res.sale && res.sale.total ? parseFloat(res.sale.total).toFixed(2) : '-')
        );

        var rows = [];
        (res.items || []).forEach(function (it) {
            rows.push(
                '<tr>' +
                '<td class="text-center">' + (it.line || '') + '</td>' +
                '<td>' + $('<div>').text(it.descripcion || '').html() + '</td>' +
                '<td class="text-center">' + (parseFloat(it.cantidad).toFixed(2) || 0) + '</td>' +
                '<td class="text-center">' + $('<div>').text(it.unidad_medida || 'NIU').html() + '</td>' +
                '</tr>'
            );
        });

        $('#tbodySaleItems').html(rows.join('') || '<tr><td colspan="4" class="next-table-empty">La venta no devuelve líneas para trasladar.</td></tr>');
        $('#saleItemsHint').hide();
        $('#saleItemsCount').text((res.items || []).length + ' items');
        $('#saleItemsPreview').show();
    }

    function initSaleSelect2() {
        if (!$('#sale_id').length) return;

        $('#sale_id').select2($.extend(true, {}, select2Common(), {
            placeholder: 'Buscar venta...',
            ajax: {
                url: window.routes.salesSelect2,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) {
                    // Debe ser {results:[{id,text}], pagination:{more:boolean}}
                    return data;
                },
                cache: true
            }
        }));
    }

    function loadSaleItems() {
        var saleId = $('#sale_id').val();
        if (!saleId) {
            toastr.error('Selecciona una venta primero');
            return;
        }

        var url = window.routes.saleItemsById.replace(':id', saleId);

        $('#btnLoadSaleItems').prop('disabled', true);

        $.get(url)
            .done(function (res) {
                if ($('#items_mode').val() === 'SALE' && $('#sale_id').val() === saleId) renderSaleItemsPreview(res);
            })
            .fail(function (xhr) {
                lookupError(xhr);
                clearSalePreview();
            })
            .always(function () {
                $('#btnLoadSaleItems').prop('disabled', false);
            });
    }

    // =========================
    // Customers (select + modal)
    // =========================
    function initCustomerEvents() {

        $(document).on('click', '#btn-add-customer', function () {
            $('#formCreateCustomer')[0].reset();
            $('#modalCustomer').modal('show');
        });

        $(document).on('click', '#btn-submit-customer', function (e) {
            e.preventDefault();

            var $btn = $('#btn-submit-customer');
            $btn.prop('disabled', true);

            var form = $('#formCreateCustomer');
            var url = form.data('url');

            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize()
            }).done(function (response) {
                toastr.success(response.message || 'Cliente creado');
                $('#modalCustomer').modal('hide');

                var customer = response.customer;

                // agregar al select2 y seleccionar
                var newOption = new Option(customer.business_name, customer.id, true, true);
                $('#customer_id').append(newOption);
                $('#customer_id').val(customer.id).trigger('change.select2');

                // llenar snapshot
                fillCustomerFields({
                    RUC: customer.RUC || customer.ruc || '',
                    address: customer.address || '',
                    email: customer.email || ''
                });

                form[0].reset();
            }).fail(function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (k) {
                        toastr.error(xhr.responseJSON.errors[k][0]);
                    });
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error');
                }
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('change', '#customer_id', function () {
            var customerId = $(this).val();

            if (!customerId) {
                $('#customer_doc_number').val('');
                $('#customer_doc_type').val('6').trigger('change');
                $('input[name="customer_address"]').val('');
                $('input[name="customer_email"]').val('');
                return;
            }

            var url = window.routes.customerPayload.replace(':id', customerId);

            $.get(url)
                .done(function (res) {
                    fillCustomerFields(res);
                })
                .fail(function (xhr) {
                    lookupError(xhr);
                });
        });
    }

    // =========================
    // Ubigeo triple select2 (dep -> prov -> dist)
    // =========================
    function initUbigeoTriple(prefix) {
        var $dep = $('#' + prefix + '_department_id');
        var $prov = $('#' + prefix + '_province_id');
        var $dist = $('#' + prefix + '_ubigeo_select');
        var $hidden = $('#' + prefix + '_ubigeo');

        // init states
        $prov.prop('disabled', true);
        $dist.prop('disabled', true);

        $dep.select2($.extend(true, {}, select2Common(), {
            ajax: {
                url: window.routes.ubigeoDepartments,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) { return data; }
            }
        }));

        $prov.select2($.extend(true, {}, select2Common(), {
            ajax: {
                url: window.routes.ubigeoProvinces,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1,
                        department_id: $dep.val() || ''
                    };
                },
                processResults: function (data) { return data; }
            }
        }));

        $dist.select2($.extend(true, {}, select2Common(), {
            ajax: {
                url: window.routes.ubigeoDistricts,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1,
                        department_id: $dep.val() || '',
                        province_id: $prov.val() || ''
                    };
                },
                processResults: function (data) { return data; }
            }
        }));

        $dep.on('change', function () {
            $prov.val(null).trigger('change');
            $dist.val(null).trigger('change');
            $hidden.val('');

            $prov.prop('disabled', !$dep.val());
            $dist.prop('disabled', true);
        });

        $prov.on('change', function () {
            $dist.val(null).trigger('change');
            $hidden.val('');

            $dist.prop('disabled', !$prov.val());
        });

        $dist.on('change', function () {
            $hidden.val($dist.val() || '');
        });
    }

    // =========================
    // Submit
    // =========================
    let guideSubmissionLocked = false;

    function guideResultRedirect(code, reference) {
        const url = new URL(window.routes.index, window.location.href);
        url.searchParams.set('gre_result', code);
        if (typeof reference === 'string' && /^[a-f0-9]{24}$/.test(reference)) {
            url.searchParams.set('gre_ref', reference);
        }
        window.location.href = url.toString();
    }

    function submitGuide(event) {
        if (event) event.preventDefault();
        if (guideSubmissionLocked) return;
        guideSubmissionLocked = true;
        const $btn = $('#btnSubmitGuide');
        const originalHtml = $btn.html();
        let requestStarted = false;
        let navigating = false;
        function restoreButton() {
            guideSubmissionLocked = false;
            $btn.prop('disabled', false).attr('aria-busy', 'false').html(originalHtml);
        }
        $.confirm({
            title: 'Confirmación',
            content: '¿Crear y enviar la guía a Nubefact/SUNAT?',
            onClose: function () {
                if (!requestStarted) restoreButton();
            },
            buttons: {
                Si: {
                    btnClass: 'btn-success',
                    action: function () {
                        if (requestStarted) return;
                        requestStarted = true;
                        clearGuideValidation();
                        $btn.prop('disabled', true).attr('aria-busy', 'true')
                            .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Enviando…');
                        $.ajax({
                            url: window.routes.store,
                            method: 'POST',
                            data: $('#frmGuide').serialize()
                        }).done(function (res) {
                            navigating = true;
                            let result = 'uncertain';
                            if (res.replayed) result = 'existing';
                            else if (res.status === 'ACCEPTED') result = 'accepted';
                            else if (res.status === 'PENDING_SUNAT') result = 'pending';
                            else if (res.status === 'REJECTED') result = 'rejected';
                            $btn.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Abriendo listado…');
                            guideResultRedirect(result, res.diagnostic_reference);
                        }).fail(function (xhr) {
                            const response = xhr.responseJSON || {};
                            if (response.persisted === true || response.status === 'SENT') {
                                navigating = true;
                                guideResultRedirect(response.emission_state === 'API_ERROR' ? 'api_error' : 'uncertain', response.diagnostic_reference);
                                return;
                            }
                            if (xhr.status === 409) {
                                navigating = true; // Mantener bloqueada esta operación conflictiva.
                                $btn.attr('aria-busy', 'false').text('Operación en conflicto');
                                toastr.error(response.message || 'La operación cambió. Revisa la guía existente antes de continuar.', null, {escapeHtml: true});
                                return;
                            }
                            if (xhr.status === 422) {
                                const errors = response.errors || {};
                                renderGuideValidation(errors, response.message);
                                const messages = Object.keys(errors).map(function (key) { return errors[key][0] || ''; });
                                toastr.error(messages.join(' ') || response.message || 'Revisa los datos de la guía.', null, {escapeHtml: true});
                            } else if ([401, 403, 419].includes(xhr.status) || response.persisted === false) {
                                toastr.error(response.message || 'Revisa tu sesión y permisos antes de volver a intentar.', null, {escapeHtml: true});
                            } else {
                                // Un timeout o error sin confirmación puede haber guardado/enviado la guía.
                                navigating = true;
                                guideResultRedirect('uncertain', response.diagnostic_reference);
                            }
                        }).always(function () {
                            if (!navigating) restoreButton();
                        });
                    }
                },
                No: function () { }
            }
        });
    }

    function manualRowTemplate(idx) {
        return `
        <tr class="manualItemRow" data-idx="${idx}">
            <td class="text-center align-middle">${idx + 1}</td>

            <td>
                <select class="form-control manualMaterialSelect" aria-label="Producto" style="width:100%"></select>

                <input type="hidden" name="items[${idx}][product_id]" class="manualProductId">
                <input type="hidden" name="items[${idx}][material_id]" class="manualMaterialId">
                <input type="hidden" name="items[${idx}][codigo]" class="manualCodigo">
                <input type="hidden" name="items[${idx}][descripcion]" class="manualDescripcion">
            </td>

            <td>
                <input type="number" step="0.001" min="0.001"
                       class="form-control text-center" aria-label="Cantidad"
                       name="items[${idx}][cantidad]" value="1">
            </td>

            <td class="text-center align-middle">NIU</td>

            <td>
                <input type="text" class="form-control"
                       name="items[${idx}][detalle_adicional]"
                       placeholder="(opcional)">
            </td>

            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger btnDelManualItem" aria-label="Quitar línea" title="Quitar línea">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }

    /*function initManualMaterialSelect2($select) {
        $select.select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar material...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: window.routes.materialsSelect2,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) { return data; },
                cache: true
            }
        });

        $select.on('select2:select', function (e) {
            const row = $(this).closest('tr');
            const data = e.params.data || {};

            const materialId = data.material_id || data.id || '';
            const descripcion = data.descripcion || data.text || '';
            const codigo = data.codigo || materialId || '';

            row.find('.manualProductId').val(materialId);
            row.find('.manualCodigo').val(codigo);
            row.find('.manualDescripcion').val(descripcion);
        });

        $select.on('select2:clear', function () {
            const row = $(this).closest('tr');
            row.find('.manualProductId').val('');
            row.find('.manualCodigo').val('');
            row.find('.manualDescripcion').val('');
        });
    }*/

    function initManualMaterialSelect2($select) {
        $select.select2({
            theme: 'bootstrap4',
            language: { noResults: function () { return 'No hay productos para esta búsqueda.'; }, errorLoading: function () { return 'No se pudo cargar el catálogo.'; } },
            placeholder: 'Buscar material...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: window.routes.materialsSelect2,
                transport: lookupTransport,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) { return data; },
                cache: true
            }
        });

        $select.on('select2:select', function (e) {
            const row = $(this).closest('tr');
            const data = e.params.data || {};

            const materialId = data.material_id || data.id || '';
            const stockItemId = data.stock_item_id || '';
            const descripcion = data.descripcion || data.text || '';
            const codigo = data.codigo || materialId || '';

            row.find('.manualProductId').val(stockItemId);
            row.find('.manualMaterialId').val(materialId);
            row.find('.manualCodigo').val(codigo);
            row.find('.manualDescripcion').val(descripcion);
        });

        $select.on('select2:clear', function () {
            const row = $(this).closest('tr');
            row.find('.manualProductId').val('');
            row.find('.manualMaterialId').val('');
            row.find('.manualCodigo').val('');
            row.find('.manualDescripcion').val('');
        });
    }

    function addManualItemRow() {
        $('#manualItemsEmpty').hide();
        const idx = $('#tbodyManualItems tr.manualItemRow').length;
        $('#tbodyManualItems').append(manualRowTemplate(idx));

        const $newRow = $('#tbodyManualItems tr.manualItemRow').last();
        initManualMaterialSelect2($newRow.find('.manualMaterialSelect'));
    }

    function reindexManualRows() {
        $('#tbodyManualItems tr.manualItemRow').each(function (i) {
            $(this).attr('data-idx', i);
            $(this).find('td:first').text(i + 1);

            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/items\[\d+]/, `items[${i}]`));
            });
        });
    }

    // =========================
    // Ready
    // =========================
    $(document).ready(function () {

        csrfHeader();

        $('#frmGuide [data-guide-section]').on('show.bs.collapse hide.bs.collapse', function (event) {
            if (event.target !== this) return;
            var opening = event.type === 'show';
            $(this).closest('section').find('[data-guide-chevron]')
                .toggleClass('fa-chevron-up', opening).toggleClass('fa-chevron-down', !opening);
        });
        // Native client validation also reveals hidden sections without changing rules.
        $('#frmGuide')[0].addEventListener('invalid', function (event) {
            openValidationSections(event.target.name || '', $(event.target));
        }, true);

        toggleTransportSections();
        toggleItemsMode();
        initSaleSelect2();
        clearSalePreview();
        initCustomerEvents();

        initUbigeoTriple('partida');
        initUbigeoTriple('llegada');

        $(document).on('change', '#tipo_transporte', toggleTransportSections);

        $(document).on('change', '#items_mode', function () {
            toggleItemsMode();
            clearSalePreview();
        });

        $(document).on('change', '#sale_id', clearSalePreview);
        $(document).on('click', '#btnLoadSaleItems', loadSaleItems);
        $(document).on('click', '#btnSubmitGuide', submitGuide);

        $(document).on('click', '#btnAddManualItem', function () {
            addManualItemRow();
        });

        $(document).on('click', '.btnDelManualItem', function () {
            $(this).closest('tr').remove();
            reindexManualRows();
            $('#manualItemsEmpty').toggle(!$('#tbodyManualItems tr.manualItemRow').length);
        });
    });

})(jQuery);
