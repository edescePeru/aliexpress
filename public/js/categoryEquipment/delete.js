$(document).on('click', '[data-delete]', function () {
    var categoryId = $(this).data('delete');
    var description = $(this).data('description');
    var image = $(this).data('image');


    $('#btn-delete-confirm').off('click').on('click', function () {

        $.ajax({
            url: $('#deleteModal').data('url').replace('__ID__', categoryId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {

                console.log(data);
                toastr.success('Categoría eliminada con éxito.');
                $('#deleteModal').modal('hide');


                window.location.reload();
            },
            error: function (error) {

                console.error(error);
                toastr.error('Error al eliminar la categoría.');
            }
        });
    });

    $('#deleteModal').modal('show');
});

