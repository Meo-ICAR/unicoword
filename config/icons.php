<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Icons Sets
    |--------------------------------------------------------------------------
    |
    | Here you can specify a set of icons that will be available for your
    | application. The "default" set is automatically loaded and will be
    | used as the default set for your application.
    |
    */

    'sets' => [
        'default' => [
            'path' => public_path('icons'),
            'prefix' => 'icon',
        ],
        'heroicons' => [
            'path' => public_path('vendor/blade-ui-kit/blade-heroicons/resources/svg'),
            'prefix' => 'heroicon',
        ],
        'fontawesome' => [
            'path' => public_path('vendor/blade-fontawesome-icons/svg'),
            'prefix' => 'fas',
        ],
    ],
];
