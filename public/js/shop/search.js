$(document).on('submit', '.search-model-form', function (e) {
    e.preventDefault();

    let search = $('#search-input').val().trim();

    if (!search) {
        return;
    }

    $.ajax({
        url: window.APP_SHOP_SEARCH.URL,
        method: 'GET',
        data: {
            search: search
        },
        beforeSend: function () {
            $('#search-input').prop('disabled', true);
        },
        success: function (response) {
            if (response.success && response.url) {
                window.location.href = response.url;
            }
        },
        error: function () {
            alert('No se encontró ningún producto con ese nombre.');
        },
        complete: function () {
            $('#search-input').prop('disabled', false);
        }
    });
});