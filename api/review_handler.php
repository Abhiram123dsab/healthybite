<?php
require_once '../includes/auth.php';
require_once '../config/db_config.php';

header('Content-Type: application/json');

class ReviewHandler {
    private $conn;
    private $userId;

    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                return $this->getReviews();
            case 'POST':
                return $this->submitReview();
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }

    private function getReviews() {
        $itemId = filter_input(INPUT_GET, 'itemId', FILTER_SANITIZE_NUMBER_INT);
        
        if (!$itemId) {
            http_response_code(400);
            return ['error' => 'Item ID is required'];
        }

        $stmt = $this->conn->prepare(
            "SELECT r.rating, r.review, r.created_at, u.username 
             FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             WHERE r.item_id = ? 
             ORDER BY r.created_at DESC"
        );

        $stmt->bind_param('i', $itemId);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $reviews = [];
            
            while ($row = $result->fetch_assoc()) {
                $reviews[] = [
                    'rating' => (int)$row['rating'],
                    'review' => htmlspecialchars($row['review']),
                    'date' => date('Y-m-d', strtotime($row['created_at'])),
                    'username' => htmlspecialchars($row['username'])
                ];
            }
            
            return $reviews;
        }

        http_response_code(500);
        return ['error' => 'Failed to fetch reviews'];
    }

    private function submitReview() {
        if (!$this->userId) {
            http_response_code(401);
            return ['error' => 'User must be logged in to submit reviews'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateReviewData($data)) {
            http_response_code(400);
            return ['error' => 'Invalid review data'];
        }

        // Check if user has already reviewed this item
        $stmt = $this->conn->prepare(
            "SELECT id FROM reviews 
             WHERE user_id = ? AND item_id = ?"
        );
        $stmt->bind_param('ii', $this->userId, $data['itemId']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            return ['error' => 'You have already reviewed this item'];
        }

        // Insert new review
        $stmt = $this->conn->prepare(
            "INSERT INTO reviews (user_id, item_id, rating, review) 
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'iiis',
            $this->userId,
            $data['itemId'],
            $data['rating'],
            $data['review']
        );

        if ($stmt->execute()) {
            // Update item's average rating
            $this->updateItemAverageRating($data['itemId']);
            
            return [
                'success' => true,
                'message' => 'Review submitted successfully'
            ];
        }

        http_response_code(500);
        return ['error' => 'Failed to submit review'];
    }

    private function validateReviewData($data) {
        return isset($data['itemId']) &&
               isset($data['rating']) &&
               isset($data['review']) &&
               is_numeric($data['itemId']) &&
               is_numeric($data['rating']) &&
               $data['rating'] >= 1 &&
               $data['rating'] <= 5 &&
               strlen($data['review']) >= 10 &&
               strlen($data['review']) <= 500;
    }

    private function updateItemAverageRating($itemId) {
        $stmt = $this->conn->prepare(
            "UPDATE menu_items 
             SET average_rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE item_id = ?
             ) 
             WHERE id = ?"
        );

        $stmt->bind_param('ii', $itemId, $itemId);
        $stmt->execute();
    }
}

// Initialize database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get current user ID from session
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle the request
$handler = new ReviewHandler($conn, $userId);
$response = $handler->handleRequest();

echo json_encode($response);
$conn->close();