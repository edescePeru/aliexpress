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

    function select2Common() {
        return {
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: 'Seleccionar...',
            dropdownParent: $('#frmGuide') // clave para accordion/cards
        };
    }

    // =========================
    // Transport sections
    // =========================
    function toggleTransportSections() {
        var t = $('#tipo_transporte').val();

        $('#cardPublicTransport').toggle(t === '01');
        $('#cardPrivateDriver').toggle(t === '02');

        if (t === '01' && $('#secDriver').length) $('#secDriver').collapse('hide');
        if (t === '02' && $('#secTransportista').length) $('#secTransportista').collapse('hide');
    }

    // =========================
    // Items mode (SALE/MANUAL)
    // =========================
    function toggleItemsMode() {
        var mode = $('#items_mode').val();

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
    }

    function renderSaleItemsPreview(res) {
        $('#saleMeta').show();
        $('#saleMetaText').text(
            'Venta: ' + (res.sale && res.sale.ref ? res.sale.ref : '') +
            ' | ' + (res.sale && res.sale.tipo ? res.sale.tipo : '') +
            ' | Cliente: ' + (res.sale && res.sale.cliente ? res.sale.cliente : '-') +
            ' | Total: ' + (res.sale && res.sale.total ? res.sale.total : '-')
        );

        var rows = [];
        (res.items || []).forEach(function (it) {
            rows.push(
                '<tr>' +
                '<td>' + (it.line || '') + '</td>' +
                '<td>' + (it.descripcion || '') + '</td>' +
                '<td class="text-right">' + (it.cantidad || 0) + '</td>' +
                '<td>' + (it.unidad_medida || 'NIU') + '</td>' +
                '</tr>'
            );
        });

        $('#tbodySaleItems').html(rows.join(''));
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
                renderSaleItemsPreview(res);
            })
            .fail(function (xhr) {
                toastr.error('Error cargando items de la venta');
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
                .fail(function () {
                    toastr.error('No se pudo cargar datos del cliente');
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
    function submitGuide() {
        $.confirm({
            title: 'Confirmación',
            content: '¿Crear y enviar la guía a Nubefact/SUNAT?',
            buttons: {
                Si: {
                    btnClass: 'btn-success',
                    action: function () {
                        var $btn = $('#btnSubmitGuide');
                        $btn.prop('disabled', true);

                        $.ajax({
                            url: window.routes.store,
                            method: 'POST',
                            data: $('#frmGuide').serialize()
                        }).done(function (res) {
                            toastr.success(res.message || 'Guía creada');
                            window.location.href = window.routes.index;
                        }).fail(function (xhr) {
                            if (xhr.status === 422) {
                                var errs = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                                var msg = '';
                                Object.keys(errs).forEach(function (k) {
                                    msg += (errs[k][0] || '') + '<br>';
                                });
                                toastr.error(msg || 'Validación fallida');
                            } else {
                                var res = xhr.responseJSON || {};
                                var msg2 = res.detail || res.message || 'Error inesperado';

                                toastr.error(msg2);
                            }
                        }).always(function () {
                            $btn.prop('disabled', false);
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
                <select class="form-control manualMaterialSelect" style="width:100%"></select>

                <input type="hidden" name="items[${idx}][product_id]" class="manualProductId">
                <input type="hidden" name="items[${idx}][codigo]" class="manualCodigo">
                <input type="hidden" name="items[${idx}][descripcion]" class="manualDescripcion">
            </td>

            <td>
                <input type="number" step="0.001" min="0.001"
                       class="form-control"
                       name="items[${idx}][cantidad]" value="1">
            </td>

            <td class="text-center align-middle">NIU</td>

            <td>
                <input type="text" class="form-control"
                       name="items[${idx}][detalle_adicional]"
                       placeholder="(opcional)">
            </td>

            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger btnDelManualItem">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }

    function initManualMaterialSelect2($select) {
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
    }

    function initManualMaterialSelect2($select) {
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
    }

    function addManualItemRow() {
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

        $(document).on('click', '#btnLoadSaleItems', loadSaleItems);
        $(document).on('click', '#btnSubmitGuide', submitGuide);

        $(document).on('click', '#btnAddManualItem', function () {
            addManualItemRow();
        });

        $(document).on('click', '.btnDelManualItem', function () {
            $(this).closest('tr').remove();
            reindexManualRows();
        });
    });

})(jQuery);
