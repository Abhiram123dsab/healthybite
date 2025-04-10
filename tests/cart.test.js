import { CartManager } from '../js/cart.js';

describe('Cart Management', () => {
    let cartManager;

    beforeEach(() => {
        // Clear localStorage before each test
        localStorage.clear();
        cartManager = new CartManager();
    });

    test('should add item to cart', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 1
        };

        cartManager.addItem(item);
        const cartItems = cartManager.getItems();

        expect(cartItems).toHaveLength(1);
        expect(cartItems[0]).toEqual(item);
    });

    test('should update item quantity', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 1
        };

        cartManager.addItem(item);
        cartManager.updateQuantity(1, 2);

        const cartItems = cartManager.getItems();
        expect(cartItems[0].quantity).toBe(2);
    });

    test('should calculate total correctly', () => {
        const items = [{
                id: 1,
                name: 'Healthy Breakfast Bowl',
                price: 12.99,
                quantity: 2
            },
            {
                id: 2,
                name: 'Green Smoothie',
                price: 6.99,
                quantity: 1
            }
        ];

        items.forEach(item => cartManager.addItem(item));
        const total = cartManager.calculateTotal();

        expect(total).toBeCloseTo(32.97);
    });

    test('should remove item from cart', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 1
        };

        cartManager.addItem(item);
        cartManager.removeItem(1);

        const cartItems = cartManager.getItems();
        expect(cartItems).toHaveLength(0);
    });

    test('should validate maximum item quantity', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 1
        };

        cartManager.addItem(item);
        expect(() => cartManager.updateQuantity(1, 11)).toThrow('Maximum quantity exceeded');
    });

    test('should persist cart data in localStorage', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 2
        };

        cartManager.addItem(item);
        const storedCart = JSON.parse(localStorage.getItem('cart'));
        expect(storedCart).toHaveLength(1);
        expect(storedCart[0]).toEqual(item);
    });

    test('should handle invalid price values', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: -12.99,
            quantity: 1
        };

        expect(() => cartManager.addItem(item)).toThrow('Invalid item price');
    });

    test('should validate item properties', () => {
        const invalidItem = {
            id: 1,
            price: 12.99,
            quantity: 1
        };

        expect(() => cartManager.addItem(invalidItem)).toThrow('Missing required item properties');
    });

    test('should handle duplicate items', () => {
        const item = {
            id: 1,
            name: 'Healthy Breakfast Bowl',
            price: 12.99,
            quantity: 1
        };

        cartManager.addItem(item);
        cartManager.addItem(item);

        const cartItems = cartManager.getItems();
        expect(cartItems).toHaveLength(1);
        expect(cartItems[0].quantity).toBe(2);
    });

    test('should clear cart', () => {
        const items = [{
                id: 1,
                name: 'Healthy Breakfast Bowl',
                price: 12.99,
                quantity: 2
            },
            {
                id: 2,
                name: 'Green Smoothie',
                price: 6.99,
                quantity: 1
            }
        ];

        items.forEach(item => cartManager.addItem(item));
        cartManager.clearCart();

        expect(cartManager.getItems()).toHaveLength(0);
        expect(localStorage.getItem('cart')).toBe(null);
    });
});