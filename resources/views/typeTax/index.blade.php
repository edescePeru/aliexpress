@extends('layouts.appAdmin2')
@section('openTypeTax', 'menu-open')
@section('activeTypeTax', 'active')
@section('activeListTypeTax', 'active')
@section('title', 'Tipos de impuesto')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Tipos de impuesto</h1><p class="next-page-description">Catálogo tributario global utilizado por Venti360.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Catálogo tributario</strong><span>Administración global de plataforma.</span></div><div class="next-toolbar-actions flex-wrap"><button type="button" class="btn btn-primary btn-sm" id="btnNewTypeTax"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Nuevo tipo de impuesto</button></div></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active">Tipos de impuesto</li></ol>
@endsection
@section('content')
<section id="typeTaxApp" class="next-operational-list" aria-label="Catálogo de tipos de impuesto" data-store-url="{{ route('platformTypeTax.store') }}" data-update-url-template="{{ route('platformTypeTax.update', ['id' => '__ID__']) }}">
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>{{ $typeTaxes->count() }} tipos de impuesto</strong><span>Predeterminado primero; luego por nombre.</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Tipos de impuesto; desplazamiento horizontal de tabla">
        <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">Código</th><th>Nombre</th><th class="text-right">Tasa</th><th class="text-center">Estado</th><th class="text-center">Default</th><th class="text-center">Acciones</th></tr></thead><tbody>
        @forelse($typeTaxes as $typeTax)
        <tr>
            <td class="text-center text-nowrap"><code>{{ $typeTax->code }}</code></td>
            <td>{{ $typeTax->name }}</td>
            <td class="text-right text-nowrap">{{ rtrim(rtrim(number_format($typeTax->tax, 4, '.', ''), '0'), '.') }}%</td>
            <td class="text-center"><span class="badge {{ $typeTax->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $typeTax->is_active ? 'Activo' : 'Inactivo' }}</span></td>
            <td class="text-center">@if($typeTax->is_default)<span class="badge badge-primary">Predeterminado</span>@else<span class="text-muted">—</span>@endif</td>
            <td class="text-center"><div class="dropdown next-row-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de {{ $typeTax->code }}"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>
                <div class="dropdown-menu dropdown-menu-right next-row-actions-menu">
                    <button type="button" class="dropdown-item" data-edit-tax data-id="{{ $typeTax->id }}" data-code="{{ $typeTax->code }}" data-name="{{ $typeTax->name }}" data-tax="{{ $typeTax->tax }}" data-default="{{ $typeTax->is_default ? 1 : 0 }}" data-active="{{ $typeTax->is_active ? 1 : 0 }}"><i class="fas fa-edit next-row-action-item-icon" aria-hidden="true"></i>Editar</button>
                </div>
            </div></td>
        </tr>
        @empty
        <tr><td colspan="6" class="next-table-empty">No existen tipos de impuesto configurados.</td></tr>
        @endforelse
        </tbody></table>
    </div></div>
</section>
@endsection
@section('scripts')
<script src="{{ asset('js/typeTax/index.js') }}?v={{ filemtime(public_path('js/typeTax/index.js')) }}"></script>
@endsection
