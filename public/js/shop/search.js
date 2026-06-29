$(document).on('submit', '.search-model-form', function (e) {
    e.preventDefault();

    let search = $('#search-input').val().trim();

    if (!search) {
        return;
    }

    let catalogUrl = window.APP_SHOP_SEARCH.CATALOG_URL;

    window.location.href = catalogUrl + '?search=' + encodeURIComponent(search);
});