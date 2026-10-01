@extends('layouts.publicAccess')

@section('title', 'Bienvenido | Venti360')

@section('content')
    <header class="public-access-heading">
        <h1 class="public-access-title">Bienvenido</h1>
        <p class="public-access-user">{{ Auth::user()->name }}</p>
    </header>

    <div class="public-access-actions">
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
    </div>
@endsection
