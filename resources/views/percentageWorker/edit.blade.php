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
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Editar parámetro laboral</h1><p class="next-page-description">Actualiza el valor global conservando la clave contractual.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Configuración laboral</strong><span>El nombre del parámetro permanece de solo lectura.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">
            <a href="{{ route('platform.dashboard') }}">
                Superadministración
            </a>
        </li>

        <li class="breadcrumb-item">
            <a href="{{ route('platformPercentageWorker.index') }}">
                Parámetros laborales
            </a>
        </li>

        <li class="breadcrumb-item active">
            Editar
        </li>

    </ol>
@endsection

@section('content')

    <form
            id="formEdit"
            class="form-horizontal"
            data-url="{{ route('platformPercentageWorker.update', $percentageWorker->id) }}"
    >
        @csrf

        <div class="form-group row">
            <div class="col-md-6">

                <label>Parámetro</label>

                <input
                        type="text"
                        class="form-control"
                        value="@switch($percentageWorker->name)
                        @case('assign_family') Asignación familiar @break
                        @case('essalud') EsSalud @break
                        @case('rmv') Remuneración mínima vital @break
                        @default {{ $percentageWorker->name }} @endswitch"
                        readonly
                >

                <small class="form-text text-muted">
                    Clave interna:
                    {{ $percentageWorker->name }}
                </small>

            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6">

                <label for="value">
                    Valor
                    <span class="badge badge-danger">
                    (*)
                </span>
                </label>

                <input
                        type="number"
                        id="value"
                        class="form-control"
                        name="value"
                        min="0"
                        step="0.01"
                        value="{{ $percentageWorker->value }}"
                        required
                >

            </div>
        </div>

        <div class="d-flex justify-content-end">

            <a
                    href="{{ route('platformPercentageWorker.index') }}"
                    class="btn btn-outline-secondary mr-2"
            >
                Cancelar
            </a>

            <button
                    type="button"
                    id="btn-submit"
                    class="btn btn-primary"
            >
                <i class="fas fa-save"></i>
                <span data-button-text>
                Guardar cambios
            </span>
            </button>

        </div>

    </form>

@endsection

@section('scripts')
    <script src="{{ asset('js/percentageWorker/edit.js') }}"></script>
@endsection
