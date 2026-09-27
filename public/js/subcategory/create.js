$(document).ready(function () {
    $(".select2").select2({
        theme: "bootstrap4",
        placeholder: "Seleccione una categoría",
        allowClear: true,
        width: "100%"
    });

    $formCreate = $('#formCreate');
    $('#btn-submit').on('click', storeSubCategory);
    $formCreate.on('reset', syncSelect2AfterReset);
    //$formCreate.on('submit', storeSubCategory);

    var subcategoryIndex = 1;

    $('#add-subcategory').click(function () {
        var newGroup = `
        <div class="form-row align-items-end subcategory-group">
            <div class="form-group col-md-5">
                <label class="sr-only" for="subcategory-name-${subcategoryIndex}">Nombre de subcategoría</label>
                <input type="text" class="form-control" id="subcategory-name-${subcategoryIndex}" name="subcategories[${subcategoryIndex}][name]" placeholder="Ej.: Calzado deportivo" onkeyup="mayus(this);" maxlength="255" required>
            </div>
            <div class="form-group col-md-5">
                <label class="sr-only" for="subcategory-description-${subcategoryIndex}">Descripción de subcategoría</label>
                <input type="text" class="form-control" id="subcategory-description-${subcategoryIndex}" name="subcategories[${subcategoryIndex}][description]" placeholder="Ej.: Calzado para actividad deportiva" onkeyup="mayus(this);" maxlength="255">
            </div>
            <div class="form-group col-md-2">
                <button type="button" class="btn btn-outline-danger btn-block remove-subcategory" aria-label="Quitar subcategoría">Quitar</button>
            </div>
        </div>
        `;
        $('#subcategory-container').append(newGroup);
        subcategoryIndex++;
    });

    // Remover subcategoría
    $(document).on('click', '.remove-subcategory', function () {
        $(this).closest('.subcategory-group').remove();
    });
});

var $formCreate;

function mayus(e) {
    e.value = e.value.toUpperCase();
}

function syncSelect2AfterReset() {
    var form = this;

    setTimeout(function () {
        $(form).find('.select2').trigger('change');
    }, 0);
}

function storeSubCategory(event) {
    event.preventDefault();
    $("#btn-submit").attr("disabled", true);
    // Obtener la URL
    var createUrl = $formCreate.data('url');
    var form = new FormData($('#formCreate')[0]);
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
