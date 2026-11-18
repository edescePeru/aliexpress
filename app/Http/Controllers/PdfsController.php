<?php

namespace App\Http\Controllers;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PdfsController extends Controller
{
    public function mergePdfs()
    {
        $oMerger = PDFMerger::init();

        $pdf1 = public_path().'/pdfs/PDF de prueba.pdf';
        $pdf2 = public_path().'/pdfs/PDF de prueba2.pdf';

        $oMerger->addPDF($pdf1, 'all');
        $oMerger->addPDF($pdf2, 'all');

        $oMerger->merge();
        $oMerger->stream();
    }
}
