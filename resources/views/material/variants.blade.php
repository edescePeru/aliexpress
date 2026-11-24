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



@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Materiales · consulta</span><h1 class="page-title">Variantes del material</h1><p class="next-page-description">{{ $material->code }} · {{ $material->full_name }}</p></div>
    <input type="hidden" id="id-material" value="{{$material->id}}">
@endsection

@section('page-title')
    <h5 class="card-title">Listado de variantes de materiales</h5>
    <a href="{{ route('material.indexV2') }}" class="btn btn-outline-secondary btn-sm float-right" > <i class="fa fa-arrow-left font-20"></i> Regresar </a>
@endsection

@section('content')
    <div id="variants-feedback" class="next-form-section d-none" role="status"></div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm" id="dynamic-table">
            <thead>
            <tr>
                <th>Material</th>
                <th>Talla</th>
                <th>Color</th>
                <th>Resumen atributo</th>
                <th>Imagen</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <div id="modalImage" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Visualización de la imagen</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <img id="image-document" src="" alt="Imagen de la variante" class="img-fluid d-block mx-auto">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
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
    <script src="{{ asset('js/material/variants.js') }}"></script>
@endsection
