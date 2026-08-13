@extends('layouts.appAdmin2')

@section('title')
    Nuevo Tenant
@endsection

@section('activePlatformTenants')
    active
@endsection


@section('content')

    <div
            id="platform-tenant-create-app"
            class="container-fluid"

            data-url-index="{{route('platformTenant.index')}}"

            data-url-store="{{route('platformTenant.store')}}"
    >

        <div class="row">

            <div class="col-lg-10 offset-lg-1">

                <form
                        id="formCreateTenant"
                >

                    @csrf


                    {{-- TENANT --}}

                    <div
                            class="
                        card
                        card-outline
                        card-primary
                    "
                    >

                        <div class="card-header">

                            <h3 class="card-title">

                                <i
                                        class="
                                    fas
                                    fa-building
                                    mr-1
                                "
                                ></i>

                                Datos del Tenant

                            </h3>

                        </div>


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-7">

                                    <div class="form-group">

                                        <label>
                                            Nombre del Tenant
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                name="tenant_name"
                                                class="form-control"
                                                maxlength="150"
                                                required
                                        >

                                        <small
                                                class="form-text text-muted"
                                        >
                                            Nombre del negocio o grupo empresarial.
                                        </small>

                                    </div>

                                </div>


                                <div class="col-md-5">

                                    <div class="form-group">

                                        <label>
                                            Plan
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <select
                                                name="plan_id"
                                                id="plan_id"
                                                class="form-control"
                                                required
                                        >

                                            <option value="">
                                                Seleccione
                                            </option>

                                            @foreach(
                                                $plans as $plan
                                            )

                                                <option
                                                        value="{{ $plan->id }}"

                                                        data-max-users="{{
                                                    $plan->max_active_users
                                                }}"
                                                >

                                                    {{ $plan->name }}
                                                    -
                                                    {{
                                                        $plan->max_active_users
                                                    }}
                                                    usuarios

                                                </option>

                                            @endforeach

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <div
                                    id="planSummary"
                                    class="
                                alert
                                alert-light
                                border
                                d-none
                                mb-0
                            "
                            ></div>

                        </div>

                    </div>


                    {{-- COMPANY --}}

                    <div
                            class="
                        card
                        card-outline
                        card-secondary
                    "
                    >

                        <div class="card-header">

                            <h3 class="card-title">

                                <i
                                        class="
                                    fas
                                    fa-landmark
                                    mr-1
                                "
                                ></i>

                                Empresa inicial

                            </h3>

                        </div>


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Razón social
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                name="company_business_name"
                                                class="form-control"
                                                maxlength="200"
                                                required
                                        >

                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Nombre comercial
                                        </label>

                                        <input
                                                type="text"
                                                name="company_trade_name"
                                                class="form-control"
                                                maxlength="200"
                                        >

                                    </div>

                                </div>

                            </div>


                            <div class="row">

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            RUC
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                name="company_ruc"
                                                class="form-control"
                                                maxlength="11"
                                                inputmode="numeric"
                                                required
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- BRANCH --}}

                    <div
                            class="
                        card
                        card-outline
                        card-info
                    "
                    >

                        <div class="card-header">

                            <h3 class="card-title">

                                <i
                                        class="
                                    fas
                                    fa-store
                                    mr-1
                                "
                                ></i>

                                Local inicial

                            </h3>

                        </div>


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-7">

                                    <div class="form-group">

                                        <label>
                                            Nombre del local
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                name="branch_name"
                                                class="form-control"
                                                maxlength="150"
                                                value="Principal"
                                                required
                                        >

                                    </div>

                                </div>


                                <div class="col-md-5">

                                    <div class="form-group">

                                        <label>
                                            Código
                                        </label>

                                        <input
                                                type="text"
                                                name="branch_code"
                                                class="form-control"
                                                maxlength="50"
                                                placeholder="PRINCIPAL"
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- OWNER --}}

                    <div
                            class="
                        card
                        card-outline
                        card-success
                    "
                    >

                        <div class="card-header">

                            <h3 class="card-title">

                                <i
                                        class="
                                    fas
                                    fa-user-shield
                                    mr-1
                                "
                                ></i>

                                Propietario del Tenant

                            </h3>

                        </div>


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Nombre
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                name="owner_name"
                                                class="form-control"
                                                maxlength="255"
                                                required
                                        >

                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Correo electrónico
                                            <span class="text-danger">
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="email"
                                                name="owner_email"
                                                class="form-control"
                                                maxlength="255"
                                                required
                                        >

                                    </div>

                                </div>

                            </div>


                            <div
                                    class="
                                alert
                                alert-info
                                mb-0
                            "
                            >

                                <i
                                        class="
                                    fas
                                    fa-info-circle
                                    mr-1
                                "
                                ></i>

                                El sistema generará una contraseña
                                temporal y el propietario deberá
                                cambiarla en su primer inicio de sesión.

                            </div>

                        </div>

                    </div>


                    <div
                            class="
                        d-flex
                        justify-content-end
                        mb-4
                    "
                    >

                        <a
                                href="{{
                            route(
                                'platformTenant.index'
                            )
                        }}"
                                class="
                            btn
                            btn-outline-secondary
                            mr-2
                        "
                        >
                            Cancelar
                        </a>


                        <button
                                type="submit"
                                id="btnCreateTenant"
                                class="
                            btn
                            btn-success
                        "
                        >

                            <i
                                    class="
                                fas
                                fa-save
                                mr-1
                            "
                            ></i>

                            Crear Tenant

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script
            src="{{asset('js/platform/tenants/create.js')}}"
    ></script>

@endsection