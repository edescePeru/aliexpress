@extends('layouts.appAdmin2')

@section('title')
    Servicios predeterminados
@endsection

@section('page-header')
    <h1 class="page-title">
        Servicios predeterminados
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Servicios predeterminados
    </h5>
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

@endsection

@section('content')

    <div
            id="workforcePage"

            data-url-data="{{ route('workforce.data') }}"

            data-url-store="{{ route('workforce.store') }}"

            data-url-edit="{{ route('workforce.edit', ':id') }}"

            data-url-update="{{ route('workforce.update', ':id') }}"

            data-url-delete="{{ route('workforce.destroy', ':id') }}"
    >

        <div class="card card-primary">

            <div class="card-header">

                <h3 class="card-title">
                    Servicios predeterminados
                </h3>

                <div class="card-tools">
                    @can('manage_workforce')
                        <button
                                type="button"
                                class="btn btn-sm btn-light"
                                id="btnCreateWorkforce"
                        >
                            <i class="fas fa-plus"></i>
                            Nuevo servicio
                        </button>
                    @endcan
                </div>

            </div>

            <div class="card-body">

                <div class="row mb-3">

                    <div class="col-md-5">

                        <input
                                type="text"
                                id="workforceSearch"
                                class="form-control form-control-sm"
                                placeholder="Buscar servicio..."
                        >

                    </div>

                </div>

                <div id="workforceTableContainer">

                    <div class="text-center py-4">

                        <i class="fas fa-spinner fa-spin"></i>

                        Cargando...

                    </div>

                </div>

            </div>

        </div>


        {{-- =============================================== --}}
        {{-- MODAL CREAR / EDITAR --}}
        {{-- =============================================== --}}

        <div
                class="modal fade"
                id="modalWorkforce"
                tabindex="-1"
        >

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5
                                class="modal-title"
                                id="modalWorkforceTitle"
                        >
                            Nuevo servicio
                        </h5>

                        <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                        >
                            <span>
                                &times;
                            </span>
                        </button>

                    </div>

                    <div class="modal-body">

                        <input
                                type="hidden"
                                id="workforceId"
                        >

                        <div class="form-group">

                            <label for="workforceDescription">
                                Descripción
                            </label>

                            <input
                                    type="text"
                                    id="workforceDescription"
                                    class="form-control"
                                    maxlength="255"
                            >

                        </div>

                        <div class="form-group">

                            <label for="workforceUnitMeasure">
                                Unidad de medida
                            </label>

                            <select
                                    id="workforceUnitMeasure"
                                    class="form-control"
                                    style="width:100%"
                            >

                                <option value="">
                                    Seleccione
                                </option>

                                @foreach(
                                    $unitMeasures
                                    as $unitMeasure
                                )

                                    <option
                                            value="{{ $unitMeasure->id }}"
                                    >
                                        {{ $unitMeasure->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="form-group">

                            <label for="workforceUnitPrice">
                                Precio de venta
                            </label>

                            <input
                                    type="number"
                                    id="workforceUnitPrice"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="0"
                            >

                        </div>

                        <div class="form-group">

                            <div class="icheck-primary">

                                <input
                                        type="checkbox"
                                        id="workforceBillable"
                                        checked
                                >

                                <label for="workforceBillable">
                                    Facturable
                                </label>

                            </div>

                            <small class="text-muted">

                                Si está activo, el servicio
                                formará parte del total de
                                la cotización.

                            </small>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="button"
                                class="btn btn-primary"
                                id="btnSaveWorkforce"
                        >
                            Guardar
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')

    <script
            src="{{ asset('js/workforce/index.js') }}?v={{ time() }}"
    ></script>

@endsection