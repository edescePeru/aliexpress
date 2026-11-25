<?php
namespace App\Services\Quotes;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};

class QuoteDocumentFiles
{
    private $publicRoot;
    public function __construct($publicRoot = null) { $this->publicRoot = $publicRoot ?? public_path(); }

    public function authorize($quoteId, string $permission, bool $historical = false): void
    {
        $user = Auth::user();
        abort_unless($user && $user->enable && $user->can($permission), 403);
        app(QuoteBranchContext::class)->assertQuote($quoteId, $historical);
    }

    public function imageDataUri($quoteId, string $filename): string
    {
        $this->attachment($quoteId, $filename, 'img');
        $file = $this->file($filename, false);
        return 'data:'.$file['mime'].';base64,'.base64_encode(file_get_contents($file['path']));
    }

    public function pdfPath($quoteId, string $filename): string
    {
        $this->attachment($quoteId, $filename, 'pdf');
        return $this->file($filename, true)['path'];
    }

    private function attachment($quoteId, string $filename, string $type): void
    {
        $user = Auth::user();
        abort_unless($user && $user->enable, 403);
        app(QuoteBranchContext::class)->assertQuote($quoteId, true);
        $rows = DB::table('images_quotes as image')->leftJoin('quotes as quote', 'quote.id', '=', 'image.quote_id')
            ->where('image.image', $filename)->select('quote.id', 'quote.tenant_id', 'quote.company_id', 'image.type')->get();
        abort_unless($rows->isNotEmpty(), 404);
        foreach ($rows as $row) {
            // Filename-only legacy storage cannot disambiguate two different owners.
            abort_unless((int) $row->id === (int) $quoteId && (int) $row->tenant_id === TenantContext::tenantId()
                && (int) $row->company_id === TenantContext::companyId() && $row->type === $type, 404);
        }
    }

    private function file(string $filename, bool $pdf): array
    {
        abort_unless($filename !== '' && $filename !== '.' && $filename !== '..'
            && !preg_match('/[\\x00-\\x1F\\x7F\\\\\/]/', $filename), 404);
        $types = $pdf ? ['pdf'=>'application/pdf'] : ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        abort_unless(isset($types[$extension]), 404);
        $root = realpath($this->publicRoot.'/images/planos');
        $path = $root ? realpath($root.DIRECTORY_SEPARATOR.$filename) : false;
        abort_unless($root && $path && is_file($path) && strncasecmp($path, $root.DIRECTORY_SEPARATOR, strlen($root.DIRECTORY_SEPARATOR)) === 0, 404);
        abort_unless((new \finfo(FILEINFO_MIME_TYPE))->file($path) === $types[$extension], 404);
        if (!$pdf) { abort_unless(@getimagesize($path) !== false, 404); }
        return ['path'=>$path, 'mime'=>$types[$extension]];
    }
}
