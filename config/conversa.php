<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class that represents users in your application.
    |
    */
    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure broadcasting settings for real-time features.
    |
    */
    'broadcasting' => [
        'enabled' => env('CONVERSA_BROADCASTING_ENABLED', true),
        'driver' => env('CONVERSA_BROADCAST_DRIVER', 'pusher'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket server settings for real-time communication.
    |
    */
    'websocket' => [
        'enabled' => env('CONVERSA_WEBSOCKET_ENABLED', true),
        'host' => env('CONVERSA_WEBSOCKET_HOST', '0.0.0.0'),
        'port' => env('CONVERSA_WEBSOCKET_PORT', 6001),
        'ssl_cert' => env('CONVERSA_WEBSOCKET_SSL_CERT'),
        'ssl_key' => env('CONVERSA_WEBSOCKET_SSL_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Encryption
    |--------------------------------------------------------------------------
    |
    | Configure message encryption settings.
    |
    */
    'encryption' => [
        'enabled' => env('CONVERSA_ENCRYPTION_ENABLED', true),
        'key' => env('CONVERSA_ENCRYPTION_KEY'),
        'cipher' => 'AES-256-GCM',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Formatting
    |--------------------------------------------------------------------------
    |
    | Configure message formatting options.
    |
    */
    'formatting' => [
        'max_message_length' => 10000,
        'allowed_html_tags' => [
            'p', 'br', 'strong', 'em', 'u', 'del', 'code', 'pre', 'blockquote',
            'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'a', 'img', 'table', 'tr', 'td', 'th'
        ],
        'markdown' => [
            'enabled' => true,
            'safe_mode' => true,
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ],
        'mentions' => [
            'user_pattern' => '@[a-zA-Z0-9_]{3,}',
            'channel_pattern' => '#[a-zA-Z0-9_-]{2,}',
        ],
        'emoji' => [
            'enabled' => true,
            'shortcode_pattern' => ':[a-zA-Z0-9_+-]+:',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Preview
    |--------------------------------------------------------------------------
    |
    | Configure URL preview settings.
    |
    */
    'url_preview' => [
        'enabled' => true,
        'max_previews_per_message' => 3,
        'cache_duration' => 86400, // 24 hours
        'max_title_length' => 100,
        'max_description_length' => 200,
        'allowed_domains' => [], // Empty array means all domains are allowed
        'blocked_domains' => [], // Domains to never preview
        'user_agent' => 'Conversa URL Preview Bot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various actions.
    |
    */
    'rate_limits' => [
        'messages_per_minute' => env('CONVERSA_MESSAGE_RATE_LIMIT', 30),
        'reactions_per_minute' => env('CONVERSA_REACTION_RATE_LIMIT', 60),
        'attachments_per_message' => env('CONVERSA_ATTACHMENTS_PER_MESSAGE', 10),
        'mentions_per_message' => env('CONVERSA_MENTIONS_PER_MESSAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure search settings using Laravel Scout.
    |
    */
    'search' => [
        'driver' => env('CONVERSA_SEARCH_DRIVER', 'meilisearch'),
        'prefix' => env('CONVERSA_SEARCH_PREFIX', 'conversa_'),
        'queue' => env('CONVERSA_SEARCH_QUEUE', true),
        'soft_delete' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    |
    | Configure file storage settings for attachments.
    |
    */
    'storage' => [
        'disk' => env('CONVERSA_STORAGE_DISK', 'local'),
        'path' => env('CONVERSA_STORAGE_PATH', 'conversa'),
        'max_file_size' => env('CONVERSA_MAX_FILE_SIZE', 100 * 1024 * 1024), // 100MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/csv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emoji Map
    |--------------------------------------------------------------------------
    |
    | Map emoji shortcodes to their Unicode equivalents.
    |
    */
    'emoji_map' => [
        'smile' => '😊',
        'laugh' => '😄',
        'wink' => '😉',
        'heart' => '❤️',
        'thumbsup' => '👍',
        'thumbsdown' => '👎',
        'tada' => '🎉',
        'fire' => '🔥',
        'rocket' => '🚀',
        'eyes' => '👀',
        'thinking' => '🤔',
        'pray' => '🙏',
        'clap' => '👏',
        'ok_hand' => '👌',
        'raised_hands' => '🙌',
        'hundred' => '💯',
        'muscle' => '💪',
        'party' => '🎊',
        'star' => '⭐',
        'zap' => '⚡',
    ],
];
