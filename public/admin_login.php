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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        
        /* Password toggle icon styling */
        .input-field .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #acacac;
            z-index: 10;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .input-field .toggle-password:hover {
            color: #666;
        }
        
        /* Adjust input padding to prevent text overlap with eye icon */
        .input-field input[type="password"],
        .input-field input[type="text"] {
            padding-right: 40px;
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
                    <div class="input-field" style="position: relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password-input" name="password" placeholder="Password" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" required />
                        <i class="fas fa-eye toggle-password" id="toggle-password" onclick="togglePasswordVisibility()"></i>
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

    <script>
        // Prevent password autocomplete suggestions - aggressive approach
        const passwordInput = document.getElementById('password-input');
        if (passwordInput) {
            // Multiple methods to prevent autocomplete
            passwordInput.setAttribute('autocomplete', 'off');
            passwordInput.setAttribute('data-lpignore', 'true'); // LastPass ignore
            passwordInput.setAttribute('data-form-type', 'other');
            passwordInput.setAttribute('data-1p-ignore', 'true'); // 1Password ignore
            passwordInput.setAttribute('data-bwignore', 'true'); // Bitwarden ignore
            
            // Prevent autocomplete on focus
            passwordInput.addEventListener('focus', function() {
                this.setAttribute('autocomplete', 'off');
                this.setAttribute('data-lpignore', 'true');
                // Remove readonly if still present
                this.removeAttribute('readonly');
            }, true);
            
            // Prevent autocomplete on click
            passwordInput.addEventListener('click', function() {
                this.setAttribute('autocomplete', 'off');
                this.setAttribute('data-lpignore', 'true');
                this.removeAttribute('readonly');
            }, true);
            
            // Prevent autocomplete when typing first character
            let isFirstChar = true;
            passwordInput.addEventListener('keydown', function(e) {
                // Reset flag if field is cleared
                if (this.value.length === 0) {
                    isFirstChar = true;
                }
                
                // If it's the first character being typed
                if (isFirstChar && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    // Temporarily change to text type to break autocomplete
                    const wasPassword = this.type === 'password';
                    if (wasPassword) {
                        this.type = 'text';
                        // Force browser to not show autocomplete
                        this.setAttribute('autocomplete', 'off');
                        setTimeout(() => {
                            this.type = 'password';
                            isFirstChar = false;
                        }, 10);
                    }
                } else {
                    isFirstChar = false;
                }
                
                // Always ensure autocomplete is off
                this.setAttribute('autocomplete', 'off');
                this.setAttribute('data-lpignore', 'true');
            }, true);
            
            // Prevent autocomplete on input
            passwordInput.addEventListener('input', function() {
                this.setAttribute('autocomplete', 'off');
                this.setAttribute('data-lpignore', 'true');
            }, true);
        }
        
        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password-input');
            const toggleIcon = document.getElementById('toggle-password');
            
            if (passwordInput && toggleIcon) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            }
        }
        
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