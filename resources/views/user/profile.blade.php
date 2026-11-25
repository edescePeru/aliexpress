@extends('layouts.appAdmin2')

@section('title')
    Perfil
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <link rel="stylesheet" href="{{ asset('admin/plugins/file-input/css/fileinput.min.css') }}">
    <link href="{{ asset('admin/plugins/file-input/themes/explorer-fas/theme.css') }}" media="all" rel="stylesheet" type="text/css"/>

@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Mi cuenta</span><h1 class="page-title">Mi perfil</h1><p class="next-page-description">Información y seguridad de tu cuenta.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Datos de mi cuenta</strong><span>Perfil del usuario autenticado.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item active">
            <i class="fa fa-archive"></i> Perfil de usuario
        </li>

    </ol>
@endsection

@section('content')
    <section class="next-form-section">
        <div class="container-fluid px-0">
            <div class="row">
                <div class="col-lg-4">
                    <!-- Profile Image -->
                    <div class="card">
                        <div class="next-form-section box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle"
                                     src="{{asset('images/users/'.$user->image)}}"
                                     alt="Foto de perfil" >
                            </div>

                            <div class="col-md-12">
                                <button id="btnImage" type="button" class="btn btn-outline-secondary btn-block"><b>Cambiar imagen</b></button>
                            </div>
                            <div id="changeImage" style="display: none">
                                <form id="formImage" data-url="{{ route('user.change.image', $user->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <input type="file" accept="image/png,image/jpeg" name="image" id="image" class="form-control" data-show-caption="true">
                                        <small class="text-muted">PNG o JPG, máximo 2 MB y 4096 píxeles por lado.</small>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <button type="submit" class="btn btn-primary btn-block"><b>Guardar imagen</b></button>

                                        </div>
                                        <div class="col-sm-6">
                                            <button id="btnCancel" type="button" class="btn btn-outline-secondary btn-block"><b>Cancelar</b></button>
                                        </div>
                                    </div>

                                </form>
                            </div>

                            <hr>

                            <h2 class="h5 text-center text-break">{{ $user->name }}</h2>

                            <hr>

                            <h3 class="h6">Roles asignados</h3>
                            @forelse( $user->roles as $role )
                            <p class="text-muted">
                                {{ $role->description }}
                            </p>
                            @empty<p class="text-muted">Sin roles asignados.</p>@endforelse

                            <hr>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="next-list-toolbar">
                            <ul class="nav nav-tabs w-100" role="tablist">
                                <li class="nav-item"><a class="nav-link active" href="#settings" data-toggle="tab" role="tab" aria-controls="settings" aria-selected="true">Información de usuario</a></li>
                                <li class="nav-item"><a class="nav-link" href="#passwordReset" data-toggle="tab" role="tab" aria-controls="passwordReset" aria-selected="false">Cambiar contraseña</a></li>
                            </ul>
                        </div><!-- /.card-header -->
                        <div class="next-form-section">
                            <div class="tab-content">
                                <div class="active tab-pane" id="settings">
                                    <form id="formSettings" data-url="{{ route('user.change.settings', $user->id) }}" class="form-horizontal">
                                        @csrf

                                        <div class="form-group row">
                                            <label for="name" class="col-sm-4 col-form-label">Nombre Completo</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="name" name="name" placeholder="Nombre completo" value="{{ old('name', $user->name) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="email" class="col-sm-4 col-form-label">Email</label>
                                            <div class="col-sm-8">
                                                <input type="email" class="form-control" name="email" id="email" placeholder="Email" value="{{ $user->email }}" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-4 col-sm-8">
                                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane" id="passwordReset">
                                    <form id="formResetPassword" data-url="{{ route('user.change.password', $user->id) }}" class="form-horizontal">
                                        @csrf
                                        <div class="form-group row">
                                            <label for="current_password" class="col-sm-4 col-form-label">Contraseña actual</label>
                                            <div class="input-group col-sm-8">
                                                <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" placeholder="Contraseña actual">
                                                <div class="input-group-append">
                                                    <button type="button" data-show class="input-group-text" aria-label="Mostrar u ocultar contraseña"><i class="far fa-eye-slash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="new_password" class="col-sm-4 col-form-label">Nueva contraseña</label>
                                            <div class="input-group col-sm-8">
                                                <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" placeholder="Nueva contraseña">
                                                <div class="input-group-append">
                                                    <button type="button" data-show class="input-group-text" aria-label="Mostrar u ocultar contraseña"><i class="far fa-eye-slash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="new_password_confirmation" class="col-sm-4 col-form-label">Repetir contraseña</label>
                                            <div class="input-group col-sm-8">
                                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" placeholder="Repetir contraseña">
                                                <div class="input-group-append">
                                                    <button type="button" data-show class="input-group-text" aria-label="Mostrar u ocultar contraseña"><i class="far fa-eye-slash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-4 col-sm-8">
                                                <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.tab-pane -->
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.nav-tabs-custom -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/file-input/js/plugins/piexif.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/file-input/js/plugins/sortable.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/file-input/js/fileinput.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/file-input/js/locales/es.js') }}"></script>
    <script src="{{ asset('admin/plugins/file-input/themes/fas/theme.js') }}" type="text/javascript"></script>
    <script src="{{ asset('admin/plugins/file-input/themes/explorer-fas/theme.js') }}" type="text/javascript"></script>

    <script src="{{ asset('admin/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/jquery-validation/localization/messages_es_PE.min.js') }}"></script>

@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#formSettings').validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 5,
                    },
                    email: {
                        required: true,
                        email: true,
                    },
                },
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    error.addClass('invalid-feedback');
                    element.parents( ".col-sm-8" ).append(error);
                },
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                }
            });
        });
    </script>
    <script src="{{ asset('js/user/profile.js') }}?v=avatar-safe-1"></script>
@endsection
