@extends('layouts.appAdmin2')

@section('activeCashBoxSubtype') active @endsection

@section('title') Subtipos de cajas @endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Subtipos de cajas</h1><p class="next-page-description">Administra los canales de pago y su configuración en la empresa activa.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Mantenimiento de empresa</strong><span>Configuración independiente de las sesiones operativas POS.</span></div><div class="next-toolbar-actions">
@can('create_cashBoxSubtype')<button type="button" data-btn_create class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Nuevo subtipo</button>@endcan
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashBoxSubtype.index') }}"><i class="fa fa-cogs"></i> Subtipos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions ?? []) }}">

    <section class="next-operational-list" aria-label="Listado de subtipos de cajas">
    <form action="#" class="next-list-toolbar" onsubmit="return false;">
        <div class="next-list-search-row">
            <div class="input-group next-list-search-control"><input type="text" id="search" class="form-control" placeholder="Buscar por código o nombre…" aria-label="Buscar subtipos de cajas" autocomplete="off"><div class="input-group-append"><button class="btn btn-primary next-list-search" type="button" id="btn-search"><i class="fas fa-search mr-1" aria-hidden="true"></i>Buscar</button></div></div>
            <div class="form-group mb-0 col-md-5"><label for="filter_cash_box" class="sr-only">Alcance</label><select id="filter_cash_box" class="form-control"><option value="">Todos los alcances</option><option value="global">Toda la empresa</option>@foreach($cashBoxes as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        </div>
    </form>
    <div id="list-feedback" class="alert alert-danger mx-3 d-none" role="alert"></div>
    <div class="next-list-summary" aria-live="polite"><div class="next-list-summary-copy"><strong id="numberItems" class="next-list-count">—</strong><span>Subtipos de cajas encontrados</span></div><span class="next-list-sort-context">Por posición e ID</span></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Tabla de subtipos de cajas; desplazamiento horizontal local">
    <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th scope="col" class="text-center">ID</th><th scope="col" class="text-left">Alcance</th><th scope="col" class="text-center">Código</th><th scope="col" class="text-left">Nombre</th><th scope="col" class="text-center">Diferido</th><th scope="col" class="text-center">Comisión</th><th scope="col" class="text-center">Orden</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-center">Acciones</th></tr></thead><tbody id="body-table"></tbody></table>
    </div></div>
    <div class="next-list-pagination"><div id="textPagination" aria-live="polite"></div><nav aria-label="Paginación de subtipos de cajas"><ul class="pagination" id="pagination"></ul></nav></div>
    </section>

    <template id="item-table">
        <tr>
            <td class="text-center" data-id></td>
            <td class="text-left" data-scope></td>
            <td class="text-center" data-code></td>
            <td class="text-left" data-name></td>
            <td class="text-center" data-deferred></td>
            <td class="text-center" data-commission></td>
            <td class="text-center" data-position></td>
            <td class="text-center" data-status></td>
            <td class="text-center" data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="9" class="text-center py-4">No se ha encontrado ningún subtipo</td>
        </tr>
    </template>

    <template id="template-btn-edit"><button type="button" class="dropdown-item" role="menuitem" data-editar><i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i>Editar</button></template>

    <template id="template-btn-toggle"><button type="button" class="dropdown-item" role="menuitem" data-toggle-active><i class="fas fa-power-off next-row-action-item-icon" aria-hidden="true"></i><span data-toggle-label>Activar/Desactivar</span></button></template>

    <template id="template-row-actions"><div class="dropdown next-row-actions"><button type="button" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu" role="menu"></div></div></template>
    <template id="badge-yes">
        <span class="badge badge-success badge-pill">Sí</span>
    </template>
    <template id="badge-no">
        <span class="badge badge-secondary badge-pill">No</span>
    </template>

    <template id="badge-active">
        <span class="badge badge-success badge-pill">Activo</span>
    </template>
    <template id="badge-inactive">
        <span class="badge badge-danger badge-pill">Inactivo</span>
    </template>

    <div class="modal fade next-aux-modal" id="modalSubtype" aria-labelledby="subtypeTitle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="next-aux-modal-form" id="formSubtype"
                      data-url_list="{{ route('cashBoxSubtype.list') }}"
                      data-url_create="{{ route('cashBoxSubtype.store') }}"
                      data-url_update="{{ route('cashBoxSubtype.update') }}"
                      data-url_toggle="{{ route('cashBoxSubtype.toggle') }}"
                >
                    @csrf
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="subtypeTitle">Nuevo Subtipo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="id" id="st_id">

                        <div class="form-group">
                            <label for="st_cash_box_id">Alcance</label>
                            <select class="form-control" name="cash_box_id" id="st_cash_box_id">
                                <option value="global">Toda la empresa</option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="st_code">Código</label>
                            <input type="text" class="form-control" name="code" id="st_code" maxlength="50" required placeholder="yape / plin / pos / transfer">
                            <small class="text-muted">Se guardará en minúsculas, sin espacios (se reemplaza por guión bajo).</small>
                        </div>

                        <div class="form-group">
                            <label for="st_name">Nombre</label>
                            <input type="text" class="form-control" name="name" id="st_name" maxlength="120" required placeholder="Yape">
                        </div>

                        {{-- ✅ NUEVOS FLAGS --}}
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="st_is_deferred" name="is_deferred">
                                <label class="custom-control-label" for="st_is_deferred">
                                    Diferido (requiere regularización)
                                </label>
                            </div>
                            <small class="text-muted">Ej: POS/pasarela que deposita después.</small>
                        </div>

                        <div class="form-group" id="wrap_requires_commission" style="display:none;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="st_requires_commission" name="requires_commission">
                                <label class="custom-control-label" for="st_requires_commission">
                                    Requiere comisión en la regularización
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="st_position">Orden (posición)</label>
                            <input type="number" class="form-control" name="position" id="st_position" min="0" value="0">
                        </div>

                    </div>

                    <div class="modal-footer">
                        @canany(['create_cashBoxSubtype', 'edit_cashBoxSubtype'])
                            <button type="button" class="btn btn-primary" id="btnSaveSubtype">
                                Guardar
                            </button>
                        @endcanany
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                    </div>
                </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/cashBoxSubtype/index.js') }}?v={{ filemtime(public_path('js/cashBoxSubtype/index.js')) }}"></script>
@endsection
