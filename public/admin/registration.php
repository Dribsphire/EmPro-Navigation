<?php
require_once __DIR__ . '/../../services/Auth.php';
require_once __DIR__ . '/../../services/StudentService.php';

$auth = new Auth();
$auth->requireAdmin();

$studentService = new StudentService();
if (!isset($_SESSION['temp_passwords'])) {
    $_SESSION['temp_passwords'] = [];
}

$successMessage = '';
$errorMessage = '';
$importErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'manual_register') {
            $result = $studentService->registerStudent([
                'school_id' => $_POST['school_id'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'section_code' => $_POST['section_code'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ]);

            $successMessage = sprintf(
                'Student %s (%s) registered successfully. Temporary password: %s',
                $result['full_name'],
                $result['school_id'],
                $result['temporary_password']
            );

            $_SESSION['temp_passwords'][$result['school_id']] = $result['temporary_password'];
        } elseif ($action === 'import_csv') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Please select a valid CSV file to import.');
            }

            $report = $studentService->importFromCsv($_FILES['csv_file']['tmp_name']);
            if ($report['imported'] > 0) {
                $successMessage = sprintf(
                    'Imported %d out of %d records successfully.',
                    $report['imported'],
                    $report['processed']
                );
            }

            if (!empty($report['errors'])) {
                $importErrors = $report['errors'];
                if ($report['imported'] === 0) {
                    $errorMessage = 'No records were imported. Please review the errors below.';
                }
            }
        } elseif ($action === 'delete_student') {
            $schoolId = $_POST['school_id'] ?? '';
            $studentService->deleteStudent($schoolId);
            unset($_SESSION['temp_passwords'][$schoolId]);
            $successMessage = "Student {$schoolId} deleted successfully.";
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

$students = $studentService->getStudents();
$sections = $studentService->getSections();
$tempPasswords = $_SESSION['temp_passwords'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration - Admin</title>
  <link rel="icon" type="image/png" href="../images/CHMSU.png">
  <link rel="stylesheet" href="../css/admin_Style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .registration-container {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background-color: var(--accent-clr);
      color: white;
    }

    .btn-secondary {
      background-color: var(--line-clr);
      color: var(--text-clr);
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .alert-container {
      position: fixed;
      top: 1.5rem;
      right: 1.5rem;
      z-index: 1500;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .alert {
      min-width: 280px;
      max-width: 360px;
      padding: 1rem 1.25rem;
      border-radius: 10px;
      border: 1px solid transparent;
      font-weight: 500;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
      backdrop-filter: blur(6px);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .alert-hide {
      opacity: 0;
      transform: translateY(-10px);
      pointer-events: none;
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.2);
      border-color: rgba(34, 197, 94, 0.45);
      color: #bbf7d0;
    }

    .alert-error {
      background: rgba(248, 113, 113, 0.2);
      border-color: rgba(248, 113, 113, 0.45);
      color: #fecaca;
    }

    .import-errors {
      margin-top: 1.5rem;
      border: 1px solid rgba(248, 113, 113, 0.4);
      border-radius: 10px;
      padding: 1rem;
      background: rgba(248, 113, 113, 0.1);
      color: #fecaca;
    }

    .import-errors ul {
      margin: 0.75rem 0 0;
      padding-left: 1.25rem;
    }

    .table-container {
      background: var(--base-clr);
      border: 1px solid var(--line-clr);
      border-radius: 12px;
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--line-clr);
    }

    th {
      background-color: var(--hover-clr);
      font-weight: 600;
      color: var(--accent-clr);
    }

    tr:hover {
      background-color: var(--hover-clr);
    }

    .action-cell {
      display: flex;
      gap: 0.5rem;
    }

    .btn-icon {
      padding: 0.5rem;
      border-radius: 6px;
      background: none;
      border: 1px solid var(--line-clr);
      color: var(--text-clr);
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-icon:hover {
      background: var(--accent-clr);
      color: white;
      border-color: var(--accent-clr);
    }

    .btn-icon.delete:hover {
      background: #dc3545;
      border-color: #dc3545;
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
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }

    .modal-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--line-clr);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-body {
      padding: 1.5rem;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-clr);
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--text-clr);
    }

    .form-control {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--line-clr);
      border-radius: 6px;
      background: var(--base-clr);
      color: var(--text-clr);
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 2rem;
    }

    .file-upload {
      border: 2px dashed var(--line-clr);
      border-radius: 8px;
      padding: 2rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .file-upload:hover {
      border-color: var(--accent-clr);
    }

    .file-upload i {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: var(--accent-clr);
    }

    .file-upload p {
      margin: 0;
      color: var(--secondary-text-clr);
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--secondary-text-clr);
    }

    .toggle-password:hover {
      color: var(--accent-clr);
    }

    .error-message {
      color: #ff6b6b;
      font-size: 0.8rem;
      margin-top: 0.25rem;
      display: none;
    }

    datalist option {
      color: #111;
    }

    .generated-password {
      margin-bottom: 1.5rem;
      padding: 1rem;
      border: 1px dashed var(--line-clr);
      border-radius: 8px;
      background: rgba(148, 163, 184, 0.05);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .generated-password code {
      font-family: 'Courier New', monospace;
      font-size: 1.1rem;
      color: var(--accent-clr);
    }

    .generated-password button {
      border: none;
      border-radius: 6px;
      padding: 0.35rem 0.75rem;
      cursor: pointer;
      background: var(--accent-clr);
      color: white;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <?php include 'admin_nav.php'; ?>

  <div class="alert-container">
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success" role="alert">
        <?= htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-error" role="alert">
        <?= htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>
  </div>
  
  <main>
    <div class="container">
      <div class="registration-container">
        <h1>Student Registration</h1>
        <p>Manage student registrations and view registered students.</p>
        
        <div class="action-buttons">
          <button class="btn btn-primary" onclick="openModal('csvModal')">
            <i class="fas fa-file-import"></i> Import from CSV
          </button>
          <button class="btn btn-secondary" onclick="openModal('manualModal')">
            <i class="fas fa-user-plus"></i> Add Student Manually
          </button>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>School ID</th>
                <th>Full Name</th>
                <th>Section Code</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($students)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--secondary-text-clr);">
                    No students registered yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <tr
                    data-school-id="<?= htmlspecialchars($student['school_id']); ?>"
                    data-temp-password="<?= htmlspecialchars($tempPasswords[$student['school_id']] ?? ''); ?>"
                  >
                    <td><?= htmlspecialchars($student['school_id']); ?></td>
                    <td><?= htmlspecialchars($student['full_name']); ?></td>
                    <td><?= htmlspecialchars($student['section_code']); ?></td>
                    <td><?= htmlspecialchars($student['email']); ?></td>
                    <td><?= htmlspecialchars($student['phone'] ?: '—'); ?></td>
                    <td class="action-cell">
                      <button
                        class="btn-icon"
                        title="Change Password"
                        onclick="openChangePasswordModal('<?= htmlspecialchars($student['school_id']); ?>', this.closest('tr'))"
                      >
                        <i class="fas fa-key"></i>
                      </button>
                      <button
                        class="btn-icon delete"
                        title="Delete"
                        onclick="openDeleteConfirmModal('<?= htmlspecialchars($student['school_id']); ?>', this.closest('tr'))"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($importErrors)): ?>
          <div class="import-errors">
            <strong>Import warnings:</strong>
            <ul>
              <?php foreach ($importErrors as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <datalist id="sectionOptions">
    <?php foreach ($sections as $section): ?>
      <option value="<?= htmlspecialchars($section['section_code']); ?>"><?= htmlspecialchars($section['section_name']); ?></option>
    <?php endforeach; ?>
  </datalist>

  <!-- CSV Import Modal -->
  <div id="csvModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Import Students from CSV</h2>
        <button class="close-modal" onclick="closeModal('csvModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="csvImportForm" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <input type="hidden" name="action" value="import_csv">
          <div class="file-upload" onclick="document.getElementById('csvFile').click()">
            <i class="fas fa-cloud-upload-alt"></i>
            <h3>Upload CSV File</h3>
            <p>Click to browse or drag and drop your CSV file here</p>
            <p><small>Supported format: .csv (columns: school_id, full_name, section_code, email, phone, password)</small></p>
            <input type="file" id="csvFile" name="csv_file" accept=".csv" style="display: none;" required>
            <p id="csvFileName" style="margin-top: 0.75rem; color: var(--accent-clr); font-weight: 600;"></p>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('csvModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Import Students</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Manual Registration Modal -->
  <div id="manualModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Student</h2>
        <button class="close-modal" onclick="closeModal('manualModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="studentForm" method="POST">
          <input type="hidden" name="action" value="manual_register">
          <div class="form-group">
            <label for="schoolId">School ID</label>
            <input type="text" id="schoolId" name="school_id" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="fullName">Full Name</label>
            <input type="text" id="fullName" name="full_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="sectionCode">Section Code</label>
            <input type="text" id="sectionCode" name="section_code" class="form-control" list="sectionOptions" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="studentPassword">
              Initial Password
              <small style="color: var(--secondary-text-clr); font-weight: 400;">(optional)</small>
            </label>
            <input type="text" id="studentPassword" name="password" class="form-control" placeholder="Leave blank to auto-generate">
          </div>
          <div class="form-group">
            <label for="contactNumber">Contact Number</label>
            <input type="tel" id="contactNumber" name="phone" class="form-control" placeholder="+63XXXXXXXXXX">
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('manualModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div id="changePasswordModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2>Change Password</h2>
        <button class="close-modal" onclick="closeModal('changePasswordModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm">
          <input type="hidden" id="studentId">
          <div class="generated-password" id="generatedPasswordSection" style="display: none;">
            <div>
              <strong>Generated Password:</strong><br>
              <code id="generatedPasswordDisplay">----------</code>
            </div>
            <button type="button" id="generatedPasswordCopyBtn" onclick="copyGeneratedPassword()">Copy</button>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <div style="position: relative;">
              <input type="password" id="newPassword" class="form-control" required minlength="8">
              <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('newPassword', this)"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirm New Password</label>
            <div style="position: relative;">
              <input type="password" id="confirmPassword" class="form-control" required minlength="8">
              <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('confirmPassword', this)"></i>
            </div>
            <div id="passwordMatchError" class="error-message">
              Passwords do not match
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('changePasswordModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2>Confirm Deletion</h2>
        <button class="close-modal" onclick="closeModal('deleteConfirmModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="deleteStudentForm" method="POST">
          <input type="hidden" name="action" value="delete_student">
          <input type="hidden" id="deleteSchoolId" name="school_id">
          <p>Are you sure you want to delete student <strong id="deleteStudentLabel"></strong>? This action cannot be undone.</p>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Global variables
    let currentStudentId = null;
    let currentRow = null;

    document.querySelectorAll('.alert').forEach((alertEl) => {
      setTimeout(() => alertEl.classList.add('alert-hide'), 3500);
      setTimeout(() => alertEl.remove(), 4000);
    });

    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
      // Reset forms when closing
      if (modalId === 'changePasswordModal') {
        document.getElementById('changePasswordForm').reset();
        document.getElementById('passwordMatchError').style.display = 'none';
      } else if (modalId === 'manualModal') {
        document.getElementById('studentForm').reset();
      } else if (modalId === 'csvModal') {
        document.getElementById('csvImportForm').reset();
        document.getElementById('csvFileName').textContent = '';
      }
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    });

    // File upload preview
    document.getElementById('csvFile')?.addEventListener('change', function(e) {
      if (this.files.length > 0) {
        const fileName = this.files[0].name;
        document.getElementById('csvFileName').textContent = fileName;
      }
    });

    // Change Password Functions
    function openChangePasswordModal(studentId, row) {
      currentStudentId = studentId;
      currentRow = row;
      document.getElementById('studentId').value = studentId;
      const tempPassword = row?.dataset?.tempPassword || '';
      const section = document.getElementById('generatedPasswordSection');
      const display = document.getElementById('generatedPasswordDisplay');
      if (tempPassword) {
        section.style.display = 'flex';
        display.textContent = tempPassword;
        document.getElementById('newPassword').value = tempPassword;
        document.getElementById('confirmPassword').value = tempPassword;
      } else {
        section.style.display = 'none';
        display.textContent = '----------';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
      }
      openModal('changePasswordModal');
    }

    // Delete Confirmation Functions
    function openDeleteConfirmModal(studentId, row) {
      currentStudentId = studentId;
      currentRow = row;
      document.getElementById('deleteSchoolId').value = studentId;
      document.getElementById('deleteStudentLabel').textContent = studentId;
      openModal('deleteConfirmModal');
    }

    // Password change form submission
    document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
      e.preventDefault();

      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const errorElement = document.getElementById('passwordMatchError');

      if (newPassword !== confirmPassword) {
        errorElement.style.display = 'block';
        return;
      }

      errorElement.style.display = 'none';

      // Here you would typically make an AJAX call to update the password
      console.log(`Updating password for student ${currentStudentId}`);

      // Show success message
      alert('Password updated successfully!');
      closeModal('changePasswordModal');
      this.reset();
    });

    // Toggle password visibility
    function togglePasswordVisibility(inputId, icon) {
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    function copyGeneratedPassword() {
      const codeEl = document.getElementById('generatedPasswordDisplay');
      const section = document.getElementById('generatedPasswordSection');
      if (section.style.display === 'none') {
        return;
      }
      const text = codeEl.textContent;
      navigator.clipboard.writeText(text).then(() => {
        codeEl.closest('.generated-password').classList.add('copied');
        setTimeout(() => codeEl.closest('.generated-password').classList.remove('copied'), 1200);
      });
    }
  </script>
</body>
</html>