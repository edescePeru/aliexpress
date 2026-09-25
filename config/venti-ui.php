<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Venti UI rollout toggle
    |--------------------------------------------------------------------------
    |
    | Venti UI is opt-in. It is loaded only when this flag is enabled and the
    | current named route is present in the allowlist below.
    |
    */
    'enabled' => env('VENTI_UI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Approved routes
    |--------------------------------------------------------------------------
    |
    | Keep this list intentionally small while the progressive rollout is
    | validated. Only named GET routes that render appAdmin2 are listed here.
    |
    */
    'routes' => [
        'brand.index',
        'brand.create',
        'brand.edit',
        'category.index',
        'category.create',
        'category.edit',
    ],
];
