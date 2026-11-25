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
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Locales de la empresa</strong><span>{{ $company->business_name }} · RUC {{ $company->ruc }}</span></div><div class="next-toolbar-actions">
<a href="{{ route('tenantCompany.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
@can('create_branch') @if($company->is_active)<a href="{{ route('tenantBranch.create', $company->id) }}" class="btn btn-primary btn-sm">Nueva sucursal</a>@endif @endcan
</div></div>
@endsection

@section('content')
@include('tenantCompany._plan_capacity', ['capacityTenant' => auth()->user()->tenant])

    <div class="table-responsive" tabindex="0" aria-label="Sucursales; tabla con desplazamiento horizontal">

        <table class="table table-bordered table-hover table-sm next-data-table">

            <thead>
            <tr>
                <th scope="col" class="text-center">
                    Código
                </th>

                <th scope="col" class="text-left">
                    Nombre
                </th>

                <th scope="col" class="text-left">
                    Dirección
                </th>

                <th scope="col" class="text-center">
                    Teléfono
                </th>

                <th scope="col" class="text-center">
                    Principal
                </th>

                <th scope="col" class="text-center">
                    Estado
                </th>

                <th scope="col" class="text-center">
                    Acciones
                </th>
            </tr>
            </thead>


            <tbody>

            @forelse($branches as $branch)

                <tr>

                    <td class="text-center">
                        {{ $branch->code }}
                    </td>


                    <td class="text-left">
                        {{ $branch->name }}
                    </td>


                    <td class="text-left">
                        {{ $branch->address ?: '-' }}
                    </td>


                    <td class="text-center">
                        {{ $branch->phone ?: '-' }}
                    </td>


                    <td class="text-center">

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


                    <td class="text-center">

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


                    <td class="text-center">

                        @php($multipleActions = auth()->user()->can('edit_branch') && auth()->user()->can('enable_branch'))
                        <div class="{{ $multipleActions ? 'dropdown next-row-actions' : 'next-toolbar-actions justify-content-center' }}">
                            @if($multipleActions)
                                <button type="button" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de {{ $branch->name }}">
                                    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right next-row-actions-menu">
                            @endif

                            @can('edit_branch')
                                <a
                                        href="{{ route('tenantBranch.edit', $branch->id) }}"
                                        class="{{ $multipleActions ? 'dropdown-item' : 'btn btn-sm btn-outline-secondary' }}"
                                >
                                    <i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i><span>Editar</span>
                                </a>
                            @endcan


                            @can('enable_branch')
                                <button
                                        type="button"
                                        class="{{ $multipleActions ? 'dropdown-item'.($branch->is_active ? ' next-row-action-danger' : '') : 'btn btn-sm '.($branch->is_active ? 'btn-outline-danger' : 'btn-outline-success') }}"
                                        data-toggle-branch-status
                                        data-branch-id="{{ $branch->id }}"
                                        data-branch-name="{{ $branch->name }}"
                                        data-is-active="{{ $branch->is_active ? 1 : 0 }}"
                                        data-is-main="{{ $branch->is_main ? 1 : 0 }}"
                                        data-url="{{ route('tenantBranch.toggleStatus', $branch->id) }}"
                                >
                                    <i class="fas {{ $branch->is_active ? 'fa-ban' : 'fa-check' }} next-row-action-item-icon" aria-hidden="true"></i><span>{{ $branch->is_active ? 'Desactivar' : 'Activar' }}</span>
                                </button>
                            @endcan

                            @if($multipleActions)
                                </div>
                            @endif
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
