@extends('layouts.appAdmin2')

@section('openTypeTax')
    menu-open
@endsection

@section('activeTypeTax')
    active
@endsection

@section('activeListTypeTax')
    active
@endsection

@section('title')
    Tipos de impuesto
@endsection

@section('page-header')
    <h1 class="page-title">
        Tipos de impuesto
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Catálogo tributario
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
            Tipos de impuesto
        </li>

    </ol>

@endsection

@section('content')

    <div
            id="typeTaxApp"
            data-store-url="{{ route('platformTypeTax.store') }}"
            data-update-url-template="{{ route(
            'platformTypeTax.update',
            ['id' => '__ID__']
        ) }}"
    >

        <div class="row mb-3">

            <div class="col-md-6">
                <p class="text-muted mb-0">
                    Catálogo global de afectaciones tributarias utilizadas por Venti360.
                </p>
            </div>

            <div class="col-md-6 text-right">

                <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNewTypeTax"
                >
                    <i class="fas fa-plus"></i>
                    Nuevo tipo de impuesto
                </button>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead>
                <tr>
                    <th width="140">
                        Código
                    </th>

                    <th>
                        Nombre
                    </th>

                    <th width="140">
                        Tasa
                    </th>

                    <th width="150">
                        Estado
                    </th>

                    <th width="170">
                        Predeterminado
                    </th>

                    <th width="100" class="text-center">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody>

                @forelse($typeTaxes as $typeTax)

                    <tr>

                        <td>
                            <code>
                                {{ $typeTax->code }}
                            </code>
                        </td>

                        <td>
                            {{ $typeTax->name }}
                        </td>

                        <td>
                            {{ rtrim(
                                rtrim(
                                    number_format(
                                        $typeTax->tax,
                                        4,
                                        '.',
                                        ''
                                    ),
                                    '0'
                                ),
                                '.'
                            ) }}%
                        </td>

                        <td>

                            @if($typeTax->is_active)

                                <span class="badge badge-success">
                                    Activo
                                </span>

                            @else

                                <span class="badge badge-secondary">
                                    Inactivo
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($typeTax->is_default)

                                <span class="badge badge-primary">
                                    <i class="fas fa-check"></i>
                                    Predeterminado
                                </span>

                            @else

                                <span class="text-muted">
                                    —
                                </span>

                            @endif

                        </td>

                        <td class="text-center">

                            <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-edit-tax
                                    data-id="{{ $typeTax->id }}"
                                    data-code="{{ $typeTax->code }}"
                                    data-name="{{ $typeTax->name }}"
                                    data-tax="{{ $typeTax->tax }}"
                                    data-default="{{ $typeTax->is_default ? 1 : 0 }}"
                                    data-active="{{ $typeTax->is_active ? 1 : 0 }}"
                                    title="Editar"
                            >
                                <i class="fas fa-edit"></i>
                            </button>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                                colspan="6"
                                class="text-center text-muted"
                        >
                            No existen tipos de impuesto configurados.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/typeTax/index.js') }}"></script>

@endsection