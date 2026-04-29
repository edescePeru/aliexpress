@extends('layouts.appAdmin2')

@section('openInventory')
    menu-open
@endsection

@section('activeInventory')
    active
@endsection

{{-- si tienes un menú específico para kardex puedes usar otra sección, de momento reutilizo --}}
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
    <h1 class="page-title">Kardex de Materiales</h1>
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
    <h5 class="card-title">Consulta de Kardex por Material</h5>
@endsection

@section('content')

    <div class="card">
        <div class="card-body">
            {{-- Filtros --}}
            <form id="form-kardex" autocomplete="off">
                <div class="row">
                    {{-- Material --}}
                    {{--<div class="col-md-5">
                        <div class="form-group">
                            <label for="material_id">Material</label>
                            <select id="material_id" class="form-control select2-material" style="width: 100%;">
                            </select>
                        </div>
                    </div>--}}
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="stock_item_id">Material</label>
                            <select id="stock_item_id" name="stock_item_id" class="form-control select2-material" style="width: 100%;">
                                {{-- Se cargan por AJAX --}}
                            </select>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="warehouse_id">Material</label>
                            <select id="warehouse_id" name="warehouse_id" class="form-control select2-material" style="width: 100%;">
                                {{-- Se cargan por AJAX --}}
                            </select>
                        </div>
                    </div>

                    {{-- Botón buscar --}}
                    <div class="col-md-2 align-items-end">
                        <label for="btn-search-kardex">&nbsp;</label>
                        <br>
                        <button type="button" id="btn-search-kardex" class="btn btn-primary btn-block">
                            <i class="fa fa-search"></i> Buscar Kardex
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            {{-- Info del material --}}
            <div id="kardex-header" class="mb-3" style="display:none;">
                <h5>
                    Material: <span id="kardex-material-name"></span><br>
                    <small>Rango: <span id="kardex-range"></span></small>
                </h5>
            </div>

            {{-- Tabla Kardex --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="kardex-table">
                    <thead class="thead-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Cant. Entrada</th>
                        <th>Cant. Salida</th>
                        <th>Costo Unit. In</th>
                        <th>Costo Unit. Out</th>
                        <th>Saldo Cant.</th>
                        <th>Costo Promedio</th>
                        <th>Saldo Total</th>
                    </tr>
                    </thead>
                    <tbody id="kardex-body">
                    <tr>
                        <td colspan="10" class="text-center">Seleccione un material y rango de fechas.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/inventory/kardex.js') }}?v={{ time() }}"></script>
@endsection
