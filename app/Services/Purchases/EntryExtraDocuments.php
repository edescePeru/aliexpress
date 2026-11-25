<?php
namespace App\Services\Purchases;

use App\{Audit, Entry, EntryImage};
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class EntryExtraDocuments
{
    private $publicRoot;
    private $stagingRoot;
    public function __construct($publicRoot = null, $stagingRoot = null)
    {
        $this->publicRoot = $publicRoot ?? public_path();
        $this->stagingRoot = $stagingRoot ?? storage_path('app/entry-extra-staging');
    }

    public function entry($id, bool $lock = false)
    {
        $user = Auth::user(); $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
        abort_unless($user && $user->enable && !$user->isPlatformAdmin() && $user->can('update_entryPurchase')
            && $tenant && $company && (int) $user->tenant_id === (int) $tenant
            && DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)->where('is_active', true)->exists()
            && DB::table('company_user')->where('company_id', $company)->where('user_id', $user->id)->where('is_active', true)->exists(), 403);
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        $query = Entry::query()->where('tenant_id', $tenant)->where('company_id', $company)->where('finance', false)->whereKey($id);
        if ($lock) { $query->lockForUpdate(); }
        return $query->firstOrFail();
    }

    private function attachment($request, $id, bool $lock = false)
    {
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        if ($request->exists('image_id') && (!is_scalar($request->input('image_id')) || (string) $request->input('image_id') !== (string) $id)) {
            throw ValidationException::withMessages(['image_id'=>'El adjunto del formulario no coincide con el de la dirección. Recargue la página.']);
        }
        $image = EntryImage::findOrFail($id);
        $this->entry($image->entry_id, $lock);
        if ($lock) { $image = EntryImage::whereKey($id)->where('entry_id', $image->entry_id)->lockForUpdate()->firstOrFail(); }
        return $image;
    }

    private function topLevel(): void { abort_unless(DB::transactionLevel() === 0, 409, 'Finalice la operación actual antes de modificar adjuntos.'); }
    private function audit(string $action): void { Audit::create(['user_id'=>Auth::id(), 'action'=>$action, 'time'=>0]); }

    public function update($request, $id): void
    {
        $this->attachment($request, $id); $this->topLevel();
        $values = $request->validate(['image_code'=>'present|nullable|string|max:10000']);
        DB::transaction(function () use ($request, $id, $values) {
            $image = $this->attachment($request, $id, true);
            $image->code = $values['image_code']; $image->save(); $this->audit('Imagen Entrada modificada');
        });
    }

    public function delete($request, $id): void
    {
        $this->attachment($request, $id); $this->topLevel();
        $filename = DB::transaction(function () use ($request, $id) {
            $image = $this->attachment($request, $id, true); $filename = $image->image;
            $image->delete(); $this->audit('Imagen Entrada eliminada'); return $filename;
        });
        // Never remove bytes before commit, outside the known folder, or while still referenced.
        try {
            if (!EntryImage::where('image', $filename)->exists()) {
                $path = $this->existingPath($filename);
                if ($path) { @unlink($path); }
            }
        } catch (\Throwable $ignored) {
            // Database removal succeeded. Keep private orphan bytes if cleanup is uncertain.
        }
    }

    public function upload($request, $entryId): void
    {
        $this->entry($entryId); $this->topLevel();
        $values = $request->validate([
            'images'=>'required|array|min:1|max:20', 'images.*'=>'required|file|max:20480',
            'codeimages'=>'required|array', 'codeimages.*'=>'nullable|string|max:10000',
            'types'=>'required|array', 'types.*'=>'required|in:i,g,o',
        ]);
        $uploads = $request->file('images');
        if (array_keys($uploads) !== array_keys($values['codeimages']) || array_keys($uploads) !== array_keys($values['types'])) {
            throw ValidationException::withMessages(['images'=>'Cada archivo debe tener una descripción y un tipo correspondientes.']);
        }
        $kinds = [];
        foreach ($uploads as $key=>$upload) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload->getPathname());
            $ext = strtolower($upload->getClientOriginalExtension());
            $pdf = $mime === 'application/pdf' && $ext === 'pdf';
            $picture = in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'], true)
                && in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
            $size = $picture ? @getimagesize($upload->getPathname()) : false;
            if (!$pdf && (!$picture || !$size || $size[0] > 10000 || $size[1] > 10000 || $size[0]*$size[1] > 25000000)) {
                throw ValidationException::withMessages(['images.'.$key=>'Use un PDF real o una imagen JPG, PNG, GIF o WebP válida (máximo 25 megapíxeles y 10000 píxeles por lado).']);
            }
            $kinds[$key] = $pdf ? 'pdf' : 'img';
        }
        $stage = $this->stagingRoot.'/'.bin2hex(random_bytes(16)); $prepared = []; $promoted = [];
        try {
            if (!is_dir($stage) && !mkdir($stage, 0700, true) && !is_dir($stage)) { throw new \RuntimeException('No se pudo preparar la carga.'); }
            foreach ($uploads as $key=>$upload) {
                $filename = 'entry-'.$entryId.'-'.bin2hex(random_bytes(16)).($kinds[$key] === 'pdf' ? '.pdf' : '.jpg');
                $prepared[$key] = ['stage'=>$stage.'/'.$filename,'filename'=>$filename,'type_file'=>$kinds[$key]];
                if ($kinds[$key] === 'pdf') {
                    if (!copy($upload->getPathname(), $prepared[$key]['stage'])) { throw new \RuntimeException('No se pudo preparar el PDF.'); }
                } else {
                    $picture = (new \Intervention\Image\ImageManager(['driver'=>'gd']))->make($upload->getPathname())->orientate();
                    $picture->save($prepared[$key]['stage'], 80, 'jpg'); $picture->destroy();
                }
            }
            DB::transaction(function () use ($entryId, $values, $prepared, &$promoted) {
                $entry = $this->entry($entryId, true);
                $folder = realpath($this->publicRoot.'/images/entries/extras');
                if (!$folder || !is_dir($folder)) { throw new \RuntimeException('No se encontró la carpeta de adjuntos.'); }
                foreach ($prepared as $key=>$item) {
                    $target = $folder.DIRECTORY_SEPARATOR.$item['filename'];
                    $output = @fopen($target, 'xb');
                    if (!$output) { throw new \RuntimeException('No se pudo guardar el adjunto.'); }
                    $promoted[$item['filename']] = $target;
                    try {
                        $input = fopen($item['stage'], 'rb');
                        if (!$input) { throw new \RuntimeException('No se pudo leer el adjunto preparado.'); }
                        try { $copied = stream_copy_to_stream($input, $output); }
                        finally { fclose($input); }
                        if ($copied !== filesize($item['stage'])) { throw new \RuntimeException('Carga incompleta del adjunto.'); }
                    } finally { fclose($output); }
                    EntryImage::create(['entry_id'=>$entry->id,'code'=>$values['codeimages'][$key], 'image'=>$item['filename'],
                        'type'=>$values['types'][$key], 'type_file'=>$item['type_file']]);
                }
                $this->audit('Guardar images entries POST');
            });
        } catch (\Throwable $error) {
            foreach ($promoted as $filename=>$path) {
                try { if (!EntryImage::where('image', $filename)->exists()) { @unlink($path); } }
                catch (\Throwable $uncertain) { /* Keep bytes when commit outcome cannot be verified. */ }
            }
            throw $error;
        } finally {
            foreach ($prepared as $item) { if (is_file($item['stage'])) { @unlink($item['stage']); } }
            if (is_dir($stage)) { @rmdir($stage); }
        }
    }

    private function existingPath($filename)
    {
        if (!is_string($filename) || $filename === '' || preg_match('/[\\x00-\\x1F\\x7F\\\\\/]/', $filename)
            || in_array(strtolower($filename), ['.','..','no_image.png','no_image.jpg'], true)) { return null; }
        $root = realpath($this->publicRoot.'/images/entries/extras'); $path = $root ? realpath($root.DIRECTORY_SEPARATOR.$filename) : false;
        return $root && $path && is_file($path) && !is_link($root.DIRECTORY_SEPARATOR.$filename)
            && strcasecmp($path, $root.DIRECTORY_SEPARATOR.$filename) === 0 ? $path : null;
    }
}
