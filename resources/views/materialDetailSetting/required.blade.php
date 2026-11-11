@extends('layouts.appAdmin2')
@section('title', 'Configurar parámetros de materiales')
@section('content')
<section class="next-form-section">
    <div class="alert alert-warning" role="alert">
        Esta empresa todavía no tiene una configuración válida de materiales.
        Configure sus parámetros incluyendo la unidad de medida antes de crear o editar productos.
    </div>
    @can('config_materialSetting')
        <a href="{{ route('settings.material-details.index') }}" class="btn btn-primary">Configurar parámetros</a>
    @else
        <p>Solicite la configuración al responsable autorizado de esta empresa.</p>
    @endcan
</section>
@endsection
