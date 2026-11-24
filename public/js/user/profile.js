$(document).ready(function () {
    $("#image").fileinput({
        showCaption: true,
        showCancel: false,
        showUpload: false,
        browseOnZoneClick: false,
        language: 'es',
        theme: 'fas',
        browseClass: "btn btn-primary",
        maxFilesNum: 1,
        allowedFileExtensions: ["jpg", "png", "jpeg"]
    });

    $('#btnImage').on('click', showFormImage);
    $('#btnCancel').on('click', hideFormImage);
    
    $formImage = $('#formImage');
    $formImage.on('submit', storeImage);

    $formSettings = $('#formSettings');
    $formSettings.on('submit', storeSettings);

    $formResetPassword = $('#formResetPassword');
    $formResetPassword.on('submit', storePassword);

    $('[data-show]').on('click', showPassword);

});

var $formImage;
var $formSettings;
var $formResetPassword;

function showPassword() {
    //console.log();
    if($(this).parent().prev().val() === '')
        return;
    if( $(this).parent().prev().attr('type') === 'password' ){

        $(this).parent().prev().attr('type', 'text');
        $(this).children().removeClass('far fa-eye-slash');
        $(this).children().addClass('far fa-eye');
    }else{
        $(this).parent().prev().attr('type', 'password');
        $(this).children().removeClass('far fa-eye');
        $(this).children().addClass('far fa-eye-slash');
    }
}

function showFormImage() {
    $('#btnImage').css("display", "none");
    $('#changeImage').removeAttr("style");
}

function hideFormImage() {
    $('#changeImage').css("display", "none");
    $('#btnImage').removeAttr("style");
}

function storeImage(event) {
    event.preventDefault();
    const form = this;
    const $button = $(form).find('button[type="submit"], input[type="submit"]');
    if ($(form).data('avatar-sending')) { return; }
    if (!form.elements.image.files.length) { toastr.error('Seleccione una imagen PNG o JPG.'); return; }
    $(form).data('avatar-sending', true);
    $button.prop('disabled', true);
    $.ajax({url: $(form).data('url'), method: 'POST', data: new FormData(form), processData: false, contentType: false})
        .done(function (response) { toastr.success(response.message || 'Imagen actualizada.'); setTimeout(function () { location.reload(); }, 1000); })
        .fail(function (xhr) {
            const response = xhr.responseJSON || {};
            const errors = response.errors || {};
            const first = Object.keys(errors)[0];
            toastr.error(first && errors[first] && errors[first][0] ? errors[first][0] : (response.message || 'No se pudo actualizar la imagen.'));
            $(form).data('avatar-sending', false);
            $button.prop('disabled', false);
        });
}

function storeSettings() {
    if ($("#name").val() === '') {
        toastr.error('Ingrese un nombre adecuado', 'Error',
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
    if ($("#email").val() === '') {
        toastr.error('Ingrese un email adecuado', 'Error',
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
    event.preventDefault();
    // Obtener la URL
    var settingsUrl = $formSettings.data('url');
    $.ajax({
        url: settingsUrl,
        method: 'POST',
        data: new FormData(this),
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
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            if (data.responseJSON.message && !data.responseJSON.errors)
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
                        "timeOut": "3000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            } else {
                for ( var nameError in data.responseJSON.errors )
                {
                    toastr.error(data.responseJSON.errors[nameError], 'Error',
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
                            "timeOut": "3000",
                            "extendedTimeOut": "1000",
                            "showEasing": "swing",
                            "hideEasing": "linear",
                            "showMethod": "fadeIn",
                            "hideMethod": "fadeOut"
                        });
                }
            }

        },
    });
}

function storePassword() {
    event.preventDefault();
    // Obtener la URL
    var passwordUrl = $formResetPassword.data('url');
    $.ajax({
        url: passwordUrl,
        method: 'POST',
        data: new FormData(this),
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
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            if (data.responseJSON.message && !data.responseJSON.errors)
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
                        "timeOut": "3000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            } else {
                for ( var nameError in data.responseJSON.errors )
                {
                    toastr.error(data.responseJSON.errors[nameError], 'Error',
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
                            "timeOut": "3000",
                            "extendedTimeOut": "1000",
                            "showEasing": "swing",
                            "hideEasing": "linear",
                            "showMethod": "fadeIn",
                            "hideMethod": "fadeOut"
                        });
                }
            }

        },
    });
}

