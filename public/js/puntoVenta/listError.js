$(document).ready(function () {
    //$permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataOrders(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $(document).on('click', '[data-ver_detalles]', showDetails);

    $(document).on('click', '[data-anular]', anularOrder);

    /*$(document).on('click', '[data-generar_comprobante]', function () {
        const orderId = $(this).data('order-id');

        $.confirm({
            title: 'Confirmación',
            content: '¿Deseas generar el comprobante?',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    action: function () {
                        $.ajax({
                            url: `/factura/generar/${orderId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.error) {
                                    $.alert('Error: ' + response.error);
                                } else {
                                    $.alert('Comprobante generado con éxito. Ticket: ' + response.ticket);
                                }
                            },
                            error: function (xhr) {
                                $.alert('Error al procesar la solicitud. Intenta nuevamente.');
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    action: function () {}
                }
            }
        });
    });

    $(document).on('click', '[data-imprimir_comprobante]', function () {
        const orderId = $(this).data('order-id');
        window.open(`/factura/imprimir/${orderId}`, '_blank');
    });*/

    $(document).on('click', '[data-facturador]', function () {
        const btn = $(this);
        if (!$('#downloadSection').hasClass('d-none')) {
            $('#downloadSection').addClass('d-none');
        }
        // Asignar ID
        $('#order_id').val(btn.data('id'));

        const tipo = btn.data('tipo-comprobante');
        const docTipo = btn.data('tipo-documento-cliente');
        const numero = btn.data('numero-documento-cliente');
        const direccion = btn.data('direccion-cliente');
        const email = btn.data('email-cliente');
        const nombre = btn.data('nombre-cliente');

        // Limpiar campos anteriores
        $('#formFacturador')[0].reset();
        $('#datos_boleta, #datos_factura').addClass('d-none');

        if (tipo === 'boleta') {
            $('#radio_boleta').prop('checked', true);
            $('#datos_boleta').removeClass('d-none');

            $('#dni').val(numero);
            $('#email_invoice_boleta').val(email);
            $('#name').val(nombre);
        } else if (tipo === 'factura') {
            $('#radio_factura').prop('checked', true);
            $('#datos_factura').removeClass('d-none');

            $('#ruc').val(numero);
            $('#razon_social').val(nombre);
            $('#direccion_fiscal').val(direccion);
            $('#email_invoice_factura').val(email);
        }

        $('#modalFacturador').modal('show');
    });

    $('input[name="invoice_type"]').on('change', function () {
        const selected = $(this).val();
        if (selected === 'boleta') {
            $('#datos_boleta').removeClass('d-none');
            $('#datos_factura').addClass('d-none');
        } else {
            $('#datos_factura').removeClass('d-none');
            $('#datos_boleta').addClass('d-none');
        }
    });

    $('#btnGuardarDatos').on('click', function () {
        const tipoComprobante = $('input[name="invoice_type"]:checked').val();
        const orderId = $('#order_id').val();

        let data = {
            order_id: orderId,
            type_document: tipoComprobante,
        };

        if (tipoComprobante === 'boleta') {
            data.dni = $('#dni').val();
            data.email = $('#email_invoice_boleta').val();
            data.name = $('#name').val();
        } else if (tipoComprobante === 'factura') {
            data.ruc = $('#ruc').val();
            data.razon_social = $('#razon_social').val();
            data.direccion_fiscal = $('#direccion_fiscal').val();
            data.email = $('#email_invoice_factura').val();
        }

        $.ajax({
            url: '/dashboard/sales/update-invoice-data',
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $.alert({
                    title: 'Éxito',
                    content: response.message,
                    type: 'green'
                });
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'Ocurrió un error',
                    type: 'red'
                });
            }
        });
    });

    $('#btnGenerarComprobante').on('click', function () {
        const orderId = $('#order_id').val();

        $.ajax({
            url: '/dashboard/facturador/generar', // Puedes cambiar el nombre si quieres
            type: 'POST',
            data: {
                order_id: orderId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('#btnGenerarComprobante').prop('disabled', true).text('Generando...');
            },
            success: function (response) {
                $.alert({
                    title: 'Comprobante generado',
                    content: response.message,
                    type: 'green'
                });
                if (response.pdf_url) {
                    $('#btnDescargarPDF').attr('href', response.pdf_url);
                    $('#downloadSection').removeClass('d-none');
                }
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'No se pudo generar el comprobante',
                    type: 'red'
                });
            },
            complete: function () {
                $('#btnGenerarComprobante').prop('disabled', false).text('Generar comprobante');
            }
        });
    });

    $(document).on('click', '[data-pagos_parciales]', function () {
        let saleId = $(this).data('sale_id');

        $('#pp_sale_id').val(saleId);
        $('#pp_fecha_pago').val(new Date().toISOString().slice(0, 10));
        $('#pp_monto').val('');
        let efectivoOption = $('#pp_cash_box_id option').filter(function () {
            return $(this).text().trim().toLowerCase() === 'efectivo';
        }).first();

        if (efectivoOption.length) {
            $('#pp_cash_box_id').val(efectivoOption.val()).trigger('change');
        } else {
            $('#pp_cash_box_id').val('').trigger('change');
        }
        $('#pp_cash_box_subtype_id').val('').trigger('change');
        $('#pp_cash_box_subtype_wrap').hide();

        cargarPagosParciales(saleId);

        $('#modalPagosParciales').modal('show');
    });

    $('#pp_cash_box_id').on('change', function () {
        let $opt = $(this).find('option:selected');
        let boxType = ($opt.data('type') || '').toString();
        let usesSub = String($opt.data('uses_subtypes')) === '1';

        $('#pp_cash_box_subtype_id').val('').trigger('change');

        if (boxType === 'bank' && usesSub) {
            $('#pp_cash_box_subtype_wrap').show();
        } else {
            $('#pp_cash_box_subtype_wrap').hide();
        }
    });

    $('#btnGuardarPagoParcial').on('click', function () {
        let saleId = $('#pp_sale_id').val();
        let fecha = $('#pp_fecha_pago').val();
        let monto = $('#pp_monto').val();
        let cashBoxId = $('#pp_cash_box_id').val();

        const $opt = $('#pp_cash_box_id').find('option:selected');
        const boxType = ($opt.data('type') || '').toString();
        const usesSub = String($opt.data('uses_subtypes') || '0') === '1';

        let cashBoxSubtypeId = null;

        if (boxType === 'bank' && usesSub) {
            cashBoxSubtypeId = $('#pp_cash_box_subtype_id').val();
        }

        if (!saleId || !fecha || !monto || parseFloat(monto) <= 0 || !cashBoxId) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Complete fecha, monto y caja.',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        if (boxType === 'bank' && usesSub && !cashBoxSubtypeId) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Seleccione el canal/subtipo.',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        let cashBoxText = $opt.text().trim();
        let subtypeText = '';

        if (cashBoxSubtypeId) {
            subtypeText = $('#pp_cash_box_subtype_id option:selected').text().trim();
        }

        let metodoPago = subtypeText ? cashBoxText + ' - ' + subtypeText : cashBoxText;

        $.confirm({
            title: 'Confirmar pago parcial',
            content: `
            <div>
                <p>¿Está seguro de registrar este pago?</p>
                <ul>
                    <li><b>Fecha:</b> ${fecha}</li>
                    <li><b>Monto:</b> S/ ${parseFloat(monto).toFixed(2)}</li>
                    <li><b>Caja:</b> ${metodoPago}</li>
                </ul>
            </div>
        `,
            type: 'blue',
            buttons: {
                confirmar: {
                    text: 'Sí, registrar',
                    btnClass: 'btn-primary',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        $.ajax({
                            url: '/dashboard/sale/partial-payment/store',
                            method: 'POST',
                            data: {
                                sale_id: saleId,
                                payment_date: fecha,
                                amount: monto,
                                cash_box_id: cashBoxId,
                                cash_box_subtype_id: cashBoxSubtypeId
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Éxito',
                                    content: res.message || 'Pago registrado con éxito.',
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-success',
                                            action: function () {
                                                resetFormularioPagoParcial();
                                                cargarPagosParciales(saleId);
                                                reloadCurrentPageOrders();
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error',
                                    content: err.responseJSON?.message || 'No se pudo registrar el pago.',
                                    type: 'red',
                                    buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    $(document).on('click', '[data-eliminar_pago_parcial]', function () {
        let paymentId = $(this).data('id');
        let saleId = $('#pp_sale_id').val();

        $.confirm({
            title: 'Eliminar pago parcial',
            content: '¿Está seguro de eliminar este pago? Se generará la reversión del movimiento de caja.',
            type: 'red',
            buttons: {
                confirmar: {
                    text: 'Sí, eliminar',
                    btnClass: 'btn-danger',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        $.ajax({
                            url: '/dashboard/sale/partial-payment/' + paymentId,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Eliminado',
                                    content: res.message || 'Pago parcial eliminado correctamente.',
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-success',
                                            action: function () {
                                                resetFormularioPagoParcial();
                                                cargarPagosParciales(saleId);
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error',
                                    content: err.responseJSON?.message || 'No se pudo eliminar el pago.',
                                    type: 'red',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-danger'
                                        }
                                    }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    $(document).on('click', '[data-generar_comprobante]', function () {
        let saleId = $(this).data('sale_id');

        limpiarModalGenerarComprobante();

        $('#gc_sale_id').val(saleId);
        $('#modalGenerarComprobante').modal('show');
    });

    $('a[data-toggle="tab"][href="#gc_boleta"], a[data-toggle="tab"][href="#gc_factura"]').on('shown.bs.tab', function () {
        $('#gc_dni').val('');
        $('#gc_name').val('').prop('readonly', true);
        $('#gc_email_boleta').val('');

        $('#gc_ruc').val('');
        $('#gc_razon_social').val('').prop('readonly', true);
        $('#gc_direccion_fiscal').val('').prop('readonly', true);
        $('#gc_email_factura').val('');
    });

    $('#gc_dni').on('keydown', function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();

        let dni = $(this).val().trim();

        if (!/^\d{8}$/.test(dni)) {
            toastr.warning('Ingrese un DNI válido de 8 dígitos.');
            return;
        }

        consultarClienteComprobante(dni, 'dni');
    });

    $('#gc_ruc').on('keydown', function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();

        let ruc = $(this).val().trim();

        if (!/^\d{11}$/.test(ruc)) {
            toastr.warning('Ingrese un RUC válido de 11 dígitos.');
            return;
        }

        consultarClienteComprobante(ruc, 'ruc');
    });

    $('#btnConfirmarGenerarComprobante').on('click', function () {
        let saleId = $('#gc_sale_id').val();

        let activeTab = $('#gc_tabs .nav-link.active').attr('href');
        let tipoComprobante = activeTab === '#gc_factura' ? 'factura' : 'boleta';

        let payload = {
            sale_id: saleId,
            tipo_comprobante: tipoComprobante,
            dni: $('#gc_dni').val(),
            name: $('#gc_name').val(),
            email_boleta: $('#gc_email_boleta').val(),

            ruc: $('#gc_ruc').val(),
            razon_social: $('#gc_razon_social').val(),
            direccion_fiscal: $('#gc_direccion_fiscal').val(),
            email_factura: $('#gc_email_factura').val()
        };

        $.confirm({
            title: 'Confirmar comprobante',
            content: '¿Está seguro de generar el comprobante electrónico?',
            type: 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-warning',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        jc.setContent(`
                        <div style="display:flex;align-items:center;gap:10px">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Generando comprobante electrónico...</span>
                        </div>
                    `);

                        $.ajax({
                            url: '/dashboard/sale/generate-invoice',
                            method: 'POST',
                            data: payload,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Éxito',
                                    content: res.message || 'Comprobante generado correctamente.',
                                    type: 'green',
                                    buttons: {
                                        ver: {
                                            text: 'Ver PDF',
                                            btnClass: 'btn-primary',
                                            action: function () {
                                                /*if (res.url_print) {
                                                    window.open(res.url_print, '_blank');
                                                }

                                                $('#modalGenerarComprobante').modal('hide');

                                                if (typeof getData === 'function') {
                                                    getData();
                                                } else {
                                                    location.reload();
                                                }*/
                                                finalizarGeneracionComprobante(res, true);
                                            }
                                        },
                                        cerrar: {
                                            text: 'Cerrar',
                                            btnClass: 'btn-secondary',
                                            action: function () {
                                                /*$('#modalGenerarComprobante').modal('hide');

                                                if (typeof getData === 'function') {
                                                    getData();
                                                } else {
                                                    location.reload();
                                                }*/
                                                finalizarGeneracionComprobante(res, false);
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error al generar comprobante',
                                    content: err.responseJSON?.message || 'No se pudo generar el comprobante.',
                                    type: 'red',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-danger'
                                        }
                                    }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    $(document).on('switchChange.bootstrapSwitch', '.switch-dispatch-status', function (event, state) {
        let $switch = $(this);
        let saleId = $switch.data('sale_id');
        let nuevoEstado = state ? 'despachado' : 'pendiente';

        $.confirm({
            title: 'Confirmar cambio',
            content: '¿Desea cambiar el estado de despacho a <strong>' + nuevoEstado.toUpperCase() + '</strong>?',
            type: state ? 'green' : 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, cambiar',
                    btnClass: state ? 'btn-success' : 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/sale/update-dispatch-status',
                            method: 'POST',
                            data: {
                                sale_id: saleId,
                                dispatch_status: nuevoEstado
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                toastr.success(res.message || 'Estado actualizado.');
                            },
                            error: function (err) {
                                toastr.error(err.responseJSON?.message || 'No se pudo actualizar.');

                                $switch.bootstrapSwitch('state', !state, true);
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary',
                    action: function () {
                        $switch.bootstrapSwitch('state', !state, true);
                    }
                }
            }
        });
    });

    $('#filter_cash_box_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const boxType = ($opt.data('type') || '').toString();
        const usesSubtypes = String($opt.data('uses_subtypes')) === '1';

        $('#filter_cash_box_subtype_id').val('').trigger('change');

        if (boxType === 'bank' && usesSubtypes) {
            $('#filter_cash_box_subtype_wrap').show();
        } else {
            $('#filter_cash_box_subtype_wrap').hide();
        }
    });

    $(document).on('click', '[data-consultar_anulacion]', function () {
        let saleId = $(this).data('sale_id');

        $.ajax({
            url: '/dashboard/consultar/anulacion/' + saleId,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            processData: false,
            contentType: false,
            success: function (data) {
                $.alert({
                    title: 'Consulta realizada',
                    content: data.message,
                    type: 'green',
                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-success'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            error: function (xhr) {
                let message = "Sucedió un error al consultar la anulación.";

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $.alert({
                    title: 'Aviso',
                    content: message,
                    type: 'orange',
                    buttons: {
                        ok: {
                            text: 'Entendido',
                            btnClass: 'btn-warning'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
        });
    });

    $(document).on('click', '[data-generar_nota_credito]', function () {
        let saleId = $(this).data('sale_id');

        $.confirm({
            title: 'Generar Nota de Crédito',
            content: 'Se generará una Nota de Crédito total por anulación de la operación. ¿Desea continuar?',
            type: 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/generar/nota-credito/total/' + saleId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                $.alert({
                                    title: 'Correcto',
                                    content: data.message,
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-success'
                                        }
                                    }
                                });

                                reloadCurrentPageOrders();
                            },
                            error: function (xhr) {
                                let message = 'No se pudo generar la Nota de Crédito.';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                $.alert({
                                    title: 'Aviso',
                                    content: message,
                                    type: 'orange',
                                    buttons: {
                                        ok: {
                                            text: 'Entendido',
                                            btnClass: 'btn-warning'
                                        }
                                    }
                                });

                                reloadCurrentPageOrders();
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar'
                }
            }
        });
    });

    $(document).on('click', '[data-consultar_nota_credito]', function () {
        let saleId = $(this).data('sale_id');

        $.ajax({
            url: '/dashboard/consultar/nota-credito/' + saleId,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            processData: false,
            contentType: false,
            success: function (data) {
                $.alert({
                    title: 'Consulta realizada',
                    content: data.message,
                    type: 'green',
                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-success'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            error: function (xhr) {
                let message = 'No se pudo consultar la Nota de Crédito.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $.alert({
                    title: 'Aviso',
                    content: message,
                    type: 'orange',
                    buttons: {
                        ok: {
                            text: 'Entendido',
                            btnClass: 'btn-warning'
                        }
                    }
                });

                reloadCurrentPageOrders();
            }
        });
    });

    $(document).on('click', '[data-generar_nota_credito_parcial]', function () {
        let saleId = $(this).data('sale_id');

        $.get('/dashboard/nota-credito/parcial/data/' + saleId, function (data) {
            $('#nc_partial_sale_id').val(data.sale_id);
            $('#nc_partial_sale_code').text(data.code);
            $('#nc_partial_customer').text(data.cliente);
            $('#nc_partial_document').text((data.type_document === '01' ? 'Factura' : 'Boleta') + ' ' + data.serie_sunat + '-' + data.numero);

            $('#body-nc-partial-items').empty();

            data.items.forEach(function (item) {
                $('#body-nc-partial-items').append(`
                <tr>
                    <td>${item.description}</td>
                    <td>${item.sold_quantity}</td>
                    <td>${item.credited_quantity}</td>
                    <td>${item.available_quantity}</td>
                    <td>
                        <input type="number"
                               class="form-control form-control-sm nc-partial-qty"
                               data-sale_detail_id="${item.sale_detail_id}"
                               data-price="${item.price}"
                               data-max="${item.available_quantity}"
                               min="0"
                               max="${item.available_quantity}"
                               step="0.01"
                               value="0">
                    </td>
                    <td>S/ <span class="nc-partial-line-total">0.00</span></td>
                </tr>
            `);
            });

            $('#nc_partial_total').text('0.00');
            $('#modalNotaCreditoParcial').modal('show');

        }).fail(function (xhr) {
            let message = 'No se pudo obtener la información de la venta.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            $.alert({
                title: 'Aviso',
                content: message,
                type: 'orange',
            });
        });
    });

    $(document).on('input', '.nc-partial-qty', function () {
        let input = $(this);
        let qty = parseFloat(input.val()) || 0;
        let max = parseFloat(input.data('max')) || 0;
        let price = parseFloat(input.data('price')) || 0;

        if (qty > max) {
            qty = max;
            input.val(max);
        }

        if (qty < 0) {
            qty = 0;
            input.val(0);
        }

        let totalLine = qty * price;

        input.closest('tr').find('.nc-partial-line-total').text(totalLine.toFixed(2));

        let total = 0;

        $('.nc-partial-qty').each(function () {
            let q = parseFloat($(this).val()) || 0;
            let p = parseFloat($(this).data('price')) || 0;
            total += q * p;
        });

        $('#nc_partial_total').text(total.toFixed(2));
    });

    $(document).on('click', '#btn-submit-nc-partial', function () {
        let saleId = $('#nc_partial_sale_id').val();

        let items = [];

        $('.nc-partial-qty').each(function () {
            let qty = parseFloat($(this).val()) || 0;

            if (qty > 0) {
                items.push({
                    sale_detail_id: $(this).data('sale_detail_id'),
                    quantity: qty
                });
            }
        });

        if (items.length === 0) {
            toastr.error('Debe ingresar al menos una cantidad a devolver.', 'Error');
            return;
        }

        $.confirm({
            title: 'Confirmar Nota de Crédito Parcial',
            content: 'Se generará una Nota de Crédito parcial por los productos seleccionados. ¿Desea continuar?',
            type: 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/generar/nota-credito/parcial/' + saleId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: JSON.stringify({
                                reason_code: '07',
                                reason_description: 'Devolución parcial',
                                items: items
                            }),
                            contentType: 'application/json',
                            success: function (data) {
                                $('#modalNotaCreditoParcial').modal('hide');

                                $.alert({
                                    title: 'Correcto',
                                    content: data.message,
                                    type: 'green'
                                });

                                getDataOrders(1);
                            },
                            error: function (xhr) {
                                let message = 'No se pudo generar la Nota de Crédito parcial.';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                $.alert({
                                    title: 'Aviso',
                                    content: message,
                                    type: 'orange'
                                });
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar'
                }
            }
        });
    });

    $(document).on('click', '[data-ver_error_sunat]', function () {
        const message = $(this).data('message') || 'Error no especificado.';

        $.alert({
            title: 'Error SUNAT / Nubefact',
            content: escapeHtml(message),
            type: 'red',
            columnClass: 'medium',
            buttons: {
                ok: {
                    text: 'Cerrar',
                    btnClass: 'btn-danger'
                }
            }
        });
    });

    $(document).on('click', '[data-descartar_error_comprobante]', function () {
        let saleId = $(this).data('sale_id');

        $.confirm({
            title: 'Descartar comprobante con error',
            content: `
            <p>
                Esta acción quitará este comprobante de la bandeja de errores.
                No cambiará el estado SUNAT original.
            </p>

            <div class="form-group">
                <label>Motivo:</label>
                <select id="discard_reason_select" class="form-control form-control-sm">
                    <option value="Generado manualmente en Nubefact">Generado manualmente en Nubefact</option>
                    <option value="Generado manualmente en SUNAT">Generado manualmente en SUNAT</option>
                    <option value="No corresponde emitir comprobante">No corresponde emitir comprobante</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div class="form-group">
                <label>Comentario adicional:</label>
                <textarea id="discard_reason_text"
                          class="form-control form-control-sm"
                          rows="3"
                          placeholder="Opcional"></textarea>
            </div>
        `,
            type: 'red',
            columnClass: 'medium',
            buttons: {
                confirmar: {
                    text: 'Sí, descartar',
                    btnClass: 'btn-danger',
                    action: function () {
                        let reasonSelect = $('#discard_reason_select').val();
                        let reasonText = $('#discard_reason_text').val();

                        let reason = reasonSelect;

                        if (reasonText && reasonText.trim() !== '') {
                            reason += ' - ' + reasonText.trim();
                        }

                        $.ajax({
                            url: '/dashboard/sales/errors/' + saleId + '/discard',
                            method: 'POST',
                            data: {
                                reason: reason
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (data) {
                                $.alert({
                                    title: 'Correcto',
                                    content: data.message,
                                    type: 'green'
                                });

                                reloadCurrentPageOrders();
                            },
                            error: function (xhr) {
                                let message = 'No se pudo descartar el error.';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                $.alert({
                                    title: 'Aviso',
                                    content: message,
                                    type: 'orange'
                                });
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar'
                }
            }
        });
    });
});

var $formDelete;
var $modalDelete;

var $permissions;

function consultarClienteComprobante(documento, tipo) {
    $.ajax({
        url: `/dashboard/customer/decolecta/${documento}`,
        type: 'GET',
        dataType: 'json',
        beforeSend: function () {
            if (tipo === 'dni') {
                $('#gc_name').val('Consultando...');
            }

            if (tipo === 'ruc') {
                $('#gc_razon_social').val('Consultando...');
                $('#gc_direccion_fiscal').val('Consultando...');
            }
        },
        success: function (response) {
            if (!response.success) {
                toastr.error(response.message || 'No se pudo consultar el documento.', 'Error');
                return;
            }

            let customer = response.customer;

            if (tipo === 'dni') {
                $('#gc_dni').val(customer.RUC);
                $('#gc_name').val(customer.business_name || '');
            }

            if (tipo === 'ruc') {
                $('#gc_ruc').val(customer.RUC);
                $('#gc_razon_social').val(customer.business_name || '');
                $('#gc_direccion_fiscal').val(customer.address || '');
            }

            toastr.success('Documento consultado correctamente.', 'Correcto');
        },
        error: function (xhr) {
            let message = xhr.responseJSON?.message || 'No se encontró información.';

            toastr.warning(message + ' Puede ingresarlo manualmente.', 'Consulta sin resultado');

            if (tipo === 'dni') {
                $('#gc_name').val('').prop('readonly', false).focus();
            }

            if (tipo === 'ruc') {
                $('#gc_razon_social').val('').prop('readonly', false).focus();
                $('#gc_direccion_fiscal').val('').prop('readonly', false);
            }
        }
    });
}

function limpiarModalGenerarComprobante() {
    $('#gc_sale_id').val('');

    $('#gc_dni').val('');
    $('#gc_name').val('').prop('readonly', true);
    $('#gc_email_boleta').val('');

    $('#gc_ruc').val('');
    $('#gc_razon_social').val('').prop('readonly', true);
    $('#gc_direccion_fiscal').val('').prop('readonly', true);
    $('#gc_email_factura').val('');

    $('#gc_boleta_tab').tab('show');
}

function resetFormularioPagoParcial() {
    $('#pp_fecha_pago').val(new Date().toISOString().slice(0, 10));
    $('#pp_monto').val('');

    let efectivoOption = $('#pp_cash_box_id option').filter(function () {
        return $(this).text().trim().toLowerCase() === 'efectivo';
    }).first();

    if (efectivoOption.length) {
        $('#pp_cash_box_id').val(efectivoOption.val()).trigger('change');
    } else {
        $('#pp_cash_box_id').val('').trigger('change');
    }

    $('#pp_cash_box_subtype_id').val('').trigger('change');
    $('#pp_cash_box_subtype_wrap').hide();
}

function pintarResumenPagos(totalVenta, totalAbonado) {
    totalVenta = parseFloat(totalVenta || 0);
    totalAbonado = parseFloat(totalAbonado || 0);

    let porcentaje = totalVenta > 0 ? (totalAbonado / totalVenta) * 100 : 0;

    let normalWidth = Math.min(porcentaje, 100);
    let overWidth = porcentaje > 100 ? Math.min(porcentaje - 100, 20) : 0;

    $('#pp_total_venta').val(totalVenta.toFixed(2));
    $('#pp_total_abonado').val(totalAbonado.toFixed(2));

    $('#pp_progress_bar').css('width', normalWidth + '%');

    $('#pp_progress_over').css({
        'left': '100%',
        'width': overWidth + '%'
    });

    $('#pp_progress_text').text(porcentaje.toFixed(2) + '%');
}

function renderPagosParciales(pagos) {
    let html = '';

    if (!pagos || pagos.length === 0) {
        html = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No se encontraron pagos registrados
                </td>
            </tr>
        `;

        $('#pp_body_pagos').html(html);
        return;
    }

    pagos.forEach(function (pago) {
        html += `
            <tr>
                <td>${pago.payment_date}</td>
                <td>${parseFloat(pago.amount).toFixed(2)}</td>
                <td>${pago.cash_box_label || '-'}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-sm" data-eliminar_pago_parcial data-id="${pago.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    $('#pp_body_pagos').html(html);
}

function cargarPagosParciales(saleId) {
    $.get('/dashboard/sale/partial-payment/' + saleId, function (res) {
        pintarResumenPagos(res.total_venta, res.total_abonado);
        renderPagosParciales(res.pagos);
    });
}

function anularOrder() {
    var order_id = $(this).data('id');

    $.confirm({
        icon: 'fas fa-trash-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de anular esta order?',
        content: 'ORDEN - '+order_id,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/anular/order/'+order_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                reloadCurrentPageOrders()
                            }, 2000 )
                        },
                        error: function (xhr) {
                            console.log(xhr);

                            let message = "Sucedió un error en el servidor. Intente nuevamente.";

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                message = xhr.responseText;
                            }

                            $.alert({
                                title: 'Aviso',
                                content: message,
                                type: 'orange',
                                buttons: {
                                    ok: {
                                        text: 'Entendido',
                                        btnClass: 'btn-warning'
                                    }
                                }
                            });

                            reloadCurrentPageOrders()
                        }
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Cambio de estado cancelado.");
                },
            },
        },
    });

}

function printOrder() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/print/order/${orderId}`,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        processData:false,
        contentType:false,
        success: function (response) {
            if (response.error) {
                // Generar dinámicamente el contenido del modal
                toastr.success(response.message, 'Éxito', {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                });

            } else {
                toastr.error(response.message, 'Error', {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                });
            }
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error(xhr.responseText, 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
    });
}

function showDetails() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/dashboard/sales/${orderId}/details`,
        method: 'GET',
        success: function (response) {
            if (response.details) {
                // Generar dinámicamente el contenido del modal
                let content = '';
                response.details.forEach((detail, index) => {
                    content += `
                        <div class="mb-4">
                            <h6><strong>Producto:</strong> ${detail.code} | ${detail.producto}</h6>
                            <p><strong>Cantidad:</strong> ${detail.quantity}</p>
                            <p><strong>Precio:</strong> ${detail.price}</p>
                            <p><strong>Total:</strong> ${detail.total}</p>
                    `;

                    content += `</div><hr />`;
                });

                // Insertar el contenido generado en el modal
                $('#order-details-content').html(content);

                // Mostrar el modal
                $('#orderDetailsModal').modal('show');
            } else {
                toastr.error('No se encontraron detalles para este pedido.', 'Error', {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                });
            }
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error('Ocurrió un error al obtener los detalles del pedido.', 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
    });
}

function finalizarGeneracionComprobante(res, abrirPdf) {
    if (abrirPdf && res.url_print) {
        window.open(res.url_print, '_blank');
    }

    limpiarModalGenerarComprobante();
    $('#modalGenerarComprobante').modal('hide');

    reloadCurrentPageOrders();
}

function getCurrentPageOrders() {
    let currentPage = $('#pagination li.active a.page-link').attr('data-item');

    return currentPage ? parseInt(currentPage) : 1;
}

function reloadCurrentPageOrders() {
    let currentPage = getCurrentPageOrders();
    getDataOrders(currentPage);
}

function showDataSearch() {
    getDataOrders(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataOrders(numberPage)
}

function getDataOrders($numberPage) {

    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var code = $('#code').val();
    var year = $('#year').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    var customerId = $('#filter_customer_id').val();
    var cashBoxId = $('#filter_cash_box_id').val();
    var cashBoxSubtypeId = $('#filter_cash_box_subtype_id').val();

    $.get('/dashboard/get/data/sales/error/' + $numberPage, {
        code: code,
        year: year,
        startDate: startDate,
        endDate: endDate,

        customer_id: customerId,
        cash_box_id: cashBoxId,
        cash_box_subtype_id: cashBoxSubtypeId

    }, function(data) {

        if (data.data.length == 0) {
            renderDataOrdersEmpty(data);
        } else {
            renderDataOrders(data);
        }

    }).fail(function(jqXHR, textStatus, errorThrown) {

        console.error(textStatus, errorThrown);

        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error');
        }

        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error');
        }

    }, 'json')
        .done(function() {

            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });

        });

}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderDataOrdersEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataOrders(data) {
    var dataQuotes = data.data;
    var pagination = data.pagination;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataQuotes.length ; j++) {
        renderDataTable(dataQuotes[j]);
    }

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function renderDataTableEmpty() {
    var clone = activateTemplate('#item-table-empty');
    $("#body-table").append(clone);
}

function renderDataTable(data) {
    var clone = activateTemplate('#item-table');

    clone.querySelector("[data-code]").innerHTML = escapeHtml(data.code);
    clone.querySelector("[data-date]").innerHTML = escapeHtml(data.date);

    clone.querySelector("[data-customer]").innerHTML = `
        <div>${escapeHtml(data.nombre_cliente)}</div>
        <small class="text-muted">
            ${escapeHtml(data.tipo_documento_cliente || '')}
            ${escapeHtml(data.numero_documento_cliente || '')}
        </small>
    `;

    let comprobanteLabel = 'SIN COMPROBANTE';
    let comprobanteClass = 'badge-secondary';

    if (data.type_document === '01') {
        comprobanteLabel = 'FACTURA';
        comprobanteClass = 'badge-primary';
    } else if (data.type_document === '03') {
        comprobanteLabel = 'BOLETA';
        comprobanteClass = 'badge-info';
    }

    clone.querySelector("[data-comprobante]").innerHTML = `
        <span class="badge ${comprobanteClass}">${comprobanteLabel}</span>
        <br>
        <small class="text-muted">
            ${escapeHtml(data.serie_sunat || '')}
            ${data.numero ? '-' + escapeHtml(data.numero) : ''}
        </small>
    `;

    clone.querySelector("[data-currency]").innerHTML = escapeHtml(data.currency);
    clone.querySelector("[data-total]").innerHTML = escapeHtml(data.total);
    clone.querySelector("[data-tipo_pago]").innerHTML = escapeHtml(data.tipo_pago);

    const errorText = data.sunat_message || 'Error no especificado';

    clone.querySelector("[data-sunat_error]").innerHTML = `
        <span class="badge badge-danger">ERROR</span>
        <button type="button"
                class="btn btn-link btn-sm p-0 ml-1"
                data-ver_error_sunat
                data-message="${escapeHtml(errorText)}"
                data-toggle="tooltip"
                title="Ver error">
            <i class="fas fa-info-circle text-danger"></i>
        </button>
    `;

    var botones = clone.querySelector("[data-buttons]");
    var cloneBtn = activateTemplate('#template-error-actions');

    const printBtn = cloneBtn.querySelector("[data-print_recibo]");
    printBtn.setAttribute("data-id", data.id);
    printBtn.setAttribute("href", data.print_url || "#");
    printBtn.setAttribute("title", data.print_label || "Ver ticket");

    if (!data.print_url) {
        printBtn.classList.add('disabled');
        printBtn.removeAttribute('target');
        printBtn.addEventListener('click', function (e) {
            e.preventDefault();
        });
    } else {
        printBtn.setAttribute('target', '_blank');
    }

    cloneBtn.querySelector("[data-ver_detalles]").setAttribute("data-id", data.id);

    const btnRetry = cloneBtn.querySelector("[data-reintentar_comprobante]");
    if (data.can_retry_invoice) {
        btnRetry.setAttribute("data-sale_id", data.id);
    } else {
        btnRetry.remove();
    }

    const btnDiscard = cloneBtn.querySelector("[data-descartar_error_comprobante]");
    if (data.can_discard_error) {
        btnDiscard.setAttribute("data-sale_id", data.id);
    } else {
        btnDiscard.remove();
    }

    botones.append(cloneBtn);
    $("#body-table").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderPreviousPage($numberPage) {
    var clone = activateTemplate('#previous-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function renderDisabledPage() {
    var clone = activateTemplate('#disabled-page');
    $("#pagination").append(clone);
}

function renderItemPage($numberPage, $currentPage) {
    var clone = activateTemplate('#item-page');
    if ( $numberPage == $currentPage )
    {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-active]").setAttribute('class', 'page-item active');
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    } else {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    }

    $("#pagination").append(clone);
}

function renderNextPage($numberPage) {
    var clone = activateTemplate('#next-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}