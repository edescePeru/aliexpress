$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    getDataCategoryEquipmentEliminated(1);

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSeach);
});

var $permissions;

function showDataSeach() {
    getDataCategoryEquipmentEliminated(1);
}

function showData() {
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataCategoryEquipmentEliminated(numberPage);
}

function getDataCategoryEquipmentEliminated($numberPage) {
    var nameCategoryEquipment = $('#inputNameCategoryEquipment').val();
    console.log(nameCategoryEquipment);
    $.get('/dashboard/get/data/category/equipmentseliminated/' + $numberPage, {
        name_category_equipment: nameCategoryEquipment
    }, function (data) {
        renderDataCategoryEquipments(data);
    }).fail(function (jqXHR, textStatus, errorThrown) {
        $('#body-card').html('<tr><td colspan="4" class="text-center text-danger py-4">No se pudieron cargar las categorías.</td></tr>');
        $('#textPagination').text('Error de carga');
        $('#numberItems').text('—');
        $('#pagination').empty();
        // Función de error
        console.error(textStatus, errorThrown);
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
    var imageElement = clone.querySelector("[data-image]");
    imageElement.setAttribute('src', document.location.origin + '/images/categoryEquipment/' + data.image);
    imageElement.setAttribute('width', '48');
    imageElement.style.height = 'auto';

    var restoreButton = clone.querySelector("[data-restore]");
    if (restoreButton) {
    restoreButton.setAttribute('data-restore', data.id);
    restoreButton.setAttribute('data-description', data.description);
    restoreButton.setAttribute('data-image', data.image);
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






