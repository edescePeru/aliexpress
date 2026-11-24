<?php

namespace App\Services\Purchases;

class SupplierCreditAttachments
{
    private $privateBase;
    private $legacyBase;

    public function __construct($privateBase = null, $legacyBase = null)
    {
        $this->privateBase = $privateBase ?? storage_path('app/private/supplier-credits');
        $this->legacyBase = $legacyBase ?? public_path('images/credits');
    }

    public static function directory($kind)
    {
        if (!in_array($kind, ['paid', 'pays'], true)) { throw new \InvalidArgumentException('Tipo de comprobante inválido.'); }
        return storage_path('app/private/supplier-credits/'.$kind);
    }

    public function resolve($kind, $filename)
    {
        abort_unless(in_array($kind, ['paid', 'pays'], true), 404);
        abort_unless(is_string($filename) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,190}$/D', $filename)
            && !in_array($filename, ['no_image.png', 'no_image.jpg'], true), 404);
        $directories = [$this->privateBase.DIRECTORY_SEPARATOR.$kind,
            $this->legacyBase.($kind === 'pays' ? DIRECTORY_SEPARATOR.'pays' : '')];
        $candidates = [];
        foreach ($directories as $directory) {
            $root = realpath($directory);
            if (!$root) { continue; }
            $path = realpath($root.DIRECTORY_SEPARATOR.$filename);
            $prefix = $root.DIRECTORY_SEPARATOR;
            if (!$path || !is_file($path) || strncasecmp($path, $prefix, strlen($prefix)) !== 0) { continue; }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 404);
            if ($mime !== 'application/pdf') { abort_unless(@getimagesize($path) !== false, 404); }
            $candidates[] = ['path' => $path, 'mime' => $mime, 'extension' => $mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg')];
        }
        abort_unless(count($candidates) > 0, 404);
        $first = $candidates[0];
        foreach (array_slice($candidates, 1) as $candidate) {
            $originalHash = hash_file('sha256', $first['path']);
            $otherHash = hash_file('sha256', $candidate['path']);
            abort_unless($originalHash && $otherHash && hash_equals($originalHash, $otherHash), 404);
        }
        return $first;
    }
}
