<?php
require_once __DIR__ . '/../services/Database.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get input data
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword)) {
        throw new Exception('Current password is required.');
    }
    
    if (empty($newPassword)) {
        throw new Exception('New password is required.');
    }
    
    if (strlen($newPassword) < 8) {
        throw new Exception('New password must be at least 8 characters long.');
    }
    
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New password and confirmation password do not match.');
    }
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        throw new Exception('Current password is incorrect.');
    }
    
    // Check if new password is the same as current password
    if (password_verify($newPassword, $user['password'])) {
        throw new Exception('New password must be different from current password.');
    }
    
    // Hash new password
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password
    $updateStmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id");
    $updateStmt->execute([
        ':password' => $passwordHash,
        ':user_id' => $userId
    ]);
    
    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Failed to update password.');
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Password changed successfully.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

