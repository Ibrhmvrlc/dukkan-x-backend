<?php

return [

    'paths' => ['api/*', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type','X-Requested-With','Authorization','Accept','Origin'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];