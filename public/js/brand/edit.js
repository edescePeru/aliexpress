var $formEdit;

$(document).ready(function () {
    $formEdit = $('#formEdit');
    $formEdit.on('submit', updateBrand);
});

function mayus(element) {
    element.value = element.value.toUpperCase();
}

function updateBrand(event) {
    event.preventDefault();

    $.ajax({
        url: $formEdit.data('url'),
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
                $(location).attr('href', data.url);
            }, 2000);
        },
        error: function (xhr) {
            showValidationErrors(xhr, 'No se pudo modificar la marca.');
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
