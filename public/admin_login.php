<?php
require_once __DIR__ . '/../services/Auth.php';

$auth = new Auth();
$error = '';
$debugLogs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolId = trim($_POST['school_id'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->authenticateAdmin($schoolId, $password);
    $debugLogs = $result['debug'] ?? [];

    if (isset($result['redirect']) && $result['status'] !== 'error') {
        header('Location: ' . $result['redirect']);
        exit();
    }

    $error = $result['message'] ?? 'Unable to process login request.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin-EmPro::</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="stylesheet" type="text/css" href="css/login_student_guest.css" />
    <style>
        /* Admin-specific overrides */
        .container:before {
            background-color: #1a1a2e; /* Darker shade for admin */
        }
        .btn {
            background-color: #4a4a8a; /* Different button color */
        }
        .btn:hover {
            background-color: #5e5e9e;
        }
        .admin-logo {
            width: 150px;
            height: 150px;
            margin-bottom: 1rem;
        }
        .error-alert {
            background-color: #fdecea;
            color: #b02a37;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            width: 100%;
        }
        .debug-panel {
            margin-bottom: 1rem;
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.5);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 0.85rem;
        }
        .debug-panel summary {
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            list-style: none;
        }
        .debug-panel pre {
            margin: 0;
            padding: 0.75rem;
            font-family: Consolas, 'Courier New', monospace;
            white-space: pre-wrap;
            border-top: 1px solid rgba(148, 163, 184, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="sign-in-form" novalidate>
                    <img src="images/logo.png" alt="Admin Logo" class="admin-logo"style="width:200px; height:200px;">
                    <h2 class="title">Admin Login</h2>
                    <?php if (!empty($error)): ?>
                        <div class="error-alert">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($debugLogs)): ?>
                        <details class="debug-panel" open>
                            <summary>Debug Details (copy & share with developer)</summary>
                            <pre><?= htmlspecialchars(implode("\n", $debugLogs)); ?></pre>
                        </details>
                    <?php endif; ?>
                    <div class="input-field">
                        <i class="fas fa-user-shield"></i>
                        <input type="text" name="school_id" placeholder="Admin ID" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>
                    <input type="submit" value="Login" class="btn solid" />
                    <p class="social-text">Restricted Access - Authorized Personnel Only</p>
                </form>
            </div>
        </div>
        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>EmPro Admin</h3>
                    <p>Administrative access to manage campus navigation system, user accounts, and system settings.</p>
                </div>
                <img src="images/CHMSU.png" class="image" alt="Campus Navigation" style="width:300px; height:300px;align-self: center; opacity: 0.8;">
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
    <script>
        // Simple form validation
        document.querySelector('.sign-in-form').addEventListener('submit', function(e) {
            const schoolId = document.querySelector('input[name="school_id"]').value.trim();
            const password = document.querySelector('input[name="password"]').value.trim();
            
            if (!schoolId || !password) {
                e.preventDefault();
                alert('Please enter both school ID and password');
            }
        });
    </script>
</body>
</html>