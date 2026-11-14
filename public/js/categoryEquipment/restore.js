
$(document).on('click', '[data-restore]', function () {
    var categoryId = $(this).data('restore');
    var description = $(this).data('description');
    var image = $(this).data('image');
    console.log('Valor de categoryId:', categoryId);

    $('#btn-restore-confirm').off('click').on('click', function () {
        $.ajax({
            url:  $('#restoreModal').data('url').replace('__ID__', categoryId),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {
                console.log(data);
                toastr.success('Categoría restaurada con éxito.');
                $('#restoreModal').modal('hide');
                window.location.reload();
            },
            error: function (error) {
                console.error(error);
                toastr.error('Error al restaurar la categoría.');
            }
        });
    });

    $('#restoreModal').modal('show');
});