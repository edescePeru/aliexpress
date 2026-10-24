@extends('layouts.appAdmin2')

@section('title', 'Modificar Empresas')

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
        <li class="breadcrumb-item"><a href="{{ route('tenantCompany.index') }}"><i class="fa fa-home"></i>Empresas</a></li>
        <li class="breadcrumb-item active">Modificar</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Editar empresas</h5>
@endsection

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    Datos de la empresa
                </h3>
            </div>

            <div class="card-body">

                <form
                        id="formEditTenantCompany"
                        data-url="{{ route('tenantCompany.update', $company->id) }}"
                >

                    @csrf


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
                                    value="{{ $company->ruc }}"
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
                                    value="{{ $company->business_name }}"
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
                                    value="{{ $company->trade_name }}"
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
                                    value="{{ $company->email }}"
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
                                    value="{{ $company->address }}"
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
                                    value="{{ $company->phone }}"
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
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    @include('inventory.managedWarehouses', [
        'branchWarehouseContext' => false,
        'warehouseCompanyId' => $company->id,
        'warehouseBranchId' => null,
    ])

@endsection


@section('scripts')

    <script src="{{ asset('js/tenantCompany/edit.js') }}"></script>
    <script src="{{ asset('js/inventory/managedWarehouses.js') }}"></script>
@endsection