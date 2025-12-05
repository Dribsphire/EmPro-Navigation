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
    $conn->beginTransaction();
    
    $updated = false;
    $profilePicturePath = null;
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Invalid file type. Only images (JPEG, PNG, GIF, WebP) are allowed.');
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Validate image dimensions
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file.');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../public/images/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Delete old profile picture if exists
        $oldPicStmt = $conn->prepare("SELECT profile_picture FROM students WHERE user_id = :user_id");
        $oldPicStmt->execute([':user_id' => $userId]);
        $oldPic = $oldPicStmt->fetchColumn();
        
        if ($oldPic && file_exists(__DIR__ . '/../public/' . $oldPic)) {
            @unlink(__DIR__ . '/../public/' . $oldPic);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'student_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $profilePicturePath = 'images/profiles/' . $filename;
            
            // Update profile picture in database
            $updatePicStmt = $conn->prepare("UPDATE students SET profile_picture = :profile_picture WHERE user_id = :user_id");
            $updatePicStmt->execute([
                ':profile_picture' => $profilePicturePath,
                ':user_id' => $userId
            ]);
            $updated = true;
        } else {
            throw new Exception('Failed to upload profile picture.');
        }
    }
    
    // Handle email update
    if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
        $email = trim($_POST['email']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }
        
        // Check if email is already in use by another user
        $checkEmailStmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id LIMIT 1");
        $checkEmailStmt->execute([':email' => $email, ':user_id' => $userId]);
        if ($checkEmailStmt->fetch()) {
            throw new Exception('Email address is already in use.');
        }
        
        // Update email in users table
        $updateEmailStmt = $conn->prepare("UPDATE users SET email = :email, updated_at = NOW() WHERE user_id = :user_id");
        $updateEmailStmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
        
        // Also update in students table if it exists there
        $updateStudentEmailStmt = $conn->prepare("UPDATE students SET email = :email, updated_at = NOW() WHERE user_id = :user_id");
        $updateStudentEmailStmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
        
        $updated = true;
    }
    
    // Handle phone update
    if (isset($_POST['phone'])) {
        $phone = trim($_POST['phone']);
        
        // Phone can be empty, but if provided, validate format (basic validation)
        if (!empty($phone) && strlen($phone) > 20) {
            throw new Exception('Phone number is too long.');
        }
        
        // Update phone in students table
        $updatePhoneStmt = $conn->prepare("UPDATE students SET phone = :phone, updated_at = NOW() WHERE user_id = :user_id");
        $updatePhoneStmt->execute([
            ':phone' => !empty($phone) ? $phone : null,
            ':user_id' => $userId
        ]);
        
        $updated = true;
    }
    
    if (!$updated) {
        throw new Exception('No fields to update.');
    }
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully.',
        'profile_picture' => $profilePicturePath
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

