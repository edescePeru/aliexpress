@extends('layouts.appAdmin2')

@section('activeMyCashMovements')
    active
@endsection

@section('title') Mis Movimientos de Caja @endsection



@section('page-header')
<div class="next-page-heading">
    <span class="next-page-eyebrow">Caja · Operaciones</span>
    <h1 class="page-title">Mis movimientos de caja</h1>
    <p class="next-page-description">Movimientos propios de la empresa y local activos.</p>
</div>
@endsection

@section('page-title')
<div class="next-page-toolbar flex-wrap flex-xl-nowrap">
    <div class="next-toolbar-context"><strong>Listado operativo</strong><span>Consulta del usuario actual.</span></div>
    <div class="next-toolbar-actions flex-wrap justify-content-xl-end flex-shrink-1" role="group" aria-label="Cajas disponibles">@foreach($cashBoxes as $box)
                @php $isOpen = in_array($box->id, $openCashBoxIds); @endphp

                <button
                        type="button"
                        class="btn btn-sm {{ $isOpen ? 'btn-outline-success' : 'btn-outline-secondary' }}"
                        data-cashbox-btn
                        data-cash_box_id="{{ $box->id }}"
                        data-cash_box_name="{{ $box->name }}"
                        data-cash_box_type="{{ $box->type }}"
                        data-is_open="{{ $isOpen ? 1 : 0 }}"
                        title="{{ $isOpen ? 'Ver sesión abierta' : 'Iniciar sesión' }}"
                >
                    {{ $box->name }} · {{ $isOpen ? 'Abierta' : 'Sin sesión' }}
                </button>
            @endforeach</div>

    @if($cashBoxes->isEmpty())<span class="text-muted">Sin cajas disponibles.</span>@endif
</div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-cash-register"></i> Caja</li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Mis movimientos</li>
    </ol>
@endsection



@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions ?? []) }}">

    <section class="next-operational-list">
    @if(session('warning'))<div class="next-list-toolbar"><div class="alert alert-warning mb-0" role="status">{{ session('warning') }}</div></div>@endif
    <div class="next-list-toolbar"><form onsubmit="return false;">
        <div class="form-row align-items-end">
            <div class="form-group col-12 col-xl-3 mb-xl-0">
                <label for="q">Descripción</label><div class="input-group input-group-sm">
                    <input aria-label="Buscar movimientos" type="text" id="q" class="form-control form-control-sm" placeholder="Buscar descripción..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary btn-sm" type="button" id="btn-search">Buscar</button>
                    </div>
                </div>
            </div>

            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0">
                <label for="cash_box_id">Caja</label><select id="cash_box_id" class="form-control form-control-sm">
                    <option value="">Todas las cajas</option>
                    @foreach($cashBoxes as $b)
                        <option value="{{ $b->id }}" data-currency="{{ $b->currency }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0">
                <label for="type">Tipo</label><select id="type" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    <option value="sale">Venta</option>
                    <option value="income">Ingreso</option>
                    <option value="expense">Egreso</option>
                </select>
            </div>

            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0">
                <label for="subtype_id">Subtipo</label><select id="subtype_id" class="form-control form-control-sm">
                    <option value="">Todos los subtipos</option>
                    <option value="none">(Sin subtipo)</option>
                    @foreach($subtypes as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-6 col-xl mb-xl-0">
                <label for="date_from">Desde</label><input type="date" id="date_from" class="form-control form-control-sm" title="Desde">
            </div>
            <div class="form-group col-sm-6 col-xl mb-xl-0">
                <label for="date_to">Hasta</label><input type="date" id="date_to" class="form-control form-control-sm" title="Hasta">
            </div>
        </div>
    </form>

    </div>
    <div id="list-error" class="alert alert-danger d-none" role="alert"></div>
    <div class="next-list-summary"><span><strong id="numberItems"></strong> movimientos</span><span>Fecha de registro · más recientes primero</span></div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead>
            <tr>
                <th class="text-center">N°</th>
                <th class="text-center">Fecha</th>
                <th class="text-left">Caja</th>        <!-- ✅ nueva -->
                <th class="text-center">Tipo</th>
                <th class="text-left">Subtipo</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Moneda de caja</th><th class="text-right">Monto</th>
                <th class="text-right">Abonado</th>
                <th class="text-right">Comisión</th>
                <th class="text-left w-25">Descripción</th>
                <th class="text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="body-table"></tbody>
        </table>
    </div>

    <div class="next-list-pagination"><div id="textPagination"></div><ul class="pagination" id="pagination"></ul></div>
    </section>
    <template id="item-table">
        <tr>
            <td data-id class="text-center"></td>
            <td data-date class="text-center text-nowrap"></td>
            <td data-cashbox class="text-left"></td>
            <td data-type class="text-center"></td>
            <td data-subtype class="text-left"></td>
            <td data-status class="text-center"></td>
            <td data-currency class="text-right"></td><td data-amount class="text-right"></td>
            <td data-amount_regularize class="text-right"></td>
            <td data-commission class="text-right"></td>
            <td data-desc class="text-left"></td>
            <td data-buttons class="text-center"></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="12" align="center">No se encontraron movimientos</td>
        </tr>
    </template>

    <template id="template-button">
        <a href="" target="_blank" rel="noopener" data-print_nota data-id=""
           class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir">
            <i class="fas fa-print"></i>
        </a>
        <button data-regularizar data-id=""
                class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Regularizar">
            <i class="fas fa-check-double"></i>
        </button>
    </template>

    @include('cashRegister._regularization')
@endsection

@section('scripts')
    <script src="{{ asset('js/cashRegister/regularization.js') }}?v={{ time() }}"></script>
    <script>
        window.CASH_MOVEMENT_LIST_URL = "{{ route('cashMovement.my.list') }}";
        window.CASH_REGISTER_OPEN_URL = "{{ route('cashRegister.open') }}";
        window.CASH_REGISTER_SESSION_URL_BASE = "{{ url('dashboard/cash-registers/session/') }}";
    </script>
    <script src="{{ asset('js/cashBox/my_index.js') }}"></script>
@endsection
