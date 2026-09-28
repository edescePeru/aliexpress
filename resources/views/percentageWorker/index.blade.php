@extends('layouts.appAdmin2')

@section('openConfigRH')
    menu-open
@endsection

@section('activeConfigRH')
    active
@endsection

@section('openPercentageWorker')
    menu-open
@endsection

@section('activeListPercentageWorker')
    active
@endsection

@section('title')
    Parámetros laborales
@endsection

@section('page-header')
    <h1 class="page-title">
        Parámetros laborales
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Listado de parámetros
    </h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">
                <i class="fa fa-home"></i>
                Dashboard
            </a>
        </li>

        <li class="breadcrumb-item active">
            Parámetros laborales
        </li>

    </ol>
@endsection

@section('content')

    <div class="table-responsive">

        <table class="table table-bordered table-hover">

            <thead>
            <tr>
                <th>Parámetro</th>
                <th width="180">Valor</th>
                <th width="100" class="text-center">
                    Acciones
                </th>
            </tr>
            </thead>

            <tbody>

            @forelse($porcentages as $percentage)

                <tr>

                    <td>
                        @switch($percentage->name)

                            @case('assign_family')
                            Asignación familiar
                            @break

                            @case('essalud')
                            EsSalud
                            @break

                            @case('rmv')
                            Remuneración mínima vital
                            @break

                            @default
                            {{ $percentage->name }}

                        @endswitch
                    </td>

                    <td>
                        {{ $percentage->value }}
                    </td>

                    <td class="text-center">

                        <a
                                href="{{ route(
                                'platformPercentageWorker.edit',
                                $percentage->id
                            ) }}"
                                class="btn btn-outline-primary btn-sm"
                                title="Editar"
                        >
                            <i class="fas fa-edit"></i>
                        </a>

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                            colspan="3"
                            class="text-center text-muted"
                    >
                        No existen parámetros configurados.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

@endsection