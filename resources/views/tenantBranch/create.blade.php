@extends('layouts.appAdmin2')

@section('title', 'Crear Sucursales')

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
        <li class="breadcrumb-item"><a href="{{ route('tenantBranch.index', $branch->company_id) }}"><i class="fa fa-home"></i> Sucursales</a></li>
        <li class="breadcrumb-item active">Crear</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Crear sucursales</h5>
@endsection

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    Nueva sucursal
                </h3>
            </div>

            <div class="card-body">

                <div class="alert alert-light border">
                    <strong>Empresa:</strong>
                    {{ $company->business_name }}

                    <br>

                    <strong>RUC:</strong>
                    {{ $company->ruc }}
                </div>


                <form
                        id="formCreateTenantBranch"
                        data-url="{{ route('tenantBranch.store') }}"
                >

                    @csrf
                    <div class="form-group">
                        <label for="catalog_slug">Nombre para el enlace público</label>
                        <input type="text" class="form-control" name="catalog_slug" id="catalog_slug" maxlength="80"
                               pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('catalog_slug') }}" placeholder="local_outlet">
                        <small class="form-text text-muted">Usa minúsculas, números, guiones o guiones bajos. Déjalo vacío para mantener el catálogo sin publicar.</small>
                    </div>


                    <input
                            type="hidden"
                            name="company_id"
                            value="{{ $company->id }}"
                    >


                    <div class="row">

                        <div class="form-group col-md-4">
                            <label>Código *</label>

                            <input
                                    type="text"
                                    name="code"
                                    class="form-control"
                                    maxlength="30"
                            >
                        </div>


                        <div class="form-group col-md-8">
                            <label>Nombre *</label>

                            <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                            >
                        </div>


                        <div class="form-group col-md-8">
                            <label>Dirección</label>

                            <input
                                    type="text"
                                    name="address"
                                    class="form-control"
                            >

                            <small class="form-text text-muted">
                                Si lo deja vacío se utilizará la dirección de la empresa.
                            </small>
                        </div>


                        <div class="form-group col-md-4">
                            <label>Teléfono</label>

                            <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                            >
                        </div>

                    </div>


                    <div class="form-group">
                        <label for="branch_price_list_id">Lista de precios para ventas</label>
                        <select id="branch_price_list_id" name="price_list_id" class="form-control">
                            <option value="">Usar predeterminada de la empresa</option>
                            @foreach($priceLists as $priceList)
                                <option value="{{ $priceList->id }}" {{ (string) old('price_list_id', null) === (string) $priceList->id ? 'selected' : '' }}>{{ $priceList->name }} ({{ $priceList->currency }})</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Los precios especiales de variante prevalecen sobre el precio base del producto, dentro de esta lista.</small>

                    </div>

                    <div class="text-right">

                        <a
                                href="{{ route('tenantBranch.index', $company->id) }}"
                                class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                                type="submit"
                                class="btn btn-primary"
                        >
                            Crear sucursal
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection


@section('scripts')
    <script src="{{ asset('js/tenantBranch/create.js') }}"></script>
@endsection