<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Venti Next pilot toggle
    |--------------------------------------------------------------------------
    |
    | Next remains opt-in and route-scoped while its visual language evolves.
    | A Next route also receives the frozen Venti Legacy layer as its base.
    |
    */
    // REAL NEXT: rollout aprobado y limitado por la allowlist inferior.
    'enabled' => true,

    'routes' => [
        'material.create',
    ],
];
