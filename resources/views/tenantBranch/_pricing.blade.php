<section class="next-form-section" aria-labelledby="branch-pricing-title">
    <div class="next-section-header"><div><span class="next-section-kicker">02</span><h2 id="branch-pricing-title">Precios de la sucursal</h2><p>Asignación local de una lista activa de esta empresa.</p></div></div>
    <div class="row"><div class="col-lg-8">
        <div class="form-group">
            <label for="branch_price_list_id">Lista de precios para ventas</label>
            <select id="branch_price_list_id" name="price_list_id" class="form-control" aria-describedby="branch-price-help">
                <option value="">Sin asignación explícita — usar default si es único</option>
                @if(isset($branch) && $branch->price_list_id && !$priceLists->contains('id', $branch->price_list_id))
                    <option value="{{ $branch->price_list_id }}" selected>Lista asignada no disponible — requiere revisión</option>
                @endif
                @foreach($priceLists as $priceList)
                    <option value="{{ $priceList->id }}" {{ (string) old('price_list_id', isset($branch) ? $branch->price_list_id : null) === (string) $priceList->id ? 'selected' : '' }}>{{ $priceList->name }} · {{ $priceList->currency }} · Activa{{ $priceList->is_default ? ' · Default empresa' : '' }}</option>
                @endforeach
            </select>
            <small id="branch-price-help" class="form-text text-muted">Sin asignación se utiliza el único default activo de la empresa. Si no existe, no se resuelve una lista; si hay varios, la configuración ambigua impide resolver una lista. Una lista asignada inválida no se sustituye automáticamente.</small>
        </div>
        @if(isset($branch) && $branch->price_list_id && !$priceLists->contains('id', $branch->price_list_id))
            <div class="alert alert-warning" role="alert">La lista asignada ya no está disponible. Revise la asignación antes de guardar.</div>
        @endif
        @if($priceLists->isEmpty())
            <div class="alert alert-light border" role="status">No hay listas activas disponibles para esta empresa.</div>
        @endif
        <p class="small text-muted mb-0">Dentro de la lista aplicada, el precio específico de la variante prevalece sobre el precio base del material. Sin precio configurado no se interpreta un importe cero. La venta POS exige que la moneda de la lista coincida con la moneda de venta de la empresa; no realiza conversión automática.</p>
    </div></div>
</section>
