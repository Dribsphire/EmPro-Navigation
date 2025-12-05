<?php
require_once __DIR__ . '/../services/Database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check for active drill alert
    $stmt = $conn->prepare("
        SELECT 
            alert_id,
            alert_type,
            title,
            description,
            created_at
        FROM drill_alerts 
        WHERE is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute();
    $alert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alert) {
        echo json_encode([
            'status' => 'success',
            'has_alert' => true,
            'alert' => $alert
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'has_alert' => false,
            'alert' => null
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'has_alert' => false
    ]);
}

