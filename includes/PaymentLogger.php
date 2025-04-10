<?php
namespace HealthyBite;

class PaymentLogger {
    private $conn;
    private $logTable = 'payment_logs';
    private $defaultDateRange = 30; // Default date range for analytics in days

    public function __construct($db) {
        $this->conn = $db;
        $this->initializeLogTable();
    }

    private function initializeLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->logTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            message TEXT,
            transaction_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )";
        $this->conn->query($sql);
    }

    public function logPaymentEvent($orderId, $eventType, $status, $message = '', $transactionData = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->logTable} 
            (order_id, event_type, status, message, transaction_data) 
            VALUES (?, ?, ?, ?, ?)"
        );

        $jsonData = $transactionData ? json_encode($transactionData) : null;
        $stmt->execute([
            $orderId,
            $eventType,
            $status,
            $message,
            $jsonData
        ]);
    }

    public function getPaymentLogs($orderId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->logTable} 
            WHERE order_id = ? 
            ORDER BY created_at DESC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRecentPaymentLogs($limit = 100) {
        $stmt = $this->conn->prepare(
            "SELECT pl.*, o.user_id, u.username 
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            JOIN users u ON o.user_id = u.id
            ORDER BY pl.created_at DESC
            LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserPaymentAnalytics($userId, $days = null) {
        $days = $days ?? $this->defaultDateRange;
        $stmt = $this->conn->prepare(
            "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN pl.status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN pl.status = 'error' THEN 1 ELSE 0 END) as failed_transactions,
                AVG(CASE WHEN pl.status = 'success' THEN p.amount ELSE 0 END) as avg_transaction_amount,
                MAX(CASE WHEN pl.status = 'success' THEN p.amount ELSE 0 END) as highest_transaction,
                MIN(CASE WHEN pl.status = 'success' THEN p.amount ELSE NULL END) as lowest_transaction,
                COUNT(DISTINCT DATE(pl.created_at)) as active_days,
                COUNT(DISTINCT pl.event_type) as unique_event_types,
                MAX(pl.created_at) as last_transaction_date,
                (SELECT COUNT(*) 
                 FROM {$this->logTable} pl2 
                 JOIN orders o2 ON pl2.order_id = o2.id 
                 WHERE o2.user_id = o.user_id 
                 AND pl2.status = 'error' 
                 AND pl2.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as errors_last_24h
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            JOIN payments p ON pl.order_id = p.order_id
            WHERE o.user_id = ? AND pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$userId, $days]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPaymentPatternAnalysis($userId, $days = null) {
        $days = $days ?? $this->defaultDateRange;
        $stmt = $this->conn->prepare(
            "SELECT 
                HOUR(pl.created_at) as hour_of_day,
                DAYNAME(pl.created_at) as day_of_week,
                COUNT(*) as transaction_count,
                AVG(CASE WHEN pl.status = 'success' THEN p.amount ELSE 0 END) as avg_amount,
                SUM(CASE WHEN pl.status = 'success' THEN 1 ELSE 0 END) as success_count
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            JOIN payments p ON pl.order_id = p.order_id
            WHERE o.user_id = ? 
            AND pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY HOUR(pl.created_at), DAYNAME(pl.created_at)
            ORDER BY day_of_week, hour_of_day"
        );
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDetailedFailureAnalysis($days = null) {
        $days = $days ?? $this->defaultDateRange;
        $stmt = $this->conn->prepare(
            "SELECT 
                event_type,
                message,
                COUNT(*) as occurrence_count,
                COUNT(DISTINCT o.user_id) as affected_users,
                MIN(pl.created_at) as first_occurrence,
                MAX(pl.created_at) as last_occurrence,
                GROUP_CONCAT(DISTINCT p.currency) as currencies_affected,
                AVG(p.amount) as avg_amount_affected,
                (COUNT(*) / ? * 100) as error_rate_percentage
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            JOIN payments p ON pl.order_id = p.order_id
            WHERE pl.status = 'error'
                AND pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY event_type, message
            ORDER BY occurrence_count DESC"
        );
        $stmt->execute([$days, $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getFailureAnalysis($days = null) {
        $days = $days ?? $this->defaultDateRange;
        $stmt = $this->conn->prepare(
            "SELECT 
                event_type,
                message,
                COUNT(*) as occurrence_count,
                COUNT(DISTINCT o.user_id) as affected_users
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            WHERE pl.status = 'error'
                AND pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY event_type, message
            ORDER BY occurrence_count DESC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaymentTrends($days = null) {
        $days = $days ?? $this->defaultDateRange;
        $stmt = $this->conn->prepare(
            "SELECT 
                DATE(pl.created_at) as date,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN pl.status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN pl.status = 'error' THEN 1 ELSE 0 END) as failed_transactions,
                AVG(CASE WHEN pl.status = 'success' THEN p.amount ELSE 0 END) as avg_amount
            FROM {$this->logTable} pl
            JOIN payments p ON pl.order_id = p.order_id
            WHERE pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(pl.created_at)
            ORDER BY date"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function exportPaymentReport($startDate, $endDate) {
        $stmt = $this->conn->prepare(
            "SELECT 
                pl.created_at,
                pl.order_id,
                u.username,
                pl.event_type,
                pl.status,
                pl.message,
                p.amount,
                p.currency
            FROM {$this->logTable} pl
            JOIN orders o ON pl.order_id = o.id
            JOIN users u ON o.user_id = u.id
            JOIN payments p ON pl.order_id = p.order_id
            WHERE pl.created_at BETWEEN ? AND ?
            ORDER BY pl.created_at DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}