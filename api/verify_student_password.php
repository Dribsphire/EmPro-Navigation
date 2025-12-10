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
    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? '';
    
    if (empty($password)) {
        throw new Exception('Password is required.');
    }
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Incorrect password.');
    }
    
    // Password is correct
    echo json_encode([
        'status' => 'success',
        'message' => 'Password verified successfully.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

