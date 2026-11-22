@extends('layouts.appAdmin2')

@section('title') Ver Guía de Remisión @endsection

@section('styles')
    <style>
        .kv { font-size: 14px; }
        .kv b { display:inline-block; min-width: 190px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Guía: {{ $guide->serie }}-{{ $guide->numero }}</h4>

            <div>
                <a href="{{ route('shipping_guides.view') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Volver
                </a>

                @if(!$guide->identity_conflict && $guide->status != "ACCEPTED" && auth()->user()->can('edit_referralGuide'))
                <button type="button" class="btn btn-primary btn-sm" id="btnConsult" data-id="{{ $guide->id }}">
                    <i class="fa fa-sync"></i> Consultar SUNAT
                </button>
                @endif

                @foreach(['pdf' => 'PDF', 'xml' => 'XML', 'cdr' => 'CDR'] as $type => $label)
                    @if(\App\Services\ShippingGuideDocument::available($guide, $type)
                        && (auth()->user()->can('download_referralGuide') || ($type === 'pdf' && auth()->user()->can('print_referralGuide'))))
                        <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer"
                           href="{{ route('shipping_guides.download', [$guide->id, $type]) }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
        </div>

        @if($guide->identity_conflict)
            <div class="alert alert-danger" role="alert">
                <strong>CONFLICTO DE NUMERACIÓN — GUÍA LOCAL BLOQUEADA</strong>
                <p class="mb-0">El mismo número fue usado en Nubefact para otra operación.
                    La aceptación consultada no corresponde a los datos de esta guía local.
                    Sus documentos y consultas están bloqueados. Se conserva la evidencia para revisión.
                    Esto no anula ni modifica la guía remota.</p>
            </div>
        @endif

        @include('shipping_guides._recovery')

        {{-- Estado --}}
        <div class="alert alert-info">
            <b>Estado:</b> {{ $guide->emission_state_label }}
            @if($guide->sunat_responsecode)
                | <b>Code:</b> {{ $guide->sunat_responsecode }}
            @endif
            @if($guide->sunat_description)
                | <b>Desc:</b> {{ $guide->sunat_description }}
            @endif
        </div>

        @if($guide->submission_diagnostic_reference)
            <div class="alert alert-warning">Referencia de diagnóstico: {{ $guide->submission_diagnostic_reference }}.
                Revisa el error antes de intentar otra emisión. Los reintentos de la misma operación no crean otra guía.</div>
        @endif

        {{-- Encabezado --}}
        <div class="card mb-3">
            <div class="card-header"><b>Encabezado</b></div>
            <div class="card-body kv">
                <div><b>Fecha emisión:</b> {{ optional($guide->fecha_emision)->format('d/m/Y') ?? $guide->fecha_emision }}</div>
                <div><b>Inicio traslado:</b> {{ optional($guide->fecha_inicio_traslado)->format('d/m/Y') ?? $guide->fecha_inicio_traslado }}</div>
                <div><b>Motivo:</b> {{ $guide->motivo_traslado_code }} - {{ $transferReasonName ?? '' }}</div>
                <div><b>Tipo transporte:</b> {{ $guide->tipo_transporte }}</div>
                <hr>
                <div><b>Destinatario:</b> {{ $guide->customer_name }}</div>
                <div><b>Doc:</b> {{ $guide->customer_doc_type }} - {{ $guide->customer_doc_number }}</div>
                <div><b>Dirección:</b> {{ $guide->customer_address }}</div>
                <div><b>Email:</b> {{ $guide->customer_email }}</div>
            </div>
        </div>

        {{-- Traslado --}}
        <div class="card mb-3">
            <div class="card-header"><b>Traslado</b></div>
            <div class="card-body kv">
                <div><b>Peso bruto:</b> {{ $guide->peso_bruto_total }} {{ $guide->peso_bruto_um_code }}</div>
                <div><b>Bultos:</b> {{ $guide->numero_bultos }}</div>
                <div><b>Indicador SUNAT:</b> {{ $guide->sunat_shipping_indicator_code }}</div>
            </div>
        </div>

        {{-- Partida/Llegada --}}
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header"><b>Punto de partida</b></div>
                    <div class="card-body kv">
                        <div><b>Ubigeo:</b> {{ $guide->partida_ubigeo }}</div>
                        <div><b>Dirección:</b> {{ $guide->partida_direccion }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header"><b>Punto de llegada</b></div>
                    <div class="card-body kv">
                        <div><b>Ubigeo:</b> {{ $guide->llegada_ubigeo }}</div>
                        <div><b>Dirección:</b> {{ $guide->llegada_direccion }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transporte --}}
        <div class="card mb-3">
            <div class="card-header"><b>Transporte</b></div>
            <div class="card-body">
                <div class="kv mb-2">
                    <b>Transportista:</b>
                    {{ $guide->transportista_name ?? '-' }}
                    ({{ $guide->transportista_doc_type ?? '' }} {{ $guide->transportista_doc_number ?? '' }})
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Placa</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($guide->vehicles as $v)
                            <tr>
                                <td>{{ $v->is_primary ? 'Principal' : 'Secundario' }}</td>
                                <td>{{ $v->plate_number }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">Sin vehículos</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Doc</th>
                            <th>Conductor</th>
                            <th>Licencia</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($guide->drivers as $d)
                            <tr>
                                <td>{{ $d->is_primary ? 'Principal' : 'Secundario' }}</td>
                                <td>{{ $d->document_type_code }} {{ $d->document_number }}</td>
                                <td>{{ $d->first_name }} {{ $d->last_name }}</td>
                                <td>{{ $d->license_number }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin conductores</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- Items --}}
        <div class="card mb-3">
            <div class="card-header"><b>Items</b></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th class="text-right">Cantidad</th>
                            <th>UM</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($guide->items as $it)
                            <tr>
                                <td>{{ $it->line }}</td>
                                <td>{{ $it->descripcion }}</td>
                                <td class="text-right">{{ $it->cantidad }}</td>
                                <td>{{ $it->unidad_medida }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin items</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
@endsection

@section('scripts')
    <script>
        window.routes = {
            consult: "{{ route('shipping_guides.consult', ['guide' => $guide->id]) }}"
        };
    </script>

    <script>
        $(function(){
            let recoveryStarted = false;
            $('#frmGreRecovery').on('submit', function(event){
                event.preventDefault();
                if (recoveryStarted || !this.checkValidity()) { return; }
                const authorizeSend = $('#greRecoverySend').is(':checked');
                if (!window.confirm(authorizeSend
                    ? 'Se consultará a Nubefact y, solo si responde que no existe, se enviará una vez esta misma guía. ¿Confirma la recuperación?'
                    : 'Solo se comprobará el número. No se enviará ni se importarán documentos. ¿Confirma?')) { return; }
                recoveryStarted = true;
                $('#btnGreRecovery, #btnConsult').prop('disabled', true);
                $('#greRecoveryResult').text('Comprobando la guía. Espere; no cierre ni repita la operación.');
                $.ajax({url: this.action, method: 'POST', data: $(this).serialize()})
                    .done(function(res){
                        $('#greRecoveryResult').text(res.message || 'Revise el estado de la guía.');
                    })
                    .fail(function(xhr){
                        $('#greRecoveryResult').text((xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'No se pudo confirmar el resultado. Consulte el estado; no repita el envío.');
                    })
                    .always(function(){
                        $('#btnConsult').prop('disabled', false);
                        // No habilitar recuperación tras timeout, error ni doble clic.
                        $('<button type="button" class="btn btn-outline-secondary btn-sm mt-2">Actualizar detalle</button>')
                            .on('click', function(){ location.reload(); }).appendTo('#greRecoveryResult');
                    });
            });
            $('#btnConsult').on('click', function(){
                let $btn = $(this);
                $btn.prop('disabled', true);

                $.post(window.routes.consult, {_token: $('meta[name="csrf-token"]').attr('content')})
                    .done(function(res){
                        toastr.success(res.message || 'Consulta OK');
                        // recarga para refrescar links/estado
                        location.reload();
                    })
                    .fail(function(xhr){
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error consultando');
                    })
                    .always(function(){
                        $btn.prop('disabled', false);
                    });
            });
        });
    </script>
@endsection
