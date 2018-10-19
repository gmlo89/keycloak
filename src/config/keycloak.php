<?php

return [
    'server' => env('KEYCLOAK_SERVER'),
    'realm' => env('KEYCLOAK_REALM'),
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),

    'custom_attributes' => [
        'notes', 'phone_number'
    ],
    'enabled_default' => true,
    'username_field' => 'email',
];
