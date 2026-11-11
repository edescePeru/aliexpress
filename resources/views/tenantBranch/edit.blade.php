@extends('layouts.appAdmin2')

@section('title', 'Editar Sucursales')

@section('activeTenantCompanyConfig', 'active')

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Configuración de Empresa</span>
        <h1 class="page-title">Sucursales</h1>
        <p class="next-page-description">Administra los datos de la sucursal.</p>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('tenantBranch.index', $branch->company_id ) }}"><i class="fa fa-home"></i> Sucursales</a></li>
        <li class="breadcrumb-item active">Modificar</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar sucursales</h5>
@endsection


@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    Datos generales sucursal
                </h3>
            </div>

            <div class="card-body">

                <div class="alert alert-light border">

                    <strong>Empresa:</strong>
                    {{ $branch->company->business_name }}

                    <br>

                    <strong>RUC:</strong>
                    {{ $branch->company->ruc }}

                </div>


                <form
                        id="formEditTenantBranch"
                        data-url="{{ route('tenantBranch.update', $branch->id) }}"
                >

                    @csrf
                    <div class="form-group">
                        <label for="catalog_slug">Nombre para el enlace público</label>
                        <input type="text" class="form-control" name="catalog_slug" id="catalog_slug" maxlength="80"
                               pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('catalog_slug', $branch->catalog_slug) }}" placeholder="local_outlet">
                        <small class="form-text text-muted">Usa minúsculas, números, guiones o guiones bajos. Déjalo vacío para mantener el catálogo sin publicar.</small>
                    </div>



                    <input
                            type="hidden"
                            name="company_id"
                            value="{{ $branch->company_id }}"
                    >


                    <div class="row">

                        <div class="form-group col-md-4">

                            <label>
                                Código *
                            </label>

                            <input
                                    type="text"
                                    name="code"
                                    class="form-control"
                                    maxlength="30"
                                    value="{{ $branch->code }}"
                            >

                        </div>


                        <div class="form-group col-md-8">

                            <label>
                                Nombre *
                            </label>

                            <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="{{ $branch->name }}"
                            >

                        </div>


                        <div class="form-group col-md-8">

                            <label>
                                Dirección
                            </label>

                            <input
                                    type="text"
                                    name="address"
                                    class="form-control"
                                    value="{{ $branch->address }}"
                            >

                        </div>


                        <div class="form-group col-md-4">

                            <label>
                                Teléfono
                            </label>

                            <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    value="{{ $branch->phone }}"
                            >

                        </div>

                    </div>


                    <div class="form-group">
                        <label for="branch_price_list_id">Lista de precios para ventas</label>
                        <select id="branch_price_list_id" name="price_list_id" class="form-control">
                            <option value="">Usar predeterminada de la empresa</option>
                            @if($branch->price_list_id && !$priceLists->contains('id', $branch->price_list_id))
                                <option value="{{ $branch->price_list_id }}" selected>Lista asignada no disponible — seleccione otra opción</option>
                            @endif
                            @foreach($priceLists as $priceList)
                                <option value="{{ $priceList->id }}" {{ (string) old('price_list_id', $branch->price_list_id) === (string) $priceList->id ? 'selected' : '' }}>{{ $priceList->name }} ({{ $priceList->currency }})</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Los precios especiales de variante prevalecen sobre el precio base del producto, dentro de esta lista.</small>
                        @if($branch->price_list_id && !$priceLists->contains('id', $branch->price_list_id))
                            <div class="text-danger mt-1">La lista asignada ya no está disponible. Seleccione otra lista o use la predeterminada.</div>
                        @endif
                    </div>

                    <div class="text-right">

                        <a
                                href="{{ route('tenantCompany.index') }}"
                                class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                                type="submit"
                                class="btn btn-primary"
                        >
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    @include('inventory.managedWarehouses', [
        'branchWarehouseContext' => true,
        'warehouseCompanyId' => $branch->company_id,
        'warehouseBranchId' => $branch->id,
    ])
@endsection


@section('scripts')
    <script src="{{ asset('js/tenantBranch/edit.js') }}"></script>
    <script src="{{ asset('js/inventory/managedWarehouses.js') }}"></script>
@endsection