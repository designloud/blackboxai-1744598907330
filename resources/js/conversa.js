class Conversa {
    constructor(options = {}) {
        this.options = {
            baseUrl: '/conversa',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
            echoConfig: {
                broadcaster: 'pusher',
                key: options.pusherKey,
                cluster: options.pusherCluster,
                forceTLS: true
            },
            ...options
        };

        this.channels = {};
        this.initEcho();
        this.bindEvents();
    }

    /**
     * Initialize Laravel Echo
     */
    initEcho() {
        if (typeof Echo !== 'undefined') {
            return;
        }

        window.Echo = new Echo(this.options.echoConfig);
    }

    /**
     * Bind global events
     */
    bindEvents() {
        document.addEventListener('conversa:send-message', (e) => this.sendMessage(e.detail));
        document.addEventListener('conversa:toggle-reaction', (e) => this.toggleReaction(e.detail));
        document.addEventListener('conversa:upload-attachment', (e) => this.uploadAttachment(e.detail));
    }

    /**
     * Subscribe to a space channel
     */
    subscribeToSpace(spaceId) {
        const channelName = `space.${spaceId}`;
        
        if (this.channels[channelName]) {
            return this.channels[channelName];
        }

        const channel = Echo.private(channelName)
            .listen('MessageSent', (e) => {
                this.dispatchEvent('message-received', e);
                this.markLastRead(spaceId);
            })
            .listen('MessageReacted', (e) => {
                this.dispatchEvent('reaction-updated', e);
            })
            .listen('MessageDeleted', (e) => {
                this.dispatchEvent('message-deleted', e);
            })
            .listenForWhisper('typing', (e) => {
                this.dispatchEvent('user-typing', e);
            });

        this.channels[channelName] = channel;
        return channel;
    }

    /**
     * Subscribe to a thread channel
     */
    subscribeToThread(threadId) {
        const channelName = `thread.${threadId}`;
        
        if (this.channels[channelName]) {
            return this.channels[channelName];
        }

        const channel = Echo.private(channelName)
            .listen('MessageSent', (e) => {
                this.dispatchEvent('message-received', e);
                this.markLastRead(null, threadId);
            })
            .listen('MessageReacted', (e) => {
                this.dispatchEvent('reaction-updated', e);
            })
            .listen('MessageDeleted', (e) => {
                this.dispatchEvent('message-deleted', e);
            })
            .listenForWhisper('typing', (e) => {
                this.dispatchEvent('user-typing', e);
            });

        this.channels[channelName] = channel;
        return channel;
    }

    /**
     * Send a message
     */
    async sendMessage({ content, spaceId, threadId, attachments = [] }) {
        try {
            const formData = new FormData();
            formData.append('content', content);
            
            if (spaceId) {
                formData.append('space_id', spaceId);
            }
            
            if (threadId) {
                formData.append('thread_id', threadId);
            }

            attachments.forEach(file => {
                formData.append('attachments[]', file);
            });

            const response = await fetch(`${this.options.baseUrl}/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.options.csrfToken,
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to send message');
            }

            const message = await response.json();
            this.dispatchEvent('message-sent', { message });
            return message;

        } catch (error) {
            console.error('Error sending message:', error);
            this.dispatchEvent('error', { error });
            throw error;
        }
    }

    /**
     * Toggle a reaction on a message
     */
    async toggleReaction({ messageId, reaction }) {
        try {
            const response = await fetch(`${this.options.baseUrl}/messages/${messageId}/reactions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                },
                body: JSON.stringify({ reaction })
            });

            if (!response.ok) {
                throw new Error('Failed to toggle reaction');
            }

            const result = await response.json();
            this.dispatchEvent('reaction-toggled', result);
            return result;

        } catch (error) {
            console.error('Error toggling reaction:', error);
            this.dispatchEvent('error', { error });
            throw error;
        }
    }

    /**
     * Upload an attachment
     */
    async uploadAttachment(file) {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(`${this.options.baseUrl}/attachments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.options.csrfToken,
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to upload attachment');
            }

            const attachment = await response.json();
            this.dispatchEvent('attachment-uploaded', { attachment });
            return attachment;

        } catch (error) {
            console.error('Error uploading attachment:', error);
            this.dispatchEvent('error', { error });
            throw error;
        }
    }

    /**
     * Mark messages as read
     */
    async markLastRead(spaceId = null, threadId = null) {
        try {
            const endpoint = spaceId 
                ? `${this.options.baseUrl}/spaces/${spaceId}/mark-read`
                : `${this.options.baseUrl}/threads/${threadId}/mark-read`;

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                }
            });

            if (!response.ok) {
                throw new Error('Failed to mark as read');
            }

        } catch (error) {
            console.error('Error marking as read:', error);
            this.dispatchEvent('error', { error });
        }
    }

    /**
     * Emit typing indicator
     */
    emitTyping(spaceId = null, threadId = null) {
        const channelName = spaceId ? `space.${spaceId}` : `thread.${threadId}`;
        const channel = this.channels[channelName];

        if (channel) {
            channel.whisper('typing', {
                user: this.options.user
            });
        }
    }

    /**
     * Dispatch a custom event
     */
    dispatchEvent(name, detail) {
        const event = new CustomEvent(`conversa:${name}`, { detail });
        document.dispatchEvent(event);
    }

    /**
     * Clean up subscriptions
     */
    destroy() {
        Object.values(this.channels).forEach(channel => {
            channel.unsubscribe();
        });
        this.channels = {};
    }
}

// Export for module bundlers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Conversa;
}

// Export for browser
if (typeof window !== 'undefined') {
    window.Conversa = Conversa;
}
