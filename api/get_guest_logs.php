<?php
require_once __DIR__ . '/../services/Database.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in as guest
$userType = $_SESSION['user_type'] ?? null;
$guestId = $_SESSION['guest_id'] ?? null;

if (!$userType || $userType !== 'guest' || !$guestId) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in as guest.'
    ]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Fetch navigation logs for the current guest
    // Only show completed and cancelled logs (not in_progress)
    $sql = "
        SELECT 
            nl.log_id,
            nl.office_id,
            o.office_name,
            oi.image_path,
            nl.start_time,
            nl.end_time,
            nl.status,
            nl.created_at
        FROM navigation_logs nl
        INNER JOIN offices o ON o.office_id = nl.office_id
        LEFT JOIN office_images oi ON oi.office_id = o.office_id AND oi.is_primary = 1
        WHERE nl.guest_id = :guest_id 
        AND nl.status IN ('completed', 'cancelled')
        ORDER BY nl.end_time DESC, nl.start_time DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':guest_id', $guestId, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format the image path
        $imagePath = null;
        if ($row['image_path']) {
            if (strpos($row['image_path'], 'buildings/') === 0 || strpos($row['image_path'], 'building_content/') === 0) {
                $imagePath = '../../' . $row['image_path'];
            } else {
                $imagePath = '../../buildings/' . $row['image_path'];
            }
        } else {
            // Default image if no marker image found
            $imagePath = '../../buildings/default.png';
        }
        
        // Format date and time
        $endTime = $row['end_time'] ? new DateTime($row['end_time']) : new DateTime($row['start_time']);
        $date = $endTime->format('n/j/y'); // Format: 11/2/25
        $time = $endTime->format('g:ia'); // Format: 2:32pm
        
        $logs[] = [
            'log_id' => (int)$row['log_id'],
            'office_id' => (int)$row['office_id'],
            'office_name' => $row['office_name'],
            'image_path' => $imagePath,
            'date' => $date,
            'time' => $time,
            'status' => $row['status'],
            'end_time' => $row['end_time']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'logs' => $logs,
        'count' => count($logs)
    ]);

} catch (PDOException $e) {
    error_log('Error fetching guest logs: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch logs: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error fetching guest logs: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch logs'
    ]);
}

