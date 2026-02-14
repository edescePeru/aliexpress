(function ($) {

    function toggleTransportSections() {
        var t = $('#tipo_transporte').val();

        // Mostrar/ocultar cards
        $('#cardPublicTransport').toggle(t === '01');
        $('#cardPrivateDriver').toggle(t === '02');

        // Si cambia, colapsar cualquier sección abierta que ya no aplica
        if (t === '01') {
            // cerrar conductor si estaba abierto
            $('#secDriver').collapse('hide');
        }
        if (t === '02') {
            $('#secTransportista').collapse('hide');
        }
    }

    function toggleItemsMode() {
        var m = $('#items_mode').val();
        $('#boxSaleRef').toggle(m === 'SALE');
        $('#boxItemsManual').toggle(m === 'MANUAL');
    }

    function addItemRow() {
        var idx = $('#itemsContainer .itemRow').length;

        var html = ''
            + '<div class="itemRow border rounded p-2 mb-2">'
            + '  <div class="row">'
            + '    <div class="col-md-6">'
            + '      <label class="required">Descripción</label>'
            + '      <input class="form-control" name="items[' + idx + '][descripcion]" />'
            + '    </div>'
            + '    <div class="col-md-2">'
            + '      <label class="required">Cantidad</label>'
            + '      <input type="number" step="0.001" class="form-control" name="items[' + idx + '][cantidad]" />'
            + '    </div>'
            + '    <div class="col-md-2">'
            + '      <label>Código</label>'
            + '      <input class="form-control" name="items[' + idx + '][codigo]" />'
            + '    </div>'
            + '    <div class="col-md-2 d-flex align-items-end">'
            + '      <button type="button" class="btn btn-danger btn-block btnDelItem">Eliminar</button>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        $('#itemsContainer').append(html);
    }

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
                                var m = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error';
                                toastr.error(m);
                            }
                        }).always(function () {
                            $btn.prop('disabled', false);
                        });
                    }
                },
                No: function () {}
            }
        });
    }

    function guessDocType(docNumber) {
        if (!docNumber) return '6';
        var digits = String(docNumber).replace(/\D/g, '');

        // Perú: DNI 8, RUC 11
        if (digits.length === 8) return '1';
        if (digits.length === 11) return '6';

        // fallback: RUC
        return '6';
    }

    function fillCustomerFields(customer) {
        var docNumber = customer.doc_number || '';
        console.log(docNumber);

        $('#customer_doc_number').val(docNumber);
        $('#customer_doc_type').val(guessDocType(docNumber)).trigger('change');

        // Si tienes input de address/email en la guía:
        $('input[name="customer_address"]').val(customer.address || '');
        $('input[name="customer_email"]').val(customer.email || '');
    }

    $(document).ready(function () {
        // Defaults
        toggleTransportSections();
        toggleItemsMode();

        // Eventos
        $(document).on('change', '#tipo_transporte', toggleTransportSections);
        $(document).on('change', '#items_mode', toggleItemsMode);

        $(document).on('click', '#btnAddItem', addItemRow);
        $(document).on('click', '.btnDelItem', function () {
            $(this).closest('.itemRow').remove();
        });

        $(document).on('click', '#btnSubmitGuide', submitGuide);

        // Abrir modal al dar click en +
        $("#btn-add-customer").on("click", function() {
            $("#formCreateCustomer")[0].reset(); // limpiar formulario
            $("#modalCustomer").modal("show");
        });

        // Enviar formulario por AJAX
        $("#btn-submit-customer").on("click", function(e) {
            e.preventDefault();

            let form = $("#formCreateCustomer");
            let url = form.data("url");
            let formData = form.serialize();

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    toastr.success(response.message);

                    // Cerrar modal
                    $("#modalCustomer").modal("hide");

                    // Obtener el cliente nuevo
                    let customer = response.customer;

                    // Crear nueva opción
                    let newOption = new Option(customer.business_name, customer.id, true, true);

                    // Agregar al select2 y seleccionarlo
                    $('#customer_id').append(newOption).trigger('change');

                    // Limpiar el formulario
                    $("#formCreateCustomer")[0].reset();

                },
                error: function(data) {
                    if( data.responseJSON.message && !data.responseJSON.errors )
                    {
                        toastr.error(data.responseJSON.message, 'Error',
                            {
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
                    for ( var property in data.responseJSON.errors ) {
                        toastr.error(data.responseJSON.errors[property], 'Error',
                            {
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
                }
            });
        });

        $(document).on('change', '#customer_id', function () {
            var customerId = $(this).val();

            // limpiar si no hay
            if (!customerId) {
                $('#customer_doc_number').val('');
                $('#customer_doc_type').val('6').trigger('change');
                $('input[name="customer_address"]').val('');
                $('input[name="customer_email"]').val('');
                return;
            }

            $.get(window.routes.customerPayload.replace(':id', customerId))
                .done(function (res) {
                    fillCustomerFields(res);
                })
                .fail(function () {
                    toastr.error('No se pudo cargar datos del cliente');
                });
        });
    });

})(jQuery);
