// Mock window.Echo
window.Echo = {
    private: jest.fn().mockReturnThis(),
    presence: jest.fn().mockReturnThis(),
    channel: jest.fn().mockReturnThis(),
    listen: jest.fn().mockReturnThis(),
    listenForWhisper: jest.fn().mockReturnThis(),
    whisper: jest.fn().mockReturnThis(),
    join: jest.fn().mockReturnThis(),
    here: jest.fn().mockReturnThis(),
    joining: jest.fn().mockReturnThis(),
    leaving: jest.fn().mockReturnThis(),
    error: jest.fn().mockReturnThis(),
    unsubscribe: jest.fn(),
};

// Mock CSRF token meta tag
document.head.innerHTML = `
    <meta name="csrf-token" content="test-token">
`;

// Mock FormData
global.FormData = class FormData {
    constructor() {
        this.data = {};
    }

    append(key, value) {
        this.data[key] = value;
    }

    get(key) {
        return this.data[key];
    }

    entries() {
        return Object.entries(this.data);
    }
};

// Mock File API
global.File = class File {
    constructor(bits, name, options = {}) {
        this.name = name;
        this.size = bits.length;
        this.type = options.type || '';
    }
};

// Mock Blob
global.Blob = class Blob {
    constructor(content, options = {}) {
        this.content = content;
        this.type = options.type || '';
    }
};

// Mock URL.createObjectURL
global.URL.createObjectURL = jest.fn(blob => 'mock-url');

// Mock URL.revokeObjectURL
global.URL.revokeObjectURL = jest.fn();

// Mock CustomEvent
global.CustomEvent = class CustomEvent {
    constructor(type, options = {}) {
        this.type = type;
        this.detail = options.detail;
        this.bubbles = options.bubbles || false;
        this.cancelable = options.cancelable || false;
    }
};

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
    constructor(callback) {
        this.callback = callback;
    }

    observe() {}
    unobserve() {}
    disconnect() {}
};

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
    constructor(callback) {
        this.callback = callback;
    }

    observe() {}
    unobserve() {}
    disconnect() {}
};

// Mock window.matchMedia
window.matchMedia = jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
}));

// Mock console methods
global.console = {
    ...console,
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn(),
    info: jest.fn(),
    debug: jest.fn(),
};

// Helper to wait for promises
global.flushPromises = () => new Promise(resolve => setImmediate(resolve));

// Helper to create a mock response
global.createResponse = (data, status = 200) => ({
    ok: status >= 200 && status < 300,
    status,
    json: () => Promise.resolve(data),
    text: () => Promise.resolve(JSON.stringify(data)),
    headers: new Map(),
});

// Helper to simulate events
global.simulateEvent = (element, eventName, data = {}) => {
    const event = new CustomEvent(eventName, { detail: data });
    element.dispatchEvent(event);
};

// Clean up after each test
afterEach(() => {
    jest.clearAllMocks();
});
