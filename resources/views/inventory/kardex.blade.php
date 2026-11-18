@extends('layouts.appAdmin2')

@section('openInventory')
    menu-open
@endsection

@section('activeInventory')
    active
@endsection

@section('activeListInventoryKardex')
    active
@endsection

@section('title')
    Kardex de Materiales
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Inventario · Consulta</span>
        <h1 class="page-title">Kardex de Materiales</h1>
        <p class="next-page-description">Movimientos por producto o variante de la empresa y local activos.</p>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-archive"></i> Kardex</li>
    </ol>
@endsection

@section('page-title')
    <div class="next-list-surface-heading">
        <strong>Consulta de movimientos</strong>
        <span>Selecciona un producto o variante y, opcionalmente, un almacén.</span>
    </div>
@endsection

@section('content')

    <section class="next-operational-list" aria-label="Kardex">
        <div class="next-form-section">
            <form id="form-kardex" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="stock_item_id">Producto o variante</label>
                            <select id="stock_item_id" name="stock_item_id" class="form-control select2-material" style="width: 100%;">
                                {{-- Se cargan por AJAX --}}
                            </select>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="warehouse_id">Almacén</label>
                            <select id="warehouse_id" name="warehouse_id" class="form-control select2-material" style="width: 100%;">
                                {{-- Se cargan por AJAX --}}
                            </select>
                        </div>
                    </div>

                    {{-- Botón buscar --}}
                    <div class="col-md-2 form-group">
                        <button type="button" id="btn-search-kardex" class="btn btn-primary btn-block">
                            <i class="fa fa-search" aria-hidden="true"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
            <p class="text-muted small mb-0">Sin seleccionar almacén se consultan los generales y los del local actual.</p>
        </div>
        <div class="next-list-summary" aria-live="polite">
            <div class="next-list-summary-copy">
                <strong id="kardex-count">Selecciona un producto o variante</strong>
                <div id="kardex-header" hidden>
                    <span id="kardex-material-name"></span>
                    <span class="d-block text-muted" id="kardex-range"></span>
                </div>
            </div>
            <span class="next-list-sort-context">Fecha de movimiento · más antiguos primero</span>
        </div>
        <div class="next-list-content">
            <div class="table-responsive" tabindex="0" aria-label="Movimientos de Kardex; desplazamiento horizontal disponible">
                <table class="table table-bordered table-hover table-sm next-data-table" id="kardex-table" aria-busy="false">
                    <thead>
                    <tr>
                        <th scope="col" class="text-center text-nowrap">Fecha</th>
                        <th scope="col" class="text-center">Tipo</th>
                        <th scope="col">Origen</th>
                        <th scope="col" class="text-center text-nowrap">Cant. Entrada</th>
                        <th scope="col" class="text-center text-nowrap">Cant. Salida</th>
                        <th scope="col" class="text-right text-nowrap">Costo Unit. In</th>
                        <th scope="col" class="text-right text-nowrap">Costo Unit. Out</th>
                        <th scope="col" class="text-center text-nowrap">Saldo Cant.</th>
                        <th scope="col" class="text-right text-nowrap">Costo Promedio</th>
                        <th scope="col" class="text-right text-nowrap">Saldo Total</th>
                    </tr>
                    </thead>
                    <tbody id="kardex-body">
                    <tr>
                        <td colspan="10" class="text-center">Seleccione un producto o variante.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/inventory/kardex.js') }}?v={{ time() }}"></script>
@endsection
