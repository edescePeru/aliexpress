let $materials=[];
let $materialsTypeahead=[];
let $consumables=[];
let $electrics=[];
let $items=[];
let $equipments=[];
let $equipmentStatus=false;
let $total=0;
let $totalUtility=0;
let $subtotal=0;
let $subtotal2=0;
let $subtotal3=0;
var $permissions;

$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    $igv = $('#igv').val();

    $("#element_loader").LoadingOverlay("show", {
        background: "rgba(61, 215, 239, 0.4)"
    });
    $selectContact = $('#contact_id');
    getContacts();

    fillEquipments();

    $.ajax({
        url: "/dashboard/get/quote/sale/materials/totals",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i = 0; i < json.length; i++) {
                $consumables.push(json[i]);
            }
        }
    });

    $modalChangePercentages = $('#modalChangePercentages');

    $(document).on('click', '[data-confirm]', confirmEquipment);

    $(document).on('click', '[data-addConsumable]', addConsumable);

    $formCreate = $('#formEdit');

    $("#btn-submit").on("click", storeQuote);

    $('.consumable_search').select2({
        placeholder: 'Selecciona un consumible',
        ajax: {
            url: '/dashboard/get/quote/sale/materials',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                //console.log(data);
                return {
                    results: $.map(data, function (item) {
                        //console.log(item.full_description);
                        return {
                            text: item.full_description,
                            id: item.id,
                        }
                    })
                }
            }
        }
    });

    $(document).on('click', '[data-deleteConsumable]', deleteConsumable);

    $(document).on('click', '[data-saveEquipment]', saveEquipment);

    // TODO: Nuevo boton para modificar los porcentages
    $(document).on('click', '[data-acEdit]', changePercentages);
    $('#btn-changePercentage').on('click', savePercentages);

    $(document).on('input', '[data-consumableQuantity]', function () {
        var card = $(this).parent().parent().parent().parent().parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });
    $(document).on('input', '[data-detailequipment]', function () {
        var card = $(this).parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });
    $(document).on("summernote.change", ".textarea_edit", function (e) {   // callback as jquery custom event
        var card = $(this).parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });

    var customerQuote = $('#customer_quote_id');
    var contactQuote = $('#contact_quote_id');

    $selectCustomer = $('#customer_id');

    $selectCustomer.change(function () {
        $selectContact.empty();
        var customer = $selectCustomer.val();
        $.get("/dashboard/get/contact/" + customer, function (data) {
            $selectContact.append($("<option>", {
                value: '',
                text: 'Seleccione contacto'
            }));
            var contact_quote_id = $('#contact_quote_id').val();
            for (var i = 0; i < data.length; i++) {
                if (data[i].id === parseInt(contact_quote_id)) {
                    var newOption = new Option(data[i].contact, data[i].id, false, true);
                    // Append it to the select
                    $selectContact.append(newOption).trigger('change');

                } else {
                    var newOption2 = new Option(data[i].contact, data[i].id, false, false);
                    // Append it to the select
                    $selectContact.append(newOption2);
                }
            }
        });

    });

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
            error: function(xhr) {
                let errors = xhr.responseJSON?.message || "Error al guardar";
                toastr.error(errors);
            }
        });
    });

    // detectar cambios en cualquier input, select o textarea dentro del card
    $(".card.card-success.datos_generales .card-body").on("change input", "input, select, textarea", function () {
        let card = $(this).closest(".card");

        if (card.hasClass("card-success")) {
            card.removeClass("card-success").addClass("card-dark");
        }
    });

    $(document).on("click", "#btn-guardar_datos_generales", function (e) {
        e.preventDefault();

        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            quote_id: $("#quote_id").val(),
            descriptionQuote: $("#descriptionQuote").val(),
            codeQuote: $("#codeQuote").val(),
            date_quote: $("#date_quote").val(),
            date_validate: $("#date_validate").val(),
            way_to_pay: $("#paymentQuote").val(), // si lo usas
            delivery_time: $("#timeQuote").val(),
            customer_id: $("#customer_id").val(),
            contact_id: $("#contact_id").val(),
            payment_deadline: $("#paymentQuote").val(),
            observations: $("#observations").val(),
        };

        $.ajax({
            url: "/dashboard/quotes/update-general",
            type: "POST",
            data: data,
            success: function (response) {
                toastr.success(response.message);

                // Cambiar card de vuelta a verde
                $(".card.datos_generales")
                    .removeClass("card-dark")
                    .addClass("card-success");
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || "Error al guardar cambios.");
            }
        });
    });

    // Cuando cambia cantidad
    $(document).on('input', '[data-serviceQuantity]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        markEquipDirty(this);
    });

    // Cuando cambia P/U
    $(document).on('input', '[data-servicePU]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        markEquipDirty(this);
    });

    $(document).on('click', '[data-addService]', addService);

    $(document).on('click', '[data-deleteService]', function () {
        markEquipDirty(this);
        $(this).closest('[data-serviceRow]').remove();
    });

    // Cuando cambia Tipo (monto/percent)
    $(document).on('change', 'input[name="discount_type"]', function () {
        const type = $(this).val(); // amount | percent
        $('#discountSection').attr('data-discount_type', type);

        if (type === 'percent') {
            $('#discount_value').attr('step', '0.01');
            $('#discount_value_hint').text('Ingrese porcentaje (0 a 100).');
        } else {
            $('#discount_value').attr('step', '0.01');
            $('#discount_value_hint').text('Ingrese monto en soles.');
        }
    });

    // Cuando cambia Modo (con/sin igv)
    $(document).on('change', 'input[name="discount_input_mode"]', function () {
        const mode = $(this).val(); // with_igv | without_igv
        $('#discountSection').attr('data-discount_input_mode', mode);
    });

    // Cuando cambia valor
    $(document).on('input', '#discount_value', function () {
        let val = parseFloat($(this).val() || 0);
        if (isNaN(val) || val < 0) val = 0;
        $('#discountSection').attr('data-discount_value', val.toFixed(2));
    });

    // Limpiar
    $('#btn-clear-discount').on('click', function () {
        $('#discount_type_amount').prop('checked', true).trigger('change');
        $('#discount_mode_without').prop('checked', true).trigger('change');
        $('#discount_value').val(0).trigger('input');
    });

    $(document).on('change', '[data-serviceBillable]', function () {
        markEquipDirty(this);
    });

    $(document).on('change input', '#discountSection input, #discount_value', function () {
        // Si tu descuento no está dentro del card, busca un card “activo”
        // O marca todos los cards si aplica globalmente:
        $('[data-equip]').each(function(){ markEquipDirty($(this)); });
    });

    $('#modalQuantityConsumable').on('hidden.bs.modal', function () {
        // ✅ asegurar que nada dentro conserve foco
        document.activeElement && document.activeElement.blur && document.activeElement.blur();

        // poner foco en algo fuera
        $('#material_search').focus();
    });
});

var $formCreate;
var $modalAddMaterial;
var $material;
var $renderMaterial;
var $selectCustomer;
var $selectContact;
var $modalChangePercentages;
let $descuento = 0;
var $igv;

function savePercentages() {
    var utility = $('#percentage_utility').val();
    var rent = $('#percentage_rent').val();
    var letter = $('#percentage_letter').val();

    var quote = $('#quote_percentage').val();
    var equipment = $('#equipment_percentage').val();

    $.ajax({
        url: '/dashboard/update/percentages/equipment/'+equipment+'/quote/'+quote,
        method: 'POST',
        data: JSON.stringify({ utility: utility, rent:rent, letter: letter}),
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        processData:false,
        contentType:'application/json; charset=utf-8',
        success: function (data) {
            console.log(data);
            $modalChangePercentages.modal('hide');
            toastr.success(data.message, 'Éxito',
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
            setTimeout( function () {
                location.reload();
            }, 2000 )
        },
        error: function (data) {
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


        },
    });


}

function changePercentages() {
    var utility = $(this).data('utility');
    var rent = $(this).data('rent');
    var letter = $(this).data('letter');

    var quote = $(this).data('acedit');
    var equipment = $(this).data('acequipment');

    $('#quote_percentage').val(quote);
    $('#equipment_percentage').val(equipment);

    $('#percentage_utility').val(utility);
    $('#percentage_rent').val(rent);
    $('#percentage_letter').val(letter);

    $modalChangePercentages.modal('show');
}

function getContacts() {
    var customer =  $('#customer_quote_id').val();
    $.get( "/dashboard/get/contact/"+customer, function( data ) {
        $selectContact.append($("<option>", {
            value: '',
            text: ''
        }));
        for ( var i=0; i<data.length; i++ )
        {
            if (data[i].id === parseInt($('#contact_quote_id').val())) {
                var newOption = new Option(data[i].contact, data[i].id, false, true);
                // Append it to the select
                $selectContact.append(newOption).trigger('change');

            } else {
                var newOption2 = new Option(data[i].contact, data[i].id, false, false);
                // Append it to the select
                $selectContact.append(newOption2);
            }

        }
    });
}

var substringMatcher = function(strs) {
    return function findMatches(q, cb) {
        var matches, substringRegex;

        // an array that will be populated with substring matches
        matches = [];

        // regex used to determine if a string contains the substring `q`
        substrRegex = new RegExp(q, 'i');

        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function(i, str) {
            if (substrRegex.test(str)) {
                matches.push(str);
            }
        });

        cb(matches);
    };
};

function addService() {
    const $card = $(this).closest('.card-body');
    const desc = $card.find('#material_search').val().trim();
    const unitId = $card.find('.unitMeasure').val();
    const unitText = $card.find('.unitMeasure option:selected').text().trim();
    const qty = parseFloat($card.find('#quantity').val() || 0);

    if (!desc) {
        toastr.error('Debe ingresar una descripción', 'Error');
        return;
    }
    if (!unitId) {
        toastr.error('Debe seleccionar una unidad', 'Error');
        return;
    }
    if (!qty || qty <= 0) {
        toastr.error('Debe ingresar una cantidad', 'Error');
        return;
    }

    // Precio (con IGV)
    let pu = 0;
    const $priceInput = $card.find('#price');
    if ($priceInput.length) {
        pu = parseFloat($priceInput.val() || 0);
        if (!pu || pu <= 0) {
            toastr.error('Debe ingresar un precio válido', 'Error');
            return;
        }
    } else {
        // si no puede ver precios, igual lo dejamos en 0, y se ocultan campos por @cannot
        pu = 0;
    }

    // render
    const $render = $card.find('[data-bodyService]');
    const clone = activateTemplate('#template-service');

    // Set values
    clone.querySelector('[data-serviceDescription]').value = desc;
    clone.querySelector('[data-serviceId]').value = ''; // nuevo, aún no existe en BD
    clone.querySelector('[data-serviceUnit]').value = unitText;
    clone.querySelector('[data-serviceQuantity]').value = qty.toFixed(2);
    clone.querySelector('[data-servicePU]').value = pu.toFixed(2);

    $render.append(clone);

    // recalcular para setear V/U e Importe
    const $lastRow = $render.find('[data-serviceRow]').last();

    const uid = 'billable_' + Date.now() + '_' + Math.floor(Math.random()*1000);
    $lastRow.find('[data-billable-id]').attr('id', uid);
    $lastRow.find('[data-billable-label]').attr('for', uid);
    $lastRow.find('[data-serviceBillable]').prop('checked', true);
    calculateServiceRow($lastRow);

    // limpiar inputs superiores
    $card.find('#material_search').val('');
    $card.find('#quantity').val(0);
    if ($priceInput.length) $priceInput.val(0);
    $card.find('.unitMeasure').val(null).trigger('change');
}

function calculateServiceRow(row) {
    const $row = $(row);

    let qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    let pu = parseFloat($row.find('[data-servicePU]').val() || 0); // con IGV
    if (isNaN(pu) || pu < 0) pu = 0;

    const igvPct = (typeof $igv !== 'undefined' && $igv !== null) ? parseFloat($igv) : 18;
    const igvFactor = 1 + (igvPct / 100);

    // V/U (sin IGV)
    const vu = (igvFactor > 0) ? (pu / igvFactor) : 0;

    // Importe (con IGV)
    const importe = qty * pu;

    $row.find('[data-serviceVU]').val(vu.toFixed(2));
    $row.find('[data-serviceImporte]').val(importe.toFixed(2));
}

function readConsumablesFromDom($card) {
    let rows = $card.find('[data-consumableRow]');
    // Si en edit no tienes data-consumableRow, usa el contenedor que tengas
    if (rows.length === 0) {
        rows = $card.find('[data-consumableDescription]').closest('.row');
    }

    const arr = [];
    let sumImporte = 0;
    let sumPromoDiscount = 0;

    rows.each(function () {
        const $row = $(this);

        const description = ($row.find('[data-consumableDescription]').val() || '').trim();
        if (!description) return;

        // material_id: robusto (attr o value)
        const materialId =
            $row.find('[data-consumableId]').attr('data-consumableid') ||
            $row.find('[data-consumableId]').val() ||
            null;

        const unit = ($row.find('[data-consumableUnit]').val() || '').trim();

        const qtyVisible = parseFloat($row.find('[data-consumableQuantity]').val() || 0); // packs o unidades
        const valor = parseFloat($row.find('[data-consumableValor]').val() || 0);
        const price = parseFloat($row.find('[data-consumablePrice]').val() || 0);
        const importe = parseFloat($row.find('[data-consumableImporte]').val() || 0);

        const discount = parseFloat($row.find('[data-descuento]').attr('data-descuento') || 0);
        const typePromo = $row.find('[data-type_promotion]').attr('data-type_promotion') || null;

        // Presentación
        const presentationId = $row.find('[data-presentation_id]').attr('data-presentation_id') || null;
        const unitsPerPack = parseFloat($row.find('[data-units_per_pack]').attr('data-units_per_pack') || 0);
        const unitsEquivalent = parseFloat($row.find('[data-units_equivalent]').attr('data-units_equivalent') || 0);

        arr.push({
            id: materialId,
            description,
            unit,
            quantity: qtyVisible,                // visible (packs o unidades)
            units_equivalent: unitsEquivalent || qtyVisible, // real
            valor,
            price,
            importe,
            discount,
            type_promo: typePromo,
            presentation_id: presentationId,
            units_per_pack: unitsPerPack || null
        });

        sumImporte += round2(importe);
        sumPromoDiscount += round2(discount);
    });

    return { array: arr, sum_importe: round2(sumImporte), sum_promos: round2(sumPromoDiscount) };
}

function readServicesFromDom(container) {
    const services = container.find('[data-serviceRow]');

    const arr = [];
    let sumAll = 0;
    let sumBillable = 0;

    services.each(function() {
        const $row = $(this);

        const desc = ($row.find('[data-serviceDescription]').val() || '').trim();
        if (!desc) return;

        const unit = ($row.find('[data-serviceUnit]').val() || '').trim();
        const qty  = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
        const vu   = parseFloat($row.find('[data-serviceVU]').val() || 0); // sin IGV
        const pu   = parseFloat($row.find('[data-servicePU]').val() || 0); // con IGV
        const imp  = parseFloat($row.find('[data-serviceImporte]').val() || 0);

        const billable = $row.find('[data-serviceBillable]').is(':checked') ? 1 : 0;

        arr.push({
            description: desc,
            unit: unit,
            quantity: qty,
            valor: vu,
            price: pu,
            importe: imp,
            billable: billable
        });

        // ✅ Cotización: suma todo
        sumAll += round2(imp);

        // ✅ Facturación (más adelante): suma solo facturables
        if (billable === 1) {
            sumBillable += round2(imp);
        }
    });

    return {
        array: arr,
        sum_all: round2(sumAll),
        sum_billable: round2(sumBillable)
    };
}

function computeGlobalDiscountBase(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    if ($d.length === 0) {
        return { base: 0, debug: 'no_section' };
    }

    const type = ($d.attr('data-discount_type') || 'amount');          // amount | percent
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv'); // with_igv | without_igv
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return { base: 0, debug: 'value_zero' };

    const factor = 1 + (igvPct / 100);

    // Base sin IGV del subtotal (si todo es gravado)
    const baseSubtotal = subtotalWithIgv / factor;

    let discountBase = 0;

    if (type === 'amount') {
        // Monto: puede venir sin IGV o con IGV
        discountBase = (mode === 'with_igv') ? (value / factor) : value;
    } else {
        // Porcentaje: puede venir calculado sobre base o sobre total
        const pct = value / 100;
        if (mode === 'with_igv') {
            discountBase = (subtotalWithIgv * pct) / factor;
        } else {
            discountBase = baseSubtotal * pct;
        }
    }

    // No permitir descuento mayor a la base
    if (discountBase > baseSubtotal) discountBase = baseSubtotal;

    return { base: round2(discountBase), debug: { type, mode, value } };
}

function fillEquipments() {
    $equipments = [];

    let subtotalWithIgvAll = 0;       // suma (productos + servicios) con IGV de todos los equipos
    let promosDiscountAll = 0;        // promos (si las tienes)
    let servicesAll = 0;              // servicios sum_all de todos los equipos

    const quote_id = $('#quote_id').val();

    $('[data-confirm]').each(function () {

        if ($(this).data('confirm') === '') return;

        const button = $(this);
        const idEquipment = button.data('confirm');

        // Card del equipo
        const $card = button.closest('[data-equip]');

        // 0) Datos generales (mantengo tu forma de obtenerlos)
        const quantity = 1;
        const utility = button.parent().parent().next().children().children().val();
        const rent    = button.parent().parent().next().children().children().next().val();
        const letter  = button.parent().parent().next().children().children().next().next().val();
        const detail  = button.parent().parent().next().children().children().next().next().next().children().next().val();

        // 1) Consumables (DOM)
        const cRead = readConsumablesFromDom($card);
        const consumablesArray = cRead.array;

        // productos subtotal (con IGV) - promos por item
        let subtotalConsumables = round2(cRead.sum_importe - cRead.sum_promos);

        // 2) Servicios (DOM)
        const servicesContainer = $card.find('[data-bodyService]');
        let servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
        if (servicesContainer.length > 0) {
            servicesRead = readServicesFromDom(servicesContainer);
        }

        const servicesArray = servicesRead.array;
        const servicesSumAll = servicesRead.sum_all; // ✅ cotización suma todo

        // subtotal equipo con IGV
        const subtotalWithIgvEquipment = round2(subtotalConsumables + servicesSumAll);

        // acumular para total general
        subtotalWithIgvAll += subtotalWithIgvEquipment;

        promosDiscountAll += cRead.sum_promos;
        servicesAll += servicesSumAll;

        // 3) Armar equipment en memoria
        // OJO: aquí guardamos total con IGV del equipo (sin aplicar descuento global aquí)
        // el descuento global es a nivel quote (no por fila)
        const equipmentIndex = $equipments.length;

        button.next().attr('data-saveEquipment', equipmentIndex);
        button.next().next().attr('data-deleteEquipment', equipmentIndex);

        $equipments.push({
            id: equipmentIndex,
            quote: quote_id,
            equipment: idEquipment,
            quantity: quantity,
            utility: utility,
            rent: rent,
            letter: letter,
            total: subtotalWithIgvEquipment,  // total del equipo con IGV antes de descuento global
            description: "",
            detail: detail,
            materials: [],
            consumables: consumablesArray,
            electrics: [],
            workforces: servicesArray,
            tornos: [],
            dias: []
        });
    });

    // 4) Totales generales de la cotización (aplicar descuento global sobre BASE sin IGV)
    const igvPct = parseFloat($igv || 18);

    const globalDiscount = computeGlobalDiscountBase(subtotalWithIgvAll, igvPct);
    const discountBase = globalDiscount.base;

    const baseSubtotal = round2(subtotalWithIgvAll / (1 + (igvPct / 100)));
    let baseFinal = round2(baseSubtotal - discountBase);
    if (baseFinal < 0) baseFinal = 0;

    const igvFinal = round2(baseFinal * (igvPct / 100));
    const totalFinal = round2(baseFinal + igvFinal);

    // 5) Pintar totales
    $('#descuento').html(discountBase.toFixed(2));
    $('#gravada').html(baseFinal.toFixed(2));
    $('#igv_total').html(igvFinal.toFixed(2));
    $('#total_importe').html(totalFinal.toFixed(2));

    $("#element_loader").LoadingOverlay("hide", true);
}

// Función para redondear a 2 decimales
function round2(num) {
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

function deleteEquipment() {
    //if($(this).attr('data-idequipment')==='') {
    var attr = $(this).attr('data-idequipment');
    console.log(attr);
    if (typeof attr === typeof undefined || attr === false) {
        var button = $(this);
        $.confirm({
            icon: 'fas fa-frown',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'red',
            title: 'Eliminar Cotización',
            content: '¿Está seguro de eliminar estos productos?',
            buttons: {
                confirm: {
                    text: 'CONFIRMAR',
                    action: function (e) {
                        var equipmentId = parseInt(button.data('deleteequipment'));
                        console.log(equipmentId);

                        var equipmentDeleted = $equipments.find(equipment => equipment.id === equipmentId);
                        console.log(equipmentDeleted);

                        $equipments = $equipments.filter(equipment => equipment.id !== equipmentId);
                        button.parent().parent().parent().parent().remove();
                        if ($equipments.length === 0) {
                            renderTemplateEquipment();
                            $equipmentStatus = false;
                        }

                        var totalEquipmentU = 0;
                        var totalEquipmentL = 0;
                        var totalEquipmentR = 0;
                        var totalEquipmentUtility = 0;

                        totalEquipmentU = equipmentDeleted.total*((equipmentDeleted.utility/100)+1);
                        totalEquipmentL = totalEquipmentU*((equipmentDeleted.letter/100)+1);
                        totalEquipmentR = totalEquipmentL*((equipmentDeleted.rent/100)+1);
                        totalEquipmentUtility = totalEquipmentR.toFixed(2);

                        $total = parseFloat($total) - parseFloat(totalEquipmentUtility);
                        $totalUtility = parseFloat($totalUtility) - parseFloat(totalEquipmentUtility);

                        $('#subtotal').html('USD '+ ($total/1.18).toFixed(2));
                        $('#total').html('USD '+$total.toFixed(2));
                        $('#subtotal_utility').html('USD '+ ($totalUtility/1.18).toFixed(2));
                        $('#total_utility').html('USD '+$totalUtility.toFixed(2));

                        if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
                            renderTemplateSummary($equipments);
                        }
                        $.alert("Productos eliminados!");

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Eliminación cancelada.");
                    },
                },
            },
        });
    } else {
        // TODO: Vamos a eliminar en la base de datos
        var button2 = $(this);
        $.confirm({
            icon: 'fas fa-frown',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'red',
            title: 'Eliminar Productos',
            content: 'Estos productos va a ser eliminado en la base de datos',
            buttons: {
                confirm: {
                    text: 'CONFIRMAR',
                    action: function (e) {
                        var equipmentId = parseInt(button2.data('deleteequipment'));
                        var idEquipment = button2.data('idequipment');
                        var idQuote = button2.data('quote');
                        console.log(equipmentId);

                        var equipmentDeleted = $equipments.find(equipment => equipment.id === equipmentId);
                        console.log(equipmentDeleted);

                        $.ajax({
                            url: '/dashboard/destroy/equipment/'+idEquipment+'/quote/'+idQuote,
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            processData:false,
                            contentType:false,
                            success: function (data) {
                                console.log(data);
                                /*toastr.success(data.message, 'Éxito',
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
                                    });*/

                                $equipments = $equipments.filter(equipment => equipment.id !== equipmentId);
                                button2.parent().parent().parent().parent().remove();

                                var totalEquipmentU = 0;
                                var totalEquipmentL = 0;
                                var totalEquipmentR = 0;
                                var totalEquipmentUtility = 0;

                                totalEquipmentU = equipmentDeleted.total*((equipmentDeleted.utility/100)+1);
                                totalEquipmentL = totalEquipmentU*((equipmentDeleted.letter/100)+1);
                                totalEquipmentR = totalEquipmentL*((equipmentDeleted.rent/100)+1);
                                totalEquipmentUtility = totalEquipmentR.toFixed(2);

                                $total = parseFloat($total) - parseFloat(equipmentDeleted.total);
                                $totalUtility = parseFloat($totalUtility) - parseFloat(totalEquipmentUtility);

                                $('#subtotal').html('USD '+ ($total/1.18).toFixed(2));
                                $('#total').html('USD '+$total.toFixed(2));
                                $('#subtotal_utility').html('USD '+ ($totalUtility/1.18).toFixed(2));
                                $('#total_utility').html('USD '+$totalUtility.toFixed(2));

                                if ($equipments.length === 0) {
                                    renderTemplateEquipment();
                                    $equipmentStatus = false;
                                }
                                if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
                                    renderTemplateSummary($equipments);
                                }
                                $.alert(data.message);

                            },
                            error: function (data) {
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


                            },
                        });

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Eliminación cancelada.");
                    },
                },
            },
        });
    }
}

function saveEquipment() {
    var button = $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        title: 'Guardar cambios',
        content: '¿Está seguro de guardar los cambios en estos productos?',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {
                    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

                    try {
                        const $card = button.closest('[data-equip]');
                        const idEquipment = button.attr('data-idequipment'); // id en BD
                        const idQuote = button.attr('data-quote');

                        // 1) Leer consumables + servicios del DOM
                        const cRead = readConsumablesFromDom($card);
                        const consumablesArray = cRead.array;

                        const servicesContainer = $card.find('[data-bodyService]');
                        let servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
                        if (servicesContainer.length) servicesRead = readServicesFromDom(servicesContainer);

                        const workforcesArray = servicesRead.array;

                        // 2) Subtotal con IGV (productos + servicios) para aplicar descuento global
                        let subtotalConsumables = round2(cRead.sum_importe - cRead.sum_promos);
                        let subtotalWithIgv = round2(subtotalConsumables + servicesRead.sum_all);

                        // 3) Descuento global (sobre base SIN IGV)
                        const igvPct = parseFloat(window.$igv || 18);
                        const globalDiscount = computeGlobalDiscountBase(subtotalWithIgv, igvPct);
                        const discountBase = globalDiscount.base;

                        const baseSubtotal = round2(subtotalWithIgv / (1 + (igvPct / 100)));
                        let baseFinal = round2(baseSubtotal - discountBase);
                        if (baseFinal < 0) baseFinal = 0;

                        const igvFinal = round2(baseFinal * (igvPct / 100));
                        const totalFinal = round2(baseFinal + igvFinal);

                        // 4) Pintar totales
                        $('#descuento').html(discountBase.toFixed(2));
                        $('#gravada').html(baseFinal.toFixed(2));
                        $('#igv_total').html(igvFinal.toFixed(2));
                        $('#total_importe').html(totalFinal.toFixed(2));

                        // 5) Armar payload (backend espera equipment: [ {...} ])
                        const utility = button.parent().parent().next().children().children().val();
                        const rent    = button.parent().parent().next().children().children().next().val();
                        const letter  = button.parent().parent().next().children().children().next().next().val();
                        const detail  = button.parent().parent().next().children().children().next().next().next().children().next().val();

                        const equipmentPayload = [{
                            id: 0,
                            quote: idQuote,
                            equipment: idEquipment,
                            quantity: 1,
                            utility: utility,
                            rent: rent,
                            letter: letter,
                            total: totalFinal,
                            description: "",
                            detail: detail,
                            materials: [],
                            consumables: consumablesArray,
                            electrics: [],
                            workforces: workforcesArray,
                            tornos: [],
                            dias: []
                        }];

                        // 6) Metadata de descuento (para persistir y rehidratar en edit)
                        const $d = $('#discountSection');
                        const discount_type = ($d.attr('data-discount_type') || $('input[name="discount_type"]:checked').val() || 'amount');
                        const discount_input_mode = ($d.attr('data-discount_input_mode') || $('input[name="discount_input_mode"]:checked').val() || 'without_igv');
                        const discount_input_value = ($d.attr('data-discount_value') || $('#discount_value').val() || '0');

                        $.ajax({
                            url: '/dashboard/update/equipment/' + idEquipment + '/quote/sale/' + idQuote,
                            method: 'POST',
                            data: JSON.stringify({
                                equipment: equipmentPayload,
                                descuento: discountBase.toFixed(2),
                                gravada: baseFinal.toFixed(2),
                                igv_total: igvFinal.toFixed(2),
                                total_importe: totalFinal.toFixed(2),

                                discount_type: discount_type,
                                discount_input_mode: discount_input_mode,
                                discount_input_value: discount_input_value
                            }),
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            processData: false,
                            contentType: 'application/json; charset=utf-8',
                            success: function (data) {
                                // Marcar card como limpio
                                if (typeof markEquipClean === 'function') markEquipClean($card);

                                // Rehidratar ids si cambian
                                if (data.quote) {
                                    button.attr('data-quote', data.quote.id);
                                    button.next().attr('data-quote', data.quote.id);
                                }
                                if (data.equipment) {
                                    button.attr('data-idequipment', data.equipment.id);
                                    button.next().attr('data-idequipment', data.equipment.id);
                                }

                                $.alert(data.message || 'Equipo guardado con éxito.');
                            },
                            error: function (xhr) {
                                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al guardar.';
                                toastr.error(msg, 'Error');
                            },
                            complete: function () {
                                $("#element_loader").LoadingOverlay("hide", true);
                            }
                        });

                    } catch (err) {
                        $("#element_loader").LoadingOverlay("hide", true);
                        toastr.error(err.message || 'Error inesperado', 'Error');
                    }
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function () {}
            }
        }
    });
}

function deleteConsumable() {
    //console.log($(this).parent().parent().parent());
    var card = $(this).parent().parent().parent().parent().parent().parent().parent();
    card.removeClass('card-success');
    card.addClass('card-gray-dark');
    $(this).parent().parent().remove();
}

function addConsumable() {
    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
        var consumableID = $(this).parent().parent().find('[data-consumable]').val();

        var inputQuantity = $(this).parent().parent().find('[data-cantidad]');

        var cantidad = inputQuantity.val();

        if ( cantidad === '' || parseFloat(cantidad) === 0 )
        {
            toastr.error('Debe ingresar una cantidad', 'Error',
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
            return;
        }

        if ( consumableID === '' || consumableID === null )
        {
            toastr.error('Debe seleccionar un consumible', 'Error',
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
            return;
        }

        var render = $(this).parent().parent().next().next();

        var consumable = $consumables.find( mat=>mat.id === parseInt(consumableID) );

        var consumables = $(this).parent().parent().next().next().children();

        consumables.each(function(e){
            var id = $(this).children().children().children().next().val();
            if (parseInt(consumable.id) === parseInt(id)) {
                inputQuantity.val(0);
                $(".consumable_search").empty().trigger('change');
                toastr.error('Este material ya esta seleccionado', 'Error',
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
                e.stopPropagation();
                return false ;
            }
        });
        inputQuantity.val(0);
        $(".consumable_search").empty().trigger('change');
        //getDiscountMaterial(consumable.id, cantidad);
        checkMaterialPromotions(consumable.id, parseFloat(cantidad).toFixed(2), consumable, cantidad, render);

        /*getDiscountMaterial(consumable.id, parseFloat(cantidad).toFixed(2)).then(function(discount) {
            console.log(discount.valueDiscount);
            if ( discount != -1 )
            {
                $descuento += discount.valueDiscount;
                renderTemplateConsumable(render, consumable, cantidad, discount.valueDiscount);
            } else  {
                $descuento += 0;
                renderTemplateConsumable(render, consumable, cantidad, 0);
            }

        });
        */
        //renderTemplateConsumable(render, consumable, cantidad);

    } else {
        var consumableID2 = $(this).parent().parent().find('[data-consumable]').val();
        //console.log(material);
        var inputQuantity2 = $(this).parent().parent().find('[data-cantidad]');
        var cantidad2 = inputQuantity2.val();
        if ( cantidad2 === '' || parseFloat(cantidad2) === 0 )
        {
            toastr.error('Debe ingresar una cantidad', 'Error',
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
            return;
        }

        if ( consumableID2 === '' || consumableID2 === null )
        {
            toastr.error('Debe seleccionar un consumible', 'Error',
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
            return;
        }

        var render2 = $(this).parent().parent().next().next();

        var consumable2 = $consumables.find( mat=>mat.id === parseInt(consumableID2) );
        var consumables2 = $(this).parent().parent().next().next().children();

        consumables2.each(function(e){
            var id = $(this).children().children().children().next().val();
            if (parseInt(consumable2.id) === parseInt(id)) {
                inputQuantity2.val(0);
                $(".consumable_search").empty().trigger('change');
                toastr.error('Este material ya esta seleccionado', 'Error',
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
                e.stopPropagation();
                return false ;
            }
        });
        inputQuantity2.val(0);
        $(".consumable_search").empty().trigger('change');
        //getDiscountMaterial(consumable2.id, cantidad2);

        checkMaterialPromotions(consumable2.id, parseFloat(cantidad2).toFixed(2), consumable2, cantidad2, render2);

        /*getDiscountMaterial(consumable2.id, parseFloat(cantidad2).toFixed(2)).then(function(discount) {
            console.log(discount.valueDiscount);
            if ( discount != -1 )
            {
                $descuento += discount.valueDiscount;
                renderTemplateConsumable(render2, consumable2, cantidad2, discount.valueDiscount);
            } else  {
                $descuento += 0;
                renderTemplateConsumable(render2, consumable2, cantidad2, 0);
            }

        });*/

        //renderTemplateConsumable(render2, consumable2, cantidad2);
    }

}

function checkMaterialPromotions(materialId, cantidad, consumable, cantidadOriginal, render) {
    $.ajax({
        url: '/dashboard/check-promotions',
        method: 'POST',
        data: {
            material_id: materialId,
            quantity: cantidad,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success && response.promotions.length > 0) {
                showPromotionModal(response.promotions, consumable, cantidadOriginal, render);
            } else {
                toastr.info("No hay promociones aplicables.");
                renderTemplateConsumable(render, consumable, cantidadOriginal, 0, "ninguno")
            }
        },
        error: function () {
            toastr.error("Error al verificar promociones.");
        }
    });
}

function showPromotionModal(promotions, consumable, cantidad, render) {
    let content = '';

    promotions.forEach((promo, index) => {
        let btn = `<button class="btn btn-primary btn-sm select-promo" 
                        data-index="${index}" 
                        data-type="${promo.type}">
                        Seleccionar
                   </button>`;

        if (promo.type === 'seasonal') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Descuento por Categoría:</strong> ${promo.discount}% hasta el ${promo.valid_until}
                            <br>${btn}
                        </div>`;
        }
        else if (promo.type === 'quantity_discount') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Descuento por Cantidad:</strong> ${promo.percentage}%
                            <br>${btn}
                        </div>`;
        }
        else if (promo.type === 'limit') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Promoción Límite:</strong> ${promo.price_type === 'fixed' ? 'Precio fijo' : 'Descuento'} 
                            ${promo.percentage || promo.promo_price}
                            <br>${btn}
                        </div>`;
        }


    });

    // ➕ Agregar botón de "sin promoción"
    content += `<div class="mb-2 border p-2 rounded text-center">
                <button class="btn btn-secondary btn-sm select-promo" 
                        data-index="-1" 
                        data-type="none">
                        No aplicar promoción
                </button>
            </div>`;

    $("#promotion-content").html(content);
    $("#promotionModal").modal('show');

    // Evento de selección de promoción
    $(".select-promo").off().on("click", function () {
        let index = $(this).data("index");
        let type = $(this).data("type");
        let promo = promotions[index];

        if (type === 'none') {
            // 👉 El usuario eligió no aplicar ninguna promoción
            let precioNormal = parseFloat(consumable.list_price);
            renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, precioNormal, 'ninguno');

            $("#promotionModal").modal('hide');
            return; // cortar aquí
        }

        if (type === 'quantity_discount') {
            getDiscountMaterial(consumable.id, parseFloat(cantidad).toFixed(2)).then(function(discount) {
                let valueDiscount = discount != -1 ? discount.valueDiscount : 0;
                $descuento += valueDiscount;
                renderTemplateConsumable(render, consumable, cantidad, valueDiscount, "quantity_discount");
            });
        }
        else if (type === 'seasonal') {
            let precioBase = parseFloat(consumable.list_price);
            let descuento = promo.discount;
            let precioFinal = precioBase - (precioBase * (descuento / 100));
            renderTemplateConsumable(render, consumable, cantidad, precioFinal, "seasonal", true);
        }
        else if (type === 'limit') {
            let limite = promo.remaining_quantity;
            let precioNormal = consumable.list_price;

            if (promo.price_type === 'fixed') {
                if (cantidad > limite) {
                    // Parte con precio promo
                    renderTemplateConsumableWithFixedPrice(render, consumable, limite, promo.promo_price, "limit");
                    // Parte sin promo
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad - limite, precioNormal, 'ninguno');
                } else {
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, promo.promo_price, "limit");
                }
            }
            else if (promo.price_type === 'percentage') {
                let precioConDescuento = precioNormal - (precioNormal * promo.percentage / 100);

                if (cantidad > limite) {
                    renderTemplateConsumableWithFixedPrice(render, consumable, limite, precioConDescuento, "limit");
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad - limite, precioNormal, 'ninguno');
                } else {
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, precioConDescuento, "limit");
                }
            }
        }

        $("#promotionModal").modal('hide');
    });
}

function renderTemplateConsumableWithFixedPrice(render, consumable, quantity, fixedPrice, type_promo) {
    var card = render.closest('[data-equip]');
    card.removeClass('card-success').addClass('card-gray-dark');

    let precioBase = parseFloat(fixedPrice);
    let valorUnitario = precioBase / ((100 + parseFloat($igv)) / 100);
    let importeTotal = precioBase * parseFloat(quantity);

    var clone = activateTemplate('#template-consumable');
    clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
    clone.querySelector("[data-consumableId]").setAttribute('data-consumableId', consumable.id);
    clone.querySelector("[data-descuento]").setAttribute('data-descuento', "0.00");
    clone.querySelector("[data-type_promotion]").setAttribute('data-type_promotion', type_promo);
    clone.querySelector("[data-consumableUnit]").setAttribute('value', consumable.unit_measure.description);
    clone.querySelector("[data-consumableQuantity]").setAttribute('value', (parseFloat(quantity)).toFixed(2));

    clone.querySelector("[data-consumableValor]").setAttribute('value', (parseFloat(valorUnitario).toFixed(2)));
    clone.querySelector("[data-consumablePrice]").setAttribute('value', (parseFloat(precioBase).toFixed(2)));
    clone.querySelector("[data-consumableImporte]").setAttribute('value', (parseFloat(importeTotal).toFixed(2)));

    render.append(clone);
}

function renderTemplateConsumable(render, consumable, quantity, discountOrPrice, type_promo,isPrice = false) {
    var card = render.closest('[data-equip]');
    card.removeClass('card-success').addClass('card-gray-dark');

    var clone = activateTemplate('#template-consumable');

    let precioBase = isPrice ? parseFloat(discountOrPrice) : parseFloat(consumable.list_price);
    let valorUnitario = precioBase / ((100 + parseFloat($igv)) / 100);
    let importeTotal = precioBase * parseFloat(quantity);

    if (consumable.enable_status == 0) {
        clone.querySelector("[data-consumableDescription]").setAttribute('style', "color:purple;");
    } else if (consumable.stock_current == 0) {
        clone.querySelector("[data-consumableDescription]").setAttribute('style', "color:red;");
    } else if (consumable.state_update_price == 1) {
        clone.querySelector("[data-consumableDescription]").setAttribute('style', "color:blue;");
    }

    clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
    clone.querySelector("[data-consumableId]").setAttribute('data-consumableId', consumable.id);
    clone.querySelector("[data-descuento]").setAttribute('data-descuento', isPrice ? "0.00" : parseFloat(discountOrPrice).toFixed(2));
    clone.querySelector("[data-type_promotion]").setAttribute('data-type_promotion', type_promo);
    clone.querySelector("[data-consumableUnit]").setAttribute('value', consumable.unit_measure.description);
    clone.querySelector("[data-consumableQuantity]").setAttribute('value', parseFloat(quantity).toFixed(2));
    clone.querySelector("[data-consumableValor]").setAttribute('value', valorUnitario.toFixed(2));
    clone.querySelector("[data-consumablePrice]").setAttribute('value', precioBase.toFixed(2));
    clone.querySelector("[data-consumableImporte]").setAttribute('value', importeTotal.toFixed(2));

    render.append(clone);
}

function getDiscountMaterial(product_id, quantity) {
    return $.get('/dashboard/get/discount/product/' + product_id, {
        quantity: quantity
    }).then(function(data) {
        console.log(data.data[0].haveDiscount);
        if (data.data[0].haveDiscount == true) {
            console.log(data);
            return data.data[0];
        } else {
            return -1;
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
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
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
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

function confirmEquipment() {
    var button = $(this);
    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Confirmar productos',
        content: 'Debe confirmar para almacenar los productos en memoria',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    //var cantidad = button.parent().parent().next().children().children().children().next();
                    //console.log($(this));
                    /*$equipmentStatus = true;*/
                    // Quitamos el boton
                    button.hide();
                    //$items.push({ 'id': $items.length+1, 'material': $material, 'material_quantity': material_quantity, 'material_price':total});
                    //console.log(button);
                    button.next().show();
                    button.next().next().show();

                    var quantity = button.parent().parent().next().children().children().children().next().val();
                    var utility = button.parent().parent().next().children().children().children().next().next().val();
                    var rent = button.parent().parent().next().children().children().children().next().next().next().val();
                    var letter = button.parent().parent().next().children().children().children().next().next().next().next().val();

                    var description = button.parent().parent().next().children().children().next().next().children().next().val();
                    var detail = button.parent().parent().next().children().children().next().next().next().children().next().val();
                    var materials = button.parent().parent().next().children().next().children().next().children().next().next().next();
                    var consumables = button.parent().parent().next().children().next().next().children().next().children().next().next();
                    var electrics = button.parent().parent().next().children().next().next().next().children().next().children().next().next();
                    var workforces = button.parent().parent().next().children().next().next().next().children().next().children().next().next();
                    var tornos = button.parent().parent().next().children().next().next().next().children().next().children().next().next().next().next().children().next().children().next().next();
                    var dias = button.parent().parent().next().children().next().next().next().next().children().next().children().next().next().next();
                    console.log(description);
                    var materialsDescription = [];
                    var materialsUnit = [];
                    var materialsLargo = [];
                    var materialsAncho = [];
                    var materialsQuantity = [];
                    var materialsPrice = [];
                    var materialsTotal = [];

                    materials.each(function(e){
                        $(this).find('[data-materialDescription]').each(function(){
                            materialsDescription.push($(this).val());
                        });
                        $(this).find('[data-materialUnit]').each(function(){
                            materialsUnit.push($(this).val());
                        });
                        $(this).find('[data-materialLargo]').each(function(){
                            materialsLargo.push($(this).val());
                        });
                        $(this).find('[data-materialAncho]').each(function(){
                            materialsAncho.push($(this).val());
                        });
                        $(this).find('[data-materialQuantity]').each(function(){
                            materialsQuantity.push($(this).val());
                        });
                        $(this).find('[data-materialPrice]').each(function(){
                            materialsPrice.push($(this).val());
                        });
                        $(this).find('[data-materialTotal]').each(function(){
                            materialsTotal.push($(this).val());
                        });
                    });

                    var materialsArray = [];

                    for (let i = 0; i < materialsDescription.length; i++) {
                        //var materialSelected = $materials.find( mat=>mat.full_description.trim().toLowerCase() === materialsDescription[i].trim().toLowerCase() );
                        var materialSelected = $materials.find( mat=>
                            //mat=>mat.full_name.trim().toLowerCase() === materialsDescription[i].trim().toLowerCase()
                            mat.full_name.trim().toLowerCase() === materialsDescription[i].trim().toLowerCase() &&
                            mat.enable_status === 1
                        );
                        materialsArray.push({'id':materialSelected.id,'material':materialSelected, 'description':materialsDescription[i], 'unit':materialsUnit[i], 'length':materialsLargo[i], 'width':materialsAncho[i], 'quantity':materialsQuantity[i], 'price': materialsPrice[i], 'total': materialsTotal[i]});
                    }

                    var diasDescription = [];
                    var diasCantidad = [];
                    var diasHoras = [];
                    var diasPrecio = [];
                    var diasTotal = [];

                    dias.each(function(e){
                        $(this).find('[data-description]').each(function(){
                            diasDescription.push($(this).val());
                        });
                        $(this).find('[data-cantidad]').each(function(){
                            diasCantidad.push($(this).val());
                        });
                        $(this).find('[data-horas]').each(function(){
                            diasHoras.push($(this).val());
                        });
                        $(this).find('[data-precio]').each(function(){
                            diasPrecio.push($(this).val());
                        });
                        $(this).find('[data-total]').each(function(){
                            diasTotal.push($(this).val());
                        });
                    });

                    var diasArray = [];

                    for (let i = 0; i < diasCantidad.length; i++) {
                        diasArray.push({'description':diasDescription[i], 'quantity':diasCantidad[i], 'hours':diasHoras[i], 'price':diasPrecio[i], 'total': diasTotal[i]});
                    }

                    var consumablesDescription = [];
                    var consumablesIds = [];
                    var consumablesUnit = [];
                    var consumablesQuantity = [];
                    var consumablesPrice = [];
                    var consumablesTotal = [];

                    consumables.each(function(e){
                        $(this).find('[data-consumableDescription]').each(function(){
                            consumablesDescription.push($(this).val());
                        });
                        $(this).find('[data-consumableId]').each(function(){
                            consumablesIds.push($(this).attr('data-consumableid'));
                        });
                        $(this).find('[data-consumableUnit]').each(function(){
                            consumablesUnit.push($(this).val());
                        });
                        $(this).find('[data-consumableQuantity]').each(function(){
                            consumablesQuantity.push($(this).val());
                        });
                        $(this).find('[data-consumablePrice]').each(function(){
                            consumablesPrice.push($(this).val());
                        });
                        $(this).find('[data-consumableTotal]').each(function(){
                            consumablesTotal.push($(this).val());
                        });
                    });

                    var consumablesArray = [];

                    for (let i = 0; i < consumablesDescription.length; i++) {
                        consumablesArray.push({'id':consumablesIds[i], 'description':consumablesDescription[i], 'unit':consumablesUnit[i], 'quantity':consumablesQuantity[i], 'price': consumablesPrice[i], 'total': consumablesTotal[i]});
                    }

                    // SECCION DE ELECTRICOS
                    var electricsDescription = [];
                    var electricsIds = [];
                    var electricsUnit = [];
                    var electricsQuantity = [];
                    var electricsPrice = [];
                    var electricsTotal = [];

                    electrics.each(function(e){
                        $(this).find('[data-electricDescription]').each(function(){
                            electricsDescription.push($(this).val());
                        });
                        $(this).find('[data-electricId]').each(function(){
                            electricsIds.push($(this).attr('data-electricid'));
                        });
                        $(this).find('[data-electricUnit]').each(function(){
                            electricsUnit.push($(this).val());
                        });
                        $(this).find('[data-electricQuantity]').each(function(){
                            electricsQuantity.push($(this).val());
                        });
                        $(this).find('[data-electricPrice]').each(function(){
                            electricsPrice.push($(this).val());
                        });
                        $(this).find('[data-electricTotal]').each(function(){
                            electricsTotal.push($(this).val());
                        });
                    });

                    var electricsArray = [];

                    for (let i = 0; i < electricsDescription.length; i++) {
                        electricsArray.push({'id':electricsIds[i], 'description':electricsDescription[i], 'unit':electricsUnit[i], 'quantity':electricsQuantity[i], 'price': electricsPrice[i], 'total': electricsTotal[i]});
                    }

                    var manosDescription = [];
                    var manosIds = [];
                    var manosUnit = [];
                    var manosQuantity = [];
                    var manosPrice = [];
                    var manosTotal = [];

                    workforces.each(function(e){
                        $(this).find('[data-manoDescription]').each(function(){
                            manosDescription.push($(this).val());
                        });
                        $(this).find('[data-manoId]').each(function(){
                            manosIds.push($(this).val());
                        });
                        $(this).find('[data-manoUnit]').each(function(){
                            manosUnit.push($(this).val());
                        });
                        $(this).find('[data-manoQuantity]').each(function(){
                            manosQuantity.push($(this).val());
                        });
                        $(this).find('[data-manoPrice]').each(function(){
                            manosPrice.push($(this).val());
                        });
                        $(this).find('[data-manoTotal]').each(function(){
                            manosTotal.push($(this).val());
                        });
                    });

                    var manosArray = [];

                    for (let i = 0; i < manosDescription.length; i++) {
                        manosArray.push({'id':manosIds[i], 'description':manosDescription[i], 'unit':manosUnit[i], 'quantity':manosQuantity[i], 'price':manosPrice[i], 'total': manosTotal[i]});
                    }

                    var tornosDescription = [];
                    var tornosQuantity = [];
                    var tornosPrice = [];
                    var tornosTotal = [];

                    tornos.each(function(e){
                        $(this).find('[data-tornoDescription]').each(function(){
                            tornosDescription.push($(this).val());
                        });
                        $(this).find('[data-tornoQuantity]').each(function(){
                            tornosQuantity.push($(this).val());
                        });
                        $(this).find('[data-tornoPrice]').each(function(){
                            tornosPrice.push($(this).val());
                        });
                        $(this).find('[data-tornoTotal]').each(function(){
                            tornosTotal.push($(this).val());
                        });
                    });

                    var tornosArray = [];

                    for (let i = 0; i < tornosDescription.length; i++) {
                        tornosArray.push({'description':tornosDescription[i], 'quantity':tornosQuantity[i], 'price':tornosPrice[i], 'total': tornosTotal[i]});
                    }

                    var totalEquipment = 0;
                    var totalEquipmentU = 0;
                    var totalEquipmentL = 0;
                    var totalEquipmentR = 0;
                    var totalEquipmentUtility = 0;
                    var totalDias = 0;
                    for (let i = 0; i < materialsTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(materialsTotal[i]);
                    }
                    for (let i = 0; i < tornosTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(tornosTotal[i]);
                    }
                    for (let i = 0; i < manosTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(manosTotal[i]);
                    }
                    for (let i = 0; i < consumablesTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(consumablesTotal[i]);
                    }
                    for (let i = 0; i < electricsTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(electricsTotal[i]);
                    }
                    for (let i = 0; i < diasTotal.length; i++) {
                        totalEquipment = parseFloat(totalEquipment) + parseFloat(diasTotal[i]);
                    }

                    totalEquipment = parseFloat((totalEquipment * quantity)/*+totalDias*/).toFixed(2);

                    totalEquipmentU = totalEquipment*((utility/100)+1);
                    totalEquipmentL = totalEquipmentU*((letter/100)+1);
                    totalEquipmentR = totalEquipmentL*((rent/100)+1);
                    totalEquipmentUtility = totalEquipmentR.toFixed(2);

                    $total = parseFloat($total) + parseFloat(totalEquipment);
                    $totalUtility = parseFloat($totalUtility) + parseFloat(totalEquipmentUtility);

                    $('#subtotal').html('USD '+ ($total/1.18).toFixed(2));
                    $('#total').html('USD '+$total.toFixed(2));
                    $('#subtotal_utility').html('USD '+ ($totalUtility/1.18).toFixed(2));
                    $('#total_utility').html('USD '+$totalUtility.toFixed(2));

                    button.next().attr('data-saveEquipment', $equipments.length);
                    button.next().next().attr('data-deleteEquipment', $equipments.length);
                    $equipments.push({'id':$equipments.length, 'quote':'', 'quantity':quantity, 'equipment':'', 'utility':utility, 'rent':rent, 'letter':letter, 'total':totalEquipment, 'description':description, 'detail':detail, 'materials': materialsArray, 'consumables':consumablesArray, 'electrics':electricsArray, 'workforces':manosArray, 'tornos':tornosArray, 'dias':diasArray});
                    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
                        renderTemplateSummary($equipments);
                    }
                    updateTableTotalsEquipment(button, {'id':$equipments.length, 'quote':'', 'quantity':quantity, 'utility':utility, 'rent':rent, 'letter':letter, 'total':totalEquipment, 'description':description, 'detail':detail, 'materials': materialsArray, 'consumables':consumablesArray, 'electrics':electricsArray, 'workforces':manosArray, 'tornos':tornosArray, 'dias':diasArray});

                    var card = button.parent().parent().parent();
                    card.removeClass('card-gray-dark');
                    card.addClass('card-success');
                    $items = [];
                    $.alert("Productos confirmado!");

                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $equipmentStatus = false;
                    $.alert("Confirmación cancelada.");
                },
            },
        },
    });

}

function updateTableTotalsEquipment(button, data) {
    var quantity = data.quantity;
    var materiales = data.materials;
    var consumibles = data.consumables;
    var electrics = data.electrics;
    var serviciosVarios = data.workforces;
    var serviciosAdicionales = data.tornos;
    var diasTrabajo = data.dias;

    var totalMaterials = 0;

    for (let i = 0; i < materiales.length; i++) {
        totalMaterials += parseFloat(materiales[i].total);
    }

    var totalConsumables = 0;

    for (let j = 0; j < consumibles.length; j++) {
        totalConsumables += parseFloat(consumibles[j].total);
    }

    var totalElectrics = 0;

    for (let j = 0; j < electrics.length; j++) {
        totalElectrics += parseFloat(electrics[j].total);
    }

    var totalWorkforces = 0;

    for (let k = 0; k < serviciosVarios.length; k++) {
        totalWorkforces += parseFloat(serviciosVarios[k].total);
    }

    var totalTornos = 0;

    for (let l = 0; l < serviciosAdicionales.length; l++) {
        totalTornos += parseFloat(serviciosAdicionales[l].total);
    }

    var totalDias = 0;

    for (let m = 0; m < diasTrabajo.length; m++) {
        totalDias += parseFloat(diasTrabajo[m].total);
    }

    var table = button.parent().parent().next().children().next().next().next().next().next().children().next().children();

    var totalMaterialsElement = table.find('[data-total_materials]');
    //totalMaterialsElement.html((totalMaterials*quantity).toFixed(2));
    totalMaterialsElement.html((totalMaterials).toFixed(2));
    totalMaterialsElement.css('text-align', 'right');

    var totalConsumablesElement = table.find('[data-total_consumables]');
    //totalConsumablesElement.html((totalConsumables*quantity).toFixed(2));
    totalConsumablesElement.html((totalConsumables).toFixed(2));
    totalConsumablesElement.css('text-align', 'right');

    var totalElectricsElement = table.find('[data-total_electrics]');
    //totalConsumablesElement.html((totalConsumables*quantity).toFixed(2));
    totalElectricsElement.html((totalElectrics).toFixed(2));
    totalElectricsElement.css('text-align', 'right');

    var totalWorkforcesElement = table.find('[data-total_workforces]');
    //totalWorkforcesElement.html((totalWorkforces*quantity).toFixed(2));
    totalWorkforcesElement.html((totalWorkforces).toFixed(2));
    totalWorkforcesElement.css('text-align', 'right');

    var totalTornosElement = table.find('[data-total_tornos]');
    //totalTornosElement.html((totalTornos*quantity).toFixed(2));
    totalTornosElement.html((totalTornos).toFixed(2));
    totalTornosElement.css('text-align', 'right');

    var totalDiasElement = table.find('[data-total_dias]');
    //totalDiasElement.html((totalDias*quantity).toFixed(2));
    totalDiasElement.html((totalDias).toFixed(2));
    totalDiasElement.css('text-align', 'right');
}

function mayus(e) {
    e.value = e.value.toUpperCase();
}

function calculateMargen(e) {
    var margen = e.value;

    var letter = $('#letter').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#subtotal2').html('USD '+$subtotal);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);
}

function calculateLetter(e) {
    var letter = e.value;
    var margen = $('#utility').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);
}

function calculateRent(e) {
    var rent = e.value;
    var margen = $('#utility').val();
    var letter = $('#letter').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#total').html('USD '+$subtotal3);

}

function calculateMargen2(margen) {
    var letter = $('#letter').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#subtotal2').html('USD '+$subtotal);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateLetter2(letter) {
    var margen = $('#utility').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateRent2(rent) {
    var margen = $('#utility').val();
    var letter = $('#letter').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#total').html('USD '+$subtotal3);

}

function calculateTotal(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);

}

function calculateTotal2(e) {
    var precio = e.value;
    var cantidad = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);

}

function calculateTotalQuatity(e) {
    var cantidad = e.value;
    var hour = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    var price = e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value;

    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);

}

function calculateTotalHour(e) {
    var cantidad = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    var hour = e.value;
    var price = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);

}

function calculateTotalC(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value;
    // CON IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);
    // SIN IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = ((parseFloat(cantidad)*parseFloat(precio))/1.18).toFixed(2);

}

function calculateTotalE(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value;
    // CON IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);
    // SIN IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = ((parseFloat(cantidad)*parseFloat(precio))/1.18).toFixed(2);

}

function calculateTotalPrice(e) {
    var cantidad = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;
    var hour = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    var price = e.value;
    console.log(cantidad);
    console.log(hour);
    console.log(price);
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);
    console.log(e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value);
}

function addEquipment() {
    renderTemplateEquipment();
    $('.materialTypeahead').typeahead('destroy');
    $('.materialTypeahead').typeahead({
            hint: true,
            highlight: true, /* Enable substring highlighting */
            minLength: 1 /* Specify minimum characters required for showing suggestions */
        },
        {
            limit: 12,
            source: substringMatcher($materialsTypeahead)
        });
    /*for (var i=0; i<$materials.length; i++)
    {
        var newOption = new Option($materials[i].full_description, $materials[i].id, false, false);
        $('.material_search').append(newOption).trigger('change');
    }*/

    $('.consumable_search').select2({
        placeholder: 'Selecciona un consumible',
        ajax: {
            url: '/dashboard/select/consumables',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                //console.log(data);
                return {
                    results: $.map(data, function (item) {
                        //console.log(item.full_description);
                        return {
                            text: item.full_description,
                            id: item.id,
                        }
                    })
                }
            }
        }
    });
    //$equipmentStatus = false;

    $('.electric_search').select2({
        placeholder: 'Selecciona un material',
        ajax: {
            url: '/dashboard/select/consumables',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                //console.log(data);
                return {
                    results: $.map(data, function (item) {
                        //console.log(item.full_description);
                        return {
                            text: item.full_description,
                            id: item.id,
                        }
                    })
                }
            }
        }
    });

    $('.textarea_edit').summernote({
        lang: 'es-ES',
        placeholder: 'Ingrese los detalles',
        tabsize: 2,
        height: 120,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
            ['view', ['codeview', 'help']]
        ]
    });
}

function deleteItem() {
    //console.log($(this).parent().parent().parent());
    var card = $(this).parent().parent().parent().parent().parent().parent().parent();
    card.removeClass('card-success');
    card.addClass('card-gray-dark');
    $(this).parent().parent().remove();
    var itemId = $(this).data('delete');
    //$items = $items.filter(item => item.id !== itemId);
}

function calculatePercentage() {
    if( $('#material_length_entered').val().trim() === '' && $("#quantity_entered_material").css('display') === 'none' )
    {
        toastr.error('Debe ingresar la longitud del material', 'Error',
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
        return;
    }
    if( $('#material_width_entered').val().trim() === '' && $("#quantity_entered_material").css('display') === 'none' && $("#width_entered_material").css('display') !== 'none' )
    {
        toastr.error('Debe ingresar el ancho del material', 'Error',
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
        return;
    }
    if( $('#material_quantity_entered').val().trim() === '' && $("#quantity_entered_material").attr('style') === '' )
    {
        toastr.error('Debe ingresar la cantidad del material', 'Error',
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
        return;
    }

    if ($('#material_length_entered').val().trim() !== '' && $('#material_width_entered').val().trim() !== '')
    {
        var price_material = parseFloat($('#material_price').val());
        var length_material = parseFloat($('#material_length').val());
        var width_material = parseFloat($('#material_width').val());
        var length = parseFloat($('#material_length_entered').val());
        var width = parseFloat($('#material_width_entered').val());
        var areaTotal = length_material*width_material;
        var areaNueva = length*width;
        var percentage = parseFloat(areaNueva/areaTotal).toFixed(2);
        var new_price = parseFloat(percentage*price_material).toFixed(2);
        $('#material_percentage_entered').val(percentage);
        $('#material_price_entered').val(new_price);
    }

    if ($('#material_length_entered').val().trim() !== '' && $("#width_entered_material").css('display') === 'none' )
    {
        var price_material2 = parseFloat($('#material_price').val());
        var length_material2 = parseFloat($('#material_length').val());

        var length2 = parseFloat($('#material_length_entered').val());

        var percentage2 = parseFloat(length2/length_material2).toFixed(2);
        var new_price2 = parseFloat(percentage2*price_material2).toFixed(2);
        $('#material_percentage_entered').val(percentage2);
        $('#material_price_entered').val(new_price2);
    }

    if ( $('#material_quantity_entered').val().trim() !== '' )
    {
        var price_material3 = parseFloat($('#material_price').val());
        var quantity_entered = parseFloat($('#material_quantity_entered').val());
        var new_price3 = parseFloat(quantity_entered*price_material3).toFixed(2);
        $('#material_percentage_entered').val(quantity_entered);
        $('#material_price_entered').val(new_price3);

    }
}

function addTableMaterials() {
    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
        if( $('#material_length_entered').val().trim() === '' && $("#length_entered_material").attr('style') === '' )
        {
            toastr.error('Debe ingresar la longitud del material', 'Error',
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
            return;
        }
        if( $('#material_width_entered').val().trim() === '' && $("#width_entered_material").attr('style') === '' )
        {
            toastr.error('Debe ingresar el ancho del material', 'Error',
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
            return;
        }
        if( $("#material_quantity_entered").css('display') === '' && $('#material_quantity_entered').val().trim() === '' )
        {
            toastr.error('Debe ingresar la cantidad del material', 'Error',
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
            return;
        }
        if( $('#material_percentage_entered').val().trim() === '' )
        {
            toastr.error('Debe hacer click en calcular', 'Error',
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
            return;
        }
        if( $('#material_price_entered').val().trim() === '' )
        {
            toastr.error('Debe hacer click en calcular', 'Error',
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
            return;
        }

        var material_quantity = ($("#material_quantity_entered").css('display') === '') ? $("#material_quantity_entered").val(): $("#material_percentage_entered").val();
        var total = $("#material_price_entered").val();
        var length = $('#material_length_entered').val();
        var witdh = $('#material_width_entered').val();

        //$items.push({ 'id': $items.length+1, 'material': $material, 'material_quantity': material_quantity, 'material_price':total, 'material_length':length, 'material_width':witdh});
        //console.log($renderMaterial);
        //renderTemplateMaterial($material.code, $material.full_description, material_quantity, $material.unit_measure.name, $material.unit_price, total, $renderMaterial, length, witdh, $material);
        renderTemplateMaterial($material.code, $material.full_name, material_quantity, $material.unit_measure.name, $material.unit_price, total, $renderMaterial, length, witdh, $material);

        $('#material_length_entered').val('');
        $('#material_width_entered').val('');
        $('#material_percentage_entered').val('');
        $('#material_price_entered').val('');
        $('#material_quantity_entered').val('');
        $(".material_search").empty().trigger('change');
        $modalAddMaterial.modal('hide');
    } else {
        if( $('#material_length_entered').val().trim() === '' && $("#length_entered_material").attr('style') === '' )
        {
            toastr.error('Debe ingresar la longitud del material', 'Error',
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
            return;
        }
        if( $('#material_width_entered').val().trim() === '' && $("#width_entered_material").attr('style') === '' )
        {
            toastr.error('Debe ingresar el ancho del material', 'Error',
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
            return;
        }
        if( $("#material_quantity_entered").css('display') === '' && $('#material_quantity_entered').val().trim() === '' )
        {
            toastr.error('Debe ingresar la cantidad del material', 'Error',
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
            return;
        }
        if( $('#material_percentage_entered').val().trim() === '' )
        {
            toastr.error('Debe hacer click en calcular', 'Error',
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
            return;
        }

        var material_quantity2 = ($("#material_quantity_entered").css('display') === '') ? $("#material_quantity_entered").val(): $("#material_percentage_entered").val();
        var length2 = $('#material_length_entered').val();
        var witdh2 = $('#material_width_entered').val();
        console.log($renderMaterial);
        //$items.push({ 'id': $items.length+1, 'material': $material, 'material_quantity': material_quantity2, 'material_price':0, 'material_length':length2, 'material_width':witdh2});
        //renderTemplateMaterial($material.code, $material.full_description, material_quantity2, $material.unit_measure.name, $material.unit_price, 0, $renderMaterial, length2, witdh2, $material);
        renderTemplateMaterial($material.code, $material.full_name, material_quantity2, $material.unit_measure.name, $material.unit_price, 0, $renderMaterial, length2, witdh2, $material);

        $('#material_length_entered').val('');
        $('#material_width_entered').val('');
        $('#material_percentage_entered').val('');
        $('#material_quantity_entered').val('');
        $(".material_search").empty().trigger('change');
        $modalAddMaterial.modal('hide');
    }

}

function addMaterial() {
    var select_material = $(this).parent().parent().children().children().children().next();
    // TODO: Tomar el texto no el val()
    var material_search = select_material.val();

    $material = $materials.find( mat=>mat.id === parseInt(material_search) );

    if( $material === undefined )
    {
        toastr.error('Debe seleccionar un material', 'Error',
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
        return;
    }

    for (var i=0; i<$items.length; i++)
    {
        var mat = $items.find( mat=>mat.material.id == $material.id );
        if (mat !== undefined)
        {
            toastr.error('Este material ya esta seleccionado', 'Error',
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
            return;
        }
    }

    if ( $material.type_scrap === null )
    {
        $('#presentation').hide();
        $('#length_material').hide();
        $('#width_material').hide();
        $('#width_entered_material').hide();
        $('#length_entered_material').hide();
        $('#material_quantity').val($material.stock_current);
        $('#quantity_entered_material').show();
        $('#material_price').val($material.unit_price);

        $renderMaterial = $(this).parent().parent().next().next().next();

        $modalAddMaterial.modal('show');
    } else {
        switch($material.type_scrap.id) {
            case 1:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#width_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#material_width').val($material.type_scrap.width);
                $('#material_quantity').val($material.stock_current);
                $('#quantity_entered_material').hide();
                $('#material_price').val($material.unit_price);
                break;
            case 2:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#width_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#material_width').val($material.type_scrap.width);
                $('#quantity_entered_material').hide();
                $('#material_quantity').val($material.stock_current);
                $('#material_price').val($material.unit_price);
                break;
            case 3:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#width_material').hide();
                $('#width_entered_material').hide();
                $('#quantity_entered_material').hide();
                $('#material_quantity').val($material.stock_current);
                $('#material_price').val($material.unit_price);
                break;
            case 4:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#width_material').hide();
                $('#width_entered_material').hide();
                $('#quantity_entered_material').hide();
                $('#material_quantity').val($material.stock_current);
                $('#material_price').val($material.unit_price);
                break;
            case 5:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#width_material').hide();
                $('#width_entered_material').hide();
                $('#quantity_entered_material').hide();
                $('#material_quantity').val($material.stock_current);
                $('#material_price').val($material.unit_price);
                break;
            case 6:
                $('#presentation').show();
                $("#fraction").prop("checked", true);
                $('#length_entered_material').show();
                $('#width_entered_material').show();
                $('#material_length').val($material.type_scrap.length);
                $('#material_width').val($material.type_scrap.width);
                $('#material_quantity').val($material.stock_current);
                $('#quantity_entered_material').hide();
                $('#material_price').val($material.unit_price);
                break;
            default:
                $('#length_material').hide();
                $('#width_material').hide();
                $('#width_entered_material').hide();
                $('#length_entered_material').hide();
                $('#material_quantity').val($material.stock_current);
                $('#material_percentage_entered').hide();
                $('#material_price').val($material.unit_price);

        }
        //var idMaterial = $(this).select2('data').id;

        $renderMaterial = $(this).parent().parent().next().next().next();

        $modalAddMaterial.modal('show');
    }


}

function editedActive() {
    var flag = false;
    $(document).find('[data-equip]').each(function(){
        console.log($(this));
        if ($(this).hasClass('card-gray-dark'))
        {
            flag = true;
        }
    });

    return flag;
}

function storeQuote() {
    event.preventDefault();
    $("#btn-submit").attr("disabled", true);
    if ( editedActive() )
    {
        toastr.error('No se puede guardar porque hay productos no confirmados.', 'Error',
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
        $("#btn-submit").attr("disabled", false);
        return;
    }
    /*if( $equipments.length === 0 )
    {
        toastr.error('No se puede agregar más equipos si no existen.', 'Error',
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
        return;
    }*/
    // Obtener la URL
    var createUrl = $formCreate.data('url');
    var equipos = JSON.stringify($equipments);
    var formulario = $('#formEdit')[0];
    var form = new FormData(formulario);
    form.append('equipments', equipos);
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
            console.log(data);
            toastr.success(data.message, 'Éxito',
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
            setTimeout( function () {
                $("#btn-submit").attr("disabled", false);
                location.reload();
            }, 2000 )
        },
        error: function (data) {
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
            $("#btn-submit").attr("disabled", false);

        },
    });
}

function calculateTotalMaterialQuantity(e) {
    var cantidad = e.value;
    var material_id = e.getAttribute('material_id');
    console.log(material_id);
    var igvRate = 0.18;

    var width = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    var length = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;

    var material = $materials.find( mat=>mat.id === parseInt(material_id) );

    if ( material.type_scrap == null )
    {
        var newPriceConIgv = parseFloat(cantidad*material.unit_price).toFixed(2);

        var newPriceSinIgv = parseFloat(newPriceConIgv / (1 + igvRate)).toFixed(2);

        var newPriceConIgvTotal = parseFloat(material.unit_price).toFixed(2);

        var newPriceSinIgvTotal = parseFloat(newPriceConIgvTotal / (1 + igvRate)).toFixed(2);

        //var priceSinIgv =
        e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotal;
        //var priceConIgv =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotal;
        //var priceSinIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgv ;
        //var priceConIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgv ;

    } else {

        // TODO: Si es tubo
        if (material && material.type_scrap && (material.type_scrap.id === 3 || material.type_scrap.id === 4 || material.type_scrap.id === 5))
        {
            if ( length == null || length == '' )
            {
                // TODO: Solo colocaron cantidad
                var newPriceConIgvT = parseFloat(cantidad*material.unit_price).toFixed(2);

                var newPriceSinIgvT = parseFloat(newPriceConIgvT / (1 + igvRate)).toFixed(2);

                var newPriceConIgvTotalT = parseFloat(material.unit_price).toFixed(2);

                var newPriceSinIgvTotalT = parseFloat(newPriceConIgvTotalT / (1 + igvRate)).toFixed(2);

                //var priceSinIgv =
                e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalT;
                //var priceConIgv =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalT;
                //var priceSinIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvT ;
                //var priceConIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvT ;

            } else {
                // TODO: Solo colocaron largo
                var lengthOriginalMaterial = material.type_scrap.length;
                var newLength = parseFloat(cantidad*lengthOriginalMaterial).toFixed(2);

                // Actualizamos la cantidad automaticamente
                e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value = newLength;

                var newPriceConIgvT2 = parseFloat(cantidad*material.unit_price).toFixed(2);

                var newPriceSinIgvT2 = parseFloat(newPriceConIgvT2 / (1 + igvRate)).toFixed(2);

                var newPriceConIgvTotalT2 = parseFloat(material.unit_price).toFixed(2);

                var newPriceSinIgvTotalT2 = parseFloat(newPriceConIgvTotalT2 / (1 + igvRate)).toFixed(2);

                //var priceSinIgv =
                e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalT2;
                //var priceConIgv =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalT2;
                //var priceSinIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvT2 ;
                //var priceConIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvT2 ;

            }

        } else {

            // TODO: Si es plancha
            if ( length == "" || width == "" )
            {
                // TODO: Solo colocaron cantidad
                var newPriceConIgvP = parseFloat(cantidad*material.unit_price).toFixed(2);

                var newPriceSinIgvP = parseFloat(newPriceConIgvP / (1 + igvRate)).toFixed(2);

                var newPriceConIgvTotalP = parseFloat(material.unit_price).toFixed(2);

                var newPriceSinIgvTotalP = parseFloat(newPriceConIgvTotalP / (1 + igvRate)).toFixed(2);

                //var priceSinIgv =
                e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalP;
                //var priceConIgv =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalP;
                //var priceSinIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvP ;
                //var priceConIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvP ;

            } else {
                // TODO: Colocaron largo y ancho, no se puede asi que seteamos el largo y ancho a 0
                var newLengthP = 0;

                var newWidthP = 0;

                // Actualizamos la cantidad automaticamente

                e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value = newLengthP;
                e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value = newWidthP;

                var newPriceConIgvP2 = parseFloat(cantidad*material.unit_price).toFixed(2);

                var newPriceSinIgvP2 = parseFloat(newPriceConIgvP2 / (1 + igvRate)).toFixed(2);

                var newPriceConIgvTotalP2 = parseFloat(material.unit_price).toFixed(2);

                var newPriceSinIgvTotalP2 = parseFloat(newPriceConIgvTotalP2 / (1 + igvRate)).toFixed(2);

                //var priceSinIgv =
                e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalP2;
                //var priceConIgv =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalP2;
                //var priceSinIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvP2 ;
                //var priceConIgvTotal =
                e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvP2 ;

            }
        }
    }


}

function calculateTotalMaterialLargo(e) {
    var largo = e.value;
    var material_id = e.getAttribute('material_id');
    console.log(material_id);
    var igvRate = 0.18;

    var width = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    //var length = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;

    var material = $materials.find( mat=>mat.id === parseInt(material_id) );

    // TODO: Si es tubo
    if (material && material.type_scrap && (material.type_scrap.id === 3 || material.type_scrap.id === 4 || material.type_scrap.id === 5))
    {

        // TODO: Solo colocaron cantidad
        var lengthOriginalMaterial = material.type_scrap.length;
        var cantidad = parseFloat(largo/lengthOriginalMaterial).toFixed(2);

        var newPriceConIgvT = parseFloat(cantidad*material.unit_price).toFixed(2);

        var newPriceSinIgvT = parseFloat(newPriceConIgvT / (1 + igvRate)).toFixed(2);

        var newPriceConIgvTotalT = parseFloat(material.unit_price).toFixed(2);

        var newPriceSinIgvTotalT = parseFloat(newPriceConIgvTotalT / (1 + igvRate)).toFixed(2);

        //var cantidad =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = cantidad;
        //var priceSinIgv =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalT;
        //var priceConIgv =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalT;
        //var priceSinIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvT ;
        //var priceConIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvT ;

    } else {

        // TODO: Si es plancha falta
        var lengthOriginalMaterialP = material.type_scrap.length;
        var widthOriginalMaterialP = material.type_scrap.width;

        var areaOriginal = lengthOriginalMaterialP*widthOriginalMaterialP;

        var areaNew = largo*width;

        var cantidadP = parseFloat(areaNew/areaOriginal).toFixed(2);

        var newPriceConIgvP = parseFloat(cantidadP*material.unit_price).toFixed(2);

        var newPriceSinIgvP = parseFloat(newPriceConIgvP / (1 + igvRate)).toFixed(2);

        var newPriceConIgvTotalP = parseFloat(material.unit_price).toFixed(2);

        var newPriceSinIgvTotalP = parseFloat(newPriceConIgvTotalP / (1 + igvRate)).toFixed(2);

        //var cantidad =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = cantidadP;
        //var priceSinIgv =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalP;
        //var priceConIgv =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalP;
        //var priceSinIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvP ;
        //var priceConIgvTotal =
        e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvP ;

    }
}

function calculateTotalMaterialAncho(e) {
    var ancho = e.value;
    var material_id = e.getAttribute('material_id');
    console.log(material_id);
    var igvRate = 0.18;

    var length = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    //var length = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;

    var material = $materials.find( mat=>mat.id === parseInt(material_id) );

    // TODO: Si es plancha falta
    var lengthOriginalMaterialP = material.type_scrap.length;
    var widthOriginalMaterialP = material.type_scrap.width;

    var areaOriginal = lengthOriginalMaterialP*widthOriginalMaterialP;

    var areaNew = length*ancho;

    var cantidadP = parseFloat(areaNew/areaOriginal).toFixed(2);

    var newPriceConIgvP = parseFloat(cantidadP*material.unit_price).toFixed(2);

    var newPriceSinIgvP = parseFloat(newPriceConIgvP / (1 + igvRate)).toFixed(2);

    var newPriceConIgvTotalP = parseFloat(material.unit_price).toFixed(2);

    var newPriceSinIgvTotalP = parseFloat(newPriceConIgvTotalP / (1 + igvRate)).toFixed(2);

    //var cantidad =
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = cantidadP;
    //var priceSinIgv =
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvTotalP;
    //var priceConIgv =
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvTotalP;
    //var priceSinIgvTotal =
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceSinIgvP ;
    //var priceConIgvTotal =
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = newPriceConIgvP ;

}

/*function renderTemplateConsumable(render, consumable, quantity, discount) {

    console.log("renderTemplateConsumable");
    console.log("consumable");
    console.log(consumable);
    console.log("quantity");
    console.log(quantity);

    var card = render.parent().parent().parent().parent();
    card.removeClass('card-success');
    card.addClass('card-gray-dark');
    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
        var clone = activateTemplate('#template-consumable');
        //console.log(consumable.stock_current );

        if ( consumable.enable_status == 0 )
        {
            clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
            clone.querySelector("[data-consumableDescription]").setAttribute("style", "color:purple;");

        } else {
            if ( consumable.stock_current == 0 )
            {
                clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                clone.querySelector("[data-consumableDescription]").setAttribute("style", "color:red;");
            } else {
                if ( consumable.state_update_price == 1 )
                {
                    clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                    clone.querySelector("[data-consumableDescription]").setAttribute("style", "color:blue;");
                } else {
                    clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                }

            }
        }

        let precioBase = parseFloat(consumable.list_price);
        console.log("igv");
        console.log($igv);
        let valorUnitario = precioBase/((100+parseFloat($igv))/100);
        //let precioUnitario = precioBase;
        let importeTotal = precioBase * parseFloat(quantity);

        clone.querySelector("[data-consumableId]").setAttribute('data-consumableId', consumable.id);
        clone.querySelector("[data-descuento]").setAttribute('data-descuento', (parseFloat(discount)).toFixed(2));
        clone.querySelector("[data-consumableUnit]").setAttribute('value', consumable.unit_measure.description);
        clone.querySelector("[data-consumableQuantity]").setAttribute('value', (parseFloat(quantity)).toFixed(2));

        clone.querySelector("[data-consumableValor]").setAttribute('value', (parseFloat(valorUnitario).toFixed(2)));
        clone.querySelector("[data-consumablePrice]").setAttribute('value', (parseFloat(precioBase).toFixed(2)));
        clone.querySelector("[data-consumableImporte]").setAttribute('value', (parseFloat(importeTotal).toFixed(2)));

        render.append(clone);
    } else {
        var clone2 = activateTemplate('#template-consumable');
        //console.log(consumable.stock_current );

        if ( consumable.enable_status == 0 )
        {
            clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
            clone2.querySelector("[data-consumableDescription]").setAttribute("style", "color:purple;");

        } else {
            if ( consumable.stock_current == 0 )
            {
                clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                clone2.querySelector("[data-consumableDescription]").setAttribute("style", "color:red;");
            } else {
                if ( consumable.state_update_price == 1 )
                {
                    clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                    clone2.querySelector("[data-consumableDescription]").setAttribute("style", "color:blue;");
                } else {
                    clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
                }

                //clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
            }
        }

        let precioBase = parseFloat(consumable.list_price);
        let valorUnitario = precioBase/((100+parseFloat($igv))/100);
        //let precioUnitario = precioBase;
        let importeTotal = precioBase * parseFloat(quantity);

        clone2.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
        clone2.querySelector("[data-consumableId]").setAttribute('data-consumableId', consumable.id);
        clone2.querySelector("[data-descuento]").setAttribute('data-descuento', (parseFloat(discount)).toFixed(2));
        clone2.querySelector("[data-consumableUnit]").setAttribute('value', consumable.unit_measure.description);
        clone2.querySelector("[data-consumableQuantity]").setAttribute('value', (parseFloat(quantity)).toFixed(2));

        clone2.querySelector("[data-consumableValor]").setAttribute('value', (parseFloat(valorUnitario).toFixed(2)));
        clone2.querySelector("[data-consumablePrice]").setAttribute('value', (parseFloat(precioBase).toFixed(2)));
        clone2.querySelector("[data-consumableImporte]").setAttribute('value', (parseFloat(importeTotal).toFixed(2)));
        clone2.querySelector("[data-consumableValor]").setAttribute("style","display:none;");
        clone2.querySelector("[data-consumablePrice]").setAttribute("style","display:none;");
        clone2.querySelector("[data-consumableImporte]").setAttribute("style","display:none;");

        clone2.querySelector("[data-deleteConsumable]").setAttribute('data-deleteConsumable', consumable.id);
        render.append(clone2);
    }


}*/

function renderTemplateEquipment() {
    var clone = activateTemplate('#template-equipment');

    $('#body-equipment').append(clone);

    $('.unitMeasure').select2({
        placeholder: "Seleccione unidad",
    });
}

function renderTemplateSummary(equipments) {
    console.log('Se renderiza');
    console.log(equipments);
    $('#body-summary').html('');
    var equipos = equipments.sort(function (a, b) {
        if (a.id > b.id) {
            return 1;
        }
        if (a.id < b.id) {
            return -1;
        }
        // a must be equal to b
        return 0;
    });
    console.log(equipos);
    console.log(equipos.length);
    for (let i = 0; i < equipos.length; i++) {
        //console.log(equipments[i]);
        var clone = activateTemplate('#template-summary');
        var price = ((parseFloat(equipos[i].total)/parseFloat(equipos[i].quantity))/1.18).toFixed(2);
        var totalE = (parseFloat(equipos[i].total)/1.18).toFixed(2);

        var subtotalUtility = totalE*((parseFloat(equipos[i].utility)/100)+1);
        var subtotalRent = subtotalUtility*((parseFloat(equipos[i].rent)/100)+1);
        var subtotalLetter = subtotalRent*((parseFloat(equipos[i].letter)/100)+1);

        clone.querySelector("[data-qEquipment]").innerHTML = equipos[i].quantity;
        clone.querySelector("[data-pEquipment]").innerHTML = parseFloat(price).toFixed(2);
        clone.querySelector("[data-uEquipment]").innerHTML = equipos[i].utility;
        //clone.querySelector("[data-uPEquipment]").innerHTML = parseFloat(subtotalUtility).toFixed(2);
        clone.querySelector("[data-rlEquipment]").innerHTML = (parseFloat(equipos[i].rent) + parseFloat(equipos[i].letter)).toFixed(2);
        clone.querySelector("[data-uPEquipment]").innerHTML = (parseFloat(subtotalLetter)/parseFloat(equipos[i].quantity)).toFixed(2);
        clone.querySelector("[data-tEquipment]").innerHTML = parseFloat(subtotalLetter).toFixed(2);

        if ( equipos[i].quote == '' && equipos[i].equipment == '' )
        {
            clone.querySelector("[data-acEdit]").setAttribute('style', 'display:none');
        } else {
            clone.querySelector("[data-acEdit]").setAttribute( 'acEdit', equipos[i].quote);
            clone.querySelector("[data-acEdit]").setAttribute( 'acEquipment', equipos[i].equipment);
            clone.querySelector("[data-acEdit]").setAttribute( 'utility', equipos[i].utility);
            clone.querySelector("[data-acEdit]").setAttribute( 'rent', equipos[i].rent);
            clone.querySelector("[data-acEdit]").setAttribute( 'letter', equipos[i].letter);
        }

        $('#body-summary').append(clone);
    }

}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}