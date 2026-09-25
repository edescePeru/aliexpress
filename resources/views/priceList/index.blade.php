@extends('layouts.appAdmin2')

@section('title', 'Precios')

@section('content')

    <div
            class="container-fluid"
            id="priceListApp"

            data-url-lists="{{ route('priceList.lists', ['companyId' => '__COMPANY__']) }}"
            data-url-store="{{ route('priceList.store') }}"
            data-url-materials="{{ route('priceList.materials', ['priceList' => '__LIST__']) }}"
            data-url-save-materials="{{ route('priceList.materials.save', ['priceList' => '__LIST__']) }}"
    >

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">
                        <h3 class="card-title">
                            Administración de precios
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-5">

                                <div class="form-group">
                                    <label>
                                        Empresa
                                    </label>

                                    <select
                                            id="priceCompany"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >

                                        @foreach($companies as $company)

                                            <option
                                                    value="{{ $company->id }}"
                                                    {{ (int) $currentCompanyId === (int) $company->id ? 'selected' : '' }}
                                            >
                                                {{ $company->business_name }}
                                                -
                                                {{ $company->ruc }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>

                            <div class="col-md-5">

                                <div class="form-group">

                                    <label>
                                        Lista de precios
                                    </label>

                                    <select
                                            id="priceList"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>

                                </div>

                            </div>

                            <div class="col-md-2 d-flex align-items-end">

                                <div class="form-group w-100">

                                    <button
                                            type="button"
                                            class="btn btn-primary btn-block"
                                            id="btnNewPriceList"
                                    >
                                        <i class="fas fa-plus"></i>
                                        Nueva lista
                                    </button>

                                </div>

                            </div>

                        </div>

                        <hr>

                        <div class="row mb-3">

                            <div class="col-md-6">

                                <input
                                        type="text"
                                        id="searchMaterialPrice"
                                        class="form-control"
                                        placeholder="Buscar producto..."
                                >

                            </div>

                        </div>

                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead>

                                <tr>
                                    <th>
                                        Producto
                                    </th>

                                    <th width="150">
                                        Variantes
                                    </th>

                                    <th width="220">
                                        Precio base
                                    </th>
                                </tr>

                                </thead>

                                <tbody id="materialPriceBody">
                                </tbody>

                            </table>

                        </div>

                        <div id="materialPricePagination">
                        </div>

                    </div>

                    <div class="card-footer text-right">

                        <button
                                type="button"
                                id="btnSaveMaterialPrices"
                                class="btn btn-success"
                        >
                            <i class="fas fa-save"></i>
                            Guardar precios
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/priceList/index.js') }}"></script>

@endsection