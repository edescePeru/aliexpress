<?php
namespace App\Services;

use App\User;

class SelfProfileAccess
{
    public static function assertSelf($actor, $target): void
    {
        abort_unless($actor instanceof User, 401);
        $targetId = $target instanceof User ? $target->getKey() : $target;
        abort_unless(is_scalar($targetId) && (string) $targetId !== ''
            && (string) $actor->getAuthIdentifier() === (string) $targetId, 403,
            'Solo puedes modificar tu propio perfil.');
    }
}
