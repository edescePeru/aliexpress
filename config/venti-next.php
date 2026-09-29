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
        'material.edit',
        'material.indexV2',
        'unitmeasure.index',
        'unitmeasure.create',
        'unitmeasure.edit',
        'category.index',
        'category.create',
        'category.edit',
        'subcategory.index',
        'subcategory.create',
        'subcategory.edit',
        'materialtype.index',
        'materialtype.create',
        'materialtype.edit',
        'subtype.index',
        'subtype.create',
        'subtype.edit',
        'genero.index',
        'genero.create',
        'genero.edit',
        'talla.index',
        'talla.create',
        'talla.edit',
        'color.index',
        'color.create',
        'color.edit',
        'brand.index',
        'brand.create',
        'brand.edit',
        'exampler.index',
        'exampler.create',
        'exampler.edit',
        'typescrap.index',
        'typescrap.create',
        'typescrap.edit',
        'settings.material-details.index',
        'stocks.files.index',
        'dashboard.principal',
        'platform.dashboard',
        'plan.index',
        'roleTemplate.index',
        'roleTemplate.create',
        'roleTemplate.edit',
        'tenantRole.index',
        'tenantRole.create',
        'tenantRole.edit',
        'platformActivity.index',
        'platformTenant.index',
        'platformTenant.create',
        'platformTenant.show',
        'platformPercentageWorker.index',
        'platformPercentageWorker.edit',
    ],
];
