<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
require_once __DIR__ . '/../includes/payment.php';

class PaymentTest extends TestCase
{
    private $payment;
    private $mockDb;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockDb = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        $this->payment = new \Payment($this->mockDb);
    }

    public function testValidPaymentProcessing()
    {
        $orderId = 1;
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        // Mock database transaction methods
        $this->mockDb->expects($this->once())
            ->method('beginTransaction');
        
        // Mock order fetch
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT total_amount, status FROM orders'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$orderId]);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['total_amount' => 25.50, 'status' => 'pending']);

        // Mock order status update
        $this->mockDb->expects($this->once())
            ->method('commit');

        $result = $this->payment->processPayment($orderId, $paymentData);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['transaction_id']);
        $this->assertEquals('Payment processed successfully', $result['message']);
    }

    public function testInvalidPaymentData()
    {
        $orderId = 1;
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '',  // Invalid: empty card number
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $this->mockDb->expects($this->once())
            ->method('beginTransaction');

        $this->mockDb->expects($this->once())
            ->method('rollBack');

        $result = $this->payment->processPayment($orderId, $paymentData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid payment data', $result['message']);
    }

    public function testOrderNotFound()
    {
        $orderId = 999; // Non-existent order
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $this->mockDb->expects($this->once())
            ->method('beginTransaction');

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT total_amount, status FROM orders'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$orderId]);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockDb->expects($this->once())
            ->method('rollBack');

        $result = $this->payment->processPayment($orderId, $paymentData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Order not found', $result['message']);
    }

    public function testInvalidOrderStatus()
    {
        $orderId = 2;
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $this->mockDb->expects($this->once())
            ->method('beginTransaction');

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT total_amount, status FROM orders'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$orderId]);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['total_amount' => 25.50, 'status' => 'completed']);

        $this->mockDb->expects($this->once())
            ->method('rollBack');

        $result = $this->payment->processPayment($orderId, $paymentData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Order is not in pending status', $result['message']);
    }

    public function testPaymentGatewayFailure()
    {
        $orderId = 3;
        $paymentData = [
            'payment_method' => 'card',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
            'should_fail' => true  // Mock flag to simulate gateway failure
        ];

        $this->mockDb->expects($this->once())
            ->method('beginTransaction');

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT total_amount, status FROM orders'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$orderId]);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['total_amount' => 25.50, 'status' => 'pending']);

        $this->mockDb->expects($this->once())
            ->method('rollBack');

        $result = $this->payment->processPayment($orderId, $paymentData);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testMissingRequiredFields()
    {
        $paymentData = [
            'amount' => 25.50,
            'currency' => 'USD'
        ];

        $result = $this->payment->validatePaymentData($paymentData);
        $this->assertFalse($result['isValid']);
        $this->assertContains('Missing payment method', $result['errors']);
    }

    public function testDuplicateTransaction()
    {
        $paymentData = [
            'amount' => 25.50,
            'currency' => 'USD',
            'payment_method' => 'card',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
            'transaction_id' => 'test_transaction'
        ];

        // First payment should succeed
        $this->payment->processPayment($paymentData);

        // Second payment with same transaction_id should fail
        $result = $this->payment->processPayment($paymentData);
        $this->assertFalse($result['success']);
        $this->assertEquals('Duplicate transaction', $result['error']);
    }
}