@extends('layouts.appAdmin2')

@section('openDataGeneral')
    menu-open
@endsection

@section('activeDataGeneral')
    active
@endsection

@section('activeConfigUserWeb', 'active')

@section('title')
    Usuarios Web
@endsection

@section('styles-plugins')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Usuarios Web</h1>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-users"></i> Usuarios Web</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de usuarios web</h5>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="card">

            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label>Mostrar</label>
                        <select id="perPage" class="form-control form-control-sm">
                            <option value="10">10 registros</option>
                            <option value="25">25 registros</option>
                            <option value="50">50 registros</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estado</label>
                        <select id="statusFilter" class="form-control form-control-sm">
                            <option value="active">Activos</option>
                            <option value="inactive">Inhabilitados</option>
                            <option value="all">Todos</option>
                        </select>
                    </div>

                    <div class="col-md-4 offset-md-3">
                        <label>Buscar</label>
                        <input type="text" id="searchUser" class="form-control form-control-sm"
                               placeholder="Buscar por nombre o email">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Última modificación</th>
                            <th>Rol</th>
                            <th style="width: 90px;">Imagen</th>
                            <th style="width: 180px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        <tr>
                            <td colspan="7" class="text-center">
                                Cargando usuarios...
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small id="paginationInfo"></small>
                    </div>

                    <div class="col-md-6">
                        <nav class="float-md-right">
                            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="modal fade" id="modalEditUser" tabindex="-1" role="dialog" aria-labelledby="modalEditUserLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="formEditUser" enctype="multipart/form-data">
                @csrf

                <input type="hidden" id="editUserId" name="user_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modificar usuario</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div id="editUserErrors" class="alert alert-danger d-none"></div>

                        <div class="form-group">
                            <label for="editName">
                                Nombre <span class="right badge badge-danger">(*)</span>
                            </label>
                            <input type="text" id="editName" name="name" class="form-control form-control-sm">
                        </div>

                        <div class="form-group">
                            <label for="editEmail">
                                Correo electrónico <span class="right badge badge-danger">(*)</span>
                            </label>
                            <input type="email" id="editEmail" name="email" class="form-control form-control-sm">
                        </div>

                        <div class="form-group">
                            <label for="editImage">Imagen</label>
                            <input type="file" id="editImage" name="image" class="form-control form-control-sm" accept="image/*">

                            <div class="mt-2">
                                <img id="editImagePreview"
                                     src=""
                                     alt="Imagen usuario"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Rol</label>
                            <input type="text" id="editRoles" class="form-control form-control-sm" readonly>
                            <small class="text-muted">
                                El rol no se puede modificar desde este módulo.
                            </small>
                        </div>

                        <hr>

                        <div class="alert alert-warning mb-0">
                            <strong>Resetear contraseña:</strong><br>
                            Esta acción cambiará la contraseña del usuario por la contraseña general configurada.
                        </div>

                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" id="btnResetPassword" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-key"></i> Resetear contraseña
                        </button>

                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit" id="btnSaveUser" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        window.configUserWebRoutes = {
            getUsers: "{{ route('configUserWeb.getUsers') }}",
            edit: "{{ route('configUserWeb.edit', ['id' => ':id']) }}",
            update: "{{ route('configUserWeb.update', ['id' => ':id']) }}",
            resetPassword: "{{ route('configUserWeb.resetPassword', ['id' => ':id']) }}",
            changeStatus: "{{ route('configUserWeb.changeStatus', ['id' => ':id']) }}"
        };
    </script>

    <script src="{{ asset('js/configUserWeb/index.js') }}"></script>
@endsection