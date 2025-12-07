<?php
require_once __DIR__ . '/../../services/Auth.php';
require_once __DIR__ . '/../../services/Database.php';

$auth = new Auth();
$auth->requireAdmin();

$database = new Database();
$conn = $database->getConnection();

// Pagination settings
$itemsPerPage = 15;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter settings
$timeFilter = $_GET['time'] ?? 'month';
$roleFilter = $_GET['role'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build date filter condition (with alias for JOINed queries)
$dateCondition = '';
$dateConditionSimple = ''; // Without alias for simple queries
switch ($timeFilter) {
    case 'week':
        $dateCondition = "AND nl.start_time >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $dateConditionSimple = "AND start_time >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $dateCondition = "AND nl.start_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $dateConditionSimple = "AND start_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'year':
        $dateCondition = "AND nl.start_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $dateConditionSimple = "AND start_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case 'all':
    default:
        $dateCondition = '';
        $dateConditionSimple = '';
        break;
}

// Build role filter condition
$roleCondition = '';
if ($roleFilter === 'student') {
    $roleCondition = "AND nl.user_id IS NOT NULL";
} elseif ($roleFilter === 'guest') {
    $roleCondition = "AND nl.guest_id IS NOT NULL";
}

// Build search condition
$searchCondition = '';
$searchParams = [];
if (!empty($searchQuery)) {
    $searchCondition = "AND (
        o.office_name LIKE :search 
        OR s.full_name LIKE :search 
        OR g.full_name LIKE :search
    )";
    $searchParams[':search'] = '%' . $searchQuery . '%';
}

// Get total count for pagination (only completed status)
$countSql = "
    SELECT COUNT(*) as total
    FROM navigation_logs nl
    LEFT JOIN offices o ON nl.office_id = o.office_id
    LEFT JOIN students s ON nl.user_id = s.user_id
    LEFT JOIN guests g ON nl.guest_id = g.guest_id
    WHERE 1=1 AND nl.status = 'completed' $dateCondition $roleCondition $searchCondition
";

$countStmt = $conn->prepare($countSql);
foreach ($searchParams as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalLogs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalLogs / $itemsPerPage);

// Fetch navigation logs (only completed status)
$sql = "
    SELECT 
        nl.log_id,
        nl.start_time,
        nl.end_time,
        nl.status,
        nl.user_id,
        nl.guest_id,
        o.office_id,
        o.office_name,
        oi.image_path as office_image,
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
        g.reason as guest_reason
    FROM navigation_logs nl
    LEFT JOIN offices o ON nl.office_id = o.office_id
    LEFT JOIN office_images oi ON o.office_id = oi.office_id AND oi.is_primary = 1
    LEFT JOIN students s ON nl.user_id = s.user_id
    LEFT JOIN guests g ON nl.guest_id = g.guest_id
    WHERE 1=1 AND nl.status = 'completed' $dateCondition $roleCondition $searchCondition
    ORDER BY nl.start_time DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($searchParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logs & Reports</title>
  <link rel="icon" type="image/png" href="../images/CHMSU.png">
  <link rel="stylesheet" href="../css/admin_Style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding: 0 1rem;
    }

    .filter-form {
      background: var(--base-clr);
      border: 1px solid var(--line-clr);
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .filters-container {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      min-width: 200px;
      flex: 1;
    }

    .filter-group label {
      font-weight: 500;
      color: var(--secondary-text-clr);
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .select-wrapper {
      position: relative;
    }

    .select-wrapper i {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--secondary-text-clr);
      pointer-events: none;
    }

    .form-control {
      width: 100%;
      padding: 0.65rem 1rem;
      border: 1px solid var(--line-clr);
      border-radius: 6px;
      background-color: var(--hover-clr);
      color: var(--text-clr);
      font-size: 0.95rem;
      appearance: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--accent-clr);
      box-shadow: 0 0 0 2px rgba(94, 99, 255, 0.2);
    }

    .search-group {
      display: flex;
      gap: 1rem;
      flex: 2;
      min-width: 300px;
    }

    .search-container {
      position: relative;
      display: flex;
      align-items: center;
      flex: 1;
    }

    .search-container i {
      position: absolute;
      left: 12px;
      color: var(--secondary-text-clr);
    }

    .search-input {
      width: 100%;
      padding: 0.65rem 1rem 0.65rem 2.5rem;
      border: 1px solid var(--line-clr);
      border-radius: 6px;
      background-color: var(--hover-clr);
      color: var(--text-clr);
      font-size: 0.95rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--accent-clr);
      box-shadow: 0 0 0 2px rgba(94, 99, 255, 0.2);
    }

    .btn-apply {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0 1.5rem;
      height: 44px;
      background-color: var(--accent-clr);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.95rem;
      font-weight: 500;
      transition: background-color 0.2s, transform 0.1s;
      white-space: nowrap;
    }

    .btn-apply:hover {
      background-color: #4a4fff;
    }

    .btn-apply:active {
      transform: translateY(1px);
    }

    @media (max-width: 768px) {
      .filters-container {
        flex-direction: column;
        gap: 1rem;
      }
      
      .filter-group {
        width: 100%;
      }
      
      .search-group {
        flex-direction: column;
        width: 100%;
        min-width: auto;
      }
      
      .btn-apply {
        width: 100%;
        height: 44px;
      }
    }

    .badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
      text-transform: capitalize;
    }

    .badge-student {
      background-color: #e3f2fd;
      color: #1976d2;
    }

    .badge-guest {
      background-color: #e8f5e9;
      color: #388e3c;
    }

    .badge-completed {
      background-color: #e8f5e9;
      color: #388e3c;
    }

    .badge-cancelled {
      background-color: #ffebee;
      color: #c62828;
    }

    .badge-in_progress {
      background-color: #fff3e0;
      color: #ef6c00;
    }

    .search-container input {
      padding-left: 40px;
      width: 100%;
    }

    .table-container {
      background: #2b2c33;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--line-clr);
      vertical-align: middle;
    }

    th {
      background-color: var(--accent-clr);
      color: white;
      font-weight: 600;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background-color: var(--hover-clr);
    }

    tr.guest-row:hover {
      background-color: var(--hover-clr);
      opacity: 0.9;
    }

    .office-cell {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      min-width: 180px;
    }

    .office-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }

    .pagination a, .pagination span {
      padding: 0.5rem 1rem;
      border: 1px solid var(--line-clr);
      background: var(--base-clr);
      color: var(--text-clr);
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .pagination a.active, .pagination span.active {
      background: var(--accent-clr);
      color: white;
      border-color: var(--accent-clr);
    }

    .pagination a:hover:not(.active) {
      background: var(--hover-clr);
    }

    .pagination span.disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-export {
      background: #28a745;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: background 0.2s;
    }

    .btn-export:hover {
      background: #218838;
    }

    .stats-summary {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }

    .stat-card {
      background: var(--base-clr);
      border: 1px solid var(--line-clr);
      border-radius: 8px;
      padding: 1rem 1.5rem;
      min-width: 150px;
    }

    .stat-card h3 {
      margin: 0;
      font-size: 1.5rem;
      color: var(--accent-clr);
    }

    .stat-card p {
      margin: 0.25rem 0 0;
      color: var(--secondary-text-clr);
      font-size: 0.9rem;
    }

    .no-data {
      text-align: center;
      padding: 3rem;
      color: var(--secondary-text-clr);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: var(--base-clr);
      border: 1px solid var(--line-clr);
      border-radius: 12px;
      width: 100%;
      max-width: 400px;
      padding: 1.5rem;
    }

    .modal-header {
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-clr);
    }

    .export-options {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .export-btn {
      padding: 0.75rem;
      border-radius: 6px;
      border: 1px solid var(--line-clr);
      background: var(--base-clr);
      color: var(--text-clr);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 0.2s;
    }

    .export-btn:hover {
      background: var(--hover-clr);
    }

    .export-btn.pdf {
      border-color: #dc3545;
      color: #dc3545;
    }

    .export-btn.csv {
      border-color: #17a2b8;
      color: #17a2b8;
    }

    .export-btn i {
      font-size: 1.25rem;
    }
  </style>
</head>
<body>
  <?php include 'admin_nav.php'; ?>
  
  <main>
    <div class="page-header">
      <h1>Logs & Reports</h1>
      <button class="btn-export" onclick="openExportModal()">
        <i class="fas fa-file-export"></i> Export
      </button>
    </div>

    <form method="GET" id="filterForm" class="filter-form">
      <div class="filters-container">
        <div class="filter-group">
          <label for="timeFilter">Filter by</label>
          <div class="select-wrapper">
            <select id="timeFilter" name="time" class="form-control" onchange="document.getElementById('filterForm').submit()">
              <option value="week" <?= $timeFilter === 'week' ? 'selected' : '' ?>>This Week</option>
              <option value="month" <?= $timeFilter === 'month' ? 'selected' : '' ?>>This Month</option>
              <option value="year" <?= $timeFilter === 'year' ? 'selected' : '' ?>>This Year</option>
              <option value="all" <?= $timeFilter === 'all' ? 'selected' : '' ?>>All Time</option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>

        <div class="filter-group">
          <label for="roleFilter">Role</label>
          <div class="select-wrapper">
            <select id="roleFilter" name="role" class="form-control" onchange="document.getElementById('filterForm').submit()">
              <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All Roles</option>
              <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Students</option>
              <option value="guest" <?= $roleFilter === 'guest' ? 'selected' : '' ?>>Guests</option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>

        <div class="search-group">
          <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" 
                   id="searchInput" 
                   name="search" 
                   class="search-input" 
                   placeholder="Search logs..." 
                   value="<?= htmlspecialchars($searchQuery) ?>">
          </div>
          <button type="submit" class="btn-apply">
            <i class="fas fa-filter"></i> Apply
          </button>
        </div>
      </div>
    </form>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Role</th>
            <th>Office</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="logsTableBody">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="7" class="no-data">
                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                No navigation logs found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $index => $log): ?>
              <tr class="<?= $log['user_role'] === 'Guest' ? 'guest-row' : '' ?>" 
                  <?php if ($log['user_role'] === 'Guest'): ?>
                    data-guest-name="<?= htmlspecialchars($log['user_name'], ENT_QUOTES) ?>"
                    data-guest-reason="<?= htmlspecialchars($log['guest_reason'] ?? '', ENT_QUOTES) ?>"
                    style="cursor: pointer;"
                  <?php endif; ?>>
                <td><?= $offset + $index + 1 ?></td>
                <td><?= htmlspecialchars($log['user_name']) ?></td>
                <td>
                  <span class="badge badge-<?= strtolower($log['user_role']) ?>">
                    <?= htmlspecialchars($log['user_role']) ?>
                  </span>
                </td>
                <td class="office-cell">
                  <?php 
                    $officeImage = $log['office_image'] ? '../../' . $log['office_image'] : '../../buildings/default.png';
                  ?>
                  <img src="<?= htmlspecialchars($officeImage) ?>" alt="<?= htmlspecialchars($log['office_name'] ?? 'Unknown') ?>" class="office-avatar" onerror="this.src='../../buildings/default.png'">
                  <span><?= htmlspecialchars($log['office_name'] ?? 'Unknown Office') ?></span>
                </td>
                <td><?= date('Y-m-d', strtotime($log['start_time'])) ?></td>
                <td><?= date('H:i:s', strtotime($log['start_time'])) ?></td>
                <td>
                  <span class="badge badge-<?= $log['status'] ?>">
                    <?= ucfirst(str_replace('_', ' ', $log['status'])) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            $queryString = $queryString ? '&' . $queryString : '';
          ?>
          
          <?php if ($currentPage > 1): ?>
            <a href="?page=1<?= $queryString ?>">First</a>
            <a href="?page=<?= $currentPage - 1 ?><?= $queryString ?>">Previous</a>
          <?php else: ?>
            <span class="disabled">First</span>
            <span class="disabled">Previous</span>
          <?php endif; ?>

          <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
          ?>
            <a href="?page=<?= $i ?><?= $queryString ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?><?= $queryString ?>">Next</a>
            <a href="?page=<?= $totalPages ?><?= $queryString ?>">Last</a>
          <?php else: ?>
            <span class="disabled">Next</span>
            <span class="disabled">Last</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Export Modal -->
  <div id="exportModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Export Data</h2>
        <button class="close-modal" onclick="closeModal('exportModal')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="exportMonth">Select Month:</label>
          <input type="month" id="exportMonth" value="<?= date('Y-m') ?>" style="width: 91%;
          padding: 0.65rem 1rem;
          border: 1px solid var(--line-clr);
          border-radius: 6px;
          background-color: var(--hover-clr);
          color: var(--text-clr);
          font-size: 0.95rem;
          appearance: none;
          transition: border-color 0.2s, box-shadow 0.2s;">
        </div>
        <div class="export-options">
          <button class="export-btn csv" onclick="exportData('csv')">
            <i class="fas fa-file-csv"></i> Export as CSV
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Guest Reason Modal -->
  <div id="guestReasonModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h2>Guest Information</h2>
        <button class="close-modal" onclick="closeModal('guestReasonModal')">&times;</button>
      </div>
      <div class="modal-body">
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: var(--secondary-text-clr); font-weight: 500;">Guest Name:</label>
          <p id="guestName" style="margin: 0; padding: 0.5rem; background: var(--hover-clr); border-radius: 6px; color: var(--text-clr);"></p>
        </div>
        <div>
          <label style="display: block; margin-bottom: 0.5rem; color: var(--secondary-text-clr); font-weight: 500;">Reason for Visit:</label>
          <p id="guestReason" style="margin: 0; padding: 1rem; background: var(--hover-clr); border-radius: 6px; color: var(--text-clr); white-space: pre-wrap; word-wrap: break-word; min-height: 100px; max-height: 300px; overflow-y: auto;"></p>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openExportModal() {
      document.getElementById('exportModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function exportData(format) {
      const month = document.getElementById('exportMonth').value;
      
      // Redirect to export API
      window.location.href = `../../api/export_logs.php?format=${format}&month=${month}`;
      closeModal('exportModal');
    }

    // Show guest reason modal
    function showGuestReasonModal(guestName, guestReason) {
      document.getElementById('guestName').textContent = guestName;
      document.getElementById('guestReason').textContent = guestReason || 'No reason provided.';
      document.getElementById('guestReasonModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    };

    // Handle image loading errors
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('img.office-avatar').forEach(img => {
        img.onerror = function() {
          this.src = '../../buildings/default.png';
        };
      });

      // Add click handlers for guest rows
      document.querySelectorAll('tr.guest-row').forEach(row => {
        row.addEventListener('click', function() {
          const guestName = this.getAttribute('data-guest-name') || 'Unknown Guest';
          const guestReason = this.getAttribute('data-guest-reason') || 'No reason provided.';
          showGuestReasonModal(guestName, guestReason);
        });
      });
    });
  </script>
</body>
</html>
