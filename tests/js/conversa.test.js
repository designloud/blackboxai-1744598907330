import Conversa from '../../resources/js/conversa';

// Mock Echo
window.Echo = {
    private: jest.fn().mockReturnThis(),
    listen: jest.fn().mockReturnThis(),
    listenForWhisper: jest.fn().mockReturnThis(),
    whisper: jest.fn(),
};

describe('Conversa', () => {
    let conversa;
    let mockFetch;

    beforeEach(() => {
        // Reset all mocks
        jest.clearAllMocks();

        // Mock fetch
        mockFetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ id: 1 })
        });
        global.fetch = mockFetch;

        // Create new instance
        conversa = new Conversa({
            pusherKey: 'test-key',
            pusherCluster: 'test-cluster',
            user: {
                id: 1,
                name: 'Test User'
            }
        });

        // Mock document.dispatchEvent
        document.dispatchEvent = jest.fn();
    });

    describe('Channel Subscriptions', () => {
        test('subscribes to space channel', () => {
            const channel = conversa.subscribeToSpace(1);

            expect(Echo.private).toHaveBeenCalledWith('space.1');
            expect(Echo.listen).toHaveBeenCalledWith('MessageSent', expect.any(Function));
            expect(Echo.listen).toHaveBeenCalledWith('MessageReacted', expect.any(Function));
            expect(Echo.listen).toHaveBeenCalledWith('MessageDeleted', expect.any(Function));
            expect(Echo.listenForWhisper).toHaveBeenCalledWith('typing', expect.any(Function));
        });

        test('subscribes to thread channel', () => {
            const channel = conversa.subscribeToThread(1);

            expect(Echo.private).toHaveBeenCalledWith('thread.1');
            expect(Echo.listen).toHaveBeenCalledWith('MessageSent', expect.any(Function));
            expect(Echo.listen).toHaveBeenCalledWith('MessageReacted', expect.any(Function));
            expect(Echo.listen).toHaveBeenCalledWith('MessageDeleted', expect.any(Function));
            expect(Echo.listenForWhisper).toHaveBeenCalledWith('typing', expect.any(Function));
        });

        test('reuses existing channel subscription', () => {
            const channel1 = conversa.subscribeToSpace(1);
            const channel2 = conversa.subscribeToSpace(1);

            expect(Echo.private).toHaveBeenCalledTimes(1);
            expect(channel1).toBe(channel2);
        });
    });

    describe('Message Operations', () => {
        test('sends a message', async () => {
            const message = {
                content: 'Test message',
                spaceId: 1,
            };

            await conversa.sendMessage(message);

            expect(mockFetch).toHaveBeenCalledWith(
                '/conversa/messages',
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({
                        'X-CSRF-TOKEN': expect.any(String),
                    }),
                })
            );

            expect(document.dispatchEvent).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: 'conversa:message-sent'
                })
            );
        });

        test('handles message send failure', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await expect(conversa.sendMessage({
                content: 'Test message',
                spaceId: 1,
            })).rejects.toThrow('Network error');

            expect(document.dispatchEvent).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: 'conversa:error'
                })
            );
        });
    });

    describe('Reaction Operations', () => {
        test('toggles a reaction', async () => {
            await conversa.toggleReaction({
                messageId: 1,
                reaction: '👍'
            });

            expect(mockFetch).toHaveBeenCalledWith(
                '/conversa/messages/1/reactions',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify({ reaction: '👍' })
                })
            );

            expect(document.dispatchEvent).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: 'conversa:reaction-toggled'
                })
            );
        });
    });

    describe('Attachment Operations', () => {
        test('uploads an attachment', async () => {
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });

            await conversa.uploadAttachment(file);

            expect(mockFetch).toHaveBeenCalledWith(
                '/conversa/attachments',
                expect.objectContaining({
                    method: 'POST',
                })
            );

            expect(document.dispatchEvent).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: 'conversa:attachment-uploaded'
                })
            );
        });
    });

    describe('Typing Indicators', () => {
        test('emits typing indicator for space', () => {
            conversa.subscribeToSpace(1);
            conversa.emitTyping(1);

            expect(Echo.whisper).toHaveBeenCalledWith('typing', {
                user: expect.objectContaining({
                    id: 1,
                    name: 'Test User'
                })
            });
        });

        test('emits typing indicator for thread', () => {
            conversa.subscribeToThread(1);
            conversa.emitTyping(null, 1);

            expect(Echo.whisper).toHaveBeenCalledWith('typing', {
                user: expect.objectContaining({
                    id: 1,
                    name: 'Test User'
                })
            });
        });
    });

    describe('Cleanup', () => {
        test('destroys all channel subscriptions', () => {
            const unsubscribe = jest.fn();
            Echo.private.mockReturnValue({ unsubscribe });

            conversa.subscribeToSpace(1);
            conversa.subscribeToThread(1);
            conversa.destroy();

            expect(unsubscribe).toHaveBeenCalledTimes(2);
            expect(conversa.channels).toEqual({});
        });
    });
});
