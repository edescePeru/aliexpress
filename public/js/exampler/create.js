var $formCreate;

$(document).ready(function () {
    $('.select2').select2({
        placeholder: 'Seleccione una marca',
        allowClear: true,
        width: '100%'
    });

    $formCreate = $('#formCreate');
    $formCreate.on('submit', storeExampler);
});

function mayus(element) {
    element.value = element.value.toUpperCase();
}

function storeExampler(event) {
    event.preventDefault();

    $.ajax({
        url: $formCreate.data('url'),
        method: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function (data) {
            toastr.success(data.message, 'Éxito', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '2000'
            });
            setTimeout(function () {
                location.reload();
            }, 2000);
        },
        error: function (xhr) {
            showValidationErrors(xhr, 'No se pudo registrar el modelo.');
        }
    });
}

function showValidationErrors(xhr, fallback) {
    var response = xhr.responseJSON || {};

    if (response.errors) {
        $.each(response.errors, function (property, messages) {
            toastr.error(messages, 'Error', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '3000'
            });
        });
        return;
    }

    toastr.error(response.message || fallback, 'Error', {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: '3000'
    });
}
