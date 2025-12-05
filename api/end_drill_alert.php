<?php
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();

$currentAdmin = $auth->getCurrentUser();
if (!$currentAdmin) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['alert_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Alert ID is required']);
    exit;
}

$alertId = intval($data['alert_id']);

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // End the alert
    $stmt = $conn->prepare("
        UPDATE drill_alerts 
        SET is_active = 0, ended_at = NOW() 
        WHERE alert_id = :alert_id AND is_active = 1
    ");
    
    $stmt->bindParam(':alert_id', $alertId);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Drill alert ended successfully'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Alert not found or already ended']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

