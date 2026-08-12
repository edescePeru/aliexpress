@extends('layouts.appAdmin2')

@section('title')
    Superadministración
@endsection

@section('activePlatformDashboard')
    active
@endsection

@section('content')

    <div class="container-fluid">

        <div class="row">

            <div class="col-12">

                <div class="card card-outline card-primary">

                    <div class="card-header">

                        <h3 class="card-title">

                            <i class="fas fa-shield-alt mr-1"></i>

                            Superadministración Venti360

                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="alert alert-info">

                            <div class="d-flex align-items-center">

                                <i
                                        class="
                                    fas
                                    fa-user-shield
                                    fa-2x
                                    mr-3
                                "
                                ></i>

                                <div>

                                    <strong>
                                        Modo Administrador de Plataforma
                                    </strong>

                                    <br>

                                    <span>
                                    {{ $user->name }}
                                </span>

                                    <br>

                                    <small>
                                        {{ $user->email }}
                                    </small>

                                </div>

                            </div>

                        </div>


                        <p class="text-muted mb-0">

                            Desde este módulo se administrarán
                            los tenants, planes, propietarios,
                            empresas y configuraciones globales
                            de Venti360.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection