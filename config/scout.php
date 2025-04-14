<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "meilisearch", "database", "collection", "null"
    |
    */
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */
    'prefix' => env('SCOUT_PREFIX', 'conversa_'),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */
    'queue' => [
        'queue' => env('SCOUT_QUEUE', null),
        'connection' => env('QUEUE_CONNECTION', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */
    'after_commit' => true,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */
    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "meilisearch"
    |
    */
    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may adjust all of the Meilisearch configuration settings for
    | your application. The host and key values are required for Scout
    | to communicate with your Meilisearch installation properly.
    |
    */
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'conversa_messages' => [
                'displayedAttributes' => [
                    'id',
                    'content',
                    'type',
                    'user_id',
                    'workspace_id',
                    'space_id',
                    'thread_id',
                    'created_at',
                    'updated_at',
                ],
                'searchableAttributes' => [
                    'content',
                    'type',
                ],
                'filterableAttributes' => [
                    'workspace_id',
                    'space_id',
                    'thread_id',
                    'user_id',
                    'type',
                    'created_at',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'updated_at',
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'created_at:desc',
                ],
                'stopWords' => [
                    'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at',
                ],
                'synonyms' => [
                    'chat' => ['conversation', 'discussion', 'talk'],
                    'message' => ['text', 'msg', 'note'],
                ],
            ],
        ],
    ],
];
