# Contributing to Conversa

Thank you for considering contributing to Conversa! This document provides guidelines and instructions for contributing to the project.

## Development Setup

1. Fork the repository
2. Clone your fork:
```bash
git clone https://github.com/your-username/conversa.git
```

3. Install PHP dependencies:
```bash
composer install
```

4. Install JavaScript dependencies:
```bash
npm install
```

5. Set up your environment:
```bash
cp .env.example .env
```

6. Run tests to ensure everything is working:
```bash
composer test
npm test
```

## Development Workflow

1. Create a new branch for your feature/fix:
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes
3. Write/update tests
4. Run tests and ensure they pass
5. Submit a pull request

## Coding Standards

### PHP

- Follow PSR-12 coding standard
- Use type hints and return type declarations
- Write DocBlocks for classes and methods
- Use dependency injection where possible
- Keep methods small and focused

### JavaScript

- Use ES6+ features
- Follow ESLint configuration
- Write JSDoc comments for functions
- Use meaningful variable names
- Keep functions pure when possible

## Testing

### PHP Tests

- Write unit tests for models and services
- Write feature tests for controllers
- Test both success and failure scenarios
- Use factories for test data
- Test rate limiting functionality

```bash
# Run all PHP tests
composer test

# Run specific test
vendor/bin/phpunit tests/path/to/test

# Run with coverage
composer test-coverage
```

### JavaScript Tests

- Write tests for all JavaScript utilities
- Test real-time functionality
- Mock Echo and Pusher appropriately
- Test event handling

```bash
# Run all JavaScript tests
npm test

# Run with watch mode
npm run test:watch

# Run with coverage
npm run test:coverage
```

## Real-time Features

When working with real-time features:

1. **Events**
- Keep events focused and specific
- Include only necessary data in payloads
- Document event structures
- Test broadcasting rules

2. **Channels**
- Use private channels for security
- Test channel authorization
- Document channel naming conventions
- Consider scalability

3. **Client-side**
- Handle connection errors gracefully
- Implement reconnection logic
- Test with different network conditions
- Document subscription patterns

## Pull Request Guidelines

1. **Description**
- Clearly describe the problem and solution
- Include relevant issue numbers
- List any breaking changes
- Include screenshots for UI changes

2. **Code Quality**
- Follow coding standards
- Include tests
- Update documentation
- Keep changes focused

3. **Testing**
- Add/update tests for new features
- Ensure all tests pass
- Include test coverage for edge cases
- Test real-time functionality thoroughly

## Documentation

- Update README.md for new features
- Document new events and channels
- Include JSDoc comments
- Update API documentation
- Add examples for new features

## Working with Real-time Features

### Adding New Events

1. Create the event class:
```php
namespace VendorName\Conversa\Events;

class YourNewEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    // Implementation
}
```

2. Update channel authorization if needed:
```php
Broadcast::channel('your-channel.{id}', function ($user, $id) {
    // Authorization logic
});
```

3. Add client-side handling:
```javascript
conversa.subscribeToChannel(channelName).listen('YourNewEvent', (e) => {
    // Handle event
});
```

### Testing Real-time Features

1. Test event broadcasting:
```php
Event::fake([YourNewEvent::class]);

// Trigger event
Event::assertDispatched(YourNewEvent::class);
```

2. Test channel authorization:
```php
$this->assertTrue(
    $this->canAccessChannel("private-your-channel.{$id}")
);
```

3. Test client-side handling:
```javascript
test('handles your new event', () => {
    // Test implementation
});
```

## Security

- Never expose sensitive data in events
- Always use private channels
- Validate all user input
- Test authorization rules
- Follow security best practices

## Questions or Problems?

- Open an issue for bugs
- Use discussions for questions
- Join our community chat
- Check existing issues first

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
