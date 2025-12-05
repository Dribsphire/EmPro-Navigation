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

if (!isset($data['alert_type']) || !isset($data['description'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$alertType = trim($data['alert_type']);
$description = trim($data['description']);

if (empty($alertType) || empty($description)) {
    echo json_encode(['status' => 'error', 'message' => 'Alert type and description are required']);
    exit;
}

// Map alert types to titles
$alertTitles = [
    'fire' => 'Fire Drill',
    'earthquake' => 'Earthquake Drill',
    'tsunami' => 'Tsunami Drill',
    'lockdown' => 'Lockdown Drill',
    'other' => 'Other Emergency'
];

$title = $alertTitles[$alertType] ?? 'Emergency Drill';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // First, end any existing active alerts
    $stmt = $conn->prepare("UPDATE drill_alerts SET is_active = 0, ended_at = NOW() WHERE is_active = 1");
    $stmt->execute();
    
    // Insert new alert
    $stmt = $conn->prepare("
        INSERT INTO drill_alerts (alert_type, title, description, created_by, is_active) 
        VALUES (:alert_type, :title, :description, :created_by, 1)
    ");
    
    $stmt->bindParam(':alert_type', $alertType);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':created_by', $currentAdmin['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Drill alert sent successfully',
            'alert_id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send alert']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

