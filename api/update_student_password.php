<?php
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/StudentService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input or form data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$schoolId = trim($input['school_id'] ?? '');
$newPassword = $input['new_password'] ?? '';

if (empty($schoolId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
    exit;
}

if (empty($newPassword) || strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
    exit;
}

try {
    $studentService = new StudentService();
    $result = $studentService->updatePassword($schoolId, $newPassword);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update password'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

