<?php

return [
    'name' => 'Saml',
    'options' => [
        'active' => ['default' => 'off'],
        'entity_id' => ['default' => 'https://freescout.mysite.com'],
        'sso_url' => ['default' => ''],
        'slo_url' => ['default' => ''],
        'oauth.auto_create' => ['default' => 'on'],
        'oauth.mapping' => ['default' => ''],
        'oauth.exclusive_login' => ['default' => 'off'],
        'x509' => ['default' => '']
    ],
];
