<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/order.php';

class OrderTest extends TestCase
{
    private $order;

    protected function setUp(): void
    {
        $this->order = new \Order();
    }

    public function testCreateOrder()
    {
        $orderData = [
            'user_id' => 1,
            'items' => [
                ['item_id' => 1, 'quantity' => 2, 'price' => 12.99],
                ['item_id' => 3, 'quantity' => 1, 'price' => 8.99]
            ],
            'total_amount' => 34.97,
            'delivery_address' => '123 College St'
        ];

        $result = $this->order->createOrder($orderData);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['order_id']);
        $this->assertEquals('pending', $result['status']);
    }

    public function testUpdateOrderStatus()
    {
        $orderId = 1;
        $newStatus = 'processing';

        $result = $this->order->updateStatus($orderId, $newStatus);
        $this->assertTrue($result['success']);
        $this->assertEquals($newStatus, $result['new_status']);
    }

    public function testInvalidOrderTotal()
    {
        $orderData = [
            'user_id' => 1,
            'items' => [
                ['item_id' => 1, 'quantity' => 2, 'price' => 12.99]
            ],
            'total_amount' => 0,
            'delivery_address' => '123 College St'
        ];

        $result = $this->order->validateOrder($orderData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Invalid order total', $result['errors']);
    }

    public function testOrderItemValidation()
    {
        $orderData = [
            'user_id' => 1,
            'items' => [],
            'total_amount' => 25.98,
            'delivery_address' => '123 College St'
        ];

        $result = $this->order->validateOrder($orderData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Order must contain at least one item', $result['errors']);
    }

    public function testInvalidUserId()
    {
        $orderData = [
            'user_id' => -1,
            'items' => [
                ['item_id' => 1, 'quantity' => 2, 'price' => 12.99]
            ],
            'total_amount' => 25.98,
            'delivery_address' => '123 College St'
        ];

        $result = $this->order->validateOrder($orderData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Invalid user ID', $result['errors']);
    }

    public function testInvalidItemQuantity()
    {
        $orderData = [
            'user_id' => 1,
            'items' => [
                ['item_id' => 1, 'quantity' => 0, 'price' => 12.99]
            ],
            'total_amount' => 25.98,
            'delivery_address' => '123 College St'
        ];

        $result = $this->order->validateOrder($orderData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Item quantity must be greater than 0', $result['errors']);
    }

    public function testMissingDeliveryAddress()
    {
        $orderData = [
            'user_id' => 1,
            'items' => [
                ['item_id' => 1, 'quantity' => 2, 'price' => 12.99]
            ],
            'total_amount' => 25.98,
            'delivery_address' => ''
        ];

        $result = $this->order->validateOrder($orderData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Delivery address is required', $result['errors']);
    }

    public function testOrderCancellation()
    {
        $orderId = 1;
        $result = $this->order->cancelOrder($orderId);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('cancelled', $result['status']);
    }

    public function testInvalidOrderStatusTransition()
    {
        $orderId = 1;
        $this->order->updateStatus($orderId, 'delivered');
        
        $result = $this->order->updateStatus($orderId, 'processing');
        $this->assertFalse($result['success']);
        $this->assertContains('Invalid status transition', $result['errors']);
    }
}