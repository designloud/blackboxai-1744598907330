class WebSocketAdapter {
    constructor(options = {}) {
        this.options = {
            host: options.host || window.location.hostname,
            port: options.port || 6001,
            path: options.path || '/ws',
            secure: options.secure || window.location.protocol === 'https:',
            ...options
        };

        this.socket = null;
        this.channels = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = 1000;
        this.connected = false;
        this.connecting = false;
        this.callbacks = new Map();

        // Bind methods
        this.connect = this.connect.bind(this);
        this.disconnect = this.disconnect.bind(this);
        this.reconnect = this.reconnect.bind(this);
        this.handleOpen = this.handleOpen.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.handleMessage = this.handleMessage.bind(this);
        this.handleError = this.handleError.bind(this);
    }

    /**
     * Connect to WebSocket server
     */
    connect() {
        if (this.connected || this.connecting) return;

        this.connecting = true;
        const protocol = this.options.secure ? 'wss' : 'ws';
        const url = `${protocol}://${this.options.host}:${this.options.port}${this.options.path}`;

        try {
            this.socket = new WebSocket(url);
            this.socket.onopen = this.handleOpen;
            this.socket.onclose = this.handleClose;
            this.socket.onmessage = this.handleMessage;
            this.socket.onerror = this.handleError;
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Disconnect from WebSocket server
     */
    disconnect() {
        if (!this.socket) return;

        this.connected = false;
        this.connecting = false;
        this.socket.close();
        this.socket = null;
        this.channels.clear();
    }

    /**
     * Subscribe to a channel
     */
    subscribe(channelName, auth = null) {
        if (!this.connected) {
            this.connect();
        }

        if (!this.channels.has(channelName)) {
            const channel = {
                name: channelName,
                listeners: new Map(),
                subscribed: false
            };

            this.channels.set(channelName, channel);

            const message = {
                event: 'subscribe',
                channel: channelName,
                auth: auth
            };

            this.send(message);
        }

        return {
            listen: (event, callback) => this.listen(channelName, event, callback),
            whisper: (event, data) => this.whisper(channelName, event, data),
            unsubscribe: () => this.unsubscribe(channelName)
        };
    }

    /**
     * Unsubscribe from a channel
     */
    unsubscribe(channelName) {
        if (this.channels.has(channelName)) {
            const message = {
                event: 'unsubscribe',
                channel: channelName
            };

            this.send(message);
            this.channels.delete(channelName);
        }
    }

    /**
     * Listen for events on a channel
     */
    listen(channelName, event, callback) {
        const channel = this.channels.get(channelName);
        if (!channel) return;

        if (!channel.listeners.has(event)) {
            channel.listeners.set(event, new Set());
        }

        channel.listeners.get(event).add(callback);
    }

    /**
     * Send a whisper event
     */
    whisper(channelName, event, data) {
        const message = {
            event: 'whisper',
            channel: channelName,
            data: {
                event,
                data
            }
        };

        this.send(message);
    }

    /**
     * Send a message to the server
     */
    send(data) {
        if (!this.connected) {
            this.connect();
            this.callbacks.set('connect', () => this.send(data));
            return;
        }

        try {
            this.socket.send(JSON.stringify(data));
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Handle WebSocket open event
     */
    handleOpen() {
        this.connected = true;
        this.connecting = false;
        this.reconnectAttempts = 0;

        // Resubscribe to channels
        this.channels.forEach((channel, name) => {
            const message = {
                event: 'subscribe',
                channel: name
            };
            this.send(message);
        });

        // Execute connect callbacks
        const connectCallbacks = this.callbacks.get('connect') || [];
        connectCallbacks.forEach(callback => callback());
        this.callbacks.delete('connect');
    }

    /**
     * Handle WebSocket close event
     */
    handleClose() {
        this.connected = false;
        this.connecting = false;

        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            setTimeout(() => {
                this.reconnectAttempts++;
                this.reconnect();
            }, this.reconnectInterval * Math.pow(2, this.reconnectAttempts));
        }
    }

    /**
     * Handle WebSocket messages
     */
    handleMessage(event) {
        try {
            const message = JSON.parse(event.data);
            const channel = this.channels.get(message.channel);

            if (channel && channel.listeners.has(message.event)) {
                channel.listeners.get(message.event).forEach(callback => {
                    callback(message.data);
                });
            }
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Handle WebSocket errors
     */
    handleError(error) {
        console.error('WebSocket Error:', error);
        this.emit('error', error);
    }

    /**
     * Attempt to reconnect
     */
    reconnect() {
        if (!this.connected && !this.connecting) {
            this.connect();
        }
    }

    /**
     * Emit an event
     */
    emit(event, data) {
        const callbacks = this.callbacks.get(event) || [];
        callbacks.forEach(callback => callback(data));
    }

    /**
     * Add an event listener
     */
    on(event, callback) {
        if (!this.callbacks.has(event)) {
            this.callbacks.set(event, new Set());
        }
        this.callbacks.get(event).add(callback);
    }

    /**
     * Remove an event listener
     */
    off(event, callback) {
        if (this.callbacks.has(event)) {
            this.callbacks.get(event).delete(callback);
        }
    }
}

// Export for module bundlers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketAdapter;
}

// Export for browser
if (typeof window !== 'undefined') {
    window.WebSocketAdapter = WebSocketAdapter;
}
