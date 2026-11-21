<?php
namespace App\Services;

use App\User;
use Illuminate\Http\UploadedFile;

class UserAvatarFiles
{
    public function store(User $user, UploadedFile $file): string
    {
        $extensions = ['image/png'=>'png', 'image/jpeg'=>'jpg', 'image/webp'=>'webp'];
        $mime = $file->getMimeType();
        if (!$file->isValid() || !isset($extensions[$mime])) {
            throw new \RuntimeException('La imagen de perfil no es válida.');
        }
        $directory = public_path('images/users');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo preparar la carpeta de perfiles.');
        }
        $public = rtrim(str_replace('\\','/',realpath(public_path())), '/').'/';
        $resolved = str_replace('\\','/',realpath($directory));
        if (strpos($resolved, $public) !== 0) {
            throw new \RuntimeException('La carpeta de perfiles no es válida.');
        }
        $name = 'user-'.$user->id.'-'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        try {
            $file->move($directory, $name);
        } catch (\Throwable $e) {
            $this->discard($user, $name);
            throw $e;
        }
        return $name;
    }

    public function discard(User $user, $name): void
    {
        if (!is_string($name) || !preg_match('~^(?:user-'.(int)$user->id.'-[a-f0-9]{32}|'.(int)$user->id.')\.(?:png|jpg|jpeg|webp)$~iD', $name)) return;
        if (User::withoutGlobalScopes()->where('image', $name)->exists()) return;
        $base = realpath(public_path('images/users'));
        $path = realpath(public_path('images/users/'.$name));
        if (!$base || !$path || !is_file($path)
            || strpos(str_replace('\\','/',$path), rtrim(str_replace('\\','/',$base),'/').'/') !== 0) return;
        if (!unlink($path)) throw new \RuntimeException('No se pudo retirar una imagen de perfil sin referencias.');
    }
}
