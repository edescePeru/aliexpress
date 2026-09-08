@extends('layouts.appAdmin2')

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h3 class="card-title mb-0">
                    Empresas
                </h3>

                @can('create_company')
                    <a href="{{ route('tenantCompany.create') }}" class="btn btn-primary">
                        Nueva empresa
                    </a>
                @endcan

            </div>


            <div class="card-body">

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

            </div>

        </div>

    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/tenantCompany/index.js') }}"></script>
@endsection