/* =========================================================
   quoteSale/edit.js (REESTRUCTURADO)
   - 1 solo equipo (data-equip)
   - Recalcula totales en vivo (descuento/gravada/igv/total)
   - Muestra alerta de cambios (#alert_edit)
   - Marca card como "dirty" (card-gray-dark) hasta guardar
   - FIX DECIMALES: total-first + base trunc + igv residual
   ========================================================= */

let $consumables = [];
let $equipments = [];
let $permissions = [];
let $igv = $("#igv").val();

var $modalConsumableQty = $('#modalQuantityConsumable');
var $currentConsumableRender = null;
var $currentConsumable = null;

/* =========================
   Helpers dinero (FIX 0.01)
   ========================= */

function mayus(e) {
    e.value = e.value.toUpperCase();
}


function moneyRound(n) {
    return Math.round((parseFloat(n || 0) + Number.EPSILON) * 100) / 100;
}
function moneyTrunc(n) {
    n = parseFloat(n || 0);
    return (n >= 0) ? Math.floor(n * 100) / 100 : Math.ceil(n * 100) / 100;
}
function divTrunc2(a, b) {
    b = parseFloat(b || 1);
    if (!b) return 0;
    return moneyTrunc((parseFloat(a || 0)) / b);
}

// Mantengo tu round2 para compatibilidad (pero ya no lo usamos para base/igv)
function round2(num) {
    return Math.round((parseFloat(num || 0) + Number.EPSILON) * 100) / 100;
}

/* =======================================
   Descuento global: utilidades (FIX 0.01)
   - Calcula descuento "con IGV" y "en base"
   - según type (amount|percent) y mode (with_igv|without_igv)
   ======================================= */
function computeDiscountWithIgv(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    if ($d.length === 0) return 0;

    const type = ($d.attr('data-discount_type') || 'amount');           // amount | percent
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv'); // with_igv | without_igv
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return 0;

    const factor = 1 + (igvPct / 100);

    let discWithIgv = 0;

    if (type === 'amount') {
        // monto ingresado puede ser con o sin IGV
        discWithIgv = (mode === 'with_igv') ? value : moneyRound(value * factor);
    } else {
        // porcentaje sobre total con IGV o sobre base sin IGV
        const pct = value / 100;
        if (mode === 'with_igv') {
            discWithIgv = moneyRound(subtotalWithIgv * pct);
        } else {
            const baseSubtotal = subtotalWithIgv / factor;
            discWithIgv = moneyRound((baseSubtotal * pct) * factor);
        }
    }

    // no permitir superar el subtotal
    if (discWithIgv > subtotalWithIgv) discWithIgv = subtotalWithIgv;

    return moneyRound(discWithIgv);
}

function computeDiscountBaseFromWithIgv(discountWithIgv, igvPct) {
    const factor = 1 + (igvPct / 100);
    // descuento base TRUNC (consistente con base truncada)
    return divTrunc2(discountWithIgv, factor);
}

/* ======================================================
   Reglas finales de totales (FIX 0.01):
   1) totalFinal = round(subtotalWithIgv - discountWithIgv)
   2) baseFinal = TRUNC(totalFinal / 1.18)
   3) igvFinal  = round(totalFinal - baseFinal)
   ====================================================== */
function computeTotals(subtotalWithIgv) {
    const igvPct = parseFloat($igv || 18);
    const factor = 1 + (igvPct / 100);

    const discountWithIgv = computeDiscountWithIgv(subtotalWithIgv, igvPct);
    const totalFinal = moneyRound(subtotalWithIgv - discountWithIgv);

    const baseFinal = divTrunc2(totalFinal, factor);
    const igvFinal = moneyRound(totalFinal - baseFinal);

    const discountBase = computeDiscountBaseFromWithIgv(discountWithIgv, igvPct);

    return {
        discountWithIgv,
        discountBase,
        baseFinal,
        igvFinal,
        totalFinal
    };
}

/* =========================
   Presentations / Modal
   ========================= */
function fetchPresentations(materialId) {
    return $.ajax({
        url: `/dashboard/materials-presentations/material/${materialId}/presentations`,
        method: 'GET',
        dataType: 'json'
    }).then(function (res) {
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
        .then(function (presentations) {
            let actives = presentations.filter(x => x.active === true || x.active === 1 || x.active === "1");
            renderPresentationsInModalConsumable(actives);
            $modalConsumableQty.modal('show');
        })
        .catch(function () {
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

/* =========================
   Render consumable row
   ========================= */
function renderTemplateConsumable(render, consumable, qtyVisible, pricePU, discount, isPrice, pres) {
    var clone = activateTemplate('#template-consumable');

    // visibles
    clone.querySelector("[data-consumableDescription]").value = consumable.full_description;
    clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.description;
    clone.querySelector("[data-consumableQuantity]").value = parseFloat(qtyVisible).toFixed(2);
    clone.querySelector("[data-consumablePrice]").value = parseFloat(pricePU).toFixed(2);
    $(clone).find('[data-consumablePrice]').attr('data-consumable_price_real', pricePU.toFixed(10));

    // V/U e importe inicial
    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);

    clone.querySelector("[data-consumableValor]").value = (parseFloat(pricePU) / igvFactor).toFixed(2);
    $(clone).find('[data-consumableValor]').attr('data-consumable_valor_real', (parseFloat(pricePU) / igvFactor).toFixed(10));

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

/* =========================
   Dirty state + alerta
   ========================= */
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

/* =========================
   Lectura DOM: Consumables
   ========================= */
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

        // ✅ NO redondear item por item: suma directo y redondea al final
        sumImporte += (parseFloat(importe) || 0);
        sumPromoDiscount += (parseFloat(discount) || 0);
    });

    return {
        array: arr,
        sum_importe: moneyRound(sumImporte),
        sum_promos: moneyRound(sumPromoDiscount)
    };
}

/* =========================
   Lectura DOM: Servicios
   ========================= */
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

        sumAll += (parseFloat(imp) || 0);
        if (billable === 1) sumBillable += (parseFloat(imp) || 0);
    });

    return {
        array: arr,
        sum_all: moneyRound(sumAll),
        sum_billable: moneyRound(sumBillable)
    };
}

/* =========================
   Recalcular totales en vista
   ========================= */
function recalcQuoteTotalsFromDom() {
    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = moneyRound((cRead.sum_importe || 0) - (cRead.sum_promos || 0));
    const subtotalWithIgv = moneyRound(subtotalConsumables + (servicesRead.sum_all || 0));

    const totals = computeTotals(subtotalWithIgv);

    // UI muestra descuento BASE (como venías guardando en Quote)
    $('#descuento').html(totals.discountBase.toFixed(2));
    $('#gravada').html(totals.baseFinal.toFixed(2));
    $('#igv_total').html(totals.igvFinal.toFixed(2));
    $('#total_importe').html(totals.totalFinal.toFixed(2));

    $('#descuento').attr('data-descuento_real', totals.discountBase);
    $('#gravada').attr('data-gravada_real', totals.baseFinal);
    $('#igv_total').attr('data-igv_total_real', totals.igvFinal);
    $('#total_importe').attr('data-total_importe_real', totals.totalFinal);

}

/* =========================
   Servicios: recalcular fila
   ========================= */
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

/* =========================
   Consumables: recalcular fila
   ========================= */
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

/* =========================
   Descuento: sync data attrs
   ========================= */
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

/* =========================
   Fill initial equipments
   ========================= */
function fillEquipments() {
    $equipments = [];

    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const quote_id = $('#quote_id').val();
    const idEquipment = $('#btn-saveProducts').attr('data-idEquipment') || null;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = moneyRound((cRead.sum_importe || 0) - (cRead.sum_promos || 0));
    const subtotalWithIgv = moneyRound(subtotalConsumables + (servicesRead.sum_all || 0));

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

    //recalcQuoteTotalsFromDom();
    setClean();
    $("#element_loader").LoadingOverlay("hide", true);
}

/* =========================
   Delete handlers
   ========================= */
function deleteConsumable() {
    $(this).closest('.row').remove();
    onQuoteChanged();
}

function deleteServiceRow() {
    $(this).closest('[data-serviceRow]').remove();
    onQuoteChanged();
}

/* =========================
   Add service
   ========================= */
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

/* =========================
   Guardar cambios
   ========================= */
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
                packs,
                presPrice,
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

    this.blur();
    document.activeElement && document.activeElement.blur && document.activeElement.blur();

    $modalConsumableQty.modal('hide');
});

/* =========================
   init
   ========================= */
$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val() || '[]');
    $igv = parseFloat($('#igv').val() || 18);

    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

    fillEquipments();

    // Carga consumables
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

    // Guardar cambios
    $('#btn-saveProducts').on('click', function (e) {
        e.preventDefault();
        saveEquipmentEdit();
    });

    // Modal focus fix
    $('#modalQuantityConsumable').on('hidden.bs.modal', function () {
        document.activeElement && document.activeElement.blur && document.activeElement.blur();
        $('#material_search').focus();
    });

    $(document).on('click', '[data-addConsumable]', addConsumable);

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
});

/* =========================
   Save (Edit) - FIX 0.01
   ========================= */
const getFactor = (igvPct) => 1 + ((Number(igvPct) || 0) / 100);
const round10 = (n) => Math.round((Number(n) || 0) * 1e10) / 1e10;

function saveEquipmentEdit() {

    const $btn = $('#btn-saveProducts');

    const idEquipment2 = $btn.attr('data-idEquipment');
    const quoteId2     = $btn.attr('data-quote');

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

                    // ===========================
                    // 0) Card principal (1 equipo)
                    // ===========================
                    const $card = $('[data-equip]').first();

                    // ===========================
                    // 1) Datos generales del equipo
                    // ===========================
                    const utility = $card.find('[data-utilityequipment]').val() || 0;
                    const rent    = $card.find('[data-rentequipment]').val() || 0;
                    const letter  = $card.find('[data-letterequipment]').val() || 0;
                    const detail  = $card.find('[data-detailequipment]').val() || "";

                    // ===========================
                    // 2) CONSUMABLES (productos) - igual que create.js
                    // ===========================
                    // Ajusta este selector si en edit cambia la estructura:
                    const consumables = $card.find('[data-bodyConsumables], [data-bodyConsumable], [data-consumablesContainer]').first().length
                        ? $card.find('[data-bodyConsumables], [data-bodyConsumable], [data-consumablesContainer]').first()
                        : $card; // fallback: busca en todo el card

                    const consumablesDescription = [];
                    const consumablesIds = [];
                    const consumablesUnit = [];

                    const consumablesQuantity = []; // packs o unidades (según presentación)
                    const consumablesValor = [];
                    const consumablesValorReal = [];
                    const consumablesPrice = [];
                    const consumablesPriceReal = [];
                    const consumablesImporte = [];

                    const consumablesDiscount = []; // compat (promos por item)
                    const consumablesTypePromos = [];

                    const consumablesPresentationId = [];
                    const consumablesUnitsPerPack = [];
                    const consumablesUnitsEquivalent = [];

                    let descuentoPromos = 0;

                    // OJO: en create tenías un wrapper "consumables.each(...)" con sub-find.
                    // En edit, normalmente ya están en el card, por eso hacemos un find directo.
                    $card.find('[data-consumableDescription]').each(function(){ consumablesDescription.push($(this).val()); });
                    $card.find('[data-consumableId]').each(function(){ consumablesIds.push($(this).attr('data-consumableid')); });

                    $card.find('[data-descuento]').each(function(){
                        const d = parseFloat($(this).attr('data-descuento') || 0);
                        consumablesDiscount.push(d);
                        descuentoPromos += d;
                    });

                    $card.find('[data-type_promotion]').each(function(){ consumablesTypePromos.push($(this).attr('data-type_promotion')); });
                    $card.find('[data-consumableUnit]').each(function(){ consumablesUnit.push($(this).val()); });
                    $card.find('[data-consumableQuantity]').each(function(){ consumablesQuantity.push($(this).val()); });

                    $card.find('[data-consumableValor]').each(function(){ consumablesValor.push($(this).val()); });
                    $card.find('[data-consumable_valor_real]').each(function(){ consumablesValorReal.push($(this).attr('data-consumable_valor_real')); });

                    $card.find('[data-consumablePrice]').each(function(){ consumablesPrice.push($(this).val()); });
                    $card.find('[data-consumable_price_real]').each(function(){ consumablesPriceReal.push($(this).attr('data-consumable_price_real')); });

                    $card.find('[data-consumableImporte]').each(function(){ consumablesImporte.push($(this).val()); });

                    $card.find('[data-presentation_id]').each(function(){ consumablesPresentationId.push($(this).attr('data-presentation_id') || null); });
                    $card.find('[data-units_per_pack]').each(function(){ consumablesUnitsPerPack.push($(this).attr('data-units_per_pack') || null); });
                    $card.find('[data-units_equivalent]').each(function(){ consumablesUnitsEquivalent.push($(this).attr('data-units_equivalent') || null); });

                    // Armamos array final de consumables (incluye reales)
                    const consumablesArray = [];
                    for (let i = 0; i < consumablesDescription.length; i++) {
                        consumablesArray.push({
                            id: consumablesIds[i],
                            description: consumablesDescription[i],
                            unit: consumablesUnit[i],

                            // ✅ packs o unidades según presentación (SUNAT)
                            quantity: consumablesQuantity[i],

                            // solo para inventario
                            units_equivalent: consumablesUnitsEquivalent[i] || consumablesQuantity[i],

                            valor: consumablesValor[i],
                            valorReal: consumablesValorReal[i],
                            price: consumablesPrice[i],
                            priceReal: consumablesPriceReal[i],
                            importe: consumablesImporte[i],

                            discount: consumablesDiscount[i] || 0,
                            type_promo: consumablesTypePromos[i] || null,
                            presentation_id: consumablesPresentationId[i] || null,
                            units_per_pack: consumablesUnitsPerPack[i] || null
                        });
                    }

                    // ===========================
                    // 3) SERVICIOS ADICIONALES
                    // ===========================
                    const servicesContainer = $card.find('[data-bodyService]');
                    let servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    const servicesArray = servicesRead.array;
                    const servicesSumAll = servicesRead.sum_all; // con IGV
                    const servicesSumBillable = servicesRead.servicesSumBillable;
                    // ===========================
                    // 4) Totales con 10 dec (reales)
                    // ===========================
                    const igvPct = parseFloat($igv) || 18;
                    const factor = getFactor(igvPct);

                    // productos con IGV: quantity * priceReal (priceReal es pack/unidad según corresponda)
                    let subtotalConsumablesWithIgvReal = 0;
                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;
                        const priceReal = Number(consumablesArray[i].priceReal ?? consumablesArray[i].price) || 0;
                        subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal + round10(qty * priceReal));
                    }

                    // promos por ítem (si ya no usan, será 0)
                    subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal - (Number(descuentoPromos) || 0));
                    if (subtotalConsumablesWithIgvReal < 0) subtotalConsumablesWithIgvReal = 0;

                    const servicesWithIgvReal = round10(Number(servicesSumBillable) || 0);
                    const subtotalWithIgvReal = round10(subtotalConsumablesWithIgvReal + servicesWithIgvReal);

                    // descuento global con IGV (tu función)
                    const discountWithIgvReal = round10(computeDiscountWithIgv(subtotalWithIgvReal, igvPct));

                    // total final con IGV
                    let totalFinalWithIgvReal = round10(subtotalWithIgvReal - discountWithIgvReal);
                    if (totalFinalWithIgvReal < 0) totalFinalWithIgvReal = 0;

                    // base + igv
                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);
                    if (baseFinalReal < 0) baseFinalReal = 0;

                    const igvFinalReal = round10(totalFinalWithIgvReal - baseFinalReal);

                    // descuento en base (sin igv) para guardar y UI
                    const discountBaseReal = round10(discountWithIgvReal / factor);

                    // ===========================
                    // 5) UI (2 dec) + data reales
                    // ===========================
                    $('#descuento').html(moneyRound(discountBaseReal).toFixed(2));
                    $('#gravada').html(moneyRound(baseFinalReal).toFixed(2));
                    $('#igv_total').html(moneyRound(igvFinalReal).toFixed(2));
                    $('#total_importe').html(moneyRound(totalFinalWithIgvReal).toFixed(2));

                    $('#descuento').attr('data-descuento_real', discountBaseReal);
                    $('#gravada').attr('data-gravada_real', baseFinalReal);
                    $('#igv_total').attr('data-igv_total_real', igvFinalReal);
                    $('#total_importe').attr('data-total_importe_real', totalFinalWithIgvReal);

                    // ===========================
                    // 6) Meta descuento (igual que tenías)
                    // ===========================
                    const $d = $('#discountSection');
                    const discount_type = $d.attr('data-discount_type') || $('input[name="discount_type"]:checked').val() || 'amount';
                    const discount_input_mode = $d.attr('data-discount_input_mode') || $('input[name="discount_input_mode"]:checked').val() || 'without_igv';
                    const discount_input_value = $d.attr('data-discount_value') || $('#discount_value').val() || '0';

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    // ===========================
                    // 7) Payload equipo (1 item) - con reales
                    // ===========================
                    const equipmentPayload = [{
                        id: 0,
                        quote: quoteId2,
                        equipment: idEquipment2,
                        quantity: 1,
                        utility: utility,
                        rent: rent,
                        letter: letter,

                        // ✅ real con IGV
                        total: totalFinalWithIgvReal,

                        description: "",
                        detail: detail,
                        materials: [],
                        consumables: consumablesArray,
                        electrics: [],
                        workforces: servicesArray,
                        discount_global: {
                            base: discountBaseReal,
                            meta: discountGlobalMeta
                        },
                        tornos: [],
                        dias: []
                    }];

                    // ===========================
                    // 8) Enviar al backend (reales a 10 dec)
                    // ===========================
                    $.ajax({
                        url: '/dashboard/update/equipment/' + idEquipment2 + '/quote/sale/' + quoteId2,
                        method: 'POST',
                        data: JSON.stringify({
                            equipment: equipmentPayload,

                            // ✅ Totales quote en REAL (10 dec)
                            descuento: discountBaseReal,
                            gravada: baseFinalReal,
                            igv_total: igvFinalReal,
                            total_importe: totalFinalWithIgvReal,

                            // meta descuento (por si lo rehidratas en edit)
                            discount_type: discount_type,
                            discount_input_mode: discount_input_mode,
                            discount_input_value: discount_input_value
                        }),
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        processData: false,
                        contentType: 'application/json; charset=utf-8',
                        success: function (data) {
                            // si tienes funciones de "clean state" para edit
                            if (typeof setClean === 'function') setClean();
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

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
