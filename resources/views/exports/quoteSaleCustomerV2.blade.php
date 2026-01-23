<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->code }}</title>

    <style>
        @page { margin: 20mm 15mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 0.5px solid #999; padding: 4px 3px; font-size: 10px; }
        thead th { background: #e5e5e5; font-size: 10px; }

        .no-border td { border: none !important; }
        .no-padding td { padding: 0 !important; }

        .title-empresa { font-weight: bold; font-size: 12px; }
        .empresa-linea { font-size: 10px; }

        .doc-box {
            border: 2px solid #333;
            padding: 8px 10px;
            text-align: center;
        }
        .doc-ruc { font-size: 14px; font-weight: bold; }
        .doc-tipo { font-size: 13px; font-weight: bold; margin: 4px 0; }
        .doc-serie-num { font-size: 14px; font-weight: bold; }

        .box-title { font-weight:bold; font-size:11px; margin-bottom:4px; }
        .label { font-weight:bold; display:inline-block; min-width:80px; }
        .texto { display:inline-block; min-width:80px; margin-left: 3px }

        .totales-table { width:40%; float:right; margin-top:8px; }
        .totales-table td { padding:3px 4px; font-size:10px; }

        .footer {
            margin-top: 20px;
            font-size: 8px;
            border-top: 0.5px solid #999;
            padding-top: 4px;
        }

        .page-break { page-break-after: always; }

        .bank-wrap{
            width:100%;
            border-collapse:collapse;
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
            color:#111;
        }
        .bank-wrap td{
            vertical-align:top;
            padding:0;
            border:none;
        }

        .bank-card{
            width:100%;
            border-collapse:collapse;
            border:none;
        }
        .bank-card td{
            border:none;
            padding:0;
            vertical-align:middle;
        }

        .bank-card tr{
            vertical-align:middle;   /* la fila define el centro */
        }

        /* LOGO */
        .logo-cell{
            width:52px;
            padding-right:10px;
            vertical-align:middle;   /* 🔥 centra el logo */
        }

        .logo-cell img{
            width:50px;
            display:block;
        }

        /* TEXTO */
        .info-cell{
            vertical-align:top;      /* el texto sigue alineado arriba */
        }

        .bank-title{
            font-weight:bold;
            margin:0 0 2px 0;
        }
        .bank-line{
            margin:0;
            line-height:1.3;
        }
        .bank-owner{
            margin-top:4px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .verse-cell{
            text-align:right;
            vertical-align:bottom;
            color:#2b6cb0;
            font-size:11px;
            font-style:italic;
            padding-top:6px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

{{-- ============================= --}}
{{-- ENCABEZADO TIPO NUBEFACT      --}}
{{-- ============================= --}}

<table class="no-border">
    <tr class="no-border">
        <td class="no-border" style="width:60%; vertical-align:top; padding-right:10px;">
            <img src="{{ asset('/images/logo/'.$logotipoEmpresa) }}" style="max-height:90px; margin-bottom:5px;">

            <div class="title-empresa">{{ $nombreEmpresa }}</div>
            <div class="empresa-linea">{{ $direccionEmpresa }}</div>
            <div class="empresa-linea">{{ $telefonoEmpresa }}</div>
            <div class="empresa-linea">{{ $emailEmpresa }}</div>
            <div class="empresa-linea">{{ $webEmpresa }}</div>
        </td>

        <td class="no-border" style="width:40%; vertical-align:top;">
            <div class="doc-box">
                <div class="doc-ruc">RUC {{ $rucEmpresa }}</div>
                <div class="doc-tipo">COTIZACIÓN</div>
                <div class="doc-serie-num">{{ $quote->code }}</div>
            </div>
        </td>
    </tr>
</table>

<br>

{{-- ============================= --}}
{{-- DATOS DEL CLIENTE Y COTIZACIÓN --}}
{{-- ============================= --}}

<table>
    <tr>
        {{-- CLIENTE --}}
        <td style="width:50%; vertical-align:top; padding:6px;">
            <div class="box-title">DATOS DEL CLIENTE</div>

            @php
                $customer = $quote->customer ?: null;
                $contact  = $quote->contact ?: null;
                $userQuote = isset($quote->users[0]) ? $quote->users[0]->user : null;
            @endphp

            <div><span class="label">DOC.:</span><span class="texto">{{ $customer->RUC ?? '—' }}</span></div>
            <div><span class="label">RAZÓN SOCIAL:</span><span class="texto">{{ $customer->business_name ?? 'Consumidor Final' }}</span></div>
            <div><span class="label">CONTACTO:</span><span class="texto">{{ $contact->name ?? '—' }}</span></div>
            <div><span class="label">DIRECCIÓN:</span><span class="texto">{{ $customer->address ?? '—' }}</span></div>
        </td>

        {{-- COTIZACIÓN --}}
        <td style="width:50%; vertical-align:top; padding:6px;">
            <div class="box-title">DATOS DE LA COTIZACIÓN</div>

            <div><span class="label">FECHA EMISIÓN:</span><span class="texto">{{ \Carbon\Carbon::parse($quote->date_quote)->format('d/m/Y') }}</span></div>
            <div><span class="label">VÁLIDO HASTA:</span><span class="texto">{{ \Carbon\Carbon::parse($quote->date_validate)->format('d/m/Y') }}</span></div>
            <div><span class="label">MONEDA:</span><span class="texto">{{ $quote->currency_invoice === 'USD' ? 'DÓLARES' : 'SOLES' }}</span></div>
            <div><span class="label">COTIZADO POR:</span><span class="texto">{{ strtoupper($userQuote->name) ?? '—' }}</span></div>
        </td>
    </tr>
</table>

<br>

{{-- ============================= --}}
{{-- TABLA DE ITEMS                --}}
{{-- ============================= --}}

<table>
    <thead>
    <tr>
        <th style="width:6%;">CANT.</th>
        <th style="width:7%;">UM</th>
        <th style="width:14%;">CÓD.</th>
        <th style="width:43%; text-align:left;">DESCRIPCIÓN</th>
        <th style="width:10%;">V/U</th>
        <th style="width:10%;">P/U</th>
        <th style="width:10%;">IMPORTE</th>
    </tr>
    </thead>

    <tbody>
    @foreach($quote->equipments as $equipment)
        @foreach($equipment->consumables as $consumable)
            @php
                $cantidad  = ($consumable->material_presentation_id == null) ? (float)$consumable->quantity: (float)$consumable->packs;
                $vunit     = round(($consumable->total/($consumable->packs ?? $consumable->quantity))/(1+($igv/100)), 2);
                $punit     = round($consumable->total/($consumable->packs ?? $consumable->quantity), 2);
                $importe   = $consumable->total;

                $present = (string) ( ($consumable->material_presentation_id == null) ? round($consumable->quantity,2): $consumable->units_per_pack.'UND');

            @endphp

            <tr>
                <td class="text-center">{{ number_format($cantidad, 0) }}</td>
                <td class="text-center">{{ $consumable->material->unitMeasure->name ?? '' }}</td>
                <td class="text-center">{{ $consumable->material->code ?? '' }}</td>
                <td style="text-align:left;">{{ "(".$present.") ".$consumable->material->full_name }}</td>

                @if($quote->state_decimals)
                    <td class="text-right">{{ number_format($vunit, 0) }}</td>
                    <td class="text-right">{{ number_format($punit, 0) }}</td>
                    <td class="text-right">{{ number_format($importe, 0) }}.00</td>
                @else
                    <td class="text-right">{{ number_format($vunit, 2) }}</td>
                    <td class="text-right">{{ number_format($punit, 2) }}</td>
                    <td class="text-right">{{ number_format($importe, 2) }}</td>
                @endif
            </tr>
        @endforeach
        @php
            $total_workforce  = 0;
        @endphp
        @foreach($equipment->workforces as $workforce)
            @php
                if ( $workforce->billable == false )
                {
                    $total_workforce = $total_workforce + $workforce->total;
                }

                $cantidad  = $equipment->quantity;
                $vunit     = $workforce->price/(1+($igv/100));
                $punit     = $workforce->price;
                $importe   = $workforce->total;
            @endphp

            <tr>
                <td class="text-center">{{ number_format($cantidad, 0) }}</td>
                <td class="text-center">{{ $workforce->unit ?? '' }}</td>
                <td class="text-center">{{ '' }}</td>
                <td style="text-align:left;">{{ $workforce->description }}</td>

                @if($quote->state_decimals)
                    <td class="text-right">{{ number_format($vunit, 0) }}</td>
                    <td class="text-right">{{ number_format($punit, 0) }}</td>
                    <td class="text-right">{{ number_format($importe, 0) }}.00</td>
                @else
                    <td class="text-right">{{ number_format($vunit, 2) }}</td>
                    <td class="text-right">{{ number_format($punit, 2) }}</td>
                    <td class="text-right">{{ number_format($importe, 2) }}</td>
                @endif
            </tr>
        @endforeach
    @endforeach
    </tbody>

</table>

{{-- ============================= --}}
{{-- TOTALES                      --}}
{{-- ============================= --}}

<table class="totales-table">
    {{--<tr>
        <td class="text-right">DESCUENTO (-)</td>
        <td class="text-right">
            {{ $quote->currency_invoice }}
            {{ number_format($quote->descuento, $quote->state_decimals ? 0 : 2) }}
        </td>
    </tr>

    <tr>
        <td class="text-right">GRAVADA</td>
        <td class="text-right">
            {{ $quote->currency_invoice }}
            {{ number_format($quote->gravada, $quote->state_decimals ? 0 : 2) }}
        </td>
    </tr>

    <tr>
        <td class="text-right">IGV {{ $igv }}%</td>
        <td class="text-right">
            {{ $quote->currency_invoice }}
            {{ number_format($quote->igv_total, $quote->state_decimals ? 0 : 2) }}
        </td>
    </tr>--}}

    <tr>
        <td class="text-right"><strong>TOTAL</strong></td>
        <td style="text-align: right">
            <strong style="text-align: right">
                {{ $quote->currency_invoice }}
                {{ number_format($quote->total_importe + $total_workforce, $quote->state_decimals ? 0 : 2) }}
            </strong>
        </td>
    </tr>
</table>

<br><br>

{{-- IMPORTE EN LETRAS --}}
<div>
    <strong>IMPORTE EN LETRAS:</strong>
    {{ strtoupper($montoEnLetras ?? '') }}
</div>

{{-- CONDICIONES --}}
<div style="margin-top:12px;">
    <strong>TÉRMINOS Y CONDICIONES:</strong><br>
    FORMA DE PAGO: {{ $quote->deadline->description ?? '—' }}<br>
    TIEMPO DE ENTREGA: {{ $quote->time_delivery ? $quote->time_delivery.' DÍAS' : '—' }}
</div>

{{-- OBSERVACIONES --}}
<div style="margin-top:8px;">
    <strong>OBSERVACIONES:</strong>
    <div style="margin-top:4px; font-size:10px; line-height:1.3;">
        {!! strip_tags($quote->observations, '<p><br>') !!}
    </div>
</div>

@if ( $tieneCuentas )
<div style="margin-top:8px;">
    <strong >CUENTAS BANCARIAS:</strong>
    <table class="bank-wrap" style="margin-top:8px;">
        <colgroup>
            <col style="width:30%;">
            <col style="width:70%;">
        </colgroup>

        <tr>
            <!-- COLUMNA 30% -->
            <td>
                <table class="bank-card">
                    <colgroup>
                        <col style="width:52px;">
                        <col>
                    </colgroup>
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/logo/'.$imgCuenta1Empresa) }}">
                        </td>
                        <td class="info-cell">
                            <p class="bank-title">{{ $titleCuenta1Empresa }}</p>
                            <p class="bank-line"><strong>Nro.:</strong> {{ $nroCuenta1Empresa }}</p>

                            @if(!empty($cciCuenta1Empresa))
                                <p class="bank-line"><strong>CCI:</strong> {{ $cciCuenta1Empresa }}</p>
                            @endif

                            <p class="bank-owner">{{ $ownerCuenta1Empresa }}</p>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- COLUMNA 70% -->
            <td>
                <table class="bank-card">
                    <colgroup>
                        <col style="width:52px;">
                        <col>
                    </colgroup>
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/logo/'.$imgCuenta2Empresa) }}">
                        </td>
                        <td class="info-cell">
                            <p class="bank-title">{{ $titleCuenta2Empresa }}</p>
                            <p class="bank-line"><strong>Nro.:</strong> {{ $nroCuenta2Empresa }}</p>

                            @if(!empty($cciCuenta2Empresa))
                                <p class="bank-line"><strong>CCI:</strong> {{ $cciCuenta2Empresa }}</p>
                            @endif

                            <p class="bank-owner">{{ $ownerCuenta2Empresa }}</p>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="1"></td>
            <td class="verse-cell">
                {{ $versiculoEmpresa }}<br>
                <strong>{{ $citaBiblicaEmpresa }}</strong>
            </td>
        </tr>
    </table>
</div>
@endif

{{--<div style="margin-top:8px;">
    <strong>DETALLES:</strong>
    <div style="font-size:10px;">
        @foreach($quote->equipments as $eq)
            @if($eq->detail)
                <p>{!! nl2br($eq->detail) !!}</p>
            @endif
        @endforeach
    </div>
</div>--}}

{{-- FOOTER --}}
<div class="footer">
    Representación impresa de la COTIZACIÓN – {{ $direccionEmpresa }}
</div>

{{-- ============================= --}}
{{-- SEGUNDA PÁGINA (si aplica)   --}}
{{-- ============================= --}}

@if($quote->have_images)
    <div class="page-break"></div>

    {{-- Encabezado repetido --}}
    <table class="no-border">
        <tr class="no-border">
            <td class="no-border" style="width:60%;">
                <img src="{{ asset('/landing/img/logo_pdf.png') }}" style="max-height:60px;">
            </td>
            <td class="no-border" style="width:40%;">
                <div class="doc-box">
                    <div class="doc-ruc">RUC {{ $rucEmpresa }}</div>
                    <div class="doc-tipo">COTIZACIÓN</div>
                    <div class="doc-serie-num">{{ $quote->code }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Planos --}}
    @if(count($images) > 0)
        <div style="font-size:10px;">
            <strong>PLANOS DE LA COTIZACIÓN</strong><br><br>

            @foreach($images as $image)
                <p><em><u>{{ $image->description }}</u></em></p>

                <img src="{{ asset('/images/planos/'.$image->image) }}"
                     style="max-width:100%; max-height:450px; margin-bottom:15px;">
            @endforeach
        </div>
    @endif

@endif

</body>
</html>
