@extends('layouts.publicAccess')

@section('title', 'Iniciar sesión | Venti360')
@section('footer-prefix', 'Venti360')

@section('content')
    <header class="public-access-heading">
        <h1 class="public-access-title">Iniciar sesión</h1>
        <p class="public-access-copy">Ingresa tus credenciales para continuar.</p>
    </header>

    @if (session('status'))
        <div class="public-access-alert public-access-alert--success" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="public-access-form-group">
            <label class="public-access-label" for="email">Correo</label>
            <input
                id="email"
                class="form-control public-access-control @error('email') is-invalid @enderror"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
            >
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="public-access-form-group">
            <label class="public-access-label" for="password">Contraseña</label>
            <div class="public-access-password">
                <input
                    id="password"
                    class="form-control public-access-control @error('password') is-invalid @enderror"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <button
                    class="public-access-password__toggle"
                    type="button"
                    aria-label="Mostrar contraseña"
                    aria-controls="password"
                    aria-pressed="false"
                    data-password-toggle="password"
                >
                    Mostrar
                </button>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="public-access-options">
            <label class="public-access-check" for="remember">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    value="1"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <span>Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="public-access-link" href="{{ route('password.request') }}">
                    Recuperar contraseña
                </a>
            @endif
        </div>

        <button class="public-access-button public-access-button--primary" type="submit">
            Ingresar
        </button>
    </form>
@endsection
