<?php
namespace App\Http\Controllers;

use App\Services\Sales\SaleListAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleListDocumentController extends Controller
{
    public function show(Request $request, $id, $kind, SaleListAccess $access)
    {
        $sale = $access->sale($id); $access->permission('print_puntoVenta');
        $definitions = [
            'invoice_pdf' => ['pdf_path', 'comprobantes/pdfs'], 'invoice_xml' => ['xml_path', 'comprobantes/xmls'], 'invoice_cdr' => ['cdr_path', 'comprobantes/cdrs'],
            'annul_pdf' => ['annulment_pdf_path', 'comprobantes/anulaciones/pdfs'], 'annul_xml' => ['annulment_xml_path', 'comprobantes/anulaciones/xmls'], 'annul_cdr' => ['annulment_cdr_path', 'comprobantes/anulaciones/cdrs'],
            'credit_pdf' => ['pdf_path', 'comprobantes/notas_credito/pdfs'], 'credit_xml' => ['xml_path', 'comprobantes/notas_credito/xmls'], 'credit_cdr' => ['cdr_path', 'comprobantes/notas_credito/cdrs'],
        ];
        abort_unless(isset($definitions[$kind]), 404);
        $record = $sale;
        if (strpos($kind, 'credit_') === 0) {
            $request->validate(['note_id' => 'required|integer|min:1']);
            $record = DB::table('credit_notes')->where('id', $request->query('note_id'))->where('sale_id', $sale->id)->first();
            abort_unless($record, 404);
        }
        list($field, $folder) = $definitions[$kind];
        $filename = $record->$field;
        $file = app(\App\Services\Sales\SaleDocumentFile::class)->resolve($folder, $filename);
        $response = response()->file($file['path'], [
            'Content-Type' => $file['mime'],
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        $response->setContentDisposition($file['extension'] === 'pdf' ? 'inline' : 'attachment', $safeName);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
