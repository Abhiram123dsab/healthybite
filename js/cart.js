// Cart management and API integration
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('healthyBitesCart')) || [];
        this.initializeEventListeners();
        this.syncWithServer();
    }

    initializeEventListeners() {
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const item = {
                    id: e.target.dataset.id,
                    name: e.target.dataset.name,
                    price: parseFloat(e.target.dataset.price),
                    image: e.target.dataset.image,
                    quantity: 1
                };
                this.addItem(item);
            });
        });

        // Update cart count when storage changes
        window.addEventListener('storage', () => {
            this.updateCartDisplay();
        });
    }

    async syncWithServer() {
        try {
            const response = await fetch('/api/cart_handler.php');
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Merge server cart with local cart
                    const serverItems = data.items.map(item => ({
                        id: item.menu_item_id,
                        name: item.name,
                        price: parseFloat(item.price),
                        image: item.image_url,
                        quantity: item.quantity
                    }));
                    this.mergeCart(serverItems);
                }
            }
        } catch (error) {
            console.error('Failed to sync with server:', error);
        }
    }

    mergeCart(serverItems) {
        // Merge local and server items, keeping the higher quantity
        const mergedItems = new Map();

        // Add local items
        this.items.forEach(item => {
            mergedItems.set(item.id, item);
        });

        // Merge with server items
        serverItems.forEach(serverItem => {
            const localItem = mergedItems.get(serverItem.id);
            if (localItem) {
                localItem.quantity = Math.max(localItem.quantity, serverItem.quantity);
            } else {
                mergedItems.set(serverItem.id, serverItem);
            }
        });

        this.items = Array.from(mergedItems.values());
        this.saveCart();
    }

    async addItem(item) {
        const existingItem = this.items.find(i => i.id === item.id);

        if (existingItem) {
            existingItem.quantity++;
        } else {
            this.items.push(item);
        }

        this.saveCart();
        this.updateCartDisplay();

        // Sync with server
        try {
            await fetch('/api/cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    menu_item_id: item.id,
                    quantity: existingItem ? existingItem.quantity : 1
                })
            });
        } catch (error) {
            console.error('Failed to sync with server:', error);
        }
    }

    async updateQuantity(itemId, quantity) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            item.quantity = quantity;
            if (quantity <= 0) {
                this.items = this.items.filter(i => i.id !== itemId);
            }
            this.saveCart();
            this.updateCartDisplay();

            // Sync with server
            try {
                await fetch('/api/cart_handler.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        menu_item_id: itemId,
                        quantity: quantity
                    })
                });
            } catch (error) {
                console.error('Failed to sync with server:', error);
            }
        }
    }

    async clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();

        // Sync with server
        try {
            await fetch('/api/cart_handler.php', {
                method: 'DELETE'
            });
        } catch (error) {
            console.error('Failed to sync with server:', error);
        }
    }

    saveCart() {
        localStorage.setItem('healthyBitesCart', JSON.stringify(this.items));
    }

    updateCartDisplay() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'inline-block' : 'none';
        }
    }

    async createOrder() {
        try {
            const response = await fetch('/api/order_handler.php', {
                method: 'POST'
            });
            const data = await response.json();

            if (data.success) {
                this.clearCart();
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Failed to create order:', error);
            throw error;
        }
    }

    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.healthyBitesCart = new Cart();
});