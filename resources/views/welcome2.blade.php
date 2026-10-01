@extends('layouts.publicAccess')

@section('title', 'Venti360')

@section('content')
    <header class="public-access-heading public-access-mobile-redundant">
        <h1 class="public-access-title">Venti360</h1>
        <p class="public-access-copy">Gestión simple para tu operación.</p>
    </header>

    <div class="public-access-actions">
        @guest
            <a class="public-access-button public-access-button--primary" href="{{ route('login') }}">
                Iniciar sesión
            </a>
        @else
            @if (Auth::user()->isPlatformAdmin())
                <a class="public-access-button public-access-button--primary" href="{{ route('platform.dashboard') }}">
                    Ir al dashboard
                </a>
            @else
                @can('access_dashboard')
                    <a class="public-access-button public-access-button--primary" href="{{ route('dashboard.principal') }}">
                        Ir al dashboard
                    </a>
                @endcan
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="public-access-button public-access-button--secondary" type="submit">
                    Cerrar sesión
                </button>
            </form>
        @endguest
    </div>
@endsection
