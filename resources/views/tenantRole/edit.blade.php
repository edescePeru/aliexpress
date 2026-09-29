@extends('layouts.appAdmin2')

@section('activePlatformTenantRoles', 'active')

@section('title')
    Editar rol del Tenant
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Editar rol de {{ $tenant->name }}</h1><p class="next-page-description">Actualiza el perfil conservando su alcance dentro del tenant.</p></div>
@endsection

@section('page-title')

    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>{{ $role->description }}</strong><span>El tenant {{ $tenant->name }} permanece como límite contractual.</span></div></div>

@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item"><a href="{{ route('tenantRole.index', ['tenant_id' => $tenant->id]) }}">Roles por tenant</a></li><li class="breadcrumb-item active" aria-current="page">Editar</li></ol>
@endsection


@section('content')

    <div
            id="tenant-role-form-app"

            data-mode="edit"

            data-tenant-id="{{ $tenant->id }}"

            data-role-id="{{ $role->id }}"

            data-url-index="{{route('tenantRole.index',['tenant_id' => $tenant->id])}}"

            data-url-permissions="{{route('roleTemplate.permissions')}}"

            data-url-show="{{route('tenantRole.show',[$tenant->id,$role->id])}}"

            data-url-update="{{route('tenantRole.update',[$tenant->id,$role->id])}}"
    >

        <form id="formTenantRole">

            @csrf

            @include(
                'tenantRole.partials.form',
                [
                    'tenant' => $tenant,
                    'role' => $role
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
