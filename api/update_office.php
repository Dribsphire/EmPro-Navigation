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
$officeId = intval($_POST['office_id'] ?? 0);
$officeName = trim($_POST['office_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId = intval($_POST['category_id'] ?? 0);

// Validation
if ($officeId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid office ID']);
    exit;
}

if (empty($officeName) || $categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Prepare data
$data = [
    'office_id' => $officeId,
    'office_name' => $officeName,
    'description' => $description,
    'category_id' => $categoryId
];

// Handle marker image (optional)
$markerImage = null;
if (isset($_FILES['marker_image']) && $_FILES['marker_image']['error'] === UPLOAD_ERR_OK) {
    $markerImage = $_FILES['marker_image'];
}

// Handle content images (optional)
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

// Handle deleted images
$deletedImages = [];
if (isset($_POST['deleted_images'])) {
    $deletedImagesJson = $_POST['deleted_images'];
    if (is_string($deletedImagesJson)) {
        $deletedImages = json_decode($deletedImagesJson, true) ?: [];
    }
}

// Update office
$result = $officeService->updateOffice(
    $data,
    $markerImage,
    $contentImages,
    $deletedImages
);

if ($result['status'] === 'success') {
    http_response_code(200);
} else {
    http_response_code(500);
}

echo json_encode($result);
?>

