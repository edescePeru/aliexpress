<?php

namespace App\Exports;

use App\DataGeneral;
use App\IdentityDocumentType;
use App\ShippingGuide;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShippingGuidesExport implements FromCollection, WithHeadings, WithMapping
{
    private $from;
    private $to;

    /** @var array code => name */
    private $reasonMap = [];

    /** @var array code => name */
    private $docTypeMap = [];

    // Emisor (empresa)
    private $emisorDocEntidad = '6'; // RUC
    private $emisorRuc = '';
    private $emisorDenominacion = '';

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to   = $to;

        // Emisor desde DataGeneral
        $this->emisorDenominacion = (string) DataGeneral::where('name', 'empresa')->value('valueText');
        $this->emisorRuc          = (string) DataGeneral::where('name', 'ruc')->value('valueText');

        // Motivos (code => name)
        $this->reasonMap = DB::table('transfer_reasons')
            ->pluck('name', 'code')
            ->toArray();

        // Tipos de doc (code => name)
        $this->docTypeMap = IdentityDocumentType::where('is_active', 1)
            ->pluck('name', 'code')
            ->toArray();
    }

    private function docTypeName($code)
    {
        $code = (string) $code;
        if ($code === '') {
            return '';
        }
        return isset($this->docTypeMap[$code]) ? $this->docTypeMap[$code] : $code;
    }

    public function collection(): Collection
    {
        return ShippingGuide::query()
            ->where('tipo_de_comprobante', 7) // Remitente
            ->whereDate('fecha_emision', '>=', $this->from)
            ->whereDate('fecha_emision', '<=', $this->to)
            ->with(['items', 'vehicles', 'drivers'])
            ->orderBy('fecha_emision')
            ->orderBy('serie')
            ->orderBy('numero')
            ->get();
    }

    public function headings(): array
    {
        return [
            'FECHA EMISIÓN',
            'TIPO',
            'SERIE',
            'NÚMERO',
            'DOC ENTIDAD',
            'RUC',
            'DENOMINACIÓN',
            'DETALLE (LINEAS)',
            'PESO BRUTO',
            'PESO UNIDAD DE MEDIDA',
            'FECHA DE TRASLADO',
            'TRANSPORTISTA DOCUMENTO TIPO',
            'TRANSPORTISTA DOCUMENTO NUMERO',
            'TRANSPORTISTA DENOMINACION',
            'PLACA',
            'CONDUCTOR DOCUMENTO TIPO',
            'CONDUCTOR DOCUMENTO NUMERO',
            'CONDUCTOR NOMBRE',
            'CONDUCTOR APELLIDOS',
            'CONDUCTOR LICENCIA',
            'DESTINATARIO DOCUMENTO TIPO',
            'DESTINATARIO DOCUMENTO NUMERO',
            'DESTINATARIO DOCUMENTO DENOMINACION',
            'PARTIDA UBIGEO',
            'PARTIDA DIRECCIÓN',
            'LLEGADA UBIGEO',
            'LLEGADA DIRECCIÓN',
            'OBSERVACIONES',
            'ACEPTADO POR LA SUNAT',
            'ESTADO SUNAT - DESCRIPCIÓN',
            'INUTILIZADO',
            'DOCUMENTOS RELACIONADOS',

            // Nuevo
            'PDF_LINK',
        ];
    }

    public function map($g): array
    {
        // dd/mm/YYYY
        $fechaEmision  = $g->fecha_emision ? date('d/m/Y', strtotime($g->fecha_emision)) : '';
        $fechaTraslado = $g->fecha_inicio_traslado ? date('d/m/Y', strtotime($g->fecha_inicio_traslado)) : '';

        // En excel Nubefact: TIPO = 09 (Guía)
        $tipo = '09';

        // Items en líneas: "1) DESC - CANT UM"
        $detalleLineas = '';
        if ($g->items && count($g->items)) {
            $lines = [];
            foreach ($g->items as $it) {
                $lines[] = ($it->line ?: '') . ') ' . trim(
                        ($it->descripcion ?? '') . ' - ' . ($it->cantidad ?? 0) . ' ' . ($it->unidad_medida ?? 'NIU')
                    );
            }
            $detalleLineas = implode("\n", $lines);
        }

        // Vehículo principal
        $plate = '';
        if ($g->vehicles && count($g->vehicles)) {
            $primary = $g->vehicles->firstWhere('is_primary', true) ?: $g->vehicles->first();
            $plate = $primary ? ($primary->plate_number ?? '') : '';
        }

        // Conductor principal
        $driverDocType = $driverDocNum = $driverFirst = $driverLast = $driverLic = '';
        if ($g->drivers && count($g->drivers)) {
            $dp = $g->drivers->firstWhere('is_primary', true) ?: $g->drivers->first();
            if ($dp) {
                $driverDocType = $this->docTypeName($dp->document_type_code ?? '');
                $driverDocNum  = $dp->document_number ?? '';
                $driverFirst   = $dp->first_name ?? '';
                $driverLast    = $dp->last_name ?? '';
                $driverLic     = $dp->license_number ?? '';
            }
        }

        // Transportista (solo público)
        $transDocType = $this->docTypeName($g->transportista_doc_type ?? '');
        $transDocNum  = $g->transportista_doc_number ?? '';
        $transName    = $g->transportista_name ?? '';

        // Destinatario
        $destDocType = $this->docTypeName($g->customer_doc_type ?? '');
        $destDocNum  = $g->customer_doc_number ?? '';
        $destName    = $g->customer_name ?? '';

        // Estado SUNAT
        $accepted = (bool)($g->nubefact_accepted ?? false);
        $acceptedText = $accepted ? 'SI' : 'NO';

        $sunatDesc = $g->sunat_description ?? $g->sunat_soap_error ?? '';

        // Motivo traslado como nombre
        $motivoNombre = isset($this->reasonMap[$g->motivo_traslado_code])
            ? $this->reasonMap[$g->motivo_traslado_code]
            : ($g->motivo_traslado_code ?? '');

        // PDF link solo si aceptada + existe link
        $pdfLink = '';
        if ($accepted && !empty($g->pdf_link)) {
            $pdfLink = $g->pdf_link;
        }

        return [
            $fechaEmision,
            $tipo,
            $g->serie ?? '',
            $g->numero ?? '',

            // Emisor
            $this->docTypeName($this->emisorDocEntidad), // mostrará "RUC" si existe en tu tabla
            $this->emisorRuc,
            $this->emisorDenominacion,

            $detalleLineas,

            $g->peso_bruto_total ?? '',
            $g->peso_bruto_um_code ?? '',
            $fechaTraslado,

            // Transportista
            $transDocType,
            $transDocNum,
            $transName,

            // Vehículo
            $plate,

            // Conductor
            $driverDocType,
            $driverDocNum,
            $driverFirst,
            $driverLast,
            $driverLic,

            // Destinatario
            $destDocType,
            $destDocNum,
            $destName,

            // Ubigeos + direcciones
            $g->partida_ubigeo ?? '',
            $g->partida_direccion ?? '',
            $g->llegada_ubigeo ?? '',
            $g->llegada_direccion ?? '',

            $g->observaciones ?? '',

            $acceptedText,
            $sunatDesc,

            'NO',           // INUTILIZADO (MVP)
            $motivoNombre,  // DOCUMENTOS RELACIONADOS (MVP: motivo)

            $pdfLink,
        ];
    }
}
