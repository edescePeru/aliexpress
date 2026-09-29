@extends('layouts.appAdmin2')

@section('activePlatformRoleTemplates', 'active')

@section('title')
    Editar plantilla de perfil
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Editar plantilla de perfil</h1><p class="next-page-description">Actualiza la identidad y los permisos del perfil global.</p></div>
@endsection

@section('page-title')

    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>{{ $template->name }}</strong><span>Los cambios se aplican mediante el contrato global existente.</span></div></div>

@endsection

@section('page-breadcrumb')

    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">

            <a
                    href="{{ route('platform.dashboard') }}"
            >
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
            Editar
        </li>

    </ol>

@endsection


@section('content')

    <div
            id="role-template-form-app"

            data-mode="edit"

            data-template-id="{{ $template->id }}"

            data-url-index="{{
            route('roleTemplate.index')
        }}"

            data-url-permissions="{{
            route('roleTemplate.permissions')
        }}"

            data-url-show="{{
            route(
                'roleTemplate.show',
                $template->id
            )
        }}"

            data-url-update="{{
            route(
                'roleTemplate.update',
                $template->id
            )
        }}"
    >

        <form id="formRoleTemplate">

            @csrf

            @include(
                'roleTemplate.partials.form',
                [
                    'mode' => 'edit',
                    'template' => $template
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
