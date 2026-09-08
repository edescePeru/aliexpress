@extends('layouts.appAdmin2')

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