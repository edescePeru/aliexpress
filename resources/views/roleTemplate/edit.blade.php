@extends('layouts.appAdmin2')

@section('title')
    Editar plantilla de perfil
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <h1 class="page-title">
        Plantillas de perfiles
    </h1>
@endsection

@section('page-title')

    <h5 class="card-title">
        Editar plantilla:
        {{ $template->name }}
    </h5>

@endsection

@section('page-breadcrumb')

    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">

            <a
                    href="{{ route('dashboard.principal') }}"
            >
                <i class="fa fa-home"></i>
                Dashboard
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