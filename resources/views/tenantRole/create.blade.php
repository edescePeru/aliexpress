@extends('layouts.appAdmin2')

@section('title')
    Nuevo rol del Tenant
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
        Nuevo rol personalizado
    </h5>
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