<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Compra {{ $purchase_order->code }}</title>

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

        .no-border td, .no-border th { border: none !important; }

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
        .label { font-weight:bold; display:inline-block; min-width:115px; }
        .texto { display:inline-flex; margin-left:3px;  font-size:9px}
        .mt-3 {margin-top: 3px}

        .footer {
            margin-top: 20px;
            font-size: 8px;
            border-top: 0.5px solid #999;
            padding-top: 4px;
            text-align: center;
        }

    </style>
</head>

<body>

{{-- ============================= --}}
{{-- ENCABEZADO GENERAL            --}}
{{-- ============================= --}}

<table class="no-border">
    <tr class="no-border">

        {{-- Datos empresa --}}
        <td class="no-border" style="width:60%; padding-right:10px; vertical-align:top;">
            <img src="{{ asset('/images/logo/'.$logotipoEmpresa) }}" style="max-height:60px; margin-bottom:5px;">

            <div class="title-empresa">{{ $nombreEmpresa }}</div>
            <div class="empresa-linea">{{ $direccionEmpresa }}</div>
            <div class="empresa-linea">Teléfono: {{ $telefonoEmpresa }}</div>
            <div class="empresa-linea">Email: {{ $emailEmpresa }}</div>
            <div class="empresa-linea">Web: {{ $webEmpresa }}</div>
        </td>

        {{-- Caja del documento --}}
        <td class="no-border" style="width:40%; vertical-align:top;">
            <div class="doc-box">
                <div class="doc-ruc">RUC {{ $rucEmpresa }}</div>
                <div class="doc-tipo">ORDEN DE COMPRA</div>
                <div class="doc-serie-num">{{ $purchase_order->code }}</div>
            </div>
        </td>

    </tr>
</table>

<br>

{{-- ============================= --}}
{{-- DATOS DE ORDEN Y EMISOR       --}}
{{-- ============================= --}}

<table>
    <tr>

        {{-- Datos de orden --}}
        <td style="width:50%; padding:6px; vertical-align:top;">
            <div class="box-title">DATOS DE LA ORDEN</div>

            <div  class="mt-3"><span class="label">CÓDIGO:</span>
                <span class="texto">{{ $purchase_order->code }}</span>
            </div>
            <div  class="mt-3"><span class="label">FECHA:</span>
                <span class="texto">{{ date('d/m/Y', strtotime($purchase_order->date_order)) }}</span>
            </div>
            <div  class="mt-3"><span class="label">APROBADO POR:</span>
                <span class="texto">
                    {{ $purchase_order->approved_user->name ?? 'No tiene aprobador' }}
                </span>
            </div>
            <div  class="mt-3"><span class="label">CONDICIÓN DE PAGO:</span>
                <span class="texto">{{ $purchase_order->deadline->description ?? '—' }}</span>
            </div>
            <div  class="mt-3"><span class="label">MONEDA:</span>
                <span class="texto">{{ $purchase_order->currency_order == 'USD' ? 'DÓLARES' : 'SOLES' }}</span>
            </div>
        </td>

        {{-- Datos emisora --}}
        <td style="width:50%; padding:6px; vertical-align:top;">
            <div class="box-title">DATOS DEL EMISOR</div>

            <div  class="mt-3"><span class="label">RAZÓN SOCIAL:</span>
                <span class="texto">{{ $nombreEmpresa }}</span>
            </div>
            <div  class="mt-3"><span class="label">RUC:</span>
                <span class="texto">{{ $rucEmpresa }}</span>
            </div>
            <div  class="mt-3"><span class="label">DIRECCIÓN:</span>
                <span class="texto">{{ $direccionEmpresa }}</span>
            </div>
            <div  class="mt-3"><span class="label">TELÉFONO:</span>
                <span class="texto">{{ $telefonoEmpresa }}</span>
            </div>
            <div  class="mt-3"><span class="label">CORREO:</span>
                <span class="texto">{{ $emailEmpresa }}</span>
            </div>
        </td>

    </tr>
</table>

<br>

{{-- ============================= --}}
{{-- PROVEEDOR                     --}}
{{-- ============================= --}}

<table>
    <tr>
        <td style="padding:6px;">
            <div class="box-title">DATOS DEL PROVEEDOR</div>

            @php
                $supplier = $purchase_order->supplier;
            @endphp

            <div><span class="label">RAZÓN SOCIAL:</span>
                <span class="texto">{{ $supplier->business_name ?? '—' }}</span>
            </div>
            <div><span class="label">RUC:</span>
                <span class="texto">{{ $supplier->RUC ?? '—' }}</span>
            </div>

            <div><span class="label">DIRECCIÓN:</span>
                <span class="texto">{{ $supplier->address ?? '—' }}</span>
            </div>

            <div><span class="label">CUENTAS BANCARIAS:</span>
                <span class="texto">
                @if(count($accounts))
                        @foreach($accounts as $index => $acc)
                            {{ $acc->bank->short_name }} -
                            {{ $acc->currency=='PEN'?'Soles':'Dólares' }} -
                            {{ $acc->number_account }}
                            @if($index < count($accounts)-1) <br> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; @endif
                        @endforeach
                    @else
                        —
                    @endif
                </span>
            </div>

            <div><span class="label">TELÉFONO:</span>
                <span class="texto">{{ $supplier->phone ?? '—' }}</span>
            </div>

            <div><span class="label">CORREO:</span>
                <span class="texto">{{ $supplier->email ?? '—' }}</span>
            </div>

            <div><span class="label">COTIZACIÓN:</span>
                <span class="texto">{{ $purchase_order->quote_supplier ?? '—' }}</span>
            </div>

            <div><span class="label">OBSERVACIÓN:</span>
                <span class="texto">{{ $purchase_order->observation ?? '—' }}</span>
            </div>
        </td>
    </tr>
</table>

<br>

{{-- ============================= --}}
{{-- DETALLE DE COMPRA             --}}
{{-- ============================= --}}

<table>
    <thead>
    <tr>
        <th style="width:40%; text-align:left;">DESCRIPCIÓN</th>
        <th style="width:10%;">UND</th>
        <th style="width:10%;">CANT.</th>
        <th style="width:13%;">PRECIO S/IGV</th>
        <th style="width:13%;">SUBTOTAL S/IGV</th>
        <th style="width:7%;">IGV</th>
        <th style="width:13%;">TOTAL</th>
    </tr>
    </thead>

    <tbody>
    @foreach($purchase_order->details as $detail)
        <tr>
            <td style="text-align:left;">{{ optional($detail->stockItem)->display_name ?? $detail->material->full_description }}</td>
            <td style="text-align:center;">{{ $detail->material->unitMeasure->name }}</td>
            <td style="text-align:center;">{{ $detail->quantity }}</td>

            <td style="text-align:right;">{{ number_format($detail->price/1.18, 2) }}</td>
            <td style="text-align:right;">{{ number_format($detail->total_detail - $detail->igv, 2) }}</td>

            <td style="text-align:right;">{{ number_format($detail->igv, 2) }}</td>
            <td style="text-align:right;">{{ number_format($detail->total_detail, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

{{-- ============================= --}}
{{-- TOTALES                       --}}
{{-- ============================= --}}

<table style="width:40%; float:right;">
    <tr>
        <td class="label" style="text-align:right;">SUBTOTAL S/IGV</td>
        <td style="text-align:right;">{{ $purchase_order->currency_order }} {{ number_format($purchase_order->total - $purchase_order->igv, 2) }}</td>
    </tr>

    <tr>
        <td class="label" style="text-align:right;">IGV</td>
        <td style="text-align:right;">{{ $purchase_order->currency_order }} {{ number_format($purchase_order->igv, 2) }}</td>
    </tr>

    <tr>
        <td class="label" style="text-align:right;"><strong>TOTAL</strong></td>
        <td style="text-align:right;"><strong>{{ $purchase_order->currency_order }} {{ number_format($purchase_order->total, 2) }}</strong></td>
    </tr>
</table>

<br><br><br><br><br>

{{-- ============================= --}}
{{-- FOOTER                        --}}
{{-- ============================= --}}

<div class="footer">
    {{ $direccionEmpresa }} | {{ $telefonoEmpresa }}
</div>

</body>
</html>