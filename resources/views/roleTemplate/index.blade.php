@extends('layouts.appAdmin2')

@section('title')
    Plantillas de Roles
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}" >
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}" >
@endsection

@section('styles')
    <style>

        .permission-groups-container {
            max-height: 520px;
            overflow-y: auto;
            background: #fafafa;
        }

        .permission-module {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .permission-module:last-child {
            margin-bottom: 0;
        }

        .permission-module-header {
            background: #f4f6f9;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 12px;
        }

        .permission-module-body {
            padding: 10px 12px;
        }

        .permission-module-title {
            font-weight: 600;
            font-size: 14px;
        }

        .permission-item {
            margin-bottom: 6px;
        }

        .permission-item:last-child {
            margin-bottom: 0;
        }

        .permission-code {
            display: block;
            color: #868e96;
            font-size: 11px;
            margin-left: 24px;
        }

        .permission-module-count {
            font-size: 11px;
        }

    </style>
@endsection

@section('page-header')
    <h1 class="page-title">
        Plantillas de perfiles
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Plantillas globales de Venti360
    </h5>

    <a href="{{ route('roleTemplate.create') }}" class="btn btn-outline-success btn-sm float-right" >
        <i class="fa fa-plus"></i>
        Nueva plantilla
    </a>
@endsection

@section('content')

    <div
        id="role-template-app"

        data-url-list="{{ route('roleTemplate.data') }}"

        data-url-edit="{{ route( 'roleTemplate.edit', ':id' ) }}"

        data-url-toggle="{{ route( 'roleTemplate.toggleStatus', ':id' ) }}"
    >

        <div class="row mb-3">

            <div class="col-md-6">
                <input
                        type="text"
                        id="searchRoleTemplate"
                        class="form-control"
                        placeholder="Buscar plantilla..."
                >
            </div>

            <div class="col-md-2">
                <select
                        id="perPageRoleTemplate"
                        class="form-control"
                >
                    <option value="10">
                        10
                    </option>

                    <option value="20">
                        20
                    </option>

                    <option value="50">
                        50
                    </option>
                </select>
            </div>

        </div>

        <div class="table-responsive">

            <table
                    class="table table-bordered table-hover"
            >
                <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Permisos</th>
                    <th>Asignable Owner</th>
                    <th>Estado</th>
                    <th width="180">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody
                        id="bodyRoleTemplates"
                >
                </tbody>

            </table>

        </div>

        <div
                id="paginationRoleTemplates"
                class="d-flex justify-content-center"
        ></div>

    </div>


@endsection


@section('plugins')
    <script
            src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"
    ></script>
@endsection


@section('scripts')
    <script
            src="{{ asset('js/roleTemplate/index.js') }}"
    ></script>
@endsection