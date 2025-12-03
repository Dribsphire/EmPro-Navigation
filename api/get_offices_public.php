<?php
require_once __DIR__ . '/../services/OfficeService.php';

header('Content-Type: application/json');

$officeService = new OfficeService();
$offices = $officeService->getAllOffices();

// Transform offices data to match frontend format
$formattedOffices = [];
foreach ($offices as $office) {
    // Get content images (gallery) for this office
    $gallery = $officeService->getOfficeContentImages($office['office_id']);
    
    // Default marker image if none exists
    $markerImage = $office['marker_image'] ?? 'default_marker.png';
    
    // Format the office data for frontend
    $formattedOffices[] = [
        'office_id' => (int)$office['office_id'],
        'name' => $office['office_name'],
        'description' => $office['description'] ?? '',
        'lngLat' => [
            (float)$office['location_lng'],
            (float)$office['location_lat']
        ],
        'image' => $markerImage,
        'popup' => $office['description'] ?? $office['office_name'],
        'iconSize' => [40, 40],
        'gallery' => $gallery,
        'category_id' => (int)$office['category_id'],
        'category_name' => $office['category_name'] ?? '',
        'radius' => 5 
    ];
}

echo json_encode([
    'status' => 'success',
    'offices' => $formattedOffices
]);
?>

