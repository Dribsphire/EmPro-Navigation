<?php
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/Database.php';

$auth = new Auth();
$auth->requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$format = $_GET['format'] ?? 'csv';
$month = $_GET['month'] ?? date('Y-m');

// Parse month
$year = substr($month, 0, 4);
$monthNum = substr($month, 5, 2);
$startDate = "$year-$monthNum-01";
$endDate = date('Y-m-t', strtotime($startDate));

// Fetch navigation logs for the selected month
$sql = "
    SELECT 
        nl.log_id,
        nl.start_time,
        nl.end_time,
        nl.status,
        o.office_name,
        CASE 
            WHEN nl.user_id IS NOT NULL THEN s.full_name
            WHEN nl.guest_id IS NOT NULL THEN g.full_name
            ELSE 'Unknown'
        END as user_name,
        CASE 
            WHEN nl.user_id IS NOT NULL THEN 'Student'
            WHEN nl.guest_id IS NOT NULL THEN 'Guest'
            ELSE 'Unknown'
        END as user_role,
        CASE 
            WHEN nl.user_id IS NOT NULL THEN st.school_id
            ELSE NULL
        END as school_id
    FROM navigation_logs nl
    LEFT JOIN offices o ON nl.office_id = o.office_id
    LEFT JOIN students s ON nl.user_id = s.user_id
    LEFT JOIN students st ON nl.user_id = st.user_id
    LEFT JOIN guests g ON nl.guest_id = g.guest_id
    WHERE nl.start_time >= :start_date AND nl.start_time <= :end_date
    ORDER BY nl.start_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':start_date' => $startDate . ' 00:00:00',
    ':end_date' => $endDate . ' 23:59:59'
]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="navigation_logs_' . $month . '.csv"');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write header row
    fputcsv($output, [
        'Log ID',
        'User Name',
        'School ID',
        'Role',
        'Office',
        'Start Date',
        'Start Time',
        'End Date',
        'End Time',
        'Status'
    ]);

    // Write data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['log_id'],
            $log['user_name'],
            $log['school_id'] ?? 'N/A',
            $log['user_role'],
            $log['office_name'] ?? 'Unknown',
            date('Y-m-d', strtotime($log['start_time'])),
            date('H:i:s', strtotime($log['start_time'])),
            $log['end_time'] ? date('Y-m-d', strtotime($log['end_time'])) : 'N/A',
            $log['end_time'] ? date('H:i:s', strtotime($log['end_time'])) : 'N/A',
            ucfirst(str_replace('_', ' ', $log['status']))
        ]);
    }

    fclose($output);
    exit;
}

// If format not supported
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unsupported export format']);
?>

