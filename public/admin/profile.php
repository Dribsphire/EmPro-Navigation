<?php
require_once __DIR__ . '/../../services/Auth.php';
require_once __DIR__ . '/../../services/Database.php';

$auth = new Auth();
$auth->requireAdmin();
$currentAdmin = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

$successMessage = '';
$errorMessage = '';

function getAdminProfile(PDO $conn, int $userId): ?array {
    $sql = "
        SELECT
            u.user_id,
            u.username,
            u.email,
            u.password,
            u.created_at,
            u.updated_at,
            a.admin_id,
            a.full_name,
            a.phone,
            a.profile_picture,
            a.last_login
        FROM users u
        INNER JOIN admins a ON a.user_id = u.user_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    return $profile ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($fullName === '' || $email === '' || $username === '') {
                throw new RuntimeException('Full name, email, and admin ID are required.');
            }

            $conn->beginTransaction();

            $updateAdmin = $conn->prepare("
                UPDATE admins
                SET full_name = :full_name,
                    phone = :phone,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $updateAdmin->execute([
                ':full_name' => $fullName,
                ':phone' => $phone !== '' ? $phone : null,
                ':user_id' => $currentAdmin['user_id']
            ]);

            $updateUser = $conn->prepare("
                UPDATE users
                SET email = :email,
                    username = :username,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $updateUser->execute([
                ':email' => $email,
                ':username' => $username,
                ':user_id' => $currentAdmin['user_id']
            ]);

            $conn->commit();

            if (isset($_SESSION['auth_user'])) {
                $_SESSION['auth_user']['username'] = $username;
                $_SESSION['auth_user']['school_id'] = $username;
            }

            $successMessage = 'Profile updated successfully.';
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('New password and confirmation do not match.');
            }

            if (strlen($newPassword) < 8) {
                throw new RuntimeException('New password must be at least 8 characters long.');
            }

            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $currentAdmin['user_id']]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userRow || !password_verify($currentPassword, $userRow['password'])) {
                throw new RuntimeException('Current password is incorrect.');
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updatePassword = $conn->prepare("
                UPDATE users
                SET password = :password,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $updatePassword->execute([
                ':password' => $newHash,
                ':user_id' => $currentAdmin['user_id']
            ]);

            $successMessage = 'Password updated successfully.';
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $errorMessage = $e->getMessage();
    }
}

$profile = getAdminProfile($conn, (int) $currentAdmin['user_id']);
if (!$profile && !$errorMessage) {
    $errorMessage = 'Unable to load admin profile.';
}

$profileName = $profile['full_name'] ?? 'Administrator';
$profileEmail = $profile['email'] ?? 'N/A';
$profileUsername = $profile['username'] ?? ($currentAdmin['username'] ?? 'N/A');
$profilePhone = $profile['phone'] ?? '';
$lastLogin = $profile['last_login'] ?? null;
$profilePicture = !empty($profile['profile_picture']) ? $profile['profile_picture'] : '../images/CHMSU.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Profile</title>
  <link rel="icon" type="image/png" href="../images/CHMSU.png">
  <link rel="stylesheet" href="../css/admin_Style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .profile-container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .profile-header {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: -5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .profile-banner {
      height: 250px;
      background-image: url('../images/homepage.png');
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .profile-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.7));
    }

    .profile-content {
      position: relative;
      padding: 2rem;
      background: var(--base-clr);
      border: 1px solid var(--line-clr);
      border-top: none;
      border-radius: 0 0 12px 12px;
    }

    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      border: 5px solid var(--base-clr);
      position: absolute;
      top: -75px;
      left: 50%;
      transform: translateX(-50%);
      object-fit: cover;
      background-color: #f0f0f0;
    }

    .profile-info {
      text-align: center;
      margin-top: 80px;
    }

    .profile-name {
      font-size: 2rem;
      font-weight: 600;
      margin: 0.5rem 0;
      color: var(--text-clr);
    }

    .profile-id {
      color: var(--accent-clr);
      font-size: 1.1rem;
      margin-bottom: 1rem;
      display: block;
    }

    .profile-email {
      color: var(--secondary-text-clr);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .profile-meta {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.35rem;
      color: var(--secondary-text-clr);
      font-size: 0.95rem;
      margin-bottom: 1.5rem;
    }

    .profile-meta span {
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .profile-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
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

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    body {
      position: relative;
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

    .alert.alert-hide {
      opacity: 0;
      transform: translateY(-8px);
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



    .detail-card {
      background: rgba(15, 23, 42, 0.65);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 12px;
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .detail-card span {
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--secondary-text-clr);
    }

    .detail-card strong {
      font-size: 1.1rem;
      color: var(--text-clr);
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
      max-width: 500px;
      max-height: 90vh;
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

    .error-message {
      color: #ff6b6b;
      font-size: 0.8rem;
      margin-top: 0.25rem;
      display: none;
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
    <div class="profile-container">
      <div class="profile-header">
        <div class="profile-banner"></div>
        <div class="profile-content">
          <img src="<?= htmlspecialchars($profilePicture); ?>" alt="Admin Profile" class="profile-avatar">
          <div class="profile-info">
            <h1 class="profile-name"><?= htmlspecialchars($profileName); ?></h1>
            <span class="profile-id"><?= htmlspecialchars($profileUsername); ?></span>
            <div class="profile-email">
              <i class="fas fa-envelope"></i>
              <span><?= htmlspecialchars($profileEmail); ?></span>
            </div>
            <div class="profile-meta">
              <?php if (!empty($profilePhone)): ?>
                <span><i class="fas fa-phone-alt"></i><?= htmlspecialchars($profilePhone); ?></span>
              <?php endif; ?>
              <span><i class="fas fa-calendar-check"></i>Joined <?= htmlspecialchars(date('M d, Y', strtotime($profile['created_at'] ?? $profile['updated_at'] ?? date('Y-m-d')))); ?></span>
              <span>
                <i class="fas fa-clock"></i>
                Last login:
                <?= htmlspecialchars($lastLogin ? date('M d, Y h:i A', strtotime($lastLogin)) : 'No activity yet'); ?>
              </span>
            </div>
            <div class="profile-actions">
              <button class="btn btn-primary" onclick="openModal('editProfileModal')">
                <i class="fas fa-user-edit"></i> Edit Profile
              </button>
              <button class="btn btn-secondary" onclick="openModal('changePasswordModal')">
                <i class="fas fa-key"></i> Change Password
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Profile Modal -->
  <div id="editProfileModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Profile</h2>
        <button class="close-modal" onclick="closeModal('editProfileModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editProfileForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label for="fullName">Full Name</label>
            <input type="text" id="fullName" name="full_name" class="form-control" value="<?= htmlspecialchars($profileName); ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($profileEmail); ?>" required>
          </div>
          <div class="form-group">
            <label for="username">Admin ID</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($profileUsername); ?>" required>
          </div>
          <div class="form-group">
            <label for="phone">Contact Number</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($profilePhone); ?>" placeholder="+63XXXXXXXXXX">
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editProfileModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div id="changePasswordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Change Password</h2>
        <button class="close-modal" onclick="closeModal('changePasswordModal')">&times;</button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <div style="position: relative;">
              <input type="password" id="currentPassword" name="current_password" class="form-control" required>
              <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('currentPassword', this)"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <div style="position: relative;">
              <input type="password" id="newPassword" name="new_password" class="form-control" required minlength="8">
              <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('newPassword', this)"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirm New Password</label>
            <div style="position: relative;">
              <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required minlength="8">
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

  <script>
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
        const pwdForm = document.getElementById('changePasswordForm');
        if (pwdForm) {
          pwdForm.reset();
        }
        const errorEl = document.getElementById('passwordMatchError');
        if (errorEl) {
          errorEl.style.display = 'none';
        }
      } else if (modalId === 'editProfileModal') {
        const profileForm = document.getElementById('editProfileForm');
        if (profileForm) {
          profileForm.reset();
        }
      }
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    });

    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
      passwordForm.addEventListener('input', function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const errorElement = document.getElementById('passwordMatchError');
        if (confirmPassword && newPassword !== confirmPassword) {
          errorElement.style.display = 'block';
        } else {
          errorElement.style.display = 'none';
        }
      });
    }

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
  </script>
</body>
</html>