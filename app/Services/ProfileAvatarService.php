<?php
namespace App\Services;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class ProfileAvatarService
{
    public function replace(Request $request, User $target): void
    {
        SelfProfileAccess::assertSelf($request->user(), $target);
        $request->validate(['image' => ['required', 'file', 'image', 'mimes:png,jpeg', 'max:2048', 'dimensions:max_width=4096,max_height=4096']]);
        $file = $request->file('image');
        $info = @getimagesize($file->getRealPath());
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)
            || $info[0] * $info[1] > 8388608) {
            $this->invalid('Seleccione una imagen PNG o JPG válida de hasta 8 megapíxeles.');
        }
        if (DB::transactionLevel() !== 0) {
            throw new \RuntimeException('El cambio de avatar debe finalizar en su propia transacción.');
        }
        // Decode and re-encode: never publish original uploaded bytes or its extension.
        try {
            $image = (new ImageManager(['driver' => 'gd']))->make($file->getRealPath())->orientate();
            $extension = $info[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
            $bytes = (string) $image->encode($extension, 90);
            $image->destroy();
        } catch (\Throwable $error) {
            $this->invalid('No se pudo procesar la imagen. Seleccione un PNG o JPG válido.');
        }
        $directory = $this->directory();
        $name = 'avatar-'.(int) $target->getKey().'-'.bin2hex(random_bytes(16)).'.'.$extension;
        $path = $directory.'/'.$name;
        $old = null;
        $created = false;
        try {
            DB::transaction(function () use ($target, $directory, $name, $path, $bytes, &$old, &$created) {
                $user = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
                $old = (string) $user->image;
                $this->writeNew($path, $bytes, $created);
                $user->image = $name;
                $user->save();
            });
        } catch (\Throwable $error) {
            // If commit outcome is uncertain, never delete bytes referenced by the DB.
            try {
                if ($created && !User::query()->where('image', $name)->exists()) { $this->unlinkOwn($path); }
            } catch (\Throwable $cleanupError) { report($cleanupError); }
            throw $error;
        }
        // The prior avatar is retired only after commit and only when no user references it.
        if ($this->ownedName($old, (int) $target->getKey())) {
            try {
                if (!User::query()->where('image', $old)->exists()) { $this->unlinkOwn($directory.'/'.$old); }
            } catch (\Throwable $error) { report($error); }
        }
    }

    protected function directory(): string
    {
        $directory = public_path('images/users');
        if (!is_dir($directory) || is_link($directory)) { throw new \RuntimeException('El directorio de avatares no está disponible.'); }
        return $directory;
    }

    protected function writeNew(string $path, string $bytes, bool &$created): void
    {
        $stream = @fopen($path, 'xb');
        if (!$stream) { throw new \RuntimeException('No se pudo preparar el nuevo avatar.'); }
        $created = true;
        try {
            $offset = 0; $length = strlen($bytes);
            while ($offset < $length) {
                $written = fwrite($stream, substr($bytes, $offset));
                if ($written === false || $written === 0) { throw new \RuntimeException('No se pudo guardar el avatar completo.'); }
                $offset += $written;
            }
        } finally { fclose($stream); }
    }

    private function ownedName(?string $name, int $userId): bool
    {
        if (!$name || basename($name) !== $name) { return false; }
        return (bool) preg_match('/^(?:avatar-'.preg_quote((string) $userId, '/').'-[a-f0-9]{32}\.(?:png|jpg)|'.preg_quote((string) $userId, '/').'\.(?:png|jpg|jpeg))$/D', $name);
    }

    private function unlinkOwn(string $path): void
    {
        if (is_file($path) && !is_link($path) && !@unlink($path)) {
            throw new \RuntimeException('No se pudo retirar un archivo de avatar propio.');
        }
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['image' => $message]);
    }
}
