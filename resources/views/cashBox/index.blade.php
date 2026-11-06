@extends('layouts.appAdmin2')

@section('activeCashBox')
    active
@endsection

@section('title')
    Cajas
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Cajas</h1><p class="next-page-description">Administra las cajas y su alcance en la empresa activa.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Mantenimiento de empresa</strong><span>Configuración independiente de las sesiones operativas POS.</span></div><div class="next-toolbar-actions">
@can('create_cashBox')<button type="button" data-btn_create class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Nueva caja</button>@endcan
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashBox.index') }}"><i class="fa fa-cogs"></i> Cajas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions ?? []) }}">

    <!-- Filtros -->
    <section class="next-operational-list" aria-label="Listado de cajas">
    <form action="#" class="next-list-toolbar" onsubmit="return false;">
        <div class="next-list-search-row">
            <div class="input-group next-list-search-control"><input type="text" id="search" class="form-control" placeholder="Buscar por nombre / banco / cuenta…" aria-label="Buscar cajas" autocomplete="off"><div class="input-group-append"><button class="btn btn-primary next-list-search" type="button" id="btn-search"><i class="fas fa-search mr-1" aria-hidden="true"></i>Buscar</button></div></div>

        </div>
    </form>
    <div id="list-feedback" class="alert alert-danger mx-3 d-none" role="alert"></div>
    <div class="next-list-summary" aria-live="polite"><div class="next-list-summary-copy"><strong id="numberItems" class="next-list-count">—</strong><span>Cajas encontradas</span></div><span class="next-list-sort-context">Por posición e ID</span></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Tabla de cajas; desplazamiento horizontal local">
    <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th scope="col" class="text-center">ID</th><th scope="col" class="text-left">Nombre</th><th scope="col" class="text-center">Tipo</th><th scope="col" class="text-left">Alcance</th><th scope="col" class="text-center">Subtipos</th><th scope="col" class="text-left">Cuenta bancaria</th><th scope="col" class="text-center">Moneda</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-center">Acciones</th></tr></thead><tbody id="body-table"></tbody></table>
    </div></div>
    <div class="next-list-pagination"><div id="textPagination" aria-live="polite"></div><nav aria-label="Paginación de cajas"><ul class="pagination" id="pagination"></ul></nav></div>
    </section>

    <template id="item-table">
        <tr>
            <td class="text-center" data-id></td>
            <td class="text-left" data-name></td>
            <td class="text-center" data-type></td>
            <td class="text-left" data-scope></td>
            <td class="text-center" data-uses_subtypes></td>
            <td class="text-left" data-bank-account></td>
            <td class="text-center" data-currency></td>
            <td class="text-center" data-status></td>
            <td class="text-center" data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="9" class="text-center py-4">No se ha encontrado ninguna caja</td>
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

    {{-- Modal --}}
    <div class="modal fade next-aux-modal" id="modalCashBox" tabindex="-1" aria-labelledby="cashBoxTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="next-aux-modal-form" id="formCashBox"
                      data-url_list="{{ route('cashBox.list') }}"
                      data-url_create="{{ route('cashBox.store') }}"
                      data-url_update="{{ route('cashBox.update') }}"
                      data-url_toggle="{{ route('cashBox.toggle') }}"
                >
                    @csrf
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashBoxTitle">Nueva Caja</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="id" id="cb_id">

                        <div class="form-group">
                            <label for="cb_name">Nombre</label>
                            <input type="text" class="form-control" name="name" id="cb_name" required maxlength="120">
                        </div>

                        <div class="form-group">
                            <label for="cb_type">Tipo</label>
                            <select class="form-control" name="type" id="cb_type" required>
                                <option value="cash">Efectivo</option>
                                <option value="bank">Bancario</option>
                            </select>
                        </div>

                        <div class="form-group">

                            <label for="cb_branch_id">
                                Alcance
                            </label>

                            <select
                                    class="form-control"
                                    name="branch_id"
                                    id="cb_branch_id"
                            >

                                <option value="">
                                    Toda la empresa
                                </option>

                                @foreach($branches as $branch)

                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                    </option>

                                @endforeach

                            </select>

                            <small class="form-text text-muted">

                                Toda la empresa indica una caja general. La disponibilidad en POS depende del local y de la sesión operativa.

                            </small>

                        </div>

                        <div class="form-group" id="wrap_uses_subtypes">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="cb_uses_subtypes" name="uses_subtypes">
                                <label class="custom-control-label" for="cb_uses_subtypes">
                                    Permitir subtipos (Yape/Plin/POS/Transferencia)
                                </label>
                            </div>
                        </div>

                        <div
                                id="wrap_bank_fields"
                                style="display:none;"
                        >

                            <div class="form-group">

                                <label for="cb_company_bank_account_id">
                                    Cuenta bancaria
                                </label>

                                <select
                                        class="form-control"
                                        name="company_bank_account_id"
                                        id="cb_company_bank_account_id"
                                >

                                    <option value="">
                                        Seleccione una cuenta bancaria
                                    </option>

                                    @foreach($bankAccounts as $account)

                                        <option
                                                value="{{ $account->id }}"
                                        >
                                            {{ optional($account->bank)->name }}
                                            -
                                            {{ $account->title }}

                                            @if($account->account_number)
                                                - {{ $account->account_number }}
                                            @endif

                                            @if($account->currency)
                                                ({{ $account->currency }})
                                            @endif
                                        </option>

                                    @endforeach

                                </select>

                                <small class="form-text text-muted">
                                    La información bancaria se obtiene del catálogo
                                    de cuentas bancarias de la empresa.
                                </small>

                            </div>

                        </div>

                        <div class="form-group" id="wrap_currency">
                            <label for="cb_currency">Moneda (opcional)</label>
                            <select class="form-control" name="currency" id="cb_currency">
                                <option value="">(Sin definir)</option>
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cb_position">Orden (posición)</label>
                            <input type="number" class="form-control" name="position" id="cb_position" min="0" value="0">
                        </div>

                    </div>

                    <div class="modal-footer">
                        @canany(['create_cashBox', 'edit_cashBox'])
                            <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnSaveCashBox"
                            >
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
    <script src="{{ asset('js/cashBox/index.js') }}?v={{ filemtime(public_path('js/cashBox/index.js')) }}"></script>
@endsection
