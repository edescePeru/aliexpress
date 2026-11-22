$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    $('#newAccount').on('click', addNewAccount);

    $(document).on('click', '[data-updateaccount]', updateAccount);
    $(document).on('click', '[data-deleteaccount]', deleteAccount);

    $(document).on('select2:select', '.bank', function (e) {
        var card = $(this).closest('[data-account-row]').find('[data-updateaccount]');
        card.removeClass('btn-outline-primary');
        card.addClass('btn-outline-secondary');
    });

    $(document).on('select2:select', '.currency', function (e) {
        var card = $(this).closest('[data-account-row]').find('[data-updateaccount]');
        card.removeClass('btn-outline-primary');
        card.addClass('btn-outline-secondary');
    });

    $(document).on('input', '[data-number_account]', function() {
        var card = $(this).closest('[data-account-row]').find('[data-updateaccount]');
        card.removeClass('btn-outline-primary');
        card.addClass('btn-outline-secondary');
    });

});

var $formCreate;

function deleteAccount() {
    var submitted = false;
    var button = $(this);
    var account_id = $(this).attr('data-deleteAccount');
    var account_number = $(this).attr('data-number');
    button.attr("disabled", true);
    $.confirm({
        icon: 'far fa-times-circle',
        theme: 'modern',
        closeIcon: true,
        onClose: function () { if (!submitted) button.prop('disabled', false); },
        animation: 'zoom',
        type: 'red',
        title: '¿Esta seguro de eliminar esta cuenta?',
        content: account_number,
        buttons: {
            confirm: {
                text: 'ELIMINAR',
                action: function (e) {
                    //$.alert('Descargado igual');
                    submitted = true;
                    $.ajax({
                        url: "/dashboard/supplier/account/destroy/"+account_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            button.closest('[data-account-row]').remove();
                            refreshAccountSummary();
                            button.attr("disabled", false);

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
                            button.attr("disabled", false);

                        },
                    });

                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Eliminación cancelada.");
                    button.attr("disabled", false);
                },
            },
        },
    });
}

function updateAccount() {
    var submitted = false;
    var button = $(this);
    var account_id = button.attr('data-updateAccount');
    var row = button.closest('[data-account-row]');
    var number_account = row.find('[data-number_account]').val();
    var bank_id = row.find('[data-bank]').val();
    var currency = row.find('[data-currency]').val();

    button.attr("disabled", true);
    $.confirm({
        icon: 'far fa-save',
        theme: 'modern',
        closeIcon: true,
        onClose: function () { if (!submitted) button.prop('disabled', false); },
        animation: 'zoom',
        type: 'orange',
        title: '¿Esta seguro de guardar los cambios?',
        content: "Se guardará en la base de datos",
        buttons: {
            confirm: {
                text: 'GUARDAR',
                action: function (e) {
                    submitted = true;
                    $.ajax({
                        url: "/dashboard/supplier/account/update/"+account_id,
                        method: 'POST',
                        data:{account_id:account_id, number_account:number_account, bank_id:bank_id, currency:currency},
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            button.attr("disabled", false);
                            button.removeClass('btn-outline-secondary');
                            button.addClass('btn-outline-primary');

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

                            button.attr("disabled", false);

                        },
                    });

                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Modificación cancelada.");
                    button.attr("disabled", false);
                },
            },
        },
    });
}

function addNewAccount() {
    var button = $(this);
    var createUrl = $(this).attr('data-url');
    button.attr("disabled", true);
    $.ajax({
        url: createUrl,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
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
            renderTemplateAccount(data.account);
            button.attr("disabled", false);

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
            button.attr("disabled", false);

        },
    });


}

function renderTemplateAccount(account) {
    var clone = activateTemplate('#template-account');

    clone.querySelector("[data-number_account]").value = account.number_account == null ? "" : account.number_account;
    var selectBank = clone.querySelector("[data-bank]");
    selectBank.value = account.bank_id == null ? "" : account.bank_id;

    var selectCurrency = clone.querySelector("[data-currency]");
    selectCurrency.value = account.currency == null ? "" : account.currency;

    ['data-updateAccount', 'data-deleteAccount'].forEach(function (hook) {
        var action = clone.querySelector('[' + hook + ']');
        if (action) {
            action.setAttribute(hook, account.id);
            action.setAttribute('data-number', account.number_account || '');
        }
    });

    $('#body-accounts').append(clone);
    refreshAccountSummary();

    var optionFormat = function(item) {
        if ( !item.id ) {
            return item.text;
        }

        var span = document.createElement('span');
        var imgUrl = item.element.getAttribute('data-image_bank');
        var template = '';

        template += '<img src="' + imgUrl + '" class="rounded-circle" width="25px" alt="image"/>  ';
        template += item.text;

        span.innerHTML = template;

        return $(span);
    };

    $('.bank').select2({
        placeholder: "Seleccione un banco",
        templateSelection: optionFormat,
        templateResult: optionFormat,
    });
    $('.currency').select2({
        placeholder: "Seleccione una moneda",
    });
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
function refreshAccountSummary() {
    var count = $('#body-accounts [data-account-row]').length;
    $('#account-count').text(count + ' cuentas');
    $('#account-empty').toggleClass('d-none', count > 0);
}
