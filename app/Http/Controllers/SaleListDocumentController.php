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
        abort_unless(is_string($filename) && $filename !== '' && strpos($filename, '/') === false && strpos($filename, '\\') === false && $filename !== '.' && $filename !== '..', 404);
        $root = realpath(public_path($folder)); $path = $root ? realpath($root.DIRECTORY_SEPARATOR.$filename) : false;
        abort_unless($root && $path && is_file($path) && strpos($path, $root.DIRECTORY_SEPARATOR) === 0, 404);
        return response()->file($path);
    }
}
