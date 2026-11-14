@extends('layouts.appAdmin2')

@section('title', 'Configurar Nubefact')

@section('activePlatformNubefact', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Configurar Nubefact</h1><p class="next-page-description">{{ $company->business_name }} · RUC {{ $company->ruc }}</p></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item"><a href="{{ route('platformNubefact.index') }}">Nubefact</a></li><li class="breadcrumb-item active">Configurar</li></ol>
@endsection

@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Configuración de la empresa</strong><span>{{ optional($company->tenant)->name }}</span></div><div class="next-toolbar-actions"><span class="badge {{ $config && $config->is_enabled ? 'badge-success' : 'badge-secondary' }}">{{ !$config ? 'Sin configurar' : ($config->is_enabled ? 'Habilitada' : 'Deshabilitada') }}</span></div></div>
@endsection

@section('content')
    <div>

        @if($errors->any())
            <div class="next-form-section"><div class="alert alert-danger mb-0" role="alert"><strong>No se guardó la configuración.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif
        <form id="formNubefactConfig" method="POST" action="{{ route('platformNubefact.update', [$company->tenant_id, $company->id]) }}" autocomplete="off">
            @csrf
            <input type="hidden" name="version" value="{{ old('version', $config ? (string) $config->updated_at : '') }}">
            <section class="next-form-section" aria-labelledby="nubefact-connection-title">
                <div class="next-section-header"><div><span class="next-section-kicker">01</span><h2 class="next-section-title" id="nubefact-connection-title">Conexión del proveedor</h2><p class="next-section-description">Datos de integración exclusivos de esta empresa.</p></div></div>
                <div>
                    <p class="text-muted">Tenant: {{ optional($company->tenant)->name }}. La configuración pertenece a esta empresa.</p>
                    <div class="row">
                        <div class="col-md-8 form-group"><label for="api_url">Ruta de integración <span class="text-danger">*</span></label><input id="api_url" name="api_url" type="url" required maxlength="2048" class="form-control" value="{{ old('api_url', $config ? $config->api_url : '') }}" aria-describedby="api-url-help"><small id="api-url-help" class="form-text text-muted">Copia la ruta de API – Integración correspondiente al RUC de esta empresa. Debe usar HTTPS y un servidor autorizado; no incluyas parámetros ni credenciales en la URL.</small></div>
                        <div class="col-md-4 form-group"><label for="api_token">{{ $config ? 'Reemplazar token' : 'Token' }}</label><input id="api_token" name="api_token" type="password" maxlength="4096" class="form-control" autocomplete="new-password" {{ $config ? '' : 'required' }} aria-describedby="api-token-help"><small id="api-token-help" class="form-text text-muted">{{ $config ? 'Token configurado. Déjalo vacío para conservarlo. Si cambias la ruta o el RUC, ingresa el token de la nueva conexión.' : 'Ingresa el token de la cuenta Nubefact de esta empresa.' }}</small></div>
                    </div>
                    <input type="hidden" name="is_enabled" value="0">
                    <div class="custom-control custom-checkbox"><input id="is_enabled" name="is_enabled" type="checkbox" value="1" class="custom-control-input" {{ old('is_enabled', $config ? (int) $config->is_enabled : 0) ? 'checked' : '' }}><label class="custom-control-label" for="is_enabled">Habilitar conexión para esta empresa</label></div>
                </div>
            </section>
            <section class="next-form-section" aria-labelledby="nubefact-series-title">
                <div class="next-section-header"><div><span class="next-section-kicker">02</span><h2 class="next-section-title" id="nubefact-series-title">Series de comprobantes</h2><p class="next-section-description">Factura, boleta y sus notas de crédito.</p></div></div>
                <div>
                    <div class="row">
                        <div class="col-sm-6 col-xl-3 form-group"><label for="serie_factura">Factura <span class="text-danger">*</span></label><input id="serie_factura" name="serie_factura" class="form-control" required maxlength="4" pattern="F[A-Z0-9]{3}" value="{{ old('serie_factura', $config ? $config->serie_factura : 'FFF1') }}"></div>
                        <div class="col-sm-6 col-xl-3 form-group"><label for="serie_boleta">Boleta <span class="text-danger">*</span></label><input id="serie_boleta" name="serie_boleta" class="form-control" required maxlength="4" pattern="B[A-Z0-9]{3}" value="{{ old('serie_boleta', $config ? $config->serie_boleta : 'BBB1') }}"></div>
                        <div class="col-sm-6 col-xl-3 form-group"><label for="serie_nc_factura">Nota de crédito de factura</label><input id="serie_nc_factura" name="serie_nc_factura" class="form-control" maxlength="4" pattern="F[A-Z0-9]{3}" value="{{ old('serie_nc_factura', $config ? $config->serie_nc_factura : '') }}"></div>
                        <div class="col-sm-6 col-xl-3 form-group"><label for="serie_nc_boleta">Nota de crédito de boleta</label><input id="serie_nc_boleta" name="serie_nc_boleta" class="form-control" maxlength="4" pattern="B[A-Z0-9]{3}" value="{{ old('serie_nc_boleta', $config ? $config->serie_nc_boleta : '') }}"></div>
                    </div>
                    <small class="text-muted">Nubefact asigna la numeración de facturas y boletas.</small>
                    <hr>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="serie_gre_remitente">Serie GRE remitente</label>
                            <input id="serie_gre_remitente" name="serie_gre_remitente" class="form-control"
                                maxlength="4" pattern="T[A-Z0-9]{3}" placeholder="TTT1"
                                value="{{ old('serie_gre_remitente', optional($config)->serie_gre_remitente) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="gre_remitente_numero_inicial">Correlativo inicial GRE</label>
                            <input type="number" id="gre_remitente_numero_inicial" name="gre_remitente_numero_inicial"
                                class="form-control" min="1" max="99999999" step="1"
                                value="{{ old('gre_remitente_numero_inicial', optional($config)->gre_remitente_numero_inicial) }}">
                        </div>
                    </div>
                    <small class="text-muted">Completa ambos campos para emitir guías. Ingresa el primer número libre en Nubefact para esa serie.</small>
                </div>
            </section>
            <div class="next-form-section"><div class="alert alert-info mb-0" role="note">Guardar actualiza la configuración. No prueba la conexión ni emite comprobantes.</div></div>
            <div class="next-list-toolbar justify-content-end"><div class="next-toolbar-actions flex-wrap"><a href="{{ route('platformNubefact.index') }}" class="btn btn-outline-secondary">Volver</a><button id="btnSaveNubefactConfig" type="submit" class="btn btn-primary">Guardar configuración</button></div></div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            const $form = $('#formNubefactConfig');
            const $button = $('#btnSaveNubefactConfig');
            const originalLabel = $button.html();
            let saving = false;

            $form.on('submit', function (event) {
                if (saving) {
                    event.preventDefault();
                    return;
                }

                if (!this.checkValidity()) {
                    event.preventDefault();
                    this.reportValidity();
                    return;
                }

                saving = true;
                $form.attr('aria-busy', 'true');
                $button.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>'
                    + 'Guardando…'
                );
            });

            // Restablecer el botón si se vuelve con el historial del navegador.
            $(window).on('pageshow', function () {
                saving = false;
                $form.removeAttr('aria-busy');
                $button.prop('disabled', false).html(originalLabel);
            });

            const successMessage = @json(session('success'));
            if (successMessage) {
                $.alert({
                    title: 'Configuración guardada',
                    content: $('<div>').text(successMessage).html(),
                    type: 'green',
                    icon: 'fas fa-check-circle',
                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-success'
                        }
                    }
                });
            }
        });
    </script>
@endsection