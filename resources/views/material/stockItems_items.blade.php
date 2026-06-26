@extends('layouts.appAdmin2')

@section('openMaterial')
    menu-open
@endsection

@section('activeMaterial')
    active
@endsection

@section('activeListMaterial')
    active
@endsection

@section('title')
    Materiales
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        td.details-control {
            background: url('/admin/plugins/datatables/resources/details_open.png') no-repeat center center;
            cursor: pointer;
        }
        tr.details td.details-control {
            background: url('/admin/plugins/datatables/resources/details_close.png') no-repeat center center;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Items de Material {{$stockMaterial->sku}}</h1>
    <input type="hidden" id="id-material" value="{{$stockMaterial->id}}">
@endsection

@section('page-title')
    <h5 class="card-title">Listado de items de materiales</h5>
    <a href="{{ route('stockitems.index') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-arrow-left font-20"></i> Regresar </a>
@endsection

@section('content')
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="dynamic-table">
            <thead>
            <tr>
                <th></th>
                <th>Material</th>
                <th>Código</th>
                <th>Precio</th>
                <th>Porcentaje</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modalEditarCodigoItem" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Editar código del ítem</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit_item_id">

                    <div class="form-group mb-0">
                        <label for="edit_item_code">Código / Serie</label>

                        <input type="text"
                               class="form-control"
                               id="edit_item_code"
                               autocomplete="off">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="button" class="btn btn-primary" id="btnGuardarCodigoItem">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        window.permissions = @json($permissions);
    </script>
    <script src="{{ asset('js/material/stockItems_items.js') }}"></script>
@endsection
