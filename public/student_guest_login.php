<?php
require_once __DIR__ . '/../services/Database.php';

session_start();

$database = new Database();
$conn = $database->getConnection();

$login_error = '';
$guest_error = '';

// Handle student login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'student_login') {
    $school_id = trim($_POST['school_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($school_id === '' || $password === '') {
        $login_error = 'Please enter both School ID and Password.';
    } else {
        // Find user by school_id (checking both users and students tables)
        $sql = "
            SELECT 
                u.user_id,
                u.username,
                u.password,
                u.user_type,
                s.school_id,
                s.full_name,
                s.student_id
            FROM users u
            INNER JOIN students s ON s.user_id = u.user_id
            WHERE s.school_id = :school_id AND u.user_type = 'student'
            LIMIT 1
        ";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':school_id', $school_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['school_id'] = $user['school_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['student_id'] = $user['student_id'];

                    header('Location: student/student_index.php');
                    exit;
                } else {
                    $login_error = 'Invalid password.';
                }
            } else {
                $login_error = 'Student with this School ID not found. Please register first.';
            }
        } catch (PDOException $e) {
            error_log('Student login error: ' . $e->getMessage());
            $login_error = 'Login temporarily unavailable.';
        }
    }
}

// Handle guest registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guest_signup') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $reason = trim($_POST['reason_summary'] ?? '');

    if ($full_name === '' || $email === '' || $contact === '' || $reason === '') {
        $guest_error = 'Full name, email, contact number, and reason are required.';
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $guest_error = 'Please enter a valid email address.';
        } else {
            // Generate token and expiry (1 day from now)
            $token = bin2hex(random_bytes(16));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 day'));

            $sql = "
                INSERT INTO guests (full_name, email, phone, reason, token, token_expiry, created_at, updated_at)
                VALUES (:full_name, :email, :phone, :reason, :token, :token_expiry, NOW(), NOW())
            ";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $contact);
                $stmt->bindParam(':reason', $reason);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':token_expiry', $token_expiry);

                if ($stmt->execute()) {
                    $guest_id = (int) $conn->lastInsertId();

                    $_SESSION['user_type'] = 'guest';
                    $_SESSION['guest_id'] = $guest_id;
                    $_SESSION['guest_name'] = $full_name;
                    $_SESSION['guest_token'] = $token;

                    // Redirect guest to guest index page
                    header('Location: guest/guest_index.php');
                    exit;
                } else {
                    $guest_error = 'Could not create guest access. Please try again.';
                }
            } else {
                $guest_error = 'Guest signup temporarily unavailable.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login-Empro::</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="stylesheet" type="text/css" href="css/login_student_guest.css" />
  
  </head>
  <body>
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form action="" method="POST" class="sign-in-form">
            <input type="hidden" name="action" value="student_login">
            <h2 class="title">Sign In</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="school_id" placeholder="School ID" required />
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Password" required />
            </div>
            <?php if (!empty($login_error)): ?>
              <p style="color:#ffb3b3; font-size:0.9rem; margin-top:0.5rem;"><?php echo htmlspecialchars($login_error); ?></p>
            <?php endif; ?>
            <input type="submit" value="Login" class="btn solid" />

            <!--<p class="social-text">Or Sign in with social platforms</p>
            <div class="social-media">
              <a href="#" class="social-icon">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>-->
          </form>

          <form action="" method="POST" class="sign-up-form">
            <input type="hidden" name="action" value="guest_signup">
            <h2 class="title">Sign Up</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="full_name" placeholder="Full name" required />
            </div>
            <div class="input-field">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-field">
              <i class="fas fa-phone"></i>
              <input type="text" name="contact_number" placeholder="Contact number" required />
            </div>
            <div class="input-field" style="max-width: 380px;
            width: 100%;
            background-color: #f0f0f0;
            margin: 10px 0;
            height: 55px;
            border-radius: 20px;
            display: grid;
            grid-template-columns: 15% 85%;
            padding: 0 0.4rem;
            position: relative;
            height: 6rem;">
              <i class="fas fa-envelope"></i>
              <input type="text" id="reason_summary" name="reason_summary" placeholder="Enter a brief reason here" required>
              
            </div>
            <?php if (!empty($guest_error)): ?>
              <p style="color:#ffb3b3; font-size:0.9rem; margin-top:0.5rem;"><?php echo htmlspecialchars($guest_error); ?></p>
            <?php endif; ?>
            <input type="submit" value="Navigate" class="btn solid" />
          </form>
        </div>
      </div>
      <div class="panels-container">

        <div class="panel left-panel">
            <div class="content">
                <h3>EmPro</h3>
                <p>A simple, intuitive tool that helps students and staff quickly access school schedules, resources, and essential information in one place.</p>
                <button class="btn transparent" id="sign-up-btn">I'm a Guest</button>
            </div>
            <img src="images/CHMSU.png" class="image" alt="" style="width:300px; height:300px;align-self: center;">
        </div>

        <div class="panel right-panel">
            <div class="content">
                <h3>EmPro</h3>
                <p>A simple, intuitive tool that helps students and staff quickly access school schedules, resources, and essential information in one place.</p>
                <button class="btn transparent" id="sign-in-btn">I'm a Student</button>
            </div>
            <img src="images/CHMSU.png" class="image" alt="" style="width:300px; height:300px;align-self: center;">
        </div>
      </div>
    </div>

    <script>
        const sign_in_btn = document.querySelector("#sign-in-btn");
        const sign_up_btn = document.querySelector("#sign-up-btn");
        const container = document.querySelector(".container");

        sign_up_btn.addEventListener('click', () =>{
            container.classList.add("sign-up-mode");
        });

        sign_in_btn.addEventListener('click', () =>{
            container.classList.remove("sign-up-mode");
        });
    </script>
  </body>
</html>