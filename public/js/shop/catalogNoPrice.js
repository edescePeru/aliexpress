let currentPage = 1;
let currentCategoryId = '';
let currentSubcategoryId = '';

let currentMinPrice = '';
let currentMaxPrice = '';

let currentSizeIds = [];

let currentColorIds = [];

$(document).ready(function () {
    loadCategories();
    loadSizes();
    loadColors();
    loadProducts(1);

    $(document).on('click', '#products-pagination a', function (e) {
        e.preventDefault();

        let page = $(this).data('page');

        if (!page || page === currentPage) {
            return;
        }

        loadProducts(page);
    });

    $(document).on('click', '.category-filter', function (e) {
        e.preventDefault();

        currentCategoryId = $(this).data('category-id');
        currentSubcategoryId = '';

        $('#show-all-products').removeClass('active');
        $('.category-filter, .subcategory-filter').removeClass('active');
        $(this).addClass('active');

        loadProducts(1);
    });

    $(document).on('click', '.subcategory-filter', function (e) {
        e.preventDefault();

        currentCategoryId = $(this).data('category-id');
        currentSubcategoryId = $(this).data('subcategory-id');

        $('#show-all-products').removeClass('active');
        $('.category-filter, .subcategory-filter').removeClass('active');
        $(this).addClass('active');

        loadProducts(1);
    });

    $(document).on('click', '#show-all-products', function (e) {
        e.preventDefault();

        currentCategoryId = '';
        currentSubcategoryId = '';

        $('.category-filter, .subcategory-filter').removeClass('active');
        $('#show-all-products').addClass('active');

        loadProducts(1);
    });

    $(document).on('click', '#btn-filter-price', function (e) {
        e.preventDefault();

        currentMinPrice = cleanPrice($('#minamount').val());
        currentMaxPrice = cleanPrice($('#maxamount').val());

        loadProducts(1);
    });

    $(document).on('change', '.size-filter', function () {
        currentSizeIds = $('.size-filter:checked').map(function () {
            return $(this).val();
        }).get();

        loadProducts(1);
    });

    $(document).on('change', '.color-filter', function () {
        currentColorIds = $('.color-filter:checked').map(function () {
            return $(this).val();
        }).get();

        loadProducts(1);
    });
});

function loadColors() {
    $.ajax({
        url: window.APP_SHOP.URLS.COLORS,
        method: 'GET',
        success: function (response) {
            renderColors(response.data || []);
        },
        error: function () {
            $('#colors-container').html('<p>No se pudieron cargar los colores.</p>');
        }
    });
}

function renderColors(colors) {
    let html = '';

    if (!colors.length) {
        $('#colors-container').html('<p>No hay colores registrados.</p>');
        return;
    }

    colors.forEach(function (color) {
        let inputId = `color_${color.id}`;

        html += `
            <label for="${inputId}">
                ${color.name}
                <input type="checkbox"
                       id="${inputId}"
                       class="color-filter"
                       value="${color.id}">
                <span class="checkmark"></span>
            </label>
        `;
    });

    $('#colors-container').html(html);
}

function loadSizes() {
    $.ajax({
        url: window.APP_SHOP.URLS.SIZES,
        method: 'GET',
        success: function (response) {
            renderSizes(response.data || []);
        },
        error: function () {
            $('#sizes-container').html('<p>No se pudieron cargar las tallas.</p>');
        }
    });
}

function renderSizes(sizes) {
    let html = '';

    if (!sizes.length) {
        $('#sizes-container').html('<p>No hay tallas registradas.</p>');
        return;
    }

    sizes.forEach(function (size) {
        let inputId = `size_${size.id}`;

        html += `
            <label for="${inputId}">
                ${size.name}
                <input type="checkbox"
                       id="${inputId}"
                       class="size-filter"
                       value="${size.id}">
                <span class="checkmark"></span>
            </label>
        `;
    });

    $('#sizes-container').html(html);
}

function cleanPrice(value) {
    return String(value || '')
        .replace('S/.', '')
        .replace('$', '')
        .replace(',', '')
        .trim();
}

function loadProducts(page = 1) {
    currentPage = page;

    let url = window.APP_SHOP.URLS.PRODUCTS.replace(':page', page);

    $.ajax({
        url: url,
        method: 'GET',
        data: {
            category_id: currentCategoryId,
            subcategory_id: currentSubcategoryId,
            min_price: currentMinPrice,
            max_price: currentMaxPrice,
            size_ids: currentSizeIds,
            color_ids: currentColorIds
        },
        success: function (response) {
            renderProducts(response.data || []);
            renderPagination(response.pagination || {});
        },
        error: function () {
            $('#products-container').html(`
                <div class="col-lg-12 text-center">
                    <p>No se pudieron cargar los productos.</p>
                </div>
            `);
        }
    });
}

function renderProducts(products) {
    let $container = $('#products-container');

    $container.empty();

    let html = '';

    if (!products.length) {
        $container.html(`
            <div class="col-lg-12 text-center">
                <p>No se encontraron productos.</p>
            </div>
        `);
        return;
    }

    products.forEach(function (product) {
        let imageUrl = product.image_url || window.APP_SHOP.URLS.DEFAULT_IMAGE;
        let productName = product.full_name || 'Producto sin nombre';
        let priceText = product.price_text || 'S/. 0.00';

        let whatsappText = encodeURIComponent(`Hola, quiero consultar por el producto: ${productName}`);
        let whatsappUrl = `${window.APP_SHOP.URLS.WHATSAPP}?text=${whatsappText}`;

        html += `
            <div class="col-lg-4 col-md-6">
                <div class="product__item"
                     data-product-id="${product.id}"
                     data-material-id="${product.material_id}"
                     data-has-variants="${product.has_variants ? 1 : 0}"
                     data-stock="${product.stock}"
                     data-price="${product.price}">

                    <div class="product__item__pic set-bg" data-setbg="${imageUrl}">
                        <ul class="product__hover">
                            <li>
                                <a href="${imageUrl}" class="image-popup">
                                    <span class="arrow_expand"></span>
                                </a>
                            </li>
                            <li>
                                <a href="${whatsappUrl}" target="_blank">
                                    <i class="fa fa-whatsapp whatsapp-icon"></i>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="product__item__text">
                        <h6><a href="${product.detail_url}">${productName}</a></h6>
                        
                    </div>
                </div>
            </div>
        `;
    });

    $container.html(html);

    refreshShopPlugins();
}

function refreshShopPlugins() {
    $('.set-bg').each(function () {
        let bg = $(this).data('setbg');
        $(this).css('background-image', 'url(' + bg + ')');
    });

    $('.image-popup').magnificPopup('destroy');

    $('.image-popup').magnificPopup({
        type: 'image'
    });
}

function renderPagination(pagination) {
    let current = parseInt(pagination.currentPage || 1);
    let total = parseInt(pagination.totalPages || 1);

    if (total <= 1) {
        $('#products-pagination').html('');
        return;
    }

    let html = '';

    if (current > 1) {
        html += `<a href="#" data-page="${current - 1}"><i class="fa fa-angle-left"></i></a>`;
    }

    let pages = getPaginationPages(current, total);

    pages.forEach(function (page) {
        if (page === '...') {
            html += `<span class="pagination-dots">...</span>`;
        } else {
            html += `
                <a href="#"
                   data-page="${page}"
                   class="${page === current ? 'active' : ''}">
                    ${page}
                </a>
            `;
        }
    });

    if (current < total) {
        html += `<a href="#" data-page="${current + 1}"><i class="fa fa-angle-right"></i></a>`;
    }

    $('#products-pagination').html(html);
}

function getPaginationPages(current, total) {
    if (total <= 5) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    if (current <= 2) {
        return [1, 2, 3, '...', total];
    }

    if (current >= total - 1) {
        return [1, '...', total - 2, total - 1, total];
    }

    return [current - 1, current, current + 1, '...', total];
}

function loadCategories() {
    $.ajax({
        url: window.APP_SHOP.URLS.CATEGORIES,
        method: 'GET',
        success: function (response) {
            renderCategories(response.data || []);
        },
        error: function () {
            $('#categoriesAccordion').html(`
                <div class="card">
                    <div class="card-body">
                        <p>No se pudieron cargar las categorías.</p>
                    </div>
                </div>
            `);
        }
    });
}

function renderCategories(categories) {
    let html = '';

    if (!categories.length) {
        $('#categoriesAccordion').html(`
            <div class="card">
                <div class="card-body">
                    <p>No hay categorías registradas.</p>
                </div>
            </div>
        `);
        return;
    }

    categories.forEach(function (category, index) {
        let collapseId = `categoryCollapse${category.id}`;
        let isFirst = index === -1;

        html += `
            <div class="card">
                <div class="card-heading ${isFirst ? 'active' : ''}">
                    <a data-toggle="collapse"
                       data-target="#${collapseId}"
                       data-category-id="${category.id}"
                       href="#">
                        ${category.name}
                    </a>
                </div>

                <div id="${collapseId}"
                     class="collapse ${isFirst ? 'show' : ''}"
                     data-parent="#categoriesAccordion">
                    <div class="card-body">
                        <ul>
        `;

        if (category.subcategories.length) {
            category.subcategories.forEach(function (subcategory) {
                html += `
                    <li>
                        <a href="#"
                           class="subcategory-filter"
                           data-category-id="${category.id}"
                           data-subcategory-id="${subcategory.id}">
                            ${subcategory.name}
                        </a>
                    </li>
                `;
            });
        } else {
            html += `
                <li>
                    <a href="#"
                       class="category-filter"
                       data-category-id="${category.id}">
                        Ver productos
                    </a>
                </li>
            `;
        }

        html += `
                        </ul>
                    </div>
                </div>
            </div>
        `;
    });

    $('#categoriesAccordion').html(html);
}

