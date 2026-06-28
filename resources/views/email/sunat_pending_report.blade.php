<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:#f4f6f9; font-family:Arial, sans-serif; color:#333;">

<div style="max-width:900px; margin:0 auto; background:#ffffff; border:1px solid #e5e5e5;">

    <div style="background:#102A56; color:#ffffff; padding:18px 24px;">
        <h2 style="margin:0; font-size:22px;">Venti360</h2>
        <p style="margin:4px 0 0 0; font-size:14px;">Reporte automático SUNAT / Nubefact</p>
        <p style="margin:4px 0 0 0; font-size:13px;">Fecha: {{ $date }}</p>
    </div>

    <div style="padding:20px 24px;">

        <h3 style="margin-top:0;">Resumen</h3>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr>
                <td style="padding:8px;">
                    <div style="background:#e8f5e9; border-left:5px solid #28a745; padding:12px;">
                        <strong>Aceptadas</strong><br>
                        <span style="font-size:24px;">{{ $summary['accepted'] }}</span>
                    </div>
                </td>
                <td style="padding:8px;">
                    <div style="background:#fff8e1; border-left:5px solid #ffc107; padding:12px;">
                        <strong>Pendientes</strong><br>
                        <span style="font-size:24px;">{{ $summary['pending'] }}</span>
                    </div>
                </td>
                <td style="padding:8px;">
                    <div style="background:#ffebee; border-left:5px solid #dc3545; padding:12px;">
                        <strong>Rechazadas</strong><br>
                        <span style="font-size:24px;">{{ $summary['rejected'] }}</span>
                    </div>
                </td>
                <td style="padding:8px;">
                    <div style="background:#eceff1; border-left:5px solid #6c757d; padding:12px;">
                        <strong>Errores</strong><br>
                        <span style="font-size:24px;">{{ $summary['error'] }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-bottom:25px;">
            <tr>
                <td style="border:1px solid #ddd;">Anulaciones consultadas</td>
                <td style="border:1px solid #ddd; text-align:center;"><strong>{{ $summary['annulments'] }}</strong></td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd;">Notas de crédito consultadas</td>
                <td style="border:1px solid #ddd; text-align:center;"><strong>{{ $summary['credit_notes'] }}</strong></td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd;">Esperando proceso</td>
                <td style="border:1px solid #ddd; text-align:center;"><strong>{{ $summary['waiting'] ?? 0 }}</strong></td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd;">Requieren Nota de Crédito</td>
                <td style="border:1px solid #ddd; text-align:center;"><strong>{{ $summary['requires_credit_note'] ?? 0 }}</strong></td>
            </tr>
        </table>

        <h3>Detalle de anulaciones</h3>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-bottom:25px;">
            <thead>
            <tr style="background:#102A56; color:#ffffff;">
                <th align="left">Venta</th>
                <th align="left">Estado</th>
                <th align="left">Mensaje</th>
            </tr>
            </thead>
            <tbody>
            @forelse($annulments as $row)
                @php
                    $status = strtolower($row['status']);
                    $color = '#6c757d';
                    $bg = '#eceff1';

                    if ($status === 'accepted') {
                        $color = '#28a745';
                        $bg = '#e8f5e9';
                    } elseif ($status === 'pending' || $status === 'waiting') {
                        $color = '#ffc107';
                        $bg = '#fff8e1';
                    } elseif ($status === 'rejected' || $status === 'error') {
                        $color = '#dc3545';
                        $bg = '#ffebee';
                    } elseif ($status === 'requires_credit_note') {
                        $color = '#fd7e14';
                        $bg = '#fff3e0';
                    }
                @endphp
                <tr>
                    <td style="border:1px solid #ddd;">#{{ $row['sale_id'] }}</td>
                    <td style="border:1px solid #ddd;">
                        <span style="background:{{ $bg }}; color:{{ $color }}; padding:4px 8px; border-radius:4px; font-weight:bold;">
                            {{ strtoupper($row['status']) }}
                        </span>
                    </td>
                    <td style="border:1px solid #ddd;">{{ $row['message'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="border:1px solid #ddd; text-align:center; color:#777;">
                        No hubo anulaciones pendientes.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <h3>Detalle de notas de crédito</h3>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
            <thead>
            <tr style="background:#102A56; color:#ffffff;">
                <th align="left">NC</th>
                <th align="left">Venta</th>
                <th align="left">Estado</th>
                <th align="left">Mensaje</th>
            </tr>
            </thead>
            <tbody>
            @forelse($creditNotes as $row)
                @php
                    $status = strtolower($row['status']);
                    $color = '#6c757d';
                    $bg = '#eceff1';

                    if ($status === 'accepted') {
                        $color = '#28a745';
                        $bg = '#e8f5e9';
                    } elseif ($status === 'pending' || $status === 'waiting') {
                        $color = '#ffc107';
                        $bg = '#fff8e1';
                    } elseif ($status === 'rejected' || $status === 'error') {
                        $color = '#dc3545';
                        $bg = '#ffebee';
                    }
                @endphp
                <tr>
                    <td style="border:1px solid #ddd;">#{{ $row['credit_note_id'] }}</td>
                    <td style="border:1px solid #ddd;">#{{ $row['sale_id'] }}</td>
                    <td style="border:1px solid #ddd;">
                        <span style="background:{{ $bg }}; color:{{ $color }}; padding:4px 8px; border-radius:4px; font-weight:bold;">
                            {{ strtoupper($row['status']) }}
                        </span>
                    </td>
                    <td style="border:1px solid #ddd;">{{ $row['message'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="border:1px solid #ddd; text-align:center; color:#777;">
                        No hubo notas de crédito pendientes.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <p style="margin-top:25px; font-size:12px; color:#777;">
            Este correo fue generado automáticamente por Venti360.
        </p>

    </div>
</div>

</body>
</html>