@extends('layouts.appAdmin2')

@section('title')
    Editar rol del Tenant
@endsection

@section('styles')
    @include('roleTemplate.partials.styles')
@endsection

@section('page-header')
    <h1 class="page-title">
        Roles por Tenant
    </h1>
@endsection

@section('page-title')

    <h5 class="card-title">
        Editar rol:
        {{ $role->description }}
    </h5>

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