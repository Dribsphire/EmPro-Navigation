<?php
require_once __DIR__ . '/../../services/Auth.php';

$auth = new Auth(); // Ensure session is started
$context = $_SESSION['access_denied_context'] ?? null;
if ($context) {
    unset($_SESSION['access_denied_context']);
}

$schoolId = $context['school_id'] ?? 'Unknown';
$userType = $context['user_type'] ?? 'user';
$attemptTime = $context['attempt_time'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - EmPro</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1f2937, #111827);
            color: #f5f5f5;
        }
        .card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 2rem;
            width: min(420px, 90vw);
            text-align: center;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.6);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            color: #fecaca;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        p {
            margin: 0 0 1rem;
            line-height: 1.5;
            color: #d1d5db;
        }
        .info {
            text-align: left;
            margin: 1.5rem 0;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .info dt {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }
        .info dd {
            margin: 0 0 0.75rem;
            font-weight: 600;
            color: #f3f4f6;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .actions a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .actions a.primary {
            background: #f97316;
            color: #111827;
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.35);
        }
        .actions a.secondary {
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #f3f4f6;
        }
        .actions a:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="badge">
            <span>Restricted</span>
        </div>
        <h1>Access Denied</h1>
        <p>The credentials you entered are valid, but your account does not have administrator privileges.</p>

        <dl class="info">
            <dt>School ID / Username</dt>
            <dd><?= htmlspecialchars($schoolId); ?></dd>
            <dt>Detected Role</dt>
            <dd><?= htmlspecialchars(ucfirst($userType)); ?></dd>
            <dt>Time</dt>
            <dd><?= htmlspecialchars($attemptTime); ?></dd>
        </dl>

        <div class="actions">
            <a class="primary" href="/public/admin_login.php">Return to Admin Login</a>
            <a class="secondary" href="/public/student_guest_login.php">Go to Student/Guest Portal</a>
        </div>
    </section>
</body>
</html>

