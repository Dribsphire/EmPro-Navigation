<?php
session_start();
// Add your session check here if needed
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
      flex-wrap: wrap;
      gap: 1rem;
    }

    .filters-container {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .filter-group label {
      white-space: nowrap;
      color: var(--text-clr);
    }

    .search-container {
      position: relative;
      max-width: 300px;
      width: 100%;
    }
    .badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: capitalize;
  }
  .badge[data-role="Student"] {
    background-color: #e3f2fd;
    color: #1976d2;
  }

  .badge[data-role="Guest"] {
    background-color: #e8f5e9;
    color: #388e3c;
  }
    .search-container i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--secondary-text-clr);
    }

    .search-container input {
      padding-left: 40px;
      width: 100%;
    }

    .table-container {
      background: #2b2c33;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Add this for consistent column widths */
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--line-clr);
      vertical-align: middle; /* Ensure vertical alignment in cells */
    }

    th {
      background-color: var(--accent-clr);
      color: white;
      font-weight: 600;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .office-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 180px; /* Set a minimum width for office column */
  }

    .office-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0; /* Prevent image from shrinking */
  }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }

    .pagination button {
      padding: 0.5rem 1rem;
      border: 1px solid var(--line-clr);
      background: var(--base-clr);
      color: var(--text-clr);
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .pagination button.active {
      background: var(--accent-clr);
      color: white;
      border-color: var(--accent-clr);
    }

    .pagination button:hover:not(.active) {
      background: var(--hover-clr);
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

      <div class="filters-container">
        <div class="filter-group">
          <label for="timeFilter">Filter by:</label>
          <select id="timeFilter" class="form-control" onchange="filterLogs()">
            <option value="week">This Week</option>
            <option value="month" selected>This Month</option>
            <option value="year">This Year</option>
            <option value="all">All Time</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="roleFilter">Role:</label>
          <select id="roleFilter" class="form-control" onchange="filterLogs()">
            <option value="all">All Roles</option>
            <option value="student">Students</option>
            <option value="guest">Guests</option>
          </select>
        </div>

        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" class="form-control" placeholder="Search logs..." onkeyup="filterLogs()">
        </div>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <td>1</td>
              <td>John Doe</td>
              <td><span class="badge" data-role="Student">Student</span></td>
              <td class="office-cell">
                <img src="../../buildings/ccs.png" alt="CCS" class="office-avatar" onerror="this.src='../../buildings/default.png'">
                <span>CCS Office</span>
              </td>
              <td>2023-11-29</td>
              <td>14:30:45</td>
            </tr>
            <tr>
              <td>2</td>
              <td>Jane Smith</td>
              <td><span class="badge" data-role="Guest">Guest</span></td>
              <td class="office-cell">
                <img src="../../buildings/cit.png" alt="CIT" class="office-avatar" onerror="this.src='../../buildings/default.png'">
                <span>CIT Office</span>
              </td>
              <td>2023-11-29</td>
              <td>15:15:22</td>
            </tr>
            <!-- Add more rows as needed -->
          </tbody>
        </table>

        <div class="pagination" id="pagination">
          <button onclick="changePage(1)">First</button>
          <button onclick="changePage(currentPage - 1)">Previous</button>
          <button class="active">1</button>
          <button>2</button>
          <button>3</button>
          <button onclick="changePage(currentPage + 1)">Next</button>
          <button onclick="changePage(5)">Last</button>
        </div>
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
          <input type="month" id="exportMonth" class="form-control" value="<?php echo date('Y-m'); ?>">
        </div>
        <div class="export-options">
          <button class="export-btn pdf" onclick="exportData('pdf')">
            <i class="fas fa-file-pdf"></i> Export as PDF
          </button>
          <button class="export-btn csv" onclick="exportData('csv')">
            <i class="fas fa-file-csv"></i> Export as CSV
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentPage = 1;
    const itemsPerPage = 10; // Adjust as needed

    // Sample data - replace with actual data from your backend
    const sampleLogs = [
      {
        id: 1,
        fullName: 'John Doe',
        role: 'Student',
        office: { name: 'CCS Office', image: '../../buildings/ccs.png' },
        date: '2023-11-29',
        time: '14:30:45'
      },
      {
        id: 2,
        fullName: 'Jane Smith',
        role: 'Guest',
        office: { name: 'Registrar\'s Office', image: '../../buildings/registrar.png' },
        date: '2023-11-29',
        time: '15:15:22'
      }
      // Add more sample data as needed
    ];

    function openExportModal() {
      document.getElementById('exportModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function filterLogs() {
      const timeFilter = document.getElementById('timeFilter').value;
      const roleFilter = document.getElementById('roleFilter').value;
      const searchQuery = document.getElementById('searchInput').value.toLowerCase();
      
      // In a real application, you would make an AJAX call here to fetch filtered data
      console.log('Filtering logs with:', { timeFilter, roleFilter, searchQuery });
      
      // For demo purposes, we'll just update the table with filtered sample data
      updateTable(sampleLogs);
    }

    function updateTable(logs) {
      const tbody = document.getElementById('logsTableBody');
      tbody.innerHTML = '';

      logs.forEach(log => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${log.id}</td>
          <td>${log.fullName}</td>
          <td><span class="badge">${log.role}</span></td>
          <td class="office-cell">
            <img src="${log.office.image}" alt="${log.office.name}" class="office-avatar">
            ${log.office.name}
          </td>
          <td>${log.date}</td>
          <td>${log.time}</td>
        `;
        tbody.appendChild(row);
      });
    }

    function changePage(page) {
      if (page < 1) page = 1;
      // In a real app, you'd fetch the data for the requested page
      currentPage = page;
      updatePagination();
    }

    function updatePagination() {
      const pagination = document.getElementById('pagination');
      const buttons = pagination.getElementsByTagName('button');
      
      // Update active state
      for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
        if (buttons[i].textContent == currentPage) {
          buttons[i].classList.add('active');
        }
      }
    }

    function exportData(format) {
      const month = document.getElementById('exportMonth').value;
      alert(`Exporting ${format.toUpperCase()} data for ${month}`);
      // In a real application, this would trigger a download
      closeModal('exportModal');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    };

    // Initialize the table
    document.addEventListener('DOMContentLoaded', function() {
      updateTable(sampleLogs);
      updatePagination();
    });

    // Add this to your existing JavaScript
  document.addEventListener('DOMContentLoaded', function() {
    // Handle image loading errors
    document.querySelectorAll('img.office-avatar').forEach(img => {
      img.onerror = function() {
        this.src = '../../buildings/default.png'; // Fallback image
      };
    });
  });
  </script>
</body>
</html>