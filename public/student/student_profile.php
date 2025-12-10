<?php
require_once __DIR__ . '/../../services/Database.php';

session_start();

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student' || !isset($_SESSION['user_id'])) {
    header('Location: ../student_guest_login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnection();

// Get student data
$stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.school_id,
        s.full_name,
        s.email,
        s.phone,
        s.profile_picture,
        sec.section_code,
        sec.section_name,
        u.email as user_email
    FROM students s
    INNER JOIN users u ON u.user_id = s.user_id
    LEFT JOIN sections sec ON sec.section_id = s.section_id
    WHERE s.user_id = :user_id
    LIMIT 1
");
$stmt->execute([':user_id' => $userId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header('Location: ../student_guest_login.php');
    exit;
}

// Get recent navigation logs - first 3 most recent
$logsStmt = $conn->prepare("
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
    WHERE nl.user_id = :user_id 
    AND nl.status IN ('completed', 'cancelled')
    ORDER BY nl.end_time DESC, nl.start_time DESC
    LIMIT 3
");
$logsStmt->execute([':user_id' => $userId]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Format logs for display
$formattedLogs = [];
foreach ($logs as $log) {
    // Format the image path - ensure correct path from student_profile.php location
    // student_profile.php is in public/student/, so we need ../../ to reach root, then buildings/
    $imagePath = '../images/CHMSU.png'; // Default fallback
    if (!empty($log['image_path']) && trim($log['image_path']) !== '') {
        $dbPath = trim($log['image_path']);
        // Check if path already includes the directory
        if (strpos($dbPath, 'buildings/') === 0) {
            // Path like "buildings/office_6_marker.png" -> "../../buildings/office_6_marker.png"
            $imagePath = '../../' . $dbPath;
        } elseif (strpos($dbPath, 'building_content/') === 0) {
            // Path like "building_content/office_6_xxx.jpg" -> "../../building_content/office_6_xxx.jpg"
            $imagePath = '../../' . $dbPath;
        } elseif (strpos($dbPath, '../') === 0 || strpos($dbPath, '/') === 0) {
            // Already has relative or absolute path, use as is
            $imagePath = $dbPath;
        } else {
            // Assume it's just a filename, prepend buildings/
            $imagePath = '../../buildings/' . $dbPath;
        }
    }
    
    // Format date and time
    $endTime = $log['end_time'] ? new DateTime($log['end_time']) : new DateTime($log['start_time']);
    $date = $endTime->format('M d, Y');
    $time = $endTime->format('h:i A');
    
    $formattedLogs[] = [
        'office_name' => $log['office_name'],
        'image_path' => $imagePath,
        'status' => $log['status'],
        'date' => $date,
        'time' => $time,
        'timestamp' => $endTime->format('Y-m-d H:i:s')
    ];
}

// Use email from users table if student email is null
$email = $student['email'] ?: $student['user_email'];
$phone = $student['phone'] ?: '';
$profilePicture = $student['profile_picture'] ? '../' . $student['profile_picture'] : '../images/profile.jpg';
$sectionCode = $student['section_code'] ?: 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Mohammad Sahragard">
    <title>Profile</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/studentStyle.css">
    <script type="text/javascript" src="../script/app.js" defer></script>
    <script type="text/javascript" src="../script/student_logs_script.js" defer></script>
    <script src="../script/drill_alert_popup.js"></script>
    <!-- import font icon (fontawesome) -->
    <script src="https://kit.fontawesome.com/b8b432d7d3.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'student_nav.php'; ?>   


        <div class="profile-card">
            <div class="profile-header"><!-- profile header section -->
                <div class="main-profile">
                    <div class="profile-image" id="profileImage" style="background-image: url('<?= htmlspecialchars($profilePicture); ?>');"></div>
                    <div class="profile-names">
                        <h1 class="username"><?= htmlspecialchars($student['full_name']); ?></h1>
                        <small class="page-title"><?= htmlspecialchars($sectionCode); ?></small>
                        <button class="edit-profile" onclick="openEditModal()" style="background-color: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Change Profile</button>
                    </div>
                </div>
            </div>

            <div class="profile-body"><!-- profile body section -->
                <div class="info" style="display: flex; flex-direction: column; gap: 10px; margin-top:6em; margin-left: 1em;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <b style="color:orange;">Email: </b>
                        <span id="emailDisplay"><?= htmlspecialchars($email); ?></span>
                        <button onclick="editField('email')" style="background: none; border: none; color: #818cf8; cursor: pointer; padding: 5px;" title="Edit Email">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <b style="color:orange;">Phone: </b>
                        <span id="phoneDisplay"><?= htmlspecialchars($phone ?: 'Not set'); ?></span>
                        <button onclick="editField('phone')" style="background: none; border: none; color: #818cf8; cursor: pointer; padding: 5px;" title="Edit Phone">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <b style="color:orange;">Password: </b>
                        <span id="passwordDisplay">••••••••</span>
                        <button onclick="showPasswordViewModal()" style="background: none; border: none; color: #818cf8; cursor: pointer; padding: 5px;" title="View Password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="openChangePasswordModal()" style="background: none; border: none; color: #818cf8; cursor: pointer; padding: 5px;" title="Change Password">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div>
                        <b style="color:orange;">School ID: </b><?= htmlspecialchars($student['school_id']); ?>
                    </div>
                </div>
                <div class="logs-section">
                    <h3 class="logs-title">Recent Logs</h3>
                    <div class="logs-table-container">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Office</th>
                                    <th>Status</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($formattedLogs)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--secondary-color);">No logs recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($formattedLogs as $log): ?>
                                        <tr>
                                            <td style="display: flex; align-items: center; gap: 0.5rem;">
                                                <img src="<?= htmlspecialchars($log['image_path']); ?>" 
                                                     alt="<?= htmlspecialchars($log['office_name']); ?>" 
                                                     onerror="if(this.src.indexOf('CHMSU.png') === -1 && this.src.indexOf('default') === -1) { this.onerror=null; this.src='../images/CHMSU.png'; }"
                                                     loading="lazy">
                                                <span><?= htmlspecialchars($log['office_name']); ?></span>
                                            </td>
                                            <td style="color: <?= $log['status'] === 'completed' ? '#10b981' : '#ef4444'; ?>; font-weight: bold; text-transform: capitalize;">
                                                <?= htmlspecialchars($log['status']); ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column;">
                                                    <span><?= htmlspecialchars($log['date']); ?></span>
                                                    <span style="font-size: 0.85rem; color: var(--secondary-color);"><?= htmlspecialchars($log['time']); ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            
            </div>
        </div>

        <!-- View Password Confirmation Modal -->
        <div id="viewPasswordConfirmModal" class="modal" style="display: none;">
            <div class="modal-content" style="background: var(--primary-bg); border: 1px solid var(--accent-bg); border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0; font-size: 1.2rem;">View Password?</h3>
                    <button onclick="closeViewPasswordConfirm()" style="background: none; border: none; color: var(--primary-color); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <p style="color: var(--secondary-color); margin-bottom: 1.5rem; line-height: 1.6;">
                    Are you sure you want to view your password? Make sure no one is watching your screen. You will need to enter your current password to verify your identity.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeViewPasswordConfirm()" style="background: var(--secondary-bg); color: var(--primary-color); border: 1px solid var(--accent-bg); padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="proceedToViewPassword()" style="background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-weight: 600;">Continue</button>
                </div>
            </div>
        </div>

        <!-- Verify Password to View Modal -->
        <div id="verifyPasswordModal" class="modal" style="display: none;">
            <div class="modal-content" style="background: var(--primary-bg); border: 1px solid var(--accent-bg); border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0; font-size: 1.2rem;">Verify Your Identity</h3>
                    <button onclick="closeVerifyPasswordModal()" style="background: none; border: none; color: var(--primary-color); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <p style="color: var(--secondary-color); margin-bottom: 1.5rem; line-height: 1.6;">
                    Enter your current password to view it:
                </p>
                <form id="verifyPasswordForm">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Current Password</label>
                        <div style="position: relative;">
                            <input type="password" id="verifyPasswordInput" name="verify_password" required style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);">
                            <button type="button" onclick="toggleVerifyPasswordVisibility()" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; padding: 0.25rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem;" title="Show password">
                                <i class="fas fa-eye" id="verifyPasswordEyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div id="verifyPasswordErrorMessage" style="color: #ef4444; margin-bottom: 1rem; display: none;"></div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeVerifyPasswordModal()" style="background: var(--secondary-bg); color: var(--primary-color); border: 1px solid var(--accent-bg); padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancel</button>
                        <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-weight: 600;">Verify</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Visibility Confirmation Modal -->
        <div id="passwordVisibilityConfirmModal" class="modal" style="display: none;">
            <div class="modal-content" style="background: var(--primary-bg); border: 1px solid var(--accent-bg); border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0; font-size: 1.2rem;">Show Password?</h3>
                    <button onclick="closePasswordVisibilityConfirm()" style="background: none; border: none; color: var(--primary-color); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <p id="passwordVisibilityConfirmMessage" style="color: var(--secondary-color); margin-bottom: 1.5rem; line-height: 1.6;">
                    Are you sure you want to show your password? Make sure no one is watching your screen.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closePasswordVisibilityConfirm()" style="background: var(--secondary-bg); color: var(--primary-color); border: 1px solid var(--accent-bg); padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="confirmPasswordVisibility()" style="background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-weight: 600;">Show Password</button>
                </div>
            </div>
        </div>

        <!-- Change Password Modal -->
        <div id="changePasswordModal" class="modal" style="display: none;">
            <div class="modal-content" style="background: var(--primary-bg); border: 1px solid var(--accent-bg); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="color: var(--primary-color); margin: 0;">Change Password</h2>
                    <button onclick="closeChangePasswordModal()" style="background: none; border: none; color: var(--primary-color); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                
                <form id="changePasswordForm">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Current Password</label>
                        <div style="position: relative;">
                            <input type="password" id="currentPasswordInput" name="current_password" required style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);">
                            <button type="button" class="password-toggle-btn" data-input-id="currentPasswordInput" onclick="togglePasswordVisibility('currentPasswordInput', this)" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; padding: 0.25rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem;" title="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="newPasswordInput" name="new_password" required minlength="8" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);">
                            <button type="button" class="password-toggle-btn" data-input-id="newPasswordInput" onclick="togglePasswordVisibility('newPasswordInput', this)" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; padding: 0.25rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem;" title="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p style="color: var(--secondary-color); font-size: 0.85rem; margin-top: 0.5rem;">Must be at least 8 characters long</p>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="confirmPasswordInput" name="confirm_password" required minlength="8" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);">
                            <button type="button" class="password-toggle-btn" data-input-id="confirmPasswordInput" onclick="togglePasswordVisibility('confirmPasswordInput', this)" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; padding: 0.25rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem;" title="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="passwordErrorMessage" style="color: #ef4444; margin-bottom: 1rem; display: none;"></div>
                    <div id="passwordSuccessMessage" style="color: #10b981; margin-bottom: 1rem; display: none;"></div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeChangePasswordModal()" style="background: var(--secondary-bg); color: var(--primary-color); border: 1px solid var(--accent-bg); padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancel</button>
                        <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-weight: 600;">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="modal" style="display: none;">
            <div class="modal-content" style="background: var(--primary-bg); border: 1px solid var(--accent-bg); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="color: var(--primary-color); margin: 0;">Edit Profile</h2>
                    <button onclick="closeEditModal()" style="background: none; border: none; color: var(--primary-color); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                
                <form id="profileForm" enctype="multipart/form-data">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Profile Picture</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div id="profilePreview" style="width: 100px; height: 100px; border-radius: 50%; background-size: cover; background-position: center; border: 3px solid var(--accent-bg); background-image: url('<?= htmlspecialchars($profilePicture); ?>');"></div>
                            <div>
                                <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;" onchange="previewProfilePicture(this)">
                                <button type="button" onclick="document.getElementById('profilePictureInput').click()" style="background: var(--accent-bg); color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                                    Choose Image
                                </button>
                                <p style="color: var(--secondary-color); font-size: 0.85rem; margin-top: 0.5rem;">Max 5MB (JPEG, PNG, GIF, WebP)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Email</label>
                        <input type="email" id="emailInput" name="email" value="<?= htmlspecialchars($email); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);" required>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 600;">Phone</label>
                        <input type="tel" id="phoneInput" name="phone" value="<?= htmlspecialchars($phone); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--accent-bg); border-radius: 5px; background: var(--secondary-bg); color: var(--primary-color);" placeholder="e.g., 09123456789">
                    </div>
                    
                    <div id="errorMessage" style="color: #ef4444; margin-bottom: 1rem; display: none;"></div>
                    <div id="successMessage" style="color: #10b981; margin-bottom: 1rem; display: none;"></div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeEditModal()" style="background: var(--secondary-bg); color: var(--primary-color); border: 1px solid var(--accent-bg); padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancel</button>
                        <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-weight: 600;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>

<style>
    
html {
    box-sizing: border-box;
    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
*,
*::before,
*::after {
    box-sizing: inherit;
    margin: 0;
    padding: 0;
}

:root {
    --primary-bg: #171717;
    --secondary-bg: #262626;
    --accent-bg:rgb(44, 43, 58);

    --primary-color: #fff;
    --secondary-color: rgba(255,255,255, 70%);
    --accent-color: #818cf8;

    --border-color: rgba(255,255,255, 30%);
    
    --username-size: 32px;
    --title-size: 28px;
    --subtitle: 24px;
}
/*../images/profile.jpg*/

/* ---------- body element's */

.profile-card {
    height: 620px;
    background-color: var(--primary-bg);
    border-radius: 1em;
    border: 2px solid var(--accent-bg);
    display: grid;
    grid-template-rows: 220px auto;
}
/* ------ profile header section */
.profile-header {
    background: url('../images/homepage.png') center;
    background-size: cover;
    margin: 10px;
    border-radius: 30px 30px 0 0;
    position: relative;
}
    .main-profile {
        display: flex;
        align-items: center;
        position: absolute;
        inset: calc(100% - 123px) auto auto 70px;
    }
        .profile-image {
            width: 250px;
            height: 250px;
            background: url('../images/profile.jpg') center;
            background-size: cover;
            border-radius: 50%;
            border: 10px solid var(--primary-bg);
        }
        .profile-names {
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--primary-color);
            background-color: var(--primary-bg);
            padding: 10px 30px;
            border-radius: 0 50px 50px 0;
            transform: translateX(-10px);
        }
            .page-title {
                color: var(--secondary-color);
            }

/* ------- profile body header */
.profile-body {
    display: grid;
    grid-template-columns: 500px auto;
    gap: 70px;
    padding: 40px;
    overflow: hidden;
}
.logs-section {
    margin-top: 2em;
    margin-left: 1em;
    display: flex;
    flex-direction: column;
    max-height: calc(623px - 100px - 92px - 132px);
    min-height: 0;
}
.logs-title {
    color: var(--primary-color);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--accent-bg);
}
.logs-table-container {
    background-color: var(--secondary-bg);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
.logs-table-container::-webkit-scrollbar {
    width: 8px;
}
.logs-table-container::-webkit-scrollbar-track {
    background: var(--primary-bg);
}
.logs-table-container::-webkit-scrollbar-thumb {
    background: var(--accent-bg);
    border-radius: 4px;
}
.logs-table-container::-webkit-scrollbar-thumb:hover {
    background: var(--accent-color);
}
.logs-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--primary-color);
}
.logs-table thead {
    position: sticky;
    top: 0;
    background-color: var(--accent-bg);
    z-index: 10;
}
.logs-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
}
.logs-table tbody tr {
    transition: background-color 0.2s ease;
    border-bottom: 1px solid var(--border-color);
}
.logs-table tbody tr:hover {
    background-color: rgba(79, 70, 229, 0.1);
}
.logs-table tbody tr:last-child {
    border-bottom: none;
}
.logs-table td {
    padding: 0.9rem 1.25rem;
    color: var(--secondary-color);
    font-size: 0.95rem;
}
.logs-table td:first-child {
    font-weight: 500;
    color: var(--accent-color);
}

.logs-table td:first-child img {
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background-color: var(--secondary-bg);
    border: 2px solid var(--border-color);
    display: block;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
    transition: opacity 0.2s ease;
}

.logs-table td:first-child img[src=""],
.logs-table td:first-child img:not([src]) {
    opacity: 0;
}

.logs-table td:first-child img {
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background-color: var(--secondary-bg);
    border: 2px solid var(--border-color);
    display: block;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}
.account-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: 2fr 1fr;
        gap: 20px;
    }
    .profile-actions {
        display: grid;
        grid-template-rows: repeat(2, max-content) auto;
        gap: 10px;
        margin-top: 30px;
    }
    .profile-actions button {
        all: unset;
        padding: 10px;
        color: var(--primary-color);
        border: 2px solid var(--accent-bg);
        text-align: center;
        border-radius: 10px;
    }
        .profile-actions .follow {background-color: var(--accent-bg)}
    .bio {
        color: var(--primary-color);
        background-color: var(--secondary-bg);
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px;
        border-radius: 10px;
    }
        .bio-header {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--secondary-color);
        }
        .data {
            grid-area: 1/1/2/3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: var(--secondary-color);
            padding: 30px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 15px;
        }
            .important-data {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .other-data {
                display: flex;
                justify-content: space-between;
                align-items: center;

                background-color: var(--secondary-bg);
                padding: 15px;
                border-radius: 10px;
            }
            .data-item .value {
                color: var(--accent-color);
            }
                .important-data .value {
                    font-size: var(--title-size);
                }
                .other-data .value {
                    font-size: var(--subtitle);
                }
        .social-media {
            grid-area: 2/1/3/3;
            background-color: var(--secondary-bg);
            color: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            gap: 15px;
        }
            .media-link {
                text-decoration: none;
                color: var(--accent-color);
                font-size: var(--subtitle);
            }
        .last-post {
            grid-area: 1/3/3/4;
            border: 1px solid var(--border-color);
            background-color: var(--secondary-bg);
            border-radius: 10px;
            padding: 10px;

            display: grid;
            grid-template-rows: 70% auto max-content;
            gap: 10px;
        }
            .post-cover {
                position: relative;
                background: url('/images/last-post.jpg') center;
                background-size: cover;
                border-radius: 5px;
            }
                .last-badge {
                    position: absolute;
                    inset: 3px 3px auto auto;
                    background-color: rgba(0,0,0, 70%);
                    color: var(--primary-color);
                    padding: 5px;
                    border-radius: 3px;
                }
            .post-title {
                color: var(--primary-color);
                font-size: 18px;
            }
            .post-CTA {
                all: unset;
                text-align: center;
                border: 1px solid var(--accent-color);
                color: var(--accent-color);
                border-radius: 5px;
                padding: 5px;
            }

/* ------------ media queries */
@media screen and (max-width: 950px) {
    .last-post {
        display: none;
    }
    .data, .social-media {
        grid-column: 1/4;
    }
    .username{
        font-size: 15px;
    }
    .info {
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        font-size: 1em;
        font-weight: 600;
        color: var(--primary-color);
        background-color: var(--secondary-bg);
        padding: 10px;
        border-radius: 10px;
        margin-top: 12em !important;
        margin-left: 0em !important;
    }
    .profile-image{
        width:200px;
        height:200px;
    }
    .logs-section {
        display: none;
    }
    .logs-title {
        display: none;
    }
    .logs-table-container {
        display: none;
    }
    .logs-table {
        display: none;
    }
    .logs-table tbody tr {
        display: none;
    }
    .logs-table tbody tr:hover {
        display: none;
    }
    .logs-table tbody tr:last-child {
        display: none;
    }
    .logs-table td {
        display: none;
    }
    .logs-table td:first-child {
        display: none;
    }
    .logs-table td:last-child {
        display: none;
    }
}


@media screen and (max-width: 768px) {
    .profile-card {
        height: 100%;
        border-radius: 0;
    }
        .profile-header {
            border-radius: 0;
        }
            .main-profile {
                inset: calc(100% - 75px) auto auto 50%;
                transform: translateX(-50%);

                flex-direction: column;
                text-align: center;
            }
                .profile-names {transform: translateX(0)}
        .profile-body {
            grid-template-columns: 1fr;
            gap: 20px;
        }
            .profile-actions {
                grid-template-columns: repeat(2, 1fr);
                margin-top: 90px;
            }
                .bio {grid-column: 1/3;}

            .data {gap: 20px;}
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    max-height: 90vh;
    overflow-y: auto;
}

</style>

<script>
function openEditModal() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editProfileModal').style.display = 'none';
    document.getElementById('errorMessage').style.display = 'none';
    document.getElementById('successMessage').style.display = 'none';
}

function previewProfilePicture(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').style.backgroundImage = `url(${e.target.result})`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function editField(field) {
    openEditModal();
    if (field === 'email') {
        document.getElementById('emailInput').focus();
    } else if (field === 'phone') {
        document.getElementById('phoneInput').focus();
    }
}

// Handle form submission
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../../api/update_student_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            successDiv.textContent = result.message || 'Profile updated successfully!';
            successDiv.style.display = 'block';
            
            // Update displayed values
            document.getElementById('emailDisplay').textContent = document.getElementById('emailInput').value;
            document.getElementById('phoneDisplay').textContent = document.getElementById('phoneInput').value || 'Not set';
            
            // Update profile picture if changed
            if (result.profile_picture) {
                const profileImage = document.getElementById('profileImage');
                profileImage.style.backgroundImage = `url('../${result.profile_picture}')`;
            }
            
            // Close modal after 2 seconds
            setTimeout(() => {
                closeEditModal();
                // Reload page to ensure all data is synced
                window.location.reload();
            }, 2000);
        } else {
            errorDiv.textContent = result.message || 'Failed to update profile. Please try again.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
        console.error('Error updating profile:', error);
    }
});

// Close modal when clicking outside
document.getElementById('editProfileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Password visibility confirmation state
let passwordVisibilityConfirmed = {
    currentPasswordInput: false,
    newPasswordInput: false,
    confirmPasswordInput: false
};

// View Password Functions
function showPasswordViewModal() {
    document.getElementById('viewPasswordConfirmModal').style.display = 'flex';
}

function closeViewPasswordConfirm() {
    document.getElementById('viewPasswordConfirmModal').style.display = 'none';
}

function proceedToViewPassword() {
    closeViewPasswordConfirm();
    document.getElementById('verifyPasswordModal').style.display = 'flex';
    document.getElementById('verifyPasswordForm').reset();
    document.getElementById('verifyPasswordErrorMessage').style.display = 'none';
    document.getElementById('verifyPasswordInput').focus();
}

function closeVerifyPasswordModal() {
    document.getElementById('verifyPasswordModal').style.display = 'none';
    document.getElementById('verifyPasswordForm').reset();
    document.getElementById('verifyPasswordErrorMessage').style.display = 'none';
    // Reset password input type
    const input = document.getElementById('verifyPasswordInput');
    if (input) {
        input.type = 'password';
    }
    const icon = document.getElementById('verifyPasswordEyeIcon');
    if (icon) {
        icon.className = 'fas fa-eye';
    }
}

function toggleVerifyPasswordVisibility() {
    const input = document.getElementById('verifyPasswordInput');
    const icon = document.getElementById('verifyPasswordEyeIcon');
    
    if (input && icon) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
}

// Handle verify password form submission
document.getElementById('verifyPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorDiv = document.getElementById('verifyPasswordErrorMessage');
    errorDiv.style.display = 'none';
    
    const password = document.getElementById('verifyPasswordInput').value;
    
    if (!password) {
        errorDiv.textContent = 'Please enter your password.';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Verify password by attempting to change it (we'll create a verify endpoint)
    // For now, we'll use a simple approach - verify and show what they entered
    try {
        const response = await fetch('../../api/verify_student_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ password: password })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            // Password verified - show it to the user
            closeVerifyPasswordModal();
            
            // Show the password in the profile display
            const passwordDisplay = document.getElementById('passwordDisplay');
            if (passwordDisplay) {
                passwordDisplay.textContent = password;
                passwordDisplay.style.color = '#10b981';
                
                // Hide it again after 5 seconds
                setTimeout(() => {
                    passwordDisplay.textContent = '••••••••';
                    passwordDisplay.style.color = '';
                }, 5000);
            }
        } else {
            errorDiv.textContent = result.message || 'Incorrect password. Please try again.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
        console.error('Error verifying password:', error);
    }
});

// Close modals when clicking outside
document.getElementById('viewPasswordConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewPasswordConfirm();
    }
});

document.getElementById('verifyPasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVerifyPasswordModal();
    }
});

// Confirmation Modal for Password Visibility
function showPasswordVisibilityConfirm(inputId, button) {
    const confirmModal = document.getElementById('passwordVisibilityConfirmModal');
    const confirmMessage = document.getElementById('passwordVisibilityConfirmMessage');
    
    // Store which input we're confirming for
    confirmModal.dataset.inputId = inputId;
    confirmModal.dataset.button = button ? 'true' : 'false';
    
    if (confirmMessage) {
        confirmMessage.textContent = 'Are you sure you want to show your password? Make sure no one is watching your screen.';
    }
    
    confirmModal.style.display = 'flex';
}

function confirmPasswordVisibility() {
    const confirmModal = document.getElementById('passwordVisibilityConfirmModal');
    const inputId = confirmModal.dataset.inputId;
    
    if (inputId) {
        passwordVisibilityConfirmed[inputId] = true;
        const button = document.querySelector(`button[onclick*="${inputId}"]`);
        togglePasswordVisibility(inputId, button, true);
    }
    
    closePasswordVisibilityConfirm();
}

function closePasswordVisibilityConfirm() {
    document.getElementById('passwordVisibilityConfirmModal').style.display = 'none';
}

function togglePasswordVisibility(inputId, buttonElement, skipConfirm = false) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Check if this input is inside the change password modal - if so, skip confirmation
    const changePasswordModal = document.getElementById('changePasswordModal');
    const isInChangePasswordModal = changePasswordModal && changePasswordModal.contains(input);
    
    // If not confirmed and not skipping confirm and NOT in change password modal, show confirmation modal
    if (!skipConfirm && !passwordVisibilityConfirmed[inputId] && !isInChangePasswordModal) {
        showPasswordVisibilityConfirm(inputId, buttonElement);
        return;
    }
    
    // Toggle password visibility
    const button = buttonElement || document.querySelector(`button[onclick*="${inputId}"]`);
    
    if (input.type === 'password') {
        input.type = 'text';
        if (button) {
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-eye-slash';
            } else {
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            }
            button.title = 'Hide password';
        }
    } else {
        input.type = 'password';
        if (button) {
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-eye';
            } else {
                button.innerHTML = '<i class="fas fa-eye"></i>';
            }
            button.title = 'Show password';
        }
        // Reset confirmation when hiding (only if not in change password modal)
        if (!isInChangePasswordModal) {
            passwordVisibilityConfirmed[inputId] = false;
        }
    }
}

// Change Password Modal Functions
function openChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'flex';
    document.getElementById('changePasswordForm').reset();
    document.getElementById('passwordErrorMessage').style.display = 'none';
    document.getElementById('passwordSuccessMessage').style.display = 'none';
    
    // Reset password visibility states
    passwordVisibilityConfirmed = {
        currentPasswordInput: false,
        newPasswordInput: false,
        confirmPasswordInput: false
    };
    
    // Reset all password inputs to password type and reset eye icons
    ['currentPasswordInput', 'newPasswordInput', 'confirmPasswordInput'].forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.type = 'password';
        }
        const button = document.querySelector(`[onclick*="${inputId}"]`);
        if (button) {
            button.innerHTML = '<i class="fas fa-eye"></i>';
            button.title = 'Show password';
        }
    });
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
    document.getElementById('changePasswordForm').reset();
    document.getElementById('passwordErrorMessage').style.display = 'none';
    document.getElementById('passwordSuccessMessage').style.display = 'none';
}

// Handle password change form submission
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorDiv = document.getElementById('passwordErrorMessage');
    const successDiv = document.getElementById('passwordSuccessMessage');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    const currentPassword = document.getElementById('currentPasswordInput').value;
    const newPassword = document.getElementById('newPasswordInput').value;
    const confirmPassword = document.getElementById('confirmPasswordInput').value;
    
    // Client-side validation
    if (newPassword.length < 8) {
        errorDiv.textContent = 'New password must be at least 8 characters long.';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (newPassword !== confirmPassword) {
        errorDiv.textContent = 'New password and confirmation password do not match.';
        errorDiv.style.display = 'block';
        return;
    }
    
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);
    
    try {
        const response = await fetch('../../api/change_student_password.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            successDiv.textContent = result.message || 'Password changed successfully!';
            successDiv.style.display = 'block';
            
            // Clear form
            document.getElementById('changePasswordForm').reset();
            
            // Close modal after 2 seconds
            setTimeout(() => {
                closeChangePasswordModal();
            }, 2000);
        } else {
            errorDiv.textContent = result.message || 'Failed to change password. Please try again.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
        console.error('Error changing password:', error);
    }
});

// Close password modal when clicking outside
document.getElementById('changePasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangePasswordModal();
    }
});

// Close password visibility confirm modal when clicking outside
document.getElementById('passwordVisibilityConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordVisibilityConfirm();
    }
});
</script>