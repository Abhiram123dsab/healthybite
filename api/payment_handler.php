<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/payment.php';
require_once __DIR__ . '/../config/db_config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$payment = new Payment($conn);
$userId = $_SESSION['user_id'];

// Only accept POST requests for payment processing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['payment_data'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Process payment
$result = $payment->processPayment($data['order_id'], $data['payment_data']);

// Return result
echo json_encode($result);