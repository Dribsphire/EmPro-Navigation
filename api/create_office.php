<?php
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/OfficeService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();
$currentAdmin = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$officeService = new OfficeService();

// Get form data
$officeName = trim($_POST['office_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$locationLat = floatval($_POST['location_lat'] ?? 0);
$locationLng = floatval($_POST['location_lng'] ?? 0);
$categoryId = intval($_POST['category_id'] ?? 0);

// Validation
if (empty($officeName) || $locationLat === 0 || $locationLng === 0 || $categoryId === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Check marker image
if (!isset($_FILES['marker_image']) || $_FILES['marker_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Marker image is required']);
    exit;
}

// Prepare data
$data = [
    'office_name' => $officeName,
    'description' => $description,
    'location_lat' => $locationLat,
    'location_lng' => $locationLng,
    'category_id' => $categoryId,
    'created_by' => $currentAdmin['admin_id'] ?? $currentAdmin['user_id']
];

// Handle content images
$contentImages = [];
if (isset($_FILES['content_images']) && is_array($_FILES['content_images']['tmp_name'])) {
    $fileCount = count($_FILES['content_images']['tmp_name']);
    for ($i = 0; $i < $fileCount && $i < 4; $i++) {
        if ($_FILES['content_images']['error'][$i] === UPLOAD_ERR_OK) {
            $contentImages[] = [
                'name' => $_FILES['content_images']['name'][$i],
                'type' => $_FILES['content_images']['type'][$i],
                'tmp_name' => $_FILES['content_images']['tmp_name'][$i],
                'error' => $_FILES['content_images']['error'][$i],
                'size' => $_FILES['content_images']['size'][$i]
            ];
        }
    }
}

// Create office
$result = $officeService->createOffice(
    $data,
    $_FILES['marker_image'],
    $contentImages
);

if ($result['status'] === 'success') {
    http_response_code(201);
} else {
    http_response_code(500);
}

echo json_encode($result);
?>

