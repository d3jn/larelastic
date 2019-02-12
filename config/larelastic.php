<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Larelastic Enabled
    |--------------------------------------------------------------------------
    |
    | Enabling observing models with searchable trait. Disabling it stops
    | observing and syncing data between your models and ElasticSearch indices.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Available Types
    |--------------------------------------------------------------------------
    |
    | Array of classes implementing D3jn\Larelastic\Contracts\Models\Searchable
    | that will be used by this package.
    |
    */

    'types' => [
        // App\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Indices For Types
    |--------------------------------------------------------------------------
    |
    | Here you can map specific type to different indices, where type name is
    | your key and index name is it's value. If you need more intricate logic
    | for mapping then you should rebind and use your own implementation of
    | D3jn\Larelastic\Contracts\IndexResolver or define index/type in model
    | class directly if you are using D3jn\Larelastic\Models\Traits\Searchable
    | trait implementation.
    |
    */

    'type_indices' => [
        // 'users' => 'users_index',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Hosts
    |--------------------------------------------------------------------------
    |
    | Array of all hosts that should be used by elasticsearch client. By
    | default one host is already set up and gets it's values from .env file.
    |
    */

    'hosts' => [
        [
            'host' => env('ELASTICSEARCH_HOST'),
            'port' => env('ELASTICSEARCH_PORT', 9200),
            'scheme' => env('ELASTICSEARCH_PROTOCOL', 'http'),
            'user' => env('ELASTICSEARCH_USER'),
            'pass' => env('ELASTICSEARCH_PASSWORD'),
        ],
    ],
];
