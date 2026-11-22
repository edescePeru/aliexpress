$(document).on('click', '[data-edit]', function () {

    var categoryId = $(this).data('edit');
    var description = $(this).data('description');
    var image = $(this).data('image');
    console.log(categoryId);

    $('#editDescription').val(description);
    var path = document.location.origin;
    var completePath = path + '/images/categoryEquipment/' + image;

    $('#editImagePreview').attr('src', completePath);
    $('#editImagePreview').css('width', '100px');
    $('#editImagePreview').css('height', 'auto');


    $('#btn-edit-confirm').off('click').on('click', function () {
        // Obtiene los nuevos valores del formulario de edición
        var newDescription = $('#editDescription').val();
        var newImage = $('#editImage')[0].files[0];

        var formData = new FormData();
        formData.append('description', newDescription);
        if (newImage) {
            formData.append('editImage', newImage);
        }

        console.log(newImage);
        $.ajax({
            url: $('#editModal').data('url').replace('__ID__', categoryId),
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
                console.log(data);
                toastr.success('Categoría actualizada con éxito.');
                $('#editModal').modal('hide');
                window.location.reload();

            },
            error: function (error) {
                console.error(error);
                toastr.error('Error al actualizar la categoría.');
            }
        });
    });
    $('#editModal').modal('show');
});
