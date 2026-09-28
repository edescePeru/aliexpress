$(document).ready(function () {
    $formStocksFile = $('#formStocksFile');
    $stockFile = $('#stockFile');
    $stockFileLabel = $('[data-stock-file-label]');
    $submitStockFiles = $('#btn-submitStockFiles');

    $('#btn-submitStockFiles').on('click', storeStockFiles);
    $('#exampleStockFile').on('click', downloadExampleStock);
    $stockFile.on('change', syncStockFileState);
    $formStocksFile.on('reset', function () {
        setTimeout(syncStockFileState, 0);
    });

    syncStockFileState();
});

var $formStocksFile;
var $stockFile;
var $stockFileLabel;
var $submitStockFiles;

function syncStockFileState() {
    var input = $stockFile[0];
    var selectedFile = input && input.files && input.files.length
        ? input.files[0]
        : null;

    $stockFileLabel.text(selectedFile ? selectedFile.name : 'Ningún archivo seleccionado');
    $submitStockFiles.prop('disabled', !selectedFile);
}

function downloadExampleStock() {
    window.location.href = $('#exampleStockFile').data('url');
}

function storeStockFiles(event) {
    event.preventDefault();

    if (!$stockFile[0].files.length) {
        syncStockFileState();
        return;
    }

    // Obtener la URL
    $submitStockFiles.prop('disabled', true);
    var formulario = $('#formStocksFile')[0];
    var form = new FormData(formulario);
    var createUrl = $formStocksFile.data('url');
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
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
                $submitStockFiles.prop('disabled', false);
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            var response = data.responseJSON || {};

            if (response.message && !response.errors)
            {
                toastr.error(response.message, 'Error',
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
            for (var property in response.errors || {}) {
                toastr.error(response.errors[property], 'Error',
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

            syncStockFileState();
        },
    });
}
