@extends('layouts.appAdmin2')

@section('title', 'Listar Empresas')

@section('activeTenantCompanyConfig', 'active')

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Configuración de Empresa</span>
        <h1 class="page-title">Empresas</h1>
        <p class="next-page-description">Administra los datos de la empresa.</p>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('tenantCompany.index') }}"><i class="fa fa-home"></i>Empresas</a></li>
        <li class="breadcrumb-item active">Listado</li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">Listado empresas</h5>

    @can('create_company')
        <a href="{{ route('tenantCompany.create') }}" class="btn btn-primary float-right">
            Nueva empresa
        </a>
    @endcan
@endsection

@section('content')
    <div class="table-responsive">

        <table class="table table-bordered table-hover">

            <thead>
            <tr>
                <th>RUC</th>
                <th>Razón Social</th>
                <th>Nombre Comercial</th>
                <th>Sucursales</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>

            <tbody>

            @forelse($companies as $company)

                <tr>

                    <td>
                        {{ $company->ruc }}
                    </td>

                    <td>
                        {{ $company->business_name }}
                    </td>

                    <td>
                        {{ $company->trade_name ?: '-' }}
                    </td>

                    <td>
                        @can('list_branch')

                            <a href="{{ route('tenantBranch.index', $company->id) }}">
                                {{ $company->branches_count }}
                            </a>

                        @else

                            {{ $company->branches_count }}

                        @endcan
                    </td>

                    <td>

                        @if($company->is_active)

                            <span class="badge badge-success">
                            Activa
                        </span>

                        @else

                            <span class="badge badge-secondary">
                            Inactiva
                        </span>

                        @endif

                    </td>

                    <td>
                        {{--@can('create_branch')
                            @if($company->is_active)
                                <a href="{{ route('tenantBranch.create', $company->id) }}" class="btn btn-sm btn-outline-primary">
                                    Nueva sucursal
                                </a>
                            @endif
                        @endcan--}}

                        @can('list_branch')
                            <a href="{{ route('tenantBranch.index', $company->id) }}" class="btn btn-sm btn-outline-secondary">
                                Ver sucursales
                            </a>
                        @endcan

                        @can('edit_company')
                            <a href="{{ route('tenantCompany.edit', $company->id) }}" class="btn btn-sm btn-outline-primary">
                                Editar
                            </a>
                        @endcan

                        @can('enable_company')

                            <button
                                    type="button"
                                    class="btn btn-sm {{ $company->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    data-toggle-company-status
                                    data-company-id="{{ $company->id }}"
                                    data-company-name="{{ $company->business_name }}"
                                    data-is-active="{{ $company->is_active ? 1 : 0 }}"
                                    data-url="{{ route('tenantCompany.toggleStatus', $company->id) }}"
                            >
                                {{ $company->is_active ? 'Desactivar' : 'Activar' }}
                            </button>

                        @endcan
                    </td>
                </tr>

            @empty

                <tr>
                    <td
                            colspan="5"
                            class="text-center"
                    >
                        No hay empresas registradas.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/tenantCompany/index.js') }}"></script>
@endsection