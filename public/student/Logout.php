<?php
// Prevent caching - force browser to not cache this page
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Destroy session
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Redirect to login page (using relative path from public/student/)
header('Location: ../student_guest_login.php');
exit();

