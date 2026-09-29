@extends('layouts.appAdmin2')

@section('activePlatformRoleTemplates', 'active')

@section('title')
    Nueva plantilla de perfil
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Nueva plantilla de perfil</h1><p class="next-page-description">Define un perfil global reutilizable y su conjunto de permisos.</p></div>
@endsection

@section('page-title')

    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Configuración de plantilla</strong><span>Selecciona únicamente los permisos que formarán parte del perfil base.</span></div></div>

@endsection

@section('page-breadcrumb')

    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">

            <a
                    href="{{ route('platform.dashboard') }}"
            >
                <i class="fa fa-home"></i>
                Superadministración
            </a>

        </li>

        <li class="breadcrumb-item">

            <a
                    href="{{ route('roleTemplate.index') }}"
            >
                Plantillas
            </a>

        </li>

        <li class="breadcrumb-item active">
            Nueva
        </li>

    </ol>

@endsection


@section('content')

    <div
            id="role-template-form-app"

            data-mode="create"

            data-url-index="{{
            route('roleTemplate.index')
        }}"

            data-url-permissions="{{
            route('roleTemplate.permissions')
        }}"

            data-url-store="{{
            route('roleTemplate.store')
        }}"
    >

        <form id="formRoleTemplate">

            @csrf

            @include(
                'roleTemplate.partials.form',
                [
                    'mode' => 'create',
                    'template' => null
                ]
            )

        </form>

    </div>

@endsection


@section('scripts')

    <script
            src="{{ asset('js/roleTemplate/form.js') }}"
    ></script>

@endsection
