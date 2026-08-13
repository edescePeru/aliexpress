@extends('layouts.appAdmin2')

@section('title')
    Tenant
@endsection

@section('activePlatformTenants')
    active
@endsection


@section('content')

    <div
            id="platform-tenant-show-app"
            class="container-fluid"

            data-tenant-id="{{$tenant->id}}"

            data-url-edit="{{route('platformTenant.editData',$tenant->id)}}"

            data-url-update="{{route('platformTenant.update',$tenant->id)}}"

            data-url-toggle-status="{{route('platformTenant.toggleStatus',$tenant->id)}}"
    >
        <select id="tenantAvailablePlans" class="d-none">

            @foreach($plans as $plan)

                <option value="{{ $plan->id }}">
                    {{ $plan->name }}
                    ({{ $plan->max_active_users }} usuarios)
                </option>

            @endforeach

        </select>

        <div
                class="
            d-flex
            justify-content-between
            align-items-center
            flex-wrap
            mb-3
        "
        >

            <div>

                <h4 class="mb-0">
                    {{ $tenant->name }}
                </h4>

                <small class="text-muted">
                    Tenant #{{ $tenant->id }}
                </small>

            </div>


            <div class="mt-2 mt-md-0">

                <button
                        type="button"
                        id="btnEditTenant"
                        class="
                    btn
                    btn-outline-primary
                    btn-sm
                "
                >
                    <i
                            class="
                        fas
                        fa-pencil-alt
                        mr-1
                    "
                    ></i>

                    Editar
                </button>


                <button
                        type="button"
                        id="btnToggleTenant"
                        class="
                    btn
                    {{
                        $tenant->is_active
                            ? 'btn-outline-danger'
                            : 'btn-outline-success'
                    }}
                                btn-sm
"

                        data-current-status="{{
                    $tenant->is_active
                        ? 1
                        : 0
                }}"
                >

                    <i
                            class="
                        fas
                        {{
                            $tenant->is_active
                                ? 'fa-ban'
                                : 'fa-check'
                        }}
                                    mr-1
"
                    ></i>

                    {{
                        $tenant->is_active
                            ? 'Inhabilitar'
                            : 'Habilitar'
                    }}

                </button>

            </div>

        </div>


        <div class="row">

            <div class="col-xl-3 col-md-6">

                <div class="small-box bg-info">

                    <div class="inner">

                        <h4>
                            {{
                                optional(
                                    $tenant->plan
                                )->name
                                ?: 'Sin plan'
                            }}
                        </h4>

                        <p>
                            Plan contratado
                        </p>

                    </div>

                    <div class="icon">
                        <i
                                class="
                            fas
                            fa-layer-group
                        "
                        ></i>
                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="small-box bg-success">

                    <div class="inner">

                        <h4>
                            {{ $activeUsers }}
                            /
                            {{ $maxUsers }}
                        </h4>

                        <p>
                            Usuarios activos
                        </p>

                    </div>

                    <div class="icon">
                        <i
                                class="
                            fas
                            fa-users
                        "
                        ></i>
                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="small-box bg-warning">

                    <div class="inner">

                        <h4>
                            {{ $availableUsers }}
                        </h4>

                        <p>
                            Cupos disponibles
                        </p>

                    </div>

                    <div class="icon">
                        <i
                                class="
                            fas
                            fa-user-plus
                        "
                        ></i>
                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="small-box bg-secondary">

                    <div class="inner">

                        <h4>
                            {{
                                $tenant
                                    ->companies
                                    ->count()
                            }}
                        </h4>

                        <p>
                            Companies
                        </p>

                    </div>

                    <div class="icon">
                        <i
                                class="
                            fas
                            fa-building
                        "
                        ></i>
                    </div>

                </div>

            </div>

        </div>


        <div class="row">

            <div class="col-lg-6">

                <div
                        class="
                    card
                    card-outline
                    card-primary
                "
                >

                    <div class="card-header">

                        <h3 class="card-title">
                            Propietario
                        </h3>

                    </div>


                    <div class="card-body">

                        @if($owner)

                            <strong>
                                {{ $owner->name }}
                            </strong>

                            <br>

                            <span>
                            {{ $owner->email }}
                        </span>

                            <br>

                            <span
                                    class="
                                badge
                                {{
                                    $owner->enable
                                        ? 'badge-success'
                                        : 'badge-secondary'
                                }}
                                            mt-2
"
                            >
                            {{
                                $owner->enable
                                    ? 'Activo'
                                    : 'Inactivo'
                            }}
                        </span>


                            <hr>


                            <button
                                    type="button"
                                    class="
                                btn
                                btn-outline-secondary
                                btn-sm
                            "
                                    disabled
                            >

                                <i
                                        class="
                                    fas
                                    fa-key
                                    mr-1
                                "
                                ></i>

                                Resetear contraseña

                            </button>

                            <small
                                    class="
                                text-muted
                                d-block
                                mt-2
                            "
                            >
                                Se habilitará en 4H-4.
                            </small>

                        @else

                            <div
                                    class="
                                alert
                                alert-warning
                                mb-0
                            "
                            >
                                Este tenant no tiene
                                un Owner configurado.
                            </div>

                        @endif

                    </div>

                </div>

            </div>


            <div class="col-lg-6">

                <div
                        class="
                    card
                    card-outline
                    card-secondary
                "
                >

                    <div class="card-header">

                        <h3 class="card-title">
                            Resumen de usuarios
                        </h3>

                    </div>


                    <div class="card-body">

                        <dl class="row mb-0">

                            <dt class="col-sm-6">
                                Usuarios totales
                            </dt>

                            <dd class="col-sm-6">
                                {{ $usersCount }}
                            </dd>


                            <dt class="col-sm-6">
                                Activos
                            </dt>

                            <dd class="col-sm-6">
                                {{ $activeUsers }}
                            </dd>


                            <dt class="col-sm-6">
                                Inactivos
                            </dt>

                            <dd class="col-sm-6">
                                {{ $inactiveUsers }}
                            </dd>


                            <dt class="col-sm-6">
                                Cupos disponibles
                            </dt>

                            <dd class="col-sm-6">
                                {{ $availableUsers }}
                            </dd>

                        </dl>

                    </div>

                </div>

            </div>

        </div>


        <div
                class="
            card
            card-outline
            card-secondary
        "
        >

            <div class="card-header">

                <h3 class="card-title">
                    Companies
                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                            class="
                        table
                        table-hover
                        mb-0
                    "
                    >

                        <thead>

                        <tr>

                            <th>
                                Empresa
                            </th>

                            <th>
                                RUC
                            </th>

                            <th>
                                Estado
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        @forelse(
                            $tenant->companies
                            as $company
                        )

                            <tr>

                                <td>
                                    {{
                                        $company->trade_name
                                        ?: $company->business_name
                                    }}
                                </td>

                                <td>
                                    {{
                                        $company->ruc
                                        ?? '-'
                                    }}
                                </td>

                                <td>

                                <span
                                        class="
                                        badge
                                        {{
                                            $company->is_active
                                                ? 'badge-success'
                                                : 'badge-secondary'
                                        }}
                                                "
                                >

                                    {{
                                        $company->is_active
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }}

                                </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                        colspan="3"
                                        class="
                                    text-center
                                    text-muted
                                    py-4
                                "
                                >
                                    No hay Companies.
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

    <script
            src="{{asset('js/platform/tenants/show.js')}}"
    ></script>

@endsection