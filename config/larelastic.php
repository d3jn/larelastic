<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Observe Searchable Models
    |--------------------------------------------------------------------------
    |
    | Enables observing your models with Searchable trait. Disabling it stops
    | observing and syncing data between your models and Elasticsearch indices
    | whenever Eloquent's events updated and created are fired.
    |
    */

    'observe_searchable_models' => env('LARELASTIC_OBSERVE_MODELS', false),

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
        // 'App\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Indices For Types
    |--------------------------------------------------------------------------
    |
    | Here you can specify indices names for your types, where type name is
    | your key and index name is it's value.
    |
    | If you need more intricate logic for resolving indices names then you can
    | use your own implementation of IndexResolver or override getSearchIndex()
    | method inside your Searchable classes.
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
    | Array of all hosts that should be used by elasticsearch client.
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

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Here you can setup different Larelastic logging settings.
    |
    */

    'logging' => [
        'enabled' => env('LARELASTIC_LOGGING_ENABLED', false),
        'channel' => env('LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Silent mode
    |--------------------------------------------------------------------------
    |
    | If enabled Larelastic won't throw non-critical errors (for example,
    | when trying to delete non-existing document from index etc), but will
    | still report them nontheless.
    |
    */

    'silent_mode' => false
];
