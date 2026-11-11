<div class="catalog-filter-drawer" id="catalog-filter-drawer" aria-hidden="true">
    <div class="catalog-filter-drawer__overlay" data-filter-close></div>
    <form method="get" action="{{ $storeWebCatalogUrl ?? ($branch ? route('publicCatalog.branch', [$company->catalog_slug, $branch->catalog_slug]) : route('publicCatalog.company', $company->catalog_slug)) }}"
          class="catalog-filter-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="catalog-filter-title" tabindex="-1">
        <header class="catalog-filter-drawer__header">
            <h2 id="catalog-filter-title">Filtros</h2>
            <button class="catalog-filter-drawer__close" type="button" data-filter-close aria-label="Cerrar filtros">&times;</button>
        </header>
        <div class="catalog-filter-drawer__body">
            <input type="hidden" name="buscar" value="{{ $filters['buscar'] ?? '' }}">
            @unless($branch)
                <div class="form-group">
                    <label for="catalog-local">Tienda</label>
                    <select class="form-control" name="local" id="catalog-local">
                        <option value="">Todas las tiendas</option>
                        @foreach($branches as $location)
                            <option value="{{ $location->catalog_slug }}" {{ $selected && $selected->id === $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            @foreach(['category_id' => 'Categoría', 'subcategory_id' => 'Subcategoría', 'talla_id' => 'Talla', 'color_id' => 'Color'] as $field => $label)
                @if($facets[$field]->isNotEmpty())
                    <div class="form-group">
                        <label for="catalog-{{ $field }}">{{ $label }}</label>
                        <select class="form-control" id="catalog-{{ $field }}" name="{{ $field }}">
                            <option value="">Todas las opciones</option>
                            @foreach($facets[$field] as $option)
                                <option value="{{ $option->id }}" {{ (string) ($filters[$field] ?? '') === (string) $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @endforeach
            @if($showPrices)
                <fieldset class="catalog-filter-group">
                    <legend class="h6">Precio en soles</legend>
                    <div class="catalog-filter-price">
                        <label>Desde <input class="form-control" type="number" min="0" step="0.01" name="min_price" value="{{ $filters['min_price'] ?? '' }}"></label>
                        <label>Hasta <input class="form-control" type="number" min="0" step="0.01" name="max_price" value="{{ $filters['max_price'] ?? '' }}"></label>
                    </div>
                </fieldset>
            @endif
        </div>
        <footer class="catalog-filter-drawer__footer">
            <a class="catalog-filter-clear" href="{{ $branch ? route('publicCatalog.branch', [$company->catalog_slug, $branch->catalog_slug]) : route('publicCatalog.company', $company->catalog_slug) }}">Limpiar</a>
            <button class="catalog-filter-apply" type="submit">Ver resultados</button>
        </footer>
    </form>
</div>
