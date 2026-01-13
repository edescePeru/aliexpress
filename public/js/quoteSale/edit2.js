/* =========================================================
   quoteSale/edit.js (REESTRUCTURADO)
   - 1 solo equipo (data-equip)
   - Recalcula totales en vivo (descuento/gravada/igv/total)
   - Muestra alerta de cambios (#alert_edit)
   - Marca card como "dirty" (card-gray-dark) hasta guardar
   ========================================================= */

let $consumables = [];
let $equipments = [];
let $permissions = [];
let $igv = $("#igv").val();

var $modalConsumableQty = $('#modalQuantityConsumable');
var $currentConsumableRender = null;
var $currentConsumable = null;

function fetchPresentations(materialId) {
    return $.ajax({
        url: `/dashboard/materials-presentations/material/${materialId}/presentations`,
        method: 'GET',
        dataType: 'json'
    }).then(function(res){
        console.log(res);
        return res.presentations || [];
    });
}

function addConsumable() {
    console.log("Vamos a agregar consumable");
    var consumableID = $(this).parent().parent().find('[data-consumable]').val();
    if (!consumableID) {
        toastr.error('Debe seleccionar un producto', 'Error');
        return;
    }

    // contenedor donde se agregan filas
    var render = $(this).parent().parent().next().next();

    var consumable = $consumables.find(mat => mat.id === parseInt(consumableID));
    if (!consumable) {
        toastr.error('Producto no encontrado', 'Error');
        return;
    }

    // limpiar UI
    $(this).parent().parent().find('[data-cantidad]').val(0);
    $(".consumable_search").empty().trigger('change');

    // abrir modal
    showModalQuantityConsumable(render, consumable);
}

function showModalQuantityConsumable(render, consumable) {
    $currentConsumableRender = render;
    $currentConsumable = consumable;

    $('#c_quantity_productId').val(consumable.id);
    $('#c_quantity_total').val(0);
    $('#c_quantity_stock_show').val(consumable.stock_current);
    $('#c_presentationsArea').html('<div class="text-muted">Cargando presentaciones...</div>');

    fetchPresentations(consumable.id)
        .then(function(presentations) {
            let actives = presentations.filter(x => x.active === true || x.active === 1 || x.active === "1");
            renderPresentationsInModalConsumable(actives);
            $modalConsumableQty.modal('show');
        })
        .catch(function() {
            $('#c_presentationsArea').html('<div class="text-danger">No se pudo cargar presentaciones.</div>');
            $modalConsumableQty.modal('show');
        });
}

function renderPresentationsInModalConsumable(presentations) {
    if (!presentations || presentations.length === 0) {
        $('#c_presentationsArea').html('<div class="text-muted">Este producto no tiene presentaciones configuradas.</div>');
        return;
    }

    let html = `
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead>
          <tr>
            <th style="width:45%;">Presentación</th>
            <th style="width:25%;">Precio</th>
            <th style="width:30%;">Paquetes</th>
          </tr>
        </thead>
        <tbody>
  `;

    presentations.forEach(p => {
        const label = (p.label && p.label.trim()) ? p.label : `${p.quantity} und`;

        html += `
      <tr data-pres-row
          data-pres-id="${p.id}"
          data-pres-qty="${p.quantity}"
          data-pres-price="${p.price}"
          data-pres-label="${label}">
        <td>
          <strong>${label}</strong>
          <div class="text-muted" style="font-size:12px;">Equivale a ${p.quantity} unidades</div>
        </td>
        <td>S/. ${parseFloat(p.price).toFixed(2)}</td>
        <td>
          <input type="number" min="0" step="1"
                 class="form-control form-control-sm"
                 value="0" data-pres-packs>
        </td>
      </tr>
    `;
    });

    html += `</tbody></table></div>`;
    $('#c_presentationsArea').html(html);
}

function renderTemplateConsumable(render, consumable, qtyVisible, pricePU, discount, isPrice, pres) {
    var clone = activateTemplate('#template-consumable');

    // visibles
    clone.querySelector("[data-consumableDescription]").value = consumable.full_description;
    clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.description;
    clone.querySelector("[data-consumableQuantity]").value = parseFloat(qtyVisible).toFixed(2);
    clone.querySelector("[data-consumablePrice]").value = parseFloat(pricePU).toFixed(2);

    // V/U e importe inicial
    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct/100);
    clone.querySelector("[data-consumableValor]").value = (parseFloat(pricePU)/igvFactor).toFixed(2);
    clone.querySelector("[data-consumableImporte]").value = (parseFloat(qtyVisible) * parseFloat(pricePU)).toFixed(2);

    // data attrs
    $(clone).find('[data-consumableId]').attr('data-consumableid', consumable.id);
    $(clone).find('[data-descuento]').attr('data-descuento', parseFloat(discount || 0).toFixed(2));
    $(clone).find('[data-type_promotion]').attr('data-type_promotion', 'ninguno');

    // presentación meta
    if (pres) {
        clone.querySelector("[data-presentation_text]").value = pres.text;

        $(clone).find('[data-presentation_id]').attr('data-presentation_id', pres.id);
        $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', pres.unitsPerPack);
        $(clone).find('[data-units_equivalent]').attr('data-units_equivalent', pres.unitsEquivalent);
        $(clone).find('[data-packs]').attr('data-packs', pres.packs);

        // step 1 para packs
        clone.querySelector("[data-consumableQuantity]").setAttribute('step', '1');
    } else {
        clone.querySelector("[data-presentation_text]").value = "Unidad";

        $(clone).find('[data-presentation_id]').attr('data-presentation_id', "");
        $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', "");
        $(clone).find('[data-units_equivalent]').attr('data-units_equivalent', parseFloat(qtyVisible));
        $(clone).find('[data-packs]').attr('data-packs', "");
    }

    render.append(clone);

    // ✅ dispara alerta + recalcula totales
    onQuoteChanged();
}

// ----------------------
// Helpers: redondeo
// ----------------------
function round2(num) {
    return Math.round((parseFloat(num || 0) + Number.EPSILON) * 100) / 100;
}

// ----------------------
// Dirty state + alerta
// ----------------------
function getMainEquipCard() {
    return $('[data-equip]').first();
}

function setDirty() {
    $('#alert_edit').show();
    const $card = getMainEquipCard();
    $card.attr('data-dirty', '1').removeClass('card-success').addClass('card-gray-dark');
}

function setClean() {
    $('#alert_edit').hide();
    const $card = getMainEquipCard();
    $card.attr('data-dirty', '0').removeClass('card-gray-dark').addClass('card-success');
}

// Llamada única por cualquier cambio
function onQuoteChanged() {
    setDirty();
    recalcQuoteTotalsFromDom();
}

// ----------------------
// Lectura DOM: Consumables
// ----------------------
function readConsumablesFromDom($card) {
    const rows = $card.find('[data-consumableDescription]').closest('.row');

    const arr = [];
    let sumImporte = 0;
    let sumPromoDiscount = 0;

    rows.each(function () {
        const $row = $(this);

        const description = ($row.find('[data-consumableDescription]').val() || '').trim();
        if (!description) return;

        const materialId =
            $row.find('[data-consumableid]').attr('data-consumableid') ||
            $row.find('[data-consumableid]').val() ||
            $row.find('[data-consumableId]').attr('data-consumableid') ||
            $row.find('[data-consumableId]').val() ||
            null;

        const unit = ($row.find('[data-consumableUnit]').val() || '').trim();

        const qtyVisible = parseFloat($row.find('[data-consumableQuantity]').val() || 0);
        const valor = parseFloat($row.find('[data-consumableValor]').val() || 0);
        const price = parseFloat($row.find('[data-consumablePrice]').val() || 0);
        const importe = parseFloat($row.find('[data-consumableImporte]').val() || 0);

        const discount = parseFloat($row.find('[data-descuento]').attr('data-descuento') || 0);
        const typePromo = $row.find('[data-type_promotion]').attr('data-type_promotion') || null;

        const presentationId = $row.find('[data-presentation_id]').attr('data-presentation_id') || null;
        const unitsPerPack = parseFloat($row.find('[data-units_per_pack]').attr('data-units_per_pack') || 0);
        const unitsEquivalent = parseFloat($row.find('[data-units_equivalent]').attr('data-units_equivalent') || 0);

        arr.push({
            id: materialId,
            description,
            unit,
            quantity: qtyVisible,
            units_equivalent: unitsEquivalent || qtyVisible,
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

// ----------------------
// Lectura DOM: Servicios
// ----------------------
function readServicesFromDom($container) {
    const services = $container.find('[data-serviceRow]');

    const arr = [];
    let sumAll = 0;
    let sumBillable = 0;

    services.each(function () {
        const $row = $(this);

        const desc = ($row.find('[data-serviceDescription]').val() || '').trim();
        if (!desc) return;

        const unit = ($row.find('[data-serviceUnit]').val() || '').trim();
        const qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
        const vu = parseFloat($row.find('[data-serviceVU]').val() || 0);
        const pu = parseFloat($row.find('[data-servicePU]').val() || 0);
        const imp = parseFloat($row.find('[data-serviceImporte]').val() || 0);

        const billable = $row.find('[data-serviceBillable]').is(':checked') ? 1 : 0;

        arr.push({ description: desc, unit, quantity: qty, valor: vu, price: pu, importe: imp, billable });

        sumAll += round2(imp);
        if (billable === 1) sumBillable += round2(imp);
    });

    return { array: arr, sum_all: round2(sumAll), sum_billable: round2(sumBillable) };
}

// ----------------------
// Descuento global (base SIN IGV)
// ----------------------
function computeGlobalDiscountBase(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    if ($d.length === 0) return { base: 0 };

    const type = ($d.attr('data-discount_type') || 'amount');
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv');
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return { base: 0 };

    const factor = 1 + (igvPct / 100);
    const baseSubtotal = subtotalWithIgv / factor;

    let discountBase = 0;

    if (type === 'amount') {
        discountBase = (mode === 'with_igv') ? (value / factor) : value;
    } else {
        const pct = value / 100;
        discountBase = (mode === 'with_igv') ? ((subtotalWithIgv * pct) / factor) : (baseSubtotal * pct);
    }

    if (discountBase > baseSubtotal) discountBase = baseSubtotal;

    return { base: round2(discountBase) };
}

// ----------------------
// Recalcular totales en vista (1 equipo)
// ----------------------
function recalcQuoteTotalsFromDom() {
    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = round2(cRead.sum_importe - cRead.sum_promos);
    const subtotalWithIgv = round2(subtotalConsumables + servicesRead.sum_all);

    const igvPct = parseFloat($igv || 18);
    const factor = 1 + (igvPct / 100);

    const discountBase = computeGlobalDiscountBase(subtotalWithIgv, igvPct).base;

    let baseSubtotal = round2(subtotalWithIgv / factor);
    let baseFinal = round2(baseSubtotal - discountBase);
    if (baseFinal < 0) baseFinal = 0;

    const igvFinal = round2(baseFinal * (igvPct / 100));
    const totalFinal = round2(baseFinal + igvFinal);

    $('#descuento').html(discountBase.toFixed(2));
    $('#gravada').html(baseFinal.toFixed(2));
    $('#igv_total').html(igvFinal.toFixed(2));
    $('#total_importe').html(totalFinal.toFixed(2));
}

// ----------------------
// Servicios: recalcular fila
// ----------------------
function calculateServiceRow(row) {
    const $row = $(row);

    let qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    let pu = parseFloat($row.find('[data-servicePU]').val() || 0);
    if (isNaN(pu) || pu < 0) pu = 0;

    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);

    const vu = (igvFactor > 0) ? (pu / igvFactor) : 0;
    const importe = qty * pu;

    $row.find('[data-serviceVU]').val(vu.toFixed(2));
    $row.find('[data-serviceImporte]').val(importe.toFixed(2));
}

// ----------------------
// Consumables: recalcular fila (packs/unidades)
// ----------------------
function calculateTotalC(input) {
    const row = input.closest('.row');
    if (!row) return;

    let qty = parseFloat(input.value || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);

    const elPrice = row.querySelector('[data-consumablePrice]');
    const elValor = row.querySelector('[data-consumableValor]');
    const elImporte = row.querySelector('[data-consumableImporte]');

    if (!elPrice || !elValor || !elImporte) return;

    const pricePU = parseFloat(elPrice.value || 0);
    const importe = qty * pricePU;

    elImporte.value = importe.toFixed(2);
    elValor.value = (pricePU / igvFactor).toFixed(2);

    const unitsPerPackEl = row.querySelector('[data-units_per_pack]');
    const unitsEqEl = row.querySelector('[data-units_equivalent]');

    const unitsPerPack = unitsPerPackEl ? parseFloat(unitsPerPackEl.getAttribute('data-units_per_pack') || 0) : 0;

    if (unitsPerPack > 0 && unitsEqEl) {
        qty = Math.floor(qty);
        input.value = qty;

        const unitsEquivalent = qty * unitsPerPack;
        unitsEqEl.setAttribute('data-units_equivalent', unitsEquivalent);
    } else if (unitsEqEl) {
        unitsEqEl.setAttribute('data-units_equivalent', qty);
    }

    onQuoteChanged();
}

// ----------------------
// Descuento: sincronizar data-attrs desde UI
// ----------------------
function syncDiscountSectionFromUI() {
    const $d = $('#discountSection');
    const type = $('input[name="discount_type"]:checked').val() || 'amount';
    const mode = $('input[name="discount_input_mode"]:checked').val() || 'without_igv';
    let val = parseFloat($('#discount_value').val() || 0);
    if (isNaN(val) || val < 0) val = 0;

    $d.attr('data-discount_type', type);
    $d.attr('data-discount_input_mode', mode);
    $d.attr('data-discount_value', val.toFixed(2));

    onQuoteChanged();
}

// ----------------------
// Fill initial equipments in memory (1 equipo)
// ----------------------
function fillEquipments() {
    $equipments = [];

    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const quote_id = $('#quote_id').val();
    const idEquipment = $('#btn-saveProducts').attr('data-idEquipment') || null;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = round2(cRead.sum_importe - cRead.sum_promos);
    const subtotalWithIgv = round2(subtotalConsumables + servicesRead.sum_all);

    $equipments.push({
        id: 0,
        quote: quote_id,
        equipment: idEquipment,
        quantity: 1,
        utility: $card.find('[data-utilityequipment]').val() || 0,
        rent: $card.find('[data-rentequipment]').val() || 0,
        letter: $card.find('[data-letterequipment]').val() || 0,
        total: subtotalWithIgv,
        description: "",
        detail: $card.find('[data-detailequipment]').val() || "",
        materials: [],
        consumables: cRead.array,
        electrics: [],
        workforces: servicesRead.array,
        tornos: [],
        dias: []
    });

    recalcQuoteTotalsFromDom();
    setClean();
    $("#element_loader").LoadingOverlay("hide", true);
}

// ----------------------
// Delete handlers
// ----------------------
function deleteConsumable() {
    $(this).closest('.row').remove();
    onQuoteChanged();
}

function deleteServiceRow() {
    $(this).closest('[data-serviceRow]').remove();
    onQuoteChanged();
}

// ----------------------
// Add service (usa template-service)
// ----------------------
function addService() {
    const $cardBody = $(this).closest('.card-body');

    const desc = $cardBody.find('#material_search').val().trim();
    const unitId = $cardBody.find('.unitMeasure').val();
    const unitText = $cardBody.find('.unitMeasure option:selected').text().trim();
    const qty = parseFloat($cardBody.find('#quantity').val() || 0);

    if (!desc) return toastr.error('Debe ingresar una descripción', 'Error');
    if (!unitId) return toastr.error('Debe seleccionar una unidad', 'Error');
    if (!qty || qty <= 0) return toastr.error('Debe ingresar una cantidad', 'Error');

    let pu = 0;
    const $priceInput = $cardBody.find('#price');
    if ($priceInput.length) {
        pu = parseFloat($priceInput.val() || 0);
        if (!pu || pu <= 0) return toastr.error('Debe ingresar un precio válido', 'Error');
    }

    const $render = $cardBody.find('[data-bodyService]');
    const clone = activateTemplate('#template-service');

    clone.querySelector('[data-serviceDescription]').value = desc;
    clone.querySelector('[data-serviceId]').value = '';
    clone.querySelector('[data-serviceUnit]').value = unitText;
    clone.querySelector('[data-serviceQuantity]').value = qty.toFixed(2);
    clone.querySelector('[data-servicePU]').value = pu.toFixed(2);

    $render.append(clone);

    const $lastRow = $render.find('[data-serviceRow]').last();

    const uid = 'billable_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    $lastRow.find('[data-billable-id]').attr('id', uid);
    $lastRow.find('[data-billable-label]').attr('for', uid);
    $lastRow.find('[data-serviceBillable]').prop('checked', true);

    calculateServiceRow($lastRow);

    $cardBody.find('#material_search').val('');
    $cardBody.find('#quantity').val(0);
    if ($priceInput.length) $priceInput.val(0);
    $cardBody.find('.unitMeasure').val(null).trigger('change');

    onQuoteChanged();
}

// ----------------------
// Alert button: guardar cambios en productos (dispara el saveEquipment existente)
// ----------------------
function triggerSaveEquipment() {
    const $btn = $('[data-saveEquipment]').first();
    if ($btn.length) $btn.click();
}

$('#btn-add_consumable_modal').on('click', function () {

    if (!$currentConsumable || !$currentConsumableRender) {
        toastr.error('No hay consumible seleccionado', 'Error');
        return;
    }

    let added = false;

    $('[data-pres-row]').each(function () {
        let packs = parseInt($(this).find('[data-pres-packs]').val() || 0);
        if (packs > 0) {
            added = true;

            let presId = parseInt($(this).attr('data-pres-id'));
            let unitsPerPack = parseInt($(this).attr('data-pres-qty'));
            let presPrice = parseFloat($(this).attr('data-pres-price'));
            let presLabel = $(this).attr('data-pres-label');

            let unitsEquivalent = packs * unitsPerPack;

            renderTemplateConsumable(
                $currentConsumableRender,
                $currentConsumable,
                packs,          // cantidad visible = packs
                presPrice,      // P/U visible = precio pack
                0,
                true,
                {
                    id: presId,
                    text: presLabel,
                    packs: packs,
                    unitsPerPack: unitsPerPack,
                    unitsEquivalent: unitsEquivalent,
                    pricePack: presPrice
                }
            );
        }
    });

    if (!added) {
        let qty = parseFloat($('#c_quantity_total').val() || 0);
        if (qty <= 0) {
            toastr.error('Ingresa cantidad o selecciona una presentación', 'Error');
            return;
        }

        let unitPrice = parseFloat($currentConsumable.list_price);

        renderTemplateConsumable(
            $currentConsumableRender,
            $currentConsumable,
            qty,
            unitPrice,
            0,
            true,
            null
        );
    }

    // ✅ evitar warning de foco
    this.blur();
    document.activeElement && document.activeElement.blur && document.activeElement.blur();

    $modalConsumableQty.modal('hide');
});

// ----------------------
// init
// ----------------------
$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val() || '[]');
    $igv = parseFloat($('#igv').val() || 18);

    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

    fillEquipments();

    // Carga consumables (para select2 + promos)
    $.ajax({
        url: "/dashboard/get/quote/sale/materials/totals",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            $consumables = [];
            for (var i = 0; i < json.length; i++) $consumables.push(json[i]);
        }
    });

    $('.consumable_search').select2({
        placeholder: 'Selecciona un consumible',
        ajax: {
            url: '/dashboard/get/quote/sale/materials',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                return {
                    results: $.map(data, function (item) {
                        return { text: item.full_description, id: item.id };
                    })
                }
            }
        }
    });

    // Eventos: productos
    $(document).on('click', '[data-deleteConsumable]', deleteConsumable);

    // Eventos: servicios
    $(document).on('click', '[data-addService]', addService);
    $(document).on('click', '[data-deleteService]', deleteServiceRow);

    $(document).on('input', '[data-serviceQuantity],[data-servicePU]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        onQuoteChanged();
    });

    $(document).on('change', '[data-serviceBillable]', function () {
        onQuoteChanged();
    });

    // Descuento
    $(document).on('change input', 'input[name="discount_type"], input[name="discount_input_mode"], #discount_value', syncDiscountSectionFromUI);

    $('#btn-clear-discount').on('click', function () {
        $('#discount_type_amount').prop('checked', true);
        $('#discount_mode_without').prop('checked', true);
        $('#discount_value').val(0);
        syncDiscountSectionFromUI();
    });

    // Alert: guardar cambios
    $('#btn-saveProducts').on('click', function(e){
        e.preventDefault();
        saveEquipmentEdit();
    });

    // Modal focus fix
    $('#modalQuantityConsumable').on('hidden.bs.modal', function () {
        document.activeElement && document.activeElement.blur && document.activeElement.blur();
        $('#material_search').focus();
    });

    $(document).on('click', '[data-addConsumable]', addConsumable);
});

function saveEquipmentEdit() {
    const $btn = $('#btn-saveProducts');

    const idQuote = $btn.data('quote');
    const idEquipment = $btn.data('idequipment'); // OJO: en tu HTML es data-idEquipment => jQuery lo lee como data('idequipment')? no.
    // Mejor:
    const idEquipment2 = $btn.attr('data-idEquipment');
    const quoteId2 = $btn.attr('data-quote');

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        title: 'Guardar cambios',
        content: '¿Está seguro de guardar los cambios en los productos?',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {

                    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

                    const $card = $('[data-equip]').first();

                    // 1) Leer DOM
                    const cRead = readConsumablesFromDom($card);
                    const sRead = readServicesFromDom($card.find('[data-bodyService]'));

                    // 2) Subtotal con IGV (productos - promos + servicios)
                    const subtotalConsumables = round2(cRead.sum_importe - cRead.sum_promos);
                    const subtotalWithIgv = round2(subtotalConsumables + sRead.sum_all);

                    // 3) Descuento global (sobre base SIN IGV)
                    const igvPct = parseFloat($igv || 18);
                    const disc = computeGlobalDiscountBase(subtotalWithIgv, igvPct);
                    const discountBase = disc.base;

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

                    // 5) Leer meta descuento (para rehidratar edit)
                    const $d = $('#discountSection');
                    const discount_type = $d.attr('data-discount_type') || $('input[name="discount_type"]:checked').val() || 'amount';
                    const discount_input_mode = $d.attr('data-discount_input_mode') || $('input[name="discount_input_mode"]:checked').val() || 'without_igv';
                    const discount_input_value = $d.attr('data-discount_value') || $('#discount_value').val() || '0';

                    // 6) Payload equipo (1 item)
                    const equipmentPayload = [{
                        id: 0,
                        quote: quoteId2,
                        equipment: idEquipment2,
                        quantity: 1,
                        utility: $card.find('[data-utilityequipment]').val() || 0,
                        rent: $card.find('[data-rentequipment]').val() || 0,
                        letter: $card.find('[data-letterequipment]').val() || 0,
                        total: totalFinal,
                        description: "",
                        detail: $card.find('[data-detailequipment]').val() || "",
                        materials: [],
                        consumables: cRead.array,
                        electrics: [],
                        workforces: sRead.array,
                        tornos: [],
                        dias: []
                    }];

                    // 7) Enviar al backend
                    $.ajax({
                        url: '/dashboard/update/equipment/' + idEquipment2 + '/quote/sale/' + quoteId2,
                        method: 'POST',
                        data: JSON.stringify({
                            equipment: equipmentPayload,

                            // totales quote
                            descuento: discountBase.toFixed(2),
                            gravada: baseFinal.toFixed(2),
                            igv_total: igvFinal.toFixed(2),
                            total_importe: totalFinal.toFixed(2),

                            // meta descuento
                            discount_type: discount_type,
                            discount_input_mode: discount_input_mode,
                            discount_input_value: discount_input_value
                        }),
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        processData: false,
                        contentType: 'application/json; charset=utf-8',
                        success: function (data) {
                            // marcar clean
                            setClean(); // oculta alerta + card-success
                            $.alert(data.message || 'Equipo guardado con éxito.');
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Error al guardar.';
                            toastr.error(msg, 'Error');
                        },
                        complete: function () {
                            $("#element_loader").LoadingOverlay("hide", true);
                        }
                    });
                }
            },
            cancel: { text: 'CANCELAR' }
        }
    });
}
