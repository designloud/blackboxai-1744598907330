# Conversa

A modern, Slack-like communication package for Laravel multi-tenant applications with enterprise-grade security and real-time messaging capabilities.

## Features

### Core Messaging
- 💬 Public and private spaces (channels)
- 📝 Direct messaging and group threads
- 📎 File attachments with preview
- 👍 Message reactions and emoji support
- 🔍 Message search with Meilisearch integration
- 📖 Read receipts and message status tracking
- 🧵 Message threading and replies
- 🏷️ User and channel mentions with notifications
- 🔗 Smart URL previews
- ✨ Rich text formatting with Markdown

### Message Formatting
- **Markdown Support**: Full Markdown syntax for rich text formatting
- **User Mentions**: Tag users with `@username` to notify them
- **Channel Mentions**: Reference channels with `#channel-name`
- **Emoji Support**: Use emoji shortcodes like `:smile:` or `:rocket:`
- **URL Previews**: Automatic rich previews for shared links
- **Code Blocks**: Syntax highlighting for code snippets
- **Lists & Tables**: Structured content with Markdown
- **Safe HTML**: Configurable HTML tag allowlist

### Real-time Features
- ⚡ Real-time updates using Laravel Echo and Pusher
- 🔄 WebSocket fallback support with custom server
- 👥 Presence channels for online status
- ✍️ Typing indicators
- 📱 Multi-device synchronization

### Security
- 🔒 End-to-end message encryption
- 🚦 Rate limiting and abuse prevention
- 🔐 Channel authorization
- 🛡️ XSS and CSRF protection

### Enterprise Features
- 👥 Multi-tenant support
- 🎨 Modern UI with Tailwind CSS
- 📊 Message analytics and insights
- 🔄 High availability and failover
- 📱 Responsive design

## Installation

```bash
composer require swellsystems/conversa
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="SwellSystems\Conversa\ConversaServiceProvider"
```

### Run Migrations

```bash
php artisan migrate
```

### Install JavaScript Dependencies

```bash
npm install laravel-echo pusher-js
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-key
PUSHER_APP_SECRET=your-pusher-secret
PUSHER_APP_CLUSTER=your-pusher-cluster

MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your-meilisearch-key

CONVERSA_ENCRYPTION_KEY=your-encryption-key
```

### Message Formatting Configuration

Configure formatting options in `config/conversa.php`:

```php
'formatting' => [
    'max_message_length' => 10000,
    'markdown' => [
        'enabled' => true,
        'safe_mode' => true,
    ],
    'mentions' => [
        'user_pattern' => '@[a-zA-Z0-9_]{3,}',
        'channel_pattern' => '#[a-zA-Z0-9_-]{2,}',
    ],
    'emoji' => [
        'enabled' => true,
    ],
],

'url_preview' => [
    'enabled' => true,
    'max_previews_per_message' => 3,
    'cache_duration' => 86400, // 24 hours
],
```

## Usage

### Initialize the JavaScript Client

```javascript
import Conversa from 'conversa';

const conversa = new Conversa({
    // Pusher configuration
    pusherKey: 'your-pusher-key',
    pusherCluster: 'your-pusher-cluster',
    
    // WebSocket fallback configuration
    webSocket: {
        enabled: true,
        host: 'your-websocket-host',
        port: 6001,
        secure: true
    },
    
    // User information
    user: {
        id: 1,
        name: 'John Doe'
    }
});
```

### Send a Formatted Message

```php
use SwellSystems\Conversa\Models\ConversaMessage;

$message = ConversaMessage::create([
    'workspace_id' => 1,
    'space_id' => $space->id,
    'user_id' => auth()->id(),
    'content' => "# Meeting Notes\n\n- @sarah please review the docs\n- Check out #general for updates\n- Great work team! :rocket:",
    'type' => 'text',
]);

// Message will be automatically formatted with:
// - Markdown rendering
// - User/channel mention parsing
// - Emoji conversion
// - URL preview generation
```

### Handle Mentions

```php
// Get all mentions in a message
$mentions = $message->mentions;

// Get unread mentions for a user
$unreadCount = ConversaMention::getUnreadCountForUser(auth()->id());

// Mark mentions as read
ConversaMention::markAllAsRead(auth()->id());
```

### Real-time Events

```javascript
// Subscribe to a space
const channel = conversa.subscribeToSpace(spaceId);

// Listen for new messages
channel.listen('message.sent', (e) => {
    console.log('New message:', e.message);
});

// Listen for mentions
channel.listen('user.mentioned', (e) => {
    console.log('You were mentioned in:', e.message);
});

// Track presence
channel.here((users) => {
    console.log('Online users:', users);
});
```

## Security

### Message Encryption

Messages are automatically encrypted using AES-256-GCM when encryption is enabled:

```php
// config/conversa.php
return [
    'encryption' => [
        'enabled' => true,
        'key' => env('CONVERSA_ENCRYPTION_KEY'),
    ],
];
```

### Rate Limiting

Configure rate limits in `config/conversa.php`:

```php
'rate_limits' => [
    'messages_per_minute' => 30,
    'reactions_per_minute' => 60,
    'mentions_per_message' => 50,
],
```

## Testing

```bash
# Run PHP tests
composer test

# Run JavaScript tests
npm test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
