<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PlatformAuditService
{
    /**
     * Campos que nunca deben almacenarse
     * en nuestra auditoría.
     */
    private $sensitiveFields = [
        'password',
        'password_confirmation',
        'temporary_password',
        'remember_token',
        'api_token',
    ];

    public function log(
        $action,
        Model $subject = null,
        array $properties = []
    )
    {
        $user = Auth::user();

        $properties = $this->sanitize(
            $properties
        );

        /*
         * Contexto técnico útil para auditoría.
         */
        $properties['action'] =
            $action;

        if ( !array_key_exists( 'tenant_id', $properties ) ) {
            $properties['tenant_id'] =
                null;
        }

        $properties['ip'] =
            request()->ip();

        $properties['user_agent'] =
            request()->userAgent();

        $logger =
            activity('platform');

        if ($user) {
            $logger->causedBy(
                $user
            );
        }

        if ($subject) {
            $logger->performedOn(
                $subject
            );
        }

        $logger
            ->withProperties(
                $properties
            )
            ->log(
                $action
            );
    }

    /**
     * Elimina datos sensibles recursivamente.
     */
    private function sanitize(
        array $data
    ) {
        foreach (
            $data as $key => $value
        ) {

            if (
            in_array(
                $key,
                $this->sensitiveFields,
                true
            )
            ) {
                unset(
                    $data[$key]
                );

                continue;
            }

            if (
            is_array(
                $value
            )
            ) {
                $data[$key] =
                    $this->sanitize(
                        $value
                    );
            }
        }

        return $data;
    }
}