<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private PDO $conn;
    private array $debugLogs = [];

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Authenticate an admin using school ID (or username) + password.
     */
    public function authenticateAdmin(string $schoolId, string $password): array {
        $this->resetDebug();
        $this->debug('Starting admin authentication', ['identifier' => $schoolId]);

        $user = $this->findUserByIdentifier($schoolId);

        if (!$user) {
            $this->debug('No matching user found for identifier', ['identifier' => $schoolId]);
            return [
                'status' => 'error',
                'message' => 'Invalid school ID or password.',
                'debug' => $this->debugLogs
            ];
        }

        $this->debug('User record retrieved', [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'user_type' => $user['user_type'],
            'has_school_id' => !empty($user['school_id']),
            'has_admin_id' => !empty($user['admin_id'])
        ]);

        if (!password_verify($password, $user['password'])) {
            $this->debug('Password verification failed', ['user_id' => $user['user_id']]);
            return [
                'status' => 'error',
                'message' => 'Invalid school ID or password.',
                'debug' => $this->debugLogs
            ];
        }

        $this->debug('Password verification passed', ['user_id' => $user['user_id']]);

        if ($user['user_type'] !== 'admin') {
            $this->debug('User is not admin', ['user_type' => $user['user_type']]);
            $_SESSION['access_denied_context'] = [
                'school_id' => $user['school_id'] ?? $user['username'],
                'user_type' => $user['user_type'],
                'attempt_time' => date('Y-m-d H:i:s')
            ];

            return [
                'status' => 'denied',
                'redirect' => '/public/admin/access_denied.php',
                'debug' => $this->debugLogs
            ];
        }

        $this->debug('User is admin, creating session', ['user_id' => $user['user_id']]);
        $_SESSION['auth_user'] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'school_id' => $user['school_id'] ?? $user['username'],
            'user_type' => $user['user_type'],
            'admin_id' => $user['admin_id']
        ];

        $this->updateLastLogin((int) $user['user_id']);
        $this->debug('Last login timestamp updated', ['user_id' => $user['user_id']]);

        return [
            'status' => 'success',
            'redirect' => '/Empro-Navigation/public/admin/admin_index.php',
            'debug' => $this->debugLogs
        ];
    }

    /**
     * Force admin session otherwise redirect away.
     */
    public function requireAdmin(): void {
        if (!$this->isAdmin()) {
            header('Location: /public/admin_login.php');
            exit();
        }
    }

    public function isAdmin(): bool {
        return isset($_SESSION['auth_user']) &&
            $_SESSION['auth_user']['user_type'] === 'admin';
    }

    public function getCurrentUser(): ?array {
        return $_SESSION['auth_user'] ?? null;
    }

    public function logout(): void {
        // Prevent caching - force browser to not cache logout page
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Determine user type before destroying session
        $userType = null;
        if (isset($_SESSION['auth_user']) && isset($_SESSION['auth_user']['user_type'])) {
            $userType = $_SESSION['auth_user']['user_type'];
        } elseif (isset($_SESSION['user_type'])) {
            $userType = $_SESSION['user_type'];
        }

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Redirect based on user type
        // Get the current script's directory to calculate relative path
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        
        // Determine base path
        // If script is in /EmPro-Navigation/public/student/Logout.php, extract /EmPro-Navigation
        if (preg_match('#^/([^/]+)/public/#', $currentScript, $matches)) {
            $projectName = $matches[1];
            $basePath = '/' . $projectName;
        } else {
            // Fallback: try to detect from SCRIPT_FILENAME
            $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if ($scriptFile && $docRoot) {
                $relativePath = str_replace($docRoot, '', dirname($scriptFile));
                $relativePath = str_replace('\\', '/', $relativePath);
                // Extract project name (e.g., /EmPro-Navigation from /EmPro-Navigation/public/student)
                if (preg_match('#^/([^/]+)/#', $relativePath, $matches)) {
                    $basePath = '/' . $matches[1];
                } else {
                    $basePath = '/EmPro-Navigation'; // Default fallback
                }
            } else {
                $basePath = '/EmPro-Navigation'; // Default fallback
            }
        }
        
        // Build redirect URLs
        if ($userType === 'student' || $userType === 'guest') {
            header('Location: ' . $basePath . '/public/student_guest_login.php');
        } else {
            header('Location: ' . $basePath . '/public/admin_login.php');
        }
        exit();
    }

    private function findUserByIdentifier(string $identifier): ?array {
        $sql = "
            SELECT
                u.user_id,
                u.username,
                u.password,
                u.user_type,
                s.school_id,
                a.admin_id
            FROM users u
            LEFT JOIN students s ON s.user_id = u.user_id
            LEFT JOIN admins a ON a.user_id = u.user_id
            WHERE u.username = :identifier OR s.school_id = :identifier
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $this->debug('findUserByIdentifier matched record', [
                'user_id' => $user['user_id'],
                'user_type' => $user['user_type']
            ]);
        } else {
            $this->debug('findUserByIdentifier returned no results', ['identifier' => $identifier]);
        }

        return $user ?: null;
    }

    private function updateLastLogin(int $userId): void {
        $sql = "UPDATE admins SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function resetDebug(): void {
        $this->debugLogs = [];
    }

    private function debug(string $message, array $context = []): void {
        $entry = '[' . date('H:i:s') . "] {$message}";
        if (!empty($context)) {
            $entry .= ' ' . json_encode($context);
        }
        $this->debugLogs[] = $entry;
        error_log('[AuthDebug] ' . $entry);
    }
}
?>
