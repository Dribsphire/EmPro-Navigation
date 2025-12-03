<?php
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/OfficeService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();

// Get office_id from query parameter
$officeId = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;

if ($officeId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid office ID'
    ]);
    exit;
}

$officeService = new OfficeService();
$office = $officeService->getOfficeById($officeId);

if (!$office) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Office not found'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'office' => $office
]);
?>

