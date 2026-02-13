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
    });

})(jQuery);
