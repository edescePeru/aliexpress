$(document).ready(function () {

    $('#btn-submit').prop('disabled', true);

    // ==============================
    // Seleccionar caja EFECTIVO por defecto
    // ==============================
    function pvSelectDefaultCashBox() {
        const $cash = $('#pv_cash_box_id');

        // Buscar option con data-type="cash"
        const $cashOption = $cash.find('option').filter(function () {
            return ($(this).data('type') || '') === 'cash';
        }).first();

        if ($cashOption.length) {
            $cash.val($cashOption.val()).trigger('change');
        }
    }

    // ==============================
    // Cascada caja -> subtipo
    // ==============================
    function pvFillSubtypes(selectId, subtypes) {
        const $sel = $(selectId);
        $sel.empty().append('<option value="">Seleccione subtipo...</option>');

        (subtypes || []).forEach(s => {
            $sel.append(new Option(s.name, s.id, false, false));
        });

        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.trigger('change.select2');
        }
    }

    function pvToggleSubtypesByCashBox() {
        const $cash = $('#pv_cash_box_id');
        const $sub  = $('#pv_cash_box_subtype_id');
        const $wrap = $('#pv_cash_box_subtype_wrap');

        const $opt = $cash.find('option:selected');
        const type = ($opt.data('type') || $opt.attr('data-type') || '').toString();
        const uses = String($opt.data('uses_subtypes') || $opt.attr('data-uses_subtypes') || '0') === '1';

        if (type === 'bank' && uses) {
            if ($wrap.length) $wrap.show();
            pvFillSubtypes('#pv_cash_box_subtype_id', window.PV_SUBTYPES || []);
        } else {
            if ($wrap.length) $wrap.hide();
            pvFillSubtypes('#pv_cash_box_subtype_id', []);
            $sub.val('').trigger('change');
        }
    }

    // OJO: aquí NO rellenamos cajas por JS porque el Blade ya las imprime.
    pvToggleSubtypesByCashBox();

    pvSelectDefaultCashBox();          // 👈 AQUÍ

    $('#pv_cash_box_id').on('change', function () {
        pvToggleSubtypesByCashBox();
    });

    // ==============================
    // Buscar cotización
    // ==============================
    $('#btnBuscarCotizacion').on('click', function () {
        let codigo = $('#codigoBusqueda').val();
        let nombre = $('#nombreBusqueda').val();

        $.ajax({
            url: "/dashboard/quotes/buscar",
            method: "GET",
            data: { code: codigo, name: nombre },
            success: function (data) {
                let html = '';

                if (data.length > 0) {
                    html += '<table class="table table-sm table-bordered">';
                    html += '<thead><tr><th>Código</th><th>Descripción</th><th>Cliente</th><th>Fecha</th><th>Acción</th></tr></thead><tbody>';

                    data.forEach(function (item) {
                        html += `<tr>
                                    <td>${item.code}</td>
                                    <td>${item.description_quote}</td>
                                    <td>${item.customer_name}</td>
                                    <td>${item.date_quote_format}</td>
                                    <td>
                                        <button class="btn btn-success btn-sm btnAgregarCotizacion" data-id="${item.id}">
                                            Agregar
                                        </button>
                                    </td>
                                 </tr>`;
                    });

                    html += '</tbody></table>';
                } else {
                    html = '<div class="alert alert-warning">No se encontraron cotizaciones confirmadas.</div>';
                }

                $('#resultadosCotizacion').html(html);
            }
        });
    });

    // Evento para botón "Agregar"
    $(document).on('click', '.btnAgregarCotizacion', function () {
        let id = $(this).data('id');

        $.ajax({
            url: "/dashboard/get/data/quotes/sale/" + id,
            method: "GET",
            success: function (quote) {

                let typeComprobante = $('#typeComprobante').val();

                if (typeComprobante == 'Boleta' || typeComprobante == 'Ticket') {
                    $('#nameCliente').val(quote.customer_format);
                } else {
                    $('#rucCliente').val(quote.customer.RUC);
                    $('#razonCliente').val(quote.customer_format);
                    $('#direccionCliente').val(quote.customer.address);
                }

                $('#codeQuote').val(quote.code);
                $('#quote_id').val(quote.id);

                $('#descriptionQuote').val(quote.description_quote);
                $('#date_quote').val(quote.date_quote_format);
                $('#date_validate').val(quote.date_validate_format);
                $('#paymentQuote').val(quote.deadline_format);
                $('#timeQuote').val(quote.delivery_time);
                $('#customer_id').val(quote.customer_format);
                $('#contact_id').val(quote.contact_format);
                $('#observations').summernote('code', quote.observations);

                $('#descuento').html(parseFloat(quote.descuento).toFixed(2));
                $('#gravada').html(parseFloat(quote.gravada).toFixed(2));
                $('#igv_total').html(parseFloat(quote.igv_total).toFixed(2));
                $('#total_importe').html(parseFloat(quote.total_importe).toFixed(2));

                $('[data-bodyConsumable]').find('[data-consumable-row]').remove();
                $('[data-bodyService]').find('[data-serviceRow]').remove();

                quote.equipments.forEach(function(equipment) {

                    $('[data-detailequipment]').summernote('code', equipment.detail);

                    if (equipment.consumables && equipment.consumables.length > 0) {
                        equipment.consumables.forEach(function(consumable) {

                            let template = document.querySelector('#template-consumable');
                            let clone = template.content.cloneNode(true);

                            $(clone).find('[data-consumableDescription]').val(consumable.material.full_description);
                            $(clone).find('[data-consumableId]').val(consumable.id);
                            $(clone).find('[data-descuento]').val(consumable.discount);
                            $(clone).find('[data-type_promotion]').val(consumable.type_promo);

                            $(clone).find('[data-presentation_text]').val((consumable.material_presentation_id == null) ? 'Unidad' : consumable.units_per_pack + ' Und');

                            $(clone).find('[data-consumableUnit]').val(consumable.material.name_unit);
                            $(clone).find('[data-consumableQuantity]').val((consumable.material_presentation_id == null) ? consumable.quantity : consumable.packs);

                            $(clone).find('[data-consumableValor]').val(consumable.valor_unitario);
                            $(clone).find('[data-consumablePrice]').val(consumable.price);
                            $(clone).find('[data-consumableImporte]').val(consumable.total);

                            $('[data-bodyConsumable]').append(clone);
                        });
                    }

                    $('[data-bodyService]').find('[data-serviceRow]').remove();

                    if (equipment.workforces && equipment.workforces.length > 0) {
                        equipment.workforces.forEach(function(wf) {

                            let templateS = document.querySelector('#template-service');
                            let cloneS = templateS.content.cloneNode(true);

                            $(cloneS).find('[data-serviceDescription]').val(wf.description).prop('readonly', true);
                            $(cloneS).find('[data-serviceUnit]').val(wf.unit).prop('readonly', true);
                            $(cloneS).find('[data-serviceQuantity]').val(parseFloat(wf.quantity).toFixed(2)).prop('readonly', true);

                            let igv = 18;
                            let pu = parseFloat(wf.price || 0);
                            let vu = (pu / (1 + (igv/100)));
                            $(cloneS).find('[data-serviceVU]').val(vu.toFixed(2)).prop('readonly', true);
                            $(cloneS).find('[data-servicePU]').val(pu.toFixed(2)).prop('readonly', true);
                            $(cloneS).find('[data-serviceImporte]').val(parseFloat(wf.total || 0).toFixed(2)).prop('readonly', true);

                            let $chk = $(cloneS).find('[data-serviceBillable]');
                            $chk.prop('checked', wf.billable == 1).prop('disabled', true);

                            const uid = 'billable_view_' + wf.id;
                            $(cloneS).find('[data-billable-id]').attr('id', uid);
                            $(cloneS).find('[data-billable-label]').attr('for', uid);

                            $('[data-bodyService]').append(cloneS);
                        });
                    }
                });

                $('#modalBuscarComprobante').modal('hide');
                $('#codigoBusqueda').val('');
                $('#nombreBusqueda').val('');
                $('#resultadosCotizacion').html('');

                // bloquear descuento (solo vista)
                $('#discountSection').attr('data-discount_type', quote.discount_type || 'amount');
                $('#discountSection').attr('data-discount_input_mode', quote.discount_input_mode || 'without_igv');
                $('#discountSection').attr('data-discount_value', (quote.discount_input_value ?? 0));

                $('input[name="discount_type"]').prop('checked', false);
                if ((quote.discount_type || 'amount') === 'percent') {
                    $('#discount_type_percent').prop('checked', true);
                } else {
                    $('#discount_type_amount').prop('checked', true);
                }

                $('input[name="discount_input_mode"]').prop('checked', false);
                if ((quote.discount_input_mode || 'without_igv') === 'with_igv') {
                    $('#discount_mode_with').prop('checked', true);
                } else {
                    $('#discount_mode_without').prop('checked', true);
                }

                $('#discount_value').val(quote.discount_input_value ?? 0);
                $('input[name="discount_type"]').prop('disabled', true);
                $('input[name="discount_input_mode"]').prop('disabled', true);
                $('#discount_value').prop('readonly', true);

                $('#btn-submit').prop('disabled', false);
            }
        });
    });

    // ==============================
    // CLICK: Guardar Comprobante
    // ==============================
    $('#btn-submit').on('click', function () {
        let typeComprobante  = $('#typeComprobante').val();
        let quote_id         = $('#quote_id').val();
        let fechaDocumento   = $('#fechaDocumento').val();

        // ✅ Caja/Subtipo (en lugar de tipoPago)
        const cashBoxId = $('#pv_cash_box_id').val();
        const $opt      = $('#pv_cash_box_id').find('option:selected');
        const boxType   = ($opt.data('type') || '').toString();
        const usesSub   = String($opt.data('uses_subtypes') || '0') === '1';

        let cashBoxSubtypeId = null;
        if (boxType === 'bank' && usesSub) {
            cashBoxSubtypeId = $('#pv_cash_box_subtype_id').val();
        }

        let nombre_cliente = '';
        let numero_documento = '';
        let direccion_cliente = '';
        let email_cliente = $('#emailCliente').val();

        if (!cashBoxId || !fechaDocumento) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Por favor complete Caja y Fecha',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        if (boxType === 'bank' && usesSub && !cashBoxSubtypeId) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Seleccione el canal/subtipo (Yape/Plin/POS/Transfer).',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        if (typeComprobante === 'Boleta' || typeComprobante === 'Ticket') {
            nombre_cliente = $('#nameCliente').val();
            numero_documento = $('#dniCliente').val();
            if (!nombre_cliente || !numero_documento ) {
                $.alert({
                    title: 'Campos incompletos',
                    content: 'Por favor complete Nombre y DNI',
                    type: 'red',
                    buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
                });
                return;
            }
        }

        if (typeComprobante === 'Factura') {
            numero_documento = $('#rucCliente').val();
            nombre_cliente = $('#razonCliente').val();
            direccion_cliente = $('#direccionCliente').val();

            if (!numero_documento || !nombre_cliente || !direccion_cliente) {
                $.alert({
                    title: 'Campos incompletos',
                    content: 'Por favor complete RUC, Razón Social y Dirección Fiscal',
                    type: 'red',
                    buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
                });
                return;
            }
        }

        let type_document = null;
        if (typeComprobante === 'Factura') type_document = '01';
        else if (typeComprobante === 'Boleta') type_document = '03';

        // -------- Construcción del payload --------
        let payload = {
            quote_id: quote_id,
            type_document: type_document,
            nombre_cliente: nombre_cliente,
            numero_documento_cliente: numero_documento,
            direccion_cliente: direccion_cliente,
            email_cliente: email_cliente,
            tipo_documento_cliente: (typeComprobante === 'Factura' ? '6' : '1'),
            fechaDocumento: fechaDocumento,

            pv_cash_box_id: cashBoxId,
            pv_cash_box_subtype_id: cashBoxSubtypeId,

            detalles: []
        };

        // Recolectamos detalles de los productos
        $('[data-bodyConsumable] [data-consumableId]').each(function () {
            let row = $(this).closest('.row');

            payload.detalles.push({
                material_id: $(this).val(),
                price: row.find('[data-consumablePrice]').val(),
                quantity: row.find('[data-consumableQuantity]').val(),
                percentage_tax: 18,
                total: row.find('[data-consumableImporte]').val(),
                discount: row.find('[data-descuento]').val()
            });
        });

        // Confirmación
        $.confirm({
            title: 'Confirmar acción',
            content: '¿Está seguro de generar el ' + typeComprobante + '?',
            type: 'green',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-success',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();
                        jc.setContent(`
                          <div style="display:flex;align-items:center;gap:10px">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Generando ${typeComprobante}…</span>
                          </div>
                        `);

                        $.ajax({
                            url: '/dashboard/store/sale/from/quote',
                            method: 'POST',
                            data: payload,
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: function (res) {
                                jc.close();

                                const urlPrint  = res.url_print || null;
                                const printType = res.print_type || null;

                                $.alert({
                                    title: 'Éxito',
                                    content: `
                                        ${typeComprobante} generada correctamente.<br>
                                        ${urlPrint ? '<small>Listo para visualizar.</small>' : '<small>Venta creada, pero no se recibió URL de impresión.</small>'}
                                      `,
                                    type: 'green',
                                    buttons: (function(){
                                        if (!urlPrint) {
                                            return {
                                                ok: {
                                                    text: 'OK',
                                                    btnClass: 'btn-success',
                                                    action: function () { location.reload(); }
                                                }
                                            };
                                        }

                                        return {
                                            ver: {
                                                text: (printType === 'sunat_pdf') ? 'Ver PDF' : 'Ver Ticket',
                                                btnClass: 'btn-primary',
                                                action: function () {
                                                    window.open(urlPrint, '_blank');
                                                    location.reload();
                                                }
                                            },
                                            ok: {
                                                text: 'Cerrar',
                                                btnClass: 'btn-secondary',
                                                action: function () { location.reload(); }
                                            }
                                        };
                                    })()
                                });
                            },
                            error: function (err) {
                                console.error(err);

                                jc.setContent('Ocurrió un error al guardar el comprobante.');
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error',
                                    content: err.responseJSON?.message || 'Ocurrió un error al guardar el comprobante',
                                    type: 'red',
                                    buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: { text: 'Cancelar', btnClass: 'btn-secondary' }
            }
        });
    });

});
