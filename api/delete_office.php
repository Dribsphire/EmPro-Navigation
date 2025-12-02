<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/OfficeService.php';

ob_clean();
header('Content-Type: application/json');

try {
    $auth = new Auth();
    $auth->requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $officeId = intval($_POST['office_id'] ?? 0);
    
    if ($officeId === 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid office ID']);
        exit;
    }
    
    $officeService = new OfficeService();
    $result = $officeService->deleteOffice($officeId);
    
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to delete office: ' . $e->getMessage()
    ]);
}
ob_end_flush();
?>

