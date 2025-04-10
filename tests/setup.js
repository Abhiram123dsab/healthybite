// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    clear: jest.fn(),
    removeItem: jest.fn(),
    length: 0,
    key: jest.fn()
};

global.localStorage = localStorageMock;

// Mock fetch API
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({})
    })
);

// Mock DOM elements and events
global.document.createElement = jest.fn().mockImplementation((tag) => {
    return {
        setAttribute: jest.fn(),
        getElementsByTagName: jest.fn(() => []),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        style: {}
    };
});

// Mock window methods
global.window.alert = jest.fn();
global.window.confirm = jest.fn();

// Reset all mocks before each test
beforeEach(() => {
    jest.clearAllMocks();
    localStorage.clear();
});

// Clean up after all tests
afterAll(() => {
    jest.restoreAllMocks();
});