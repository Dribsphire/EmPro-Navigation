<?php
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/OfficeService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();

$officeService = new OfficeService();
$categories = $officeService->getCategories();

echo json_encode([
    'status' => 'success',
    'categories' => $categories
]);
?>

