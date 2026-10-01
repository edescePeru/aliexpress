@extends('layouts.appAdmin2')

@section('activePlatformTenantRoles', 'active')

@section('title')
    Nuevo rol del Tenant
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Nuevo rol de {{ $tenant->name }}</h1><p class="next-page-description">Configura un perfil personalizado dentro del tenant seleccionado.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Rol personalizado</strong><span>El alcance permanece limitado al tenant {{ $tenant->name }}.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item"><a href="{{ route('tenantRole.index', ['tenant_id' => $tenant->id]) }}">Roles por tenant</a></li><li class="breadcrumb-item active" aria-current="page">Nuevo</li></ol>
@endsection

@section('content')

    <div
            id="tenant-role-form-app"

            data-mode="create"

            data-tenant-id="{{ $tenant->id }}"

            data-url-index="{{route('tenantRole.index',['tenant_id' => $tenant->id])}}"

            data-url-permissions="{{route('roleTemplate.permissions')}}"

            data-url-store="{{route('tenantRole.store',$tenant->id)}}"
    >

        <form id="formTenantRole">

            @csrf

            @include(
                'tenantRole.partials.form',
                [
                    'tenant' => $tenant
                ]
            )

        </form>

    </div>

@endsection


@section('scripts')
    <script
            src="{{ asset('js/tenantRole/form.js') }}"
    ></script>
@endsection
