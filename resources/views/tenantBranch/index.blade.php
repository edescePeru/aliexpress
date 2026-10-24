@extends('layouts.appAdmin2')

@section('title', 'Listar Sucursales')

@section('activeTenantCompanyConfig', 'active')

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Configuración de Empresa</span>
        <h1 class="page-title">Sucursales</h1>
        <p class="next-page-description">Administra los datos de la sucursal.</p>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('tenantBranch.index', $company->id) }}"><i class="fa fa-home"></i> Sucursales</a></li>
        <li class="breadcrumb-item active">Listado</li>
    </ol>
@endsection

@section('page-title')
    <div class="d-flex justify-content-between align-items-start w-100">

        {{-- Información de la empresa --}}
        <div class="flex-grow-1" style="min-width: 0;">
            <h5 class="card-title mb-1">Listado sucursales</h5>

            <div class="text-muted" style="overflow-wrap: anywhere; clear: both;">
                {{ $company->business_name }} - RUC {{ $company->ruc }}
            </div>
        </div>

        {{-- Botones de acción --}}
        <div class="d-flex align-items-start flex-shrink-0 ml-3">
            <a href="{{ route('tenantCompany.index') }}"
               class="btn btn-secondary mr-2">
                Volver
            </a>

            @can('create_branch')
                @if($company->is_active)
                    <a href="{{ route('tenantBranch.create', $company->id) }}"
                       class="btn btn-primary">
                        Nueva sucursal
                    </a>
                @endif
            @endcan
        </div>

    </div>
@endsection

@section('content')

    <div class="table-responsive">

        <table class="table table-bordered table-hover">

            <thead>
            <tr>
                <th style="width: 130px;">
                    Código
                </th>

                <th>
                    Nombre
                </th>

                <th>
                    Dirección
                </th>

                <th>
                    Teléfono
                </th>

                <th style="width: 110px;">
                    Principal
                </th>

                <th style="width: 100px;">
                    Estado
                </th>

                <th style="width: 220px;">
                    Acciones
                </th>
            </tr>
            </thead>


            <tbody>

            @forelse($branches as $branch)

                <tr>

                    <td>
                        {{ $branch->code }}
                    </td>


                    <td>
                        {{ $branch->name }}
                    </td>


                    <td>
                        {{ $branch->address ?: '-' }}
                    </td>


                    <td>
                        {{ $branch->phone ?: '-' }}
                    </td>


                    <td>

                        @if($branch->is_main)

                            <span class="badge badge-primary">
                            Principal
                        </span>

                        @else

                            <span class="text-muted">
                            -
                        </span>

                        @endif

                    </td>


                    <td>

                        @if($branch->is_active)

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

                        <div class="d-flex flex-wrap">

                            @can('edit_branch')
                                <a
                                        href="{{ route('tenantBranch.edit', $branch->id) }}"
                                        class="btn btn-sm btn-outline-primary mr-1 mb-1"
                                >
                                    Editar
                                </a>
                            @endcan


                            @can('enable_branch')
                                <button
                                        type="button"
                                        class="btn btn-sm {{ $branch->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} mr-1 mb-1"
                                        data-toggle-branch-status
                                        data-branch-id="{{ $branch->id }}"
                                        data-branch-name="{{ $branch->name }}"
                                        data-is-active="{{ $branch->is_active ? 1 : 0 }}"
                                        data-is-main="{{ $branch->is_main ? 1 : 0 }}"
                                        data-url="{{ route('tenantBranch.toggleStatus', $branch->id) }}"
                                >
                                    {{ $branch->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            @endcan

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                            colspan="7"
                            class="text-center text-muted"
                    >
                        No hay sucursales registradas para esta empresa.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/tenantBranch/index.js') }}"></script>
@endsection