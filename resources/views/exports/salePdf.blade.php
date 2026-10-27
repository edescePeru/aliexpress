<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8pt; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            /*width: 100%;*/
            text-align: center;
            box-sizing: border-box;
        }

        .ticket {
            width: 240px;
            max-width: 240px;
            margin: 0 auto;
        }

        .centered {
            text-align: center;
            align-content: center;
        }

        .ticket td,
        .ticket th {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .ticket .total {
            border-top: 1px dashed black;
            font-weight: bold;
            text-align: right;
        }

        .ticket .header {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .ticket .item {
            margin-bottom: 5px;
        }

        .ticket .right {
            text-align: right;
        }

        .ticket .separator {
            border-top: 1px dashed black;
            margin: 10px 0;
        }

        .full-width {
            width: 100%;
        }

        .table-operations {
            width: 100%;
            border-collapse: collapse;
        }

        .table-operations td {
            border: none;
            padding: 2px 0;
        }

        /* Nueva clase para asegurar que la tabla de detalles esté centrada y ocupe todo el ancho */
        .details-table {
            width: 100%;
           /* margin: 0 auto;*/
            border-collapse: collapse; /* Elimina los espacios entre celdas */
        }

        img {
            max-width: 100px; /* Ajusta el tamaño del logo según sea necesario */
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .address {
            margin: 0;
            font-weight: bold; /* Negrita */
            font-size: 10px;   /* Tamaño de fuente 11px */
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
            width: 100%;
            display: block;
        }

        .ticket p {
            text-align: left;
            margin: 0;
        }

        .line2 {
            display: block;
            text-align: center;
            width: 100%;
            margin: 5px 0;
        }

        .line2::after {
            content: "***********************";
            letter-spacing: 2px;
            font-weight: bold;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="ticket">
    {{--<img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo/logoPequeno.png'))) }}" alt="Logo de Tienda">--}}

    {{--<p class="centered header" style="text-align: center">{{ $nameEmpresa }}<br>
        R.U.C.: {{ $ruc }}<br>
        BOLETA DE VENTA ELECTRÓNICA</p>
    <p class="centered address bold text-sm" style="text-align: center">{{ $address }}</p>
    <div class="line"></div>--}}
    <p><b>Fecha: </b>{{ \Carbon\Carbon::parse($sale->date_sale)->format('d/m/Y') }} {{ \Carbon\Carbon::parse($sale->date_sale)->format('H:i') }}</p>
    <div class="line"></div>
    {{--<p style="font-size: 10px; margin: 0; text-align: center">
        {{ $sale->serie }} - {{ $sale->attended_by_name }} - 4 - 1 - transacción-{{ $sale->serie }}
    </p>--}}
    {{--<div class="line"></div>--}}
    <table class="details-table">
        <thead>
        <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th class="right">Valor</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->details as $detail)
            <tr class="item">
                <td>{{ $detail->material->code }}</td>
                <td>{{ $detail->material->full_name }}</td>
                <td class="right">S/. {{ number_format($detail->total, 2) }}</td>
            </tr>
            <tr>
                <td>{{ $detail->quantity }} x {{ $detail->material->unitMeasure->description }}</td>
                <td></td>
                <td class="right">c/u</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{--<p class="right">SON: {{ strtoupper(NumeroALetras::convertir($sale->total)) }} SOLES</p>--}}

    <div class="separator"></div>

    <table class="table-operations full-width">
        <tr>
            <td><b>OP. EXONERADA</b></td>
            <td class="right">S/. {{ number_format($sale->op_exonerada, 2) }}</td>
        </tr>
        <tr>
            <td><b>OP. INAFECTA</b></td>
            <td class="right">S/. {{ number_format($sale->op_inafecta, 2) }}</td>
        </tr>
        <tr>
            <td><b>OP. GRAVADA</b></td>
            <td class="right">S/. {{ number_format($sale->op_gravada, 2) }}</td>
        </tr>
        <tr>
            <td><b>I.G.V.</b></td>
            <td class="right">S/. {{ number_format($sale->igv, 2) }}</td>
        </tr>
        <tr>
            <td><b>TOTAL DESCUENTOS</b></td>
            <td class="right">S/. {{ number_format($sale->total_descuentos, 2) }}</td>
        </tr>
        <tr>
            <td class="total"><b>TOTAL A PAGAR</b></td>
            <td class="total right">S/. {{ number_format($sale->importe_total, 2) }}</td>
        </tr>
    </table>

    <div class="separator"></div>

    <p class="text-center" style="font-size: 18px; text-align: center"><b>{{ strtoupper($sale->tipoPago->description) }} </b></p>

    <div class="separator"></div>

    <p class="bold right">Pago con: S/. {{ number_format($sale->importe_total+$sale->vuelto, 2) }}</p>
    <p class="bold right">Vuelto: S/. {{ number_format($sale->vuelto, 2) }}</p>

    {{--<div class="separator"></div>

    <p style="text-align: center">Atendido por: {{ $sale->attended_by_name }}</p>--}}
    <div class="line"></div>
    <div class="text-center" >
        <p style="text-align: center">¡Gracias por su compra!</p>
        <p style="text-align: center">www.edesce.com</p>
    </div>
   {{-- <div class="line2"></div>
    <div class="text-center" >
        <p style="text-align: center">¿Ya conoces nuestra web?</p>
        <p style="text-align: center">En <strong>www.edesce.com</strong> puedes explorar nuestro menú, descubrir <strong>promociones especiales</strong> y hacer tu pedido de manera fácil y rápida.</p>
    </div>
    <div class="line2"></div>--}}
</div>
</body>
</html>
