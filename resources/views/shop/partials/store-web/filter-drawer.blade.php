{{-- CURRENT TEMPORARY BACKEND CONTRACT: scalar category/subcategory, sizes/colors arrays, price. VISUAL PRESENT / FUNCTIONAL PENDING: brand, availability, condition and real multicategory. --}}
<div class="catalog-filter-drawer" id="catalog-filter-drawer" aria-hidden="true">
        <div class="catalog-filter-drawer__overlay" data-filter-close></div>
        <div class="catalog-filter-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="catalog-filter-title" tabindex="-1">
            <header class="catalog-filter-drawer__header">
                <div>
                    <span class="catalog-filter-drawer__eyebrow">Explorar catálogo</span>
                    <h2 id="catalog-filter-title">Filtros</h2>
                </div>
                <button class="catalog-filter-drawer__close" type="button" data-filter-close aria-label="Cerrar filtros">
                    <span aria-hidden="true">&times;</span>
                </button>
            </header>

            <div class="catalog-filter-drawer__body">
                <p class="catalog-filter-drawer__intro">Ajusta las opciones para encontrar productos más fácilmente.</p>

                <details class="catalog-filter-group" open>
                    <summary><span>Categoría</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content" data-facet="category_id" aria-label="Categoría: selección única">
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="category" value="category-1"><span>Categoría 1</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="category" value="category-2"><span>Categoría 2</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="category" value="category-3"><span>Categoría 3</span></label>
                    </div>
                </details>

                <details class="catalog-filter-group" data-real-group hidden open>
                    <summary><span>Subcategoría</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content" data-facet="subcategory_id" aria-label="Subcategoría: selección única"></div>
                </details>

                <details class="catalog-filter-group" data-real-group hidden open>
                    <summary><span>Talla</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content" data-facet="size_ids"></div>
                </details>

                <details class="catalog-filter-group" data-real-group hidden open>
                    <summary><span>Color</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content" data-facet="color_ids"></div>
                </details>

                <details class="catalog-filter-group" open>
                    <summary><span>Marca</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content">
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="brand" value="brand-a"><span>Marca A</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="brand" value="brand-b"><span>Marca B</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="brand" value="brand-c"><span>Marca C</span></label>
                    </div>
                </details>

                <details class="catalog-filter-group" open>
                    <summary><span>Precio</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content">
                        <div class="catalog-filter-price">
                            <label><span>Desde</span><span class="catalog-filter-price__input"><span>S/</span><input type="text" inputmode="decimal" name="min_price" data-price="min_price" disabled placeholder="0" aria-label="Precio desde"></span></label>
                            <label><span>Hasta</span><span class="catalog-filter-price__input"><span>S/</span><input type="text" inputmode="decimal" name="max_price" data-price="max_price" disabled placeholder="{{ $maxPrice ?? 500 }}" aria-label="Precio hasta"></span></label>
                        </div>
                    </div>
                </details>

                <details class="catalog-filter-group" open>
                    <summary><span>Disponibilidad</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content">
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="availability" value="available"><span>Disponible</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="availability" value="offer"><span>En promoción</span></label>
                    </div>
                </details>

                <details class="catalog-filter-group">
                    <summary><span>Condición</span><span class="arrow_carrot-down" aria-hidden="true"></span></summary>
                    <div class="catalog-filter-group__content">
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="condition" value="new"><span>Nuevo</span></label>
                        <label class="catalog-filter-option"><input disabled type="checkbox" name="condition" value="other"><span>Otra condición</span></label>
                    </div>
                </details>
            </div>

            <footer class="catalog-filter-drawer__footer">
                <button class="catalog-filter-clear" type="button">Limpiar</button>
                <button class="catalog-filter-apply" type="button" data-filter-apply>Ver resultados</button>
            </footer>
        </div>
    </div>