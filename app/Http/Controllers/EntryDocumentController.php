<?php
namespace App\Http\Controllers;

use App\Services\Purchases\{EntryDocumentAccess, EntryDocumentFile};

class EntryDocumentController extends Controller
{
    public function main($filename) { return $this->stream('main', $filename); }
    public function observation($filename) { return $this->stream('observation', $filename); }
    public function extra($filename) { return $this->stream('extra', $filename); }

    private function stream(string $kind, string $filename)
    {
        app(EntryDocumentAccess::class)->authorize($kind, $filename);
        $file = app(EntryDocumentFile::class)->resolve($kind, $filename);
        $response = response()->file($file['path'], ['Content-Type' => $file['mime'], 'X-Content-Type-Options' => 'nosniff']);
        $response->setContentDisposition('inline', 'documento.'.$file['extension']);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
