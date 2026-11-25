<?php
namespace App\Services\Purchases;

class EntryDocumentFile
{
    private $root;
    private const FOLDERS = ['main' => 'images/entries', 'observation' => 'images/entries/observations', 'extra' => 'images/entries/extras'];

    public function __construct($root = null) { $this->root = $root ?? public_path(); }

    public function resolve(string $kind, string $filename): array
    {
        abort_unless(isset(self::FOLDERS[$kind]) && $filename !== '' && $filename !== '.' && $filename !== '..'
            && !preg_match('/[\\x00-\\x1F\\x7F\\\\\/]/', $filename), 404);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp'];
        abort_unless(isset($types[$extension]), 404);
        $base = realpath($this->root.'/'.self::FOLDERS[$kind]);
        $path = $base ? realpath($base.DIRECTORY_SEPARATOR.$filename) : false;
        abort_unless($base && $path && is_file($path)
            && strncasecmp($path, $base.DIRECTORY_SEPARATOR, strlen($base.DIRECTORY_SEPARATOR)) === 0, 404);
        abort_unless((new \finfo(FILEINFO_MIME_TYPE))->file($path) === $types[$extension], 404);
        if ($extension !== 'pdf') { abort_unless(@getimagesize($path) !== false, 404); }
        return ['path' => $path, 'mime' => $types[$extension], 'extension' => $extension];
    }
}
