<?php
require_once __DIR__ . '/../services/Database.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in (student or guest)
$userType = $_SESSION['user_type'] ?? null;
$userId = $_SESSION['user_id'] ?? null;
$guestId = $_SESSION['guest_id'] ?? null;

if (!$userType || ($userType !== 'student' && $userType !== 'guest')) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'start':
            // Start navigation logging
            $officeId = isset($input['office_id']) ? intval($input['office_id']) : null;
            $officeName = trim($input['office_name'] ?? '');

            // If office_id is not provided, try to find it by name
            if (!$officeId && $officeName) {
                $stmt = $conn->prepare("SELECT office_id FROM offices WHERE office_name = :name LIMIT 1");
                $stmt->bindParam(':name', $officeName);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officeId = $result ? (int)$result['office_id'] : null;
            }

            if (!$officeId) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Office ID or name is required'
                ]);
                exit;
            }

            // Insert navigation log
            $sql = "
                INSERT INTO navigation_logs (user_id, guest_id, office_id, start_time, status, created_at)
                VALUES (:user_id, :guest_id, :office_id, NOW(), 'in_progress', NOW())
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':guest_id', $guestId, PDO::PARAM_INT);
            $stmt->bindParam(':office_id', $officeId, PDO::PARAM_INT);
            $stmt->execute();

            $logId = (int) $conn->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'message' => 'Navigation started',
                'log_id' => $logId
            ]);
            break;

        case 'end':
            // End navigation logging
            $logId = isset($input['log_id']) ? intval($input['log_id']) : 0;
            $status = $input['status'] ?? 'cancelled'; // 'completed' or 'cancelled'

            if ($logId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid log ID'
                ]);
                exit;
            }

            // Verify the log belongs to current user
            $checkSql = "
                SELECT log_id FROM navigation_logs 
                WHERE log_id = :log_id 
                AND ((user_id = :user_id AND :user_id IS NOT NULL) OR (guest_id = :guest_id AND :guest_id IS NOT NULL))
                LIMIT 1
            ";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bindParam(':log_id', $logId, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $checkStmt->bindParam(':guest_id', $guestId, PDO::PARAM_INT);
            $checkStmt->execute();

            if (!$checkStmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized to update this log'
                ]);
                exit;
            }

            // Update navigation log
            $sql = "
                UPDATE navigation_logs 
                SET end_time = NOW(), status = :status 
                WHERE log_id = :log_id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':log_id', $logId, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'status' => 'success',
                'message' => 'Navigation ended'
            ]);
            break;

        case 'reached':
            // Log that user reached the destination (only logs if they're in radius)
            $officeId = isset($input['office_id']) ? intval($input['office_id']) : null;
            $officeName = trim($input['office_name'] ?? '');

            if (!$officeId && $officeName) {
                $stmt = $conn->prepare("SELECT office_id FROM offices WHERE office_name = :name LIMIT 1");
                $stmt->bindParam(':name', $officeName);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $officeId = $result ? (int)$result['office_id'] : null;
            }

            if (!$officeId) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Office ID or name is required'
                ]);
                exit;
            }

            // Log user visit (this is separate from navigation log)
            $sql = "
                INSERT INTO user_visits (user_id, guest_id, office_id, visit_time, created_at)
                VALUES (:user_id, :guest_id, :office_id, NOW(), NOW())
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':guest_id', $guestId, PDO::PARAM_INT);
            $stmt->bindParam(':office_id', $officeId, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'status' => 'success',
                'message' => 'Visit logged'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

