@extends('layouts.appAdmin2')

@section('content')

    <div
            class="container-fluid"
            id="company-stock-items-app"

            data-url-data="{{ route('companyStockItem.data') }}"
            data-url-status="{{ route('companyStockItem.status') }}"
    >

        <div class="card">

            <div class="card-header">

                <h3 class="card-title">
                    Catálogo de productos por empresa
                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Empresa
                            </label>

                            <select
                                    id="company_id"
                                    class="form-control"
                            >

                                @foreach($companies as $company)

                                    <option
                                            value="{{ $company->id }}"
                                            {{ $company->id == \App\Support\TenantContext::companyId()
                                                ? 'selected'
                                                : '' }}
                                    >
                                        {{ $company->business_name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Marca
                            </label>

                            <select
                                    id="brand_id"
                                    class="form-control"
                            >

                                <option value="">
                                    Todas
                                </option>

                                @foreach($brands as $brand)

                                    <option
                                            value="{{ $brand->id }}"
                                    >
                                        {{ $brand->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Categoría
                            </label>

                            <select
                                    id="category_id"
                                    class="form-control"
                            >

                                <option value="">
                                    Todas
                                </option>

                                @foreach($categories as $category)

                                    <option
                                            value="{{ $category->id }}"
                                    >
                                        {{ $category->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group">

                            <label>
                                Estado
                            </label>

                            <select
                                    id="status"
                                    class="form-control"
                            >

                                <option value="all">
                                    Todos
                                </option>

                                <option value="enabled">
                                    Habilitados
                                </option>

                                <option value="disabled">
                                    No habilitados
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-12">

                        <div class="form-group">

                            <label>
                                Buscar
                            </label>

                            <input
                                    type="text"
                                    id="search"
                                    class="form-control"
                                    placeholder="Producto, SKU o código de barras"
                            >

                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table
                            class="table table-bordered table-hover"
                    >

                        <thead>

                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>Variante</th>
                            <th>SKU</th>
                            <th>Código</th>
                            <th class="text-center">
                                Vende
                            </th>
                        </tr>

                        </thead>

                        <tbody id="stock-items-body">

                        </tbody>

                    </table>

                </div>


                <div
                        id="pagination-container"
                        class="mt-3"
                >
                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/companyStockItem/index.js') }}"></script>

@endsection