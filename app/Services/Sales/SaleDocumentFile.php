<?php
namespace App\Services\Sales;

class SaleDocumentFile
{
    private $publicRoot;
    private const FOLDERS = [
        'comprobantes/pdfs' => ['pdf'], 'comprobantes/xmls' => ['xml'], 'comprobantes/cdrs' => ['zip', 'xml'],
        'comprobantes/anulaciones/pdfs' => ['pdf'], 'comprobantes/anulaciones/xmls' => ['xml'], 'comprobantes/anulaciones/cdrs' => ['zip', 'xml'],
        'comprobantes/notas_credito/pdfs' => ['pdf'], 'comprobantes/notas_credito/xmls' => ['xml'], 'comprobantes/notas_credito/cdrs' => ['zip', 'xml'],
    ];

    public function __construct($publicRoot = null) { $this->publicRoot = $publicRoot ?? public_path(); }

    public function resolve(string $folder, $filename): array
    {
        abort_unless(isset(self::FOLDERS[$folder]) && is_string($filename) && $filename !== ''
            && strpos($filename, '/') === false && strpos($filename, '\\') === false
            && !preg_match('/[\x00-\x1F\x7F]/', $filename) && $filename !== '.' && $filename !== '..', 404);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, self::FOLDERS[$folder], true), 404);
        $root = realpath($this->publicRoot.DIRECTORY_SEPARATOR.$folder);
        $path = $root ? realpath($root.DIRECTORY_SEPARATOR.$filename) : false;
        abort_unless($root && $path && is_file($path) && strncasecmp($path, $root.DIRECTORY_SEPARATOR, strlen($root.DIRECTORY_SEPARATOR)) === 0, 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if ($extension === 'pdf') {
            abort_unless($mime === 'application/pdf', 404);
            $mime = 'application/pdf';
        } elseif ($extension === 'zip') {
            abort_unless(in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed'], true), 404);
            $mime = 'application/zip';
        } else {
            abort_unless(in_array($mime, ['application/xml', 'text/xml', 'text/plain'], true), 404);
            $contents = file_get_contents($path);
            abort_unless(is_string($contents) && !preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $contents), 404);
            $previous = libxml_use_internal_errors(true);
            try { $valid = (new \DOMDocument())->loadXML($contents, LIBXML_NONET); }
            finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            abort_unless($valid, 404);
            $mime = 'application/xml';
        }
        return ['path' => $path, 'mime' => $mime, 'extension' => $extension];
    }
}
