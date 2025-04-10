<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/PaymentLogger.php';

use HealthyBite\PaymentLogger;

class Payment {
    private $conn;
    private $logger;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->logger = new PaymentLogger($db);
    }
    
    public function processPayment($orderId, $paymentData) {
        try {
            $this->conn->beginTransaction();
            $this->logger->logPaymentEvent($orderId, 'payment_initiated', 'pending', 'Payment processing started', $paymentData);
            
            // Validate payment data
            $validationResult = $this->validatePaymentData($paymentData);
            if (!$validationResult['isValid']) {
                $errorMessage = isset($validationResult['error']) ? $validationResult['error'] : implode(', ', $validationResult['errors']);
                $this->logger->logPaymentEvent($orderId, 'validation_failed', 'error', $errorMessage, $paymentData);
                throw new Exception($errorMessage);
            }
            
            // Get order details
            $stmt = $this->conn->prepare(
                "SELECT total_amount, status FROM orders WHERE id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $this->logger->logPaymentEvent($orderId, 'order_check', 'error', 'Order not found');
                throw new Exception('Order not found');
            }
            
            if ($order['status'] !== 'pending') {
                $this->logger->logPaymentEvent($orderId, 'order_check', 'error', 'Invalid order status: ' . $order['status']);
                throw new Exception('Order is not in pending status');
            }
            
            // Process payment (integrate with payment gateway here)
            $paymentResult = $this->processPaymentWithGateway($paymentData, $order['total_amount']);
            
            if ($paymentResult['success']) {
                // Log successful payment
                $this->logger->logPaymentEvent($orderId, 'payment_processed', 'success', 'Payment processed successfully', $paymentResult);
                
                // Update order status
                $stmt = $this->conn->prepare(
                    "UPDATE orders SET status = 'processing' WHERE id = ?"
                );
                $stmt->execute([$orderId]);
                
                // Record payment
                $stmt = $this->conn->prepare(
                    "INSERT INTO payments (order_id, amount, payment_method, transaction_id) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([
                    $orderId,
                    $order['total_amount'],
                    $paymentData['payment_method'],
                    $paymentResult['transaction_id']
                ]);
                
                $this->conn->commit();
                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'transaction_id' => $paymentResult['transaction_id'],
                    'logs' => $this->logger->getPaymentLogs($orderId)
                ];
            } else {
                $this->logger->logPaymentEvent($orderId, 'payment_failed', 'error', $paymentResult['message'], $paymentResult);
                throw new Exception($paymentResult['message']);
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logger->logPaymentEvent($orderId, 'payment_failed', 'error', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'logs' => $this->logger->getPaymentLogs($orderId)
            ];
        }
    }
    
    public function validatePaymentData($paymentData) {
        $errors = [];
        $required = ['payment_method', 'card_number', 'expiry_month', 'expiry_year', 'cvv'];
        
        foreach ($required as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                $errors[] = "Missing {$field}";
            }
        }
        
        if (!empty($errors)) {
            return ['isValid' => false, 'errors' => $errors];
        }
        
        // Basic card number validation (Luhn algorithm)
        if (!$this->validateCardNumber($paymentData['card_number'])) {
            $errors[] = 'Invalid card number';
        }
        
        // Expiry date validation
        $currentYear = date('Y');
        $currentMonth = date('m');
        if ($paymentData['expiry_year'] < $currentYear ||
            ($paymentData['expiry_year'] == $currentYear && $paymentData['expiry_month'] < $currentMonth)) {
            $errors[] = 'Card has expired';
        }
        
        // Check for duplicate transaction
        if (isset($paymentData['transaction_id'])) {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM payments WHERE transaction_id = ?"
            );
            $stmt->execute([$paymentData['transaction_id']]);
            if ($stmt->fetchColumn() > 0) {
                return ['isValid' => false, 'error' => 'Duplicate transaction'];
            }
        }
        
        return ['isValid' => empty($errors), 'errors' => $errors];
    }
    
    public function validateCardNumber($number) {
        $number = preg_replace('/\D/', '', $number);
        $length = strlen($number);
        $parity = $length % 2;
        $sum = 0;
        
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = $number[$i];
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        
        return ($sum % 10) == 0;
    }
    
    private function processPaymentWithGateway($paymentData, $amount) {
        // TODO: Integrate with actual payment gateway
        // This is a mock implementation
        return [
            'success' => true,
            'transaction_id' => uniqid('TRANS_'),
            'message' => 'Payment processed successfully'
        ];
    }
}