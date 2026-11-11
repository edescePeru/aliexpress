@extends('layouts.appAdmin2')

@section('title', 'Crear Empresas')

@section('activeTenantCompanyConfig', 'active')

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Configuración de Empresa</span>
        <h1 class="page-title">Empresas</h1>
        <p class="next-page-description">Administra los datos de la empresa.</p>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('tenantCompany.index') }}">Empresas</a></li>
        <li class="breadcrumb-item active">Crear</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Crear empresas</h5>
@endsection

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header">

                <h3 class="card-title">
                    Nueva empresa
                </h3>

            </div>


            <div class="card-body">

                <form
                        id="formCreateTenantCompany"
                        data-url="{{ route('tenantCompany.store') }}"
                >

                    @csrf
                    <div class="form-group">
                        <label for="catalog_slug">Nombre para el enlace público</label>
                        <input type="text" class="form-control" name="catalog_slug" id="catalog_slug" maxlength="80"
                               pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('catalog_slug') }}" placeholder="company">
                        <small class="form-text text-muted">Usa minúsculas, números, guiones o guiones bajos. Déjalo vacío para mantener el catálogo sin publicar.</small>
                    </div>
                    <div class="form-group">
                        <label for="branch_catalog_slug">Nombre para el enlace del local principal</label>
                        <input type="text" class="form-control" name="branch_catalog_slug" id="branch_catalog_slug" maxlength="80"
                               pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('branch_catalog_slug') }}" placeholder="local_principal">
                        <small class="form-text text-muted">Completa este campo para incluir el local principal en el catálogo.</small>
                    </div>



                    <h5 class="mb-3">
                        Datos de la empresa
                    </h5>


                    <div class="row">

                        <div class="form-group col-md-4">

                            <label>
                                RUC *
                            </label>

                            <input
                                    type="text"
                                    name="ruc"
                                    class="form-control"
                                    maxlength="11"
                            >

                        </div>


                        <div class="form-group col-md-8">

                            <label>
                                Razón Social *
                            </label>

                            <input
                                    type="text"
                                    name="business_name"
                                    class="form-control"
                            >

                        </div>


                        <div class="form-group col-md-6">

                            <label>
                                Nombre Comercial
                            </label>

                            <input
                                    type="text"
                                    name="trade_name"
                                    class="form-control"
                            >

                        </div>


                        <div class="form-group col-md-6">

                            <label>
                                Correo
                            </label>

                            <input
                                    type="email"
                                    name="email"
                                    class="form-control"
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
                            >

                        </div>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Sucursal principal
                    </h5>


                    <div class="row">

                        <div class="form-group col-md-6">

                            <label>
                                Nombre *
                            </label>

                            <input
                                    type="text"
                                    name="branch_name"
                                    class="form-control"
                                    value="PRINCIPAL"
                            >

                        </div>


                        <div class="form-group col-md-6">

                            <label>
                                Código *
                            </label>

                            <input
                                    type="text"
                                    name="branch_code"
                                    class="form-control"
                                    value="PRINCIPAL"
                            >

                        </div>


                        <div class="form-group col-md-8">

                            <label>
                                Dirección
                            </label>

                            <input
                                    type="text"
                                    name="branch_address"
                                    class="form-control"
                            >

                            <small class="form-text text-muted">
                                Si lo deja vacío se utilizará la dirección de la empresa.
                            </small>

                        </div>


                        <div class="form-group col-md-4">

                            <label>
                                Teléfono
                            </label>

                            <input
                                    type="text"
                                    name="branch_phone"
                                    class="form-control"
                            >

                        </div>

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
                            Crear empresa
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/tenantCompany/create.js') }}"></script>

@endsection