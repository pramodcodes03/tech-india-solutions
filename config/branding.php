<?php

/*
|--------------------------------------------------------------------------
| Login / brand identity
|--------------------------------------------------------------------------
|
| Per-deployment branding for the pre-auth login screen. Each deployment
| sharing this image overrides these via its own .env (BRAND_NAME etc.);
| the defaults below preserve the original "Tech India Solutions" branding
| so any deployment WITHOUT overrides is unchanged.
|
*/

return [
    // Logo brand name (large) + sub-line (small), shown next to the login logo.
    'name' => env('BRAND_NAME', 'Tech India'),
    'sub'  => env('BRAND_SUB', 'Solutions'),

    // Full brand name used in prose ("Sign in to your … admin panel", footer).
    'full' => env('BRAND_FULL', 'Tech India Solutions'),
];
