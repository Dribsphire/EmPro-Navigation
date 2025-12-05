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

// Get recent logs (visits) - last 10
$logsStmt = $conn->prepare("
    SELECT 
        o.office_name,
        uv.visit_time,
        oi.image_path as office_image
    FROM user_visits uv
    INNER JOIN offices o ON o.office_id = uv.office_id
    LEFT JOIN office_images oi ON oi.office_id = o.office_id AND oi.is_primary = 1
    WHERE uv.user_id = :user_id
    ORDER BY uv.visit_time DESC
    LIMIT 10
");
$logsStmt->execute([':user_id' => $userId]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

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
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center; color: var(--secondary-color);">No visits recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['office_name']); ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($log['visit_time'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            
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
</script>