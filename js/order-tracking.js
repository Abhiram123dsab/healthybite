// WebSocket connection for real-time order tracking
let socket = null;

class OrderTracker {
    constructor() {
        this.orderId = null;
        this.statusElement = null;
        this.notifications = [];
    }

    init(orderId) {
        this.orderId = orderId;
        this.statusElement = document.getElementById('order-status');
        this.initializeWebSocket();
        this.setupNotifications();
    }

    initializeWebSocket() {
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.host}/ws/orders/${this.orderId}`;

        socket = new WebSocket(wsUrl);

        socket.onopen = () => {
            console.log('Connected to order tracking system');
            this.sendSubscription();
        };

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleStatusUpdate(data);
        };

        socket.onclose = () => {
            console.log('Disconnected from order tracking system');
            // Attempt to reconnect after 5 seconds
            setTimeout(() => this.initializeWebSocket(), 5000);
        };
    }

    sendSubscription() {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                type: 'subscribe',
                orderId: this.orderId
            }));
        }
    }

    handleStatusUpdate(data) {
        if (this.statusElement) {
            this.statusElement.textContent = data.status;
            this.statusElement.className = `status-${data.status.toLowerCase()}`;
        }

        // Show notification
        this.showNotification(data.status);

        // Update order timeline
        this.updateTimeline(data);
    }

    setupNotifications() {
        if ('Notification' in window) {
            Notification.requestPermission();
        }
    }

    showNotification(status) {
        if (Notification.permission === 'granted') {
            new Notification('Order Status Update', {
                body: `Your order status: ${status}`,
                icon: '/img/logo.png'
            });
        }
    }

    updateTimeline(data) {
        const timeline = document.getElementById('order-timeline');
        if (timeline) {
            const timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item';
            timelineItem.innerHTML = `
                <div class="timeline-status">${data.status}</div>
                <div class="timeline-time">${new Date().toLocaleTimeString()}</div>
                <div class="timeline-description">${data.description || ''}</div>
            `;
            timeline.appendChild(timelineItem);
        }
    }

    disconnect() {
        if (socket) {
            socket.close();
        }
    }
}

// Export the OrderTracker class
window.OrderTracker = OrderTracker;