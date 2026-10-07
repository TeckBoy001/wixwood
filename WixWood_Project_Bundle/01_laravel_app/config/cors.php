<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list in ALLOWED_ORIGINS, e.g.
    // ALLOWED_ORIGINS=https://wixwoodcrafts.com,https://www.wixwoodcrafts.com
    // Empty means "allow any origin" — fine for local development, but
    // set this for real before going live.
    'allowed_origins' => array_filter(explode(',', env('ALLOWED_ORIGINS', ''))) ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
