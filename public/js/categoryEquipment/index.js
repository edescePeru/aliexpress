$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    $(document).on('click', '[data-item]', showData);
    getDataCategoryEquipment(1);
    $("#btn-search").on('click', showDataSeach);

    var suggestionsContainer = $('#suggestions-container');
    $('.categoryTypeahead').typeahead({
            hint: true,
            highlight: true,
            minLength: 2
        },
        {
            limit: 12,
            source: function(query, process) {
                $.ajax({
                    url: '/dashboard/get/category/equipment/typeahead',
                    method: 'GET',
                    data: { query: query },
                    success: function(data) {
                        process(data);
                        renderSuggestions(data);
                    }

                });
            },

        });
    $('#inputNameCategoryEquipment').on('input', function() {
        var inputContent = $(this).val();
        if (inputContent.trim() === '') {
            var suggestionsContainer = $('#suggestions-container');
            suggestionsContainer.html('');
        }
        getDataCategoryEquipment(1);
    });

    suggestionsContainer.on('click', '.suggestion', function() {
        var selectedValue = $(this).text();
        $('#inputNameCategoryEquipment').val(selectedValue);
        suggestionsContainer.html('');
    });
});

var $permissions;
function showDataSeach() {
    getDataCategoryEquipment(1)
}
function renderSuggestions(suggestions) {
    var suggestionsContainer = $('#suggestions-container');
    suggestionsContainer.html('');

    for (var i = 0; i < suggestions.length; i++) {
        var suggestionHtml = $('<button type="button" class="suggestion list-group-item list-group-item-action py-2"></button>').text(suggestions[i].description);
        suggestionsContainer.append(suggestionHtml);
    }
}
function showData() {
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataCategoryEquipment(numberPage)
}

function getDataCategoryEquipment($numberPage) {
    var nameCategoryEquipment = $('#inputNameCategoryEquipment').val();
    console.log(nameCategoryEquipment);
    $.get('/dashboard/get/data/category/equipments/'+$numberPage, {
        name_category_equipment: nameCategoryEquipment
    }, function(data) {
        renderDataCategoryEquipments(data);

    }).fail(function(jqXHR, textStatus, errorThrown) {
        $('#body-card').html('<tr><td colspan="4" class="text-center text-danger py-4">No se pudieron cargar las categorías.</td></tr>');
        $('#textPagination').text('Error de carga');
        $('#numberItems').text('—');
        $('#pagination').empty();
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if ((jqXHR.responseJSON || {}).message && !(jqXHR.responseJSON || {}).errors) {
            toastr.error((jqXHR.responseJSON || {}).message, 'Error', {
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
        for (var property in (jqXHR.responseJSON || {}).errors) {
            toastr.error((jqXHR.responseJSON || {}).errors[property], 'Error', {
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

function renderDataCategoryEquipments(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").text(pagination.totalFilteredRecords ? 'Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' categorías' : 'Sin resultados');
    if (!dataAccounting.length) $('#body-card').html('<tr><td colspan="4" class="text-center text-muted py-4">No se encontraron categorías.</td></tr>');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataAccounting.length ; j++) {
        renderCategoryEquipmentCard(dataAccounting[j]);
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

function renderCategoryEquipmentCard(data) {
    var clone = activateTemplate('#item-card');
    clone.querySelector("[data-description]").textContent = data.description;
    clone.querySelector("[data-number]").innerHTML = data.number;
    clone.querySelector("[data-id]").setAttribute('data-id', data.id);
    /*var imageUrl = document.location.origin + '/images/categoryEquipment/' + data.image;
    clone.querySelector("[data-image]").setAttribute('src', imageUrl);*/
    var imageElement = clone.querySelector("[data-image]");
    imageElement.setAttribute('src', document.location.origin + '/images/categoryEquipment/' + data.image);
    imageElement.setAttribute('width', '48');
    imageElement.style.height = 'auto';

    var equipButton = clone.querySelector("[data-equip]");
    equipButton.setAttribute('data-equip', data.id);
    equipButton.setAttribute('href', document.location.origin + '/dashboard/equipos/categoria/' + data.id);

    var editButton = clone.querySelector("[data-edit]");
    if (editButton) {
    editButton.setAttribute('data-edit', data.id);
    editButton.setAttribute('data-description', data.description);
    editButton.setAttribute('data-image', data.image);
    }

    var deleteButton = clone.querySelector("[data-delete]");
    if (deleteButton) {
    deleteButton.setAttribute('data-delete', data.id);
    deleteButton.setAttribute('data-description', data.description);
    deleteButton.setAttribute('data-image', data.image);
    }

    $("#body-card").append(clone);
    $('[data-toggle="tooltip"]').tooltip();
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



