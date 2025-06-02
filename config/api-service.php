<?php

return [
    'navigation' => [
        'token' => [
            'cluster' => null,
            'group' => 'Settings',
            'sort' => 110,
            'icon' => '',
        ],
    ],
    'models' => [
        'token' => [
            'enable_policy' => true,
        ],
    ],
    'route' => [
        'panel_prefix' => true,
        'use_resource_middlewares' => false,
    ],
    'tenancy' => [
        'enabled' => true,
        'awareness' => true,
    ],
    'login-rules' => [
        'email' => 'required|email',
        'password' => 'required',
    ],
    'use-spatie-permission-middleware' => true,
];
