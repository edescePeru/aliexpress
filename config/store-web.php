<?php

return [
    // Local QA only: anonymous visual fixtures, or the authenticated data bridge.
    // Keep false by default. Authenticated access also verifies active session context.
    // Neither mode resolves public tenant context (SW-01).
    'preview' => env('STORE_WEB_PREVIEW', false),
];
