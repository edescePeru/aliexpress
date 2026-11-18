<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Venti360') }} | Cambiar contraseña</title>
    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.css') }}">
    @if(config('venti-next.enabled', false) === true && in_array(optional(request()->route())->getName(), config('venti-next.routes', []), true))
    <link rel="stylesheet" href="{{ asset('admin/dist/css/venti.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/venti-next.css') }}">
    @endif
</head>
<body>
<main class="container-fluid min-vh-100 d-flex align-items-center py-4">
    <div class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 px-0">
            <section class="card mb-0" aria-labelledby="password-change-title">
                <div class="card-header"><span class="text-primary font-weight-bold">Venti360</span></div>
                <div class="next-form-section">
                    <h1 id="password-change-title" class="h4">Actualizar contraseña</h1>
                    <p class="text-muted">Debes actualizar tu contraseña antes de continuar.</p>
                    <form id="formRequiredPasswordChange" data-url="{{ route('password.required.update') }}">
                        @csrf
                        @foreach(['password' => 'Nueva contraseña', 'password_confirmation' => 'Confirmar contraseña'] as $field => $label)
                        <div class="form-group">
                            <label for="{{ $field }}">{{ $label }}</label>
                            <div class="input-group">
                                <input type="password" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" autocomplete="new-password" required aria-describedby="password-requirements {{ $field }}-error" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary btn-show-password" data-target="#{{ $field }}" aria-label="Mostrar {{ strtolower($label) }}" aria-controls="{{ $field }}" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                                </div>
                            </div>
                            <span id="{{ $field }}-error" class="invalid-feedback {{ $errors->has($field) ? 'd-block' : '' }}">@error($field){{ $message }}@enderror</span>
                        </div>
                        @endforeach
                        <p id="password-requirements" class="text-muted small">La contraseña debe tener al menos 8 caracteres y ser diferente a la contraseña temporal.</p>
                        <div id="password-change-error" class="alert alert-danger d-none" role="alert" tabindex="-1"></div>
                        <button type="submit" id="btnSavePassword" class="btn btn-primary btn-block">Actualizar contraseña</button>
                    </form>
                </div>
                <div class="next-list-toolbar text-center">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link text-muted btn-sm">Cerrar sesión</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</main>
<script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
<script src="{{ asset('js/auth/required-password-change.js') }}"></script>
</body>
</html>
