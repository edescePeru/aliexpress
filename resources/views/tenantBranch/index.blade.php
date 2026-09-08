@extends('layouts.appAdmin2')

@section('content')

    <div class="container-fluid">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h3 class="card-title mb-1">
                        Sucursales
                    </h3>

                    <div class="text-muted small">
                        {{ $company->business_name }}
                        ·
                        RUC {{ $company->ruc }}
                    </div>
                </div>


                <div class="d-flex">

                    <a
                            href="{{ route('tenantCompany.index') }}"
                            class="btn btn-secondary mr-2"
                    >
                        Volver
                    </a>

                    @can('create_branch')

                        @if($company->is_active)

                            <a
                                    href="{{ route('tenantBranch.create', $company->id) }}"
                                    class="btn btn-primary"
                            >
                                Nueva sucursal
                            </a>

                        @endif

                    @endcan

                </div>

            </div>


            <div class="card-body">

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

            </div>

        </div>

    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/tenantBranch/index.js') }}"></script>
@endsection