var $materialsTypeahead = [];
var $permissions;
var $materials = [];
var $total = 0;
var $modalOpen;
var $modalClose;
var $modalIncome;
var $modalExpense;
var $modalRegularize;

$(document).ready(function () {
    try {
        $permissions = JSON.parse($('#permissions').val() || '[]');
    } catch (e) {
        $permissions = [];
    }

    getDataMovements(1);

    $(document).on("click", '[id=btn-incomeCashRegister]', incomeCashRegister);

    $modalIncome = $("#modalIncome");

    $("#btn_ingreso").on("click", ingresoCaja);

    $(document).on("click", '[id=btn-expenseCashRegister]', expenseCashRegister);

    $modalExpense = $("#modalExpense");

    $("#btn_egreso").on("click", egresoCaja);

    $(document).on("click", '[data-regularizar]', regularizeCashRegister);

    $modalRegularize = $("#modalRegularize");

    $("#btn_regularizar").on("click", regularizarCaja);

    $(document).on('click', '[data-item]', showData);

    /*$(document).on('click', '#btn-arqueoCashRegister', function (e) {
        e.preventDefault();
        $('#modalArqueo').modal('show');
    });*/
    $(document).on('click', '#btn-arqueoCashRegister', function (e) {
        e.preventDefault();

        // Toma el balance actual del hidden (ya actualizado por income/expense)
        const current = $("#balance_total").val();
        if ($("#arqueo_teorico").length) {
            $("#arqueo_teorico").html(current); // o "S/."+current si quieres
        }

        // limpia campos
        $('#arqueo_observation').val('');
        if ($('#arqueo_counted').length) $('#arqueo_counted').val('');

        $('#modalArqueo').modal('show');
    });

    // Limpia también al cerrar
    $('#modalArqueo').on('hidden.bs.modal', function () {
        $('#arqueo_observation').val('');
        if ($('#arqueo_counted').length) $('#arqueo_counted').val('');
    });

    $(document).on('click', '#btn_confirm_arqueo', function (e) {
        e.preventDefault();

        const cashRegisterId = $('#cash_register_id').val();
        const url = $("#formArqueo").data('url');

        // Si tu data-url ya es route('cashRegister.arqueo', $cashRegister->id) mejor aún
        const observation = $('#arqueo_observation').val() || '';
        const counted = $('#arqueo_counted').length ? $('#arqueo_counted').val() : null;

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                observation: observation,
                counted: counted
            },
            success: function (res) {
                toastr.success(res.message || 'Arqueo realizado', 'Éxito');
                $('#modalArqueo').modal('hide');

                // Redirigir a Mis Movimientos (porque la sesión ya quedó cerrada)
                setTimeout(function () {
                    window.location.href = '/dashboard/cash-movements/my';
                }, 800);
            },
            error: function (xhr) {
                let msg = 'Error al arqueo.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg, 'Error');
            }
        });
    });

    // Limpia también al cerrar (por si cancela)
    $('#modalExpense').on('hidden.bs.modal', function () {
        resetExpenseModal();
    });

    $('#modalIncome').on('hidden.bs.modal', function () {
        resetIncomeModal();
    });

    // Limpieza al cerrar
    $('#modalRegularize').on('hidden.bs.modal', function () {
        $('#cash_movement_id').val('');
        $('#regularize_amount').val('');
        $('#regularize_observation').val('');
    });
});

function updateBalanceUI(newBalance) {
    // Hidden balance
    $("#balance_total").val(newBalance);

    // Header
    $("#valueBalanceTotal").html("S/." + newBalance);

    // Modal arqueo
    if ($("#arqueo_teorico").length) {
        $("#arqueo_teorico").html(newBalance); // tu modal ya muestra "S/." en el texto
        // Si quieres con S/. directo:
        // $("#arqueo_teorico").html("S/." + newBalance);
    }
}

function regularizarCaja(e) {
    e.preventDefault();

    const url = $('#formRegularize').data('url');
    const movementId = $('#cash_movement_id').val();
    const amountReg = $('#regularize_amount').val();
    const obs = $('#regularize_observation').val();

    if (!amountReg || parseFloat(amountReg) <= 0) {
        toastr.error('Ingrese un monto neto válido.', 'Error');
        return;
    }

    $.ajax({
        url: url,
        method: 'POST',
        dataType: 'json',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            cash_movement_id: movementId,
            amount_regularize: amountReg,
            observation: obs
        },
        success: function (res) {
            toastr.success(res.message || 'Regularizado', 'Éxito');

            // actualizar balance header + modal arqueo si usas updateBalanceUI
            if (typeof updateBalanceUI === 'function' && res.balance_total !== undefined) {
                updateBalanceUI(res.balance_total);
            } else if (res.balance_total !== undefined) {
                $("#balance_total").val(res.balance_total);
                $("#valueBalanceTotal").html("S/." + res.balance_total);
                if ($("#arqueo_teorico").length) $("#arqueo_teorico").html(res.balance_total);
            }

            $('#modalRegularize').modal('hide');

            // refrescar tabla
            setTimeout(function () {
                getDataMovements(1);
            }, 400);
        },
        error: function (xhr) {
            let msg = 'No se pudo regularizar.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            toastr.error(msg, 'Error');
        }
    });
}

function regularizeCashRegister(e) {
    e.preventDefault();

    const movementId = $(this).data('id');

    $('#cash_movement_id').val(movementId);
    $('#regularize_amount').val('');
    $('#regularize_observation').val('');

    $('#modalRegularize').modal('show');
}

function egresoCaja() {
    event.preventDefault();

    let cash_register_id = $('#cash_register_id').val();
    let amount = $('#expense_amount').val();
    let description = $('#expense_description').val();

    let cash_box_type = $('#cash_box_type').val();
    let uses_subtypes = $('#cash_box_uses_subtypes').val() === '1';

    let subtype_id = null;
    if (cash_box_type === 'bank' && uses_subtypes) {
        subtype_id = $('#expense_subtype_id').val();
        if (!subtype_id) {
            toastr.error('Debe seleccionar un subtipo bancario.', 'Error');
            return;
        }
    }

    let packageData = {
        cash_register_id: cash_register_id,
        amount: amount,
        description: description,
        cash_box_subtype_id: subtype_id
    };

    $.ajax({
        url: $("#formExpense").data('url'),
        method: 'POST',
        data: JSON.stringify(packageData),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            toastr.success(response.message, 'Éxito');
            /*$("#balance_total").val(response.balance_total);
            $("#valueBalanceTotal").html("S/."+response.balance_total);
            $("#arqueo_teorico").html("S/."+response.balance_total);*/
            updateBalanceUI(response.balance_total);

            setTimeout(function () {
                $modalExpense.modal('hide');
                getDataMovements(1);
            }, 700);
        },
        error: function(data) {
            let msg = (data.responseJSON && data.responseJSON.message) ? data.responseJSON.message : 'Error';
            toastr.error(msg, 'Error');
        }
    });
}

/*function expenseCashRegister() {
    var balance_total = $("#balance_total").val();
    $modalExpense.find('[id=balance_total_expense]').val(balance_total);
    $modalExpense.modal('show');
}*/

function resetExpenseModal() {
    $('#expense_amount').val('');
    $('#expense_description').val('');

    // si existe (solo bancario)
    if ($('#expense_subtype_id').length) {
        $('#expense_subtype_id').val('');
    }
}

function expenseCashRegister() {
    var balance_total = $("#balance_total").val();
    $modalExpense.find('[id=balance_total_expense]').val(balance_total);

    resetExpenseModal();   // ✅ limpia antes de mostrar
    $modalExpense.modal('show');
}

function ingresoCaja() {
    event.preventDefault();

    let cash_register_id = $('#cash_register_id').val();
    let amount = $('#income_amount').val();
    let description = $('#income_description').val();

    let cash_box_type = $('#cash_box_type').val(); // cash | bank
    let uses_subtypes = $('#cash_box_uses_subtypes').val() === '1';

    let subtype_id = null;
    if (cash_box_type === 'bank' && uses_subtypes) {
        subtype_id = $('#income_subtype_id').val();
        if (!subtype_id) {
            toastr.error('Debe seleccionar un subtipo bancario.', 'Error');
            return;
        }
    }

    let packageData = {
        cash_register_id: cash_register_id,
        amount: amount,
        description: description,
        cash_box_subtype_id: subtype_id
    };

    $.ajax({
        url: $("#formIncome").data('url'),
        method: 'POST',
        data: JSON.stringify(packageData),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            toastr.success(response.message, 'Éxito');
            /*$("#balance_total").val(response.balance_total);
            $("#valueBalanceTotal").html("S/."+response.balance_total);
            $("#arqueo_teorico").html("S/."+response.balance_total);*/
            updateBalanceUI(response.balance_total);
            setTimeout(function () {
                $modalIncome.modal('hide');
                getDataMovements(1);
            }, 700);
        },
        error: function(data) {
            let msg = (data.responseJSON && data.responseJSON.message) ? data.responseJSON.message : 'Error';
            toastr.error(msg, 'Error');
        }
    });
}

/*function incomeCashRegister() {
    var balance_total = $("#balance_total").val();
    $modalIncome.find('[id=balance_total_income]').val(balance_total);
    $modalIncome.modal('show');
}*/
function resetIncomeModal() {
    $('#income_amount').val('');
    $('#income_description').val('');

    if ($('#income_subtype_id').length) {
        $('#income_subtype_id').val('');
    }
}

function incomeCashRegister() {
    var balance_total = $("#balance_total").val();
    $modalIncome.find('[id=balance_total_income]').val(balance_total);

    resetIncomeModal();    // ✅ limpia antes de mostrar
    $modalIncome.modal('show');
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataMovements(numberPage)
}

function getDataMovements($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    const cashRegisterId = $('#cash_register_id').val();
    $.get('/dashboard/cash-registers/' + cashRegisterId + '/movements/' + $numberPage, {}, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataMovementsEmpty(data);
        } else {
            renderDataMovements(data);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
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
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataMovementsEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' movimientos');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataMovements(data) {
    var dataCombos = data.data;
    var pagination = data.pagination;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' movimientos.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataCombos.length ; j++) {
        renderDataTable(dataCombos[j]);
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

    // Campos base
    clone.querySelector("[data-id]").innerHTML = data.id;
    clone.querySelector("[data-type]").innerHTML = data.type;
    clone.querySelector("[data-date]").innerHTML = data.date;

    // Nuevos: subtipo / estado / montos extra
    // subtype viene como string o null
    const subtypeText = (data.subtype && data.subtype.trim() !== '') ? data.subtype : '-';
    clone.querySelector("[data-subtype]").innerHTML = subtypeText;

    // Estado: pendiente/confirmado según regularize (0/1)
    let statusHtml = '-';
    if (data.regularize === 0 || data.regularize === '0') {
        statusHtml = '<span class="badge badge-warning">Pendiente</span>';
    } else if (data.regularize === 1 || data.regularize === '1') {
        statusHtml = '<span class="badge badge-success">Confirmado</span>';
    }
    clone.querySelector("[data-status]").innerHTML = statusHtml;

    // Monto bruto
    clone.querySelector("[data-amount]").innerHTML = data.amount !== null ? data.amount : '0.00';

    // Monto abonado y comisión (si aplica)
    clone.querySelector("[data-amount_regularize]").innerHTML =
        (data.amount_regularize !== null && data.amount_regularize !== undefined)
            ? data.amount_regularize
            : '-';

    clone.querySelector("[data-commission]").innerHTML =
        (data.commission !== null && data.commission !== undefined)
            ? data.commission
            : '-';

    // Descripción + observation (si existe)
    let descHtml = data.description ? data.description : '';
    if (data.observation) {
        descHtml += `<br><small class="text-muted">${escapeHtml(data.observation)}</small>`;
    }
    clone.querySelector("[data-description]").innerHTML = descHtml;

    // Colores por tipo
    var trElement = clone.querySelector('tr');
    if (trElement) {
        if (data.regularize === 0 || data.regularize === '0') {
            trElement.classList.add('regularize-row');
        } else {
            if (data.type === 'Ingreso' || data.type === 'Venta') {
                trElement.classList.add('income-row');
            } else if (data.type === 'Egreso') {
                trElement.classList.add('expense-row');
            }
        }

    }

    // Botones (igual que tu lógica actual)
    /*if (data.sale_id != null) {
        var botones2 = clone.querySelector("[data-buttons]");
        var cloneBtn2 = activateTemplate('#template-button');

        cloneBtn2.querySelector("[data-print_nota]").setAttribute("data-id", data.id);
        let url = document.location.origin + '/dashboard/imprimir/documento/venta/' + data.sale_id;
        cloneBtn2.querySelector("[data-print_nota]").setAttribute("href", url);

        // Botón regularizar solo si corresponde
        var regularizarBtn = cloneBtn2.querySelector("[data-regularizar]");
        if (data.regularize === 0 || data.regularize === '0') {
            regularizarBtn.setAttribute("data-id", data.id);
        } else {
            regularizarBtn.style.display = 'none';
        }

        botones2.append(cloneBtn2);
    }
*/
    var botones2 = clone.querySelector("[data-buttons]");
    var cloneBtn2 = activateTemplate('#template-button');

    // 1) Botón imprimir: solo si hay sale_id
    var printBtn = cloneBtn2.querySelector("[data-print_nota]");
    if (data.sale_id != null) {
        printBtn.setAttribute("data-id", data.id);
        let url = document.location.origin + '/dashboard/imprimir/documento/venta/' + data.sale_id;
        printBtn.setAttribute("href", url);
    } else {
        // Si no hay sale_id, ocultamos el print
        printBtn.style.display = 'none';
    }

    // 2) Botón regularizar: si está pendiente
    var regularizarBtn = cloneBtn2.querySelector("[data-regularizar]");

    // ✅ mostrar si regularize=0 y (sale o income)
    const isPending = (data.regularize === 0 || data.regularize === '0');
    const canBeRegularized = (data.type_raw === 'sale' || data.type_raw === 'income');
    // si no tienes type_raw, usa data.type == 'Regularizar' o agrega type_raw en backend

    const canRegularize = $.inArray('regularize_caja', $permissions) !== -1;

    if (canRegularize && isPending && canBeRegularized) {
        regularizarBtn.setAttribute("data-id", data.id);
    } else {
        regularizarBtn.style.display = 'none';
    }

    botones2.append(cloneBtn2);

    $("#body-table").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Escapar HTML simple para observation (evita inyección)
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
