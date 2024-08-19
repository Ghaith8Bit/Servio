<?php

return [
    'User' => [
        'table' => 'users',
        'dto' => [
            'fields' => [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ],
        ],
        'resource' => [
            'fields' => [
                'id',
                'name',
                'email',
            ],
        ],
    ],
    'Example' => [
        'table' => 'examples',
        'dto' => [
            'fields' => [
                // dto fields
            ],
        ],
        'resource' => [
            'fields' => [
                // resource fields
            ],
        ],
    ],
];
