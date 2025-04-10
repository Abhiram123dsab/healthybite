<?php
namespace HealthyBite;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $orders;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->orders = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if ($data['type'] === 'subscribe') {
            $this->subscriptions[$from->resourceId] = $data['orderId'];
            
            // Send initial order status
            $orderStatus = $this->getOrderStatus($data['orderId']);
            $from->send(json_encode([
                'status' => $orderStatus['status'],
                'description' => $orderStatus['description'],
                'timestamp' => time()
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function getOrderStatus($orderId) {
        // In a real implementation, this would fetch from database
        // For now, return mock data
        return [
            'status' => 'Processing',
            'description' => 'Your order is being prepared',
            'timestamp' => time()
        ];
    }

    public function broadcastOrderUpdate($orderId, $status, $description = '') {
        foreach ($this->clients as $client) {
            if (isset($this->subscriptions[$client->resourceId]) 
                && $this->subscriptions[$client->resourceId] === $orderId) {
                $client->send(json_encode([
                    'status' => $status,
                    'description' => $description,
                    'timestamp' => time()
                ]));
            }
        }
    }
}