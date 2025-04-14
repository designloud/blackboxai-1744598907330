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
- ⏰ Message reminders and scheduling
- 📥 Mark messages as unread

### Message Management
- **Reminders**: Set reminders to revisit messages later
- **Scheduled Messages**: Schedule messages to be sent at specific times
- **Read Status**: Mark messages as read/unread
- **Natural Language Time**: Parse human-friendly time expressions

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

### Message Management

```php
use SwellSystems\Conversa\Models\ConversaMessage;

// Set a reminder for a message
$message->setReminder(auth()->id(), 'Remind me tomorrow at 9am');

// Schedule a message to be sent later
$message->scheduleFor('tomorrow at 9am');

// Mark a message as unread
$message->markAsUnread(auth()->id());

// Mark a message as read
$message->markAsRead(auth()->id());

// Check read status
$isRead = $message->hasBeenReadBy(auth()->id());
```

### Working with Reminders

```php
use SwellSystems\Conversa\Models\ConversaReminder;

// Create a reminder with natural language
$reminder = ConversaReminder::createFromText(
    'Check this in 3 hours',
    $messageId,
    auth()->id(),
    $workspaceId
);

// Snooze a reminder
$reminder->snooze('tomorrow');

// Cancel a reminder
$reminder->cancel();

// Get upcoming reminders
$reminders = ConversaReminder::getUpcomingForUser(auth()->id());
```

### Scheduled Messages

```php
use SwellSystems\Conversa\Models\ConversaScheduledMessage;

// Schedule a new message
$scheduled = ConversaScheduledMessage::scheduleFromText([
    'workspace_id' => 1,
    'space_id' => $spaceId,
    'content' => 'Good morning team!',
    'user_id' => auth()->id(),
], 'tomorrow at 9am');

// Reschedule a message
$scheduled->reschedule('next monday at 10am');

// Cancel a scheduled message
$scheduled->cancel();

// Get upcoming scheduled messages
$messages = ConversaScheduledMessage::getUpcomingForUser(auth()->id());
```

### Real-time Events

```javascript
// Subscribe to a space
const channel = conversa.subscribeToSpace(spaceId);

// Listen for reminders
channel.listen('reminder.due', (e) => {
    console.log('Reminder:', e.reminder);
});

// Listen for read status changes
channel.listen('message.read', (e) => {
    console.log('Message read by:', e.user_id);
});
```

## Console Commands

Process scheduled messages and reminders:

```bash
# Add to your scheduler
php artisan conversa:process-scheduled
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
