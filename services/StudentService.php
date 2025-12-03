<?php
require_once __DIR__ . '/Database.php';

class StudentService {
    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Retrieve all registered students with section and account metadata.
     */
    public function getStudents(): array {
        $sql = "
            SELECT
                s.student_id,
                s.school_id,
                s.full_name,
                s.phone,
                s.created_at,
                sec.section_code,
                u.user_id,
                u.email,
                u.created_at AS account_created_at,
                u.updated_at
            FROM students s
            INNER JOIN sections sec ON sec.section_id = s.section_id
            INNER JOIN users u ON u.user_id = s.user_id
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Register a single student manually.
     *
     * @throws RuntimeException when validation fails
     */
    public function registerStudent(array $payload): array {
        $required = ['school_id', 'full_name', 'section_code', 'email'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new RuntimeException("Field {$field} is required.");
            }
        }

        $schoolId = trim($payload['school_id']);
        $fullName = trim($payload['full_name']);
        $sectionCode = strtoupper(trim($payload['section_code']));
        $email = trim($payload['email']);
        $phone = trim($payload['phone'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }

        if ($this->studentExists($schoolId)) {
            throw new RuntimeException("Student with school ID {$schoolId} already exists.");
        }

        $sectionId = $this->getOrCreateSectionId($sectionCode);
        $temporaryPassword = $payload['password'] ?? ($payload['initial_password'] ?? $this->generateTemporaryPassword());
        if (strlen($temporaryPassword) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }
        $passwordHash = password_hash($temporaryPassword, PASSWORD_BCRYPT);

        try {
            $this->conn->beginTransaction();

            $insertUser = $this->conn->prepare("
                INSERT INTO users (username, password, email, user_type, created_at, updated_at)
                VALUES (:username, :password, :email, 'student', NOW(), NOW())
            ");
            $insertUser->execute([
                ':username' => $schoolId,
                ':password' => $passwordHash,
                ':email' => $email
            ]);

            $userId = (int) $this->conn->lastInsertId();

            $insertStudent = $this->conn->prepare("
                INSERT INTO students (
                    user_id,
                    school_id,
                    full_name,
                    section_id,
                    email,
                    phone,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :school_id,
                    :full_name,
                    :section_id,
                    :email,
                    :phone,
                    NOW(),
                    NOW()
                )
            ");
            $insertStudent->execute([
                ':user_id' => $userId,
                ':school_id' => $schoolId,
                ':full_name' => $fullName,
                ':section_id' => $sectionId,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null
            ]);

            $this->conn->commit();
        } catch (Throwable $th) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw new RuntimeException($th->getMessage(), 0, $th);
        }

        return [
            'school_id' => $schoolId,
            'full_name' => $fullName,
            'section_code' => $sectionCode,
            'email' => $email,
            'phone' => $phone,
            'temporary_password' => $temporaryPassword
        ];
    }

    /**
     * Import students from csv file path.
     */
    public function importFromCsv(string $tmpPath): array {
        if (!file_exists($tmpPath)) {
            throw new RuntimeException('Uploaded file not found.');
        }

        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            throw new RuntimeException('Unable to open uploaded file.');
        }

        $results = [
            'processed' => 0,
            'imported' => 0,
            'errors' => []
        ];

        $header = null;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            // Skip empty rows
            if ($this->isRowEmpty($row)) {
                continue;
            }

            if ($header === null) {
                $header = $this->normalizeHeader($row);
                continue;
            }

            $results['processed']++;

            $data = $this->mapRowToData($header, $row);
            try {
                $this->registerStudent($data);
                $results['imported']++;
            } catch (Throwable $th) {
                $results['errors'][] = "Row {$results['processed']}: " . $th->getMessage();
            }
        }

        fclose($handle);
        return $results;
    }

    public function getSections(): array {
        $stmt = $this->conn->query("SELECT section_id, section_code, section_name FROM sections ORDER BY section_code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteStudent(string $schoolId): bool {
        $schoolId = trim($schoolId);
        if ($schoolId === '') {
            throw new RuntimeException('Invalid school ID provided.');
        }

        $stmt = $this->conn->prepare("
            SELECT u.user_id
            FROM students s
            INNER JOIN users u ON u.user_id = s.user_id
            WHERE s.school_id = :school_id
            LIMIT 1
        ");
        $stmt->execute([':school_id' => $schoolId]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            throw new RuntimeException('Student record not found.');
        }

        try {
            $this->conn->beginTransaction();

            $deleteStudent = $this->conn->prepare("DELETE FROM students WHERE school_id = :school_id");
            $deleteStudent->execute([':school_id' => $schoolId]);

            $deleteUser = $this->conn->prepare("DELETE FROM users WHERE user_id = :user_id");
            $deleteUser->execute([':user_id' => $userId]);

            $this->conn->commit();
            return true;
        } catch (Throwable $th) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new RuntimeException('Unable to delete student: ' . $th->getMessage());
        }
    }

    /**
     * Update a student's password
     */
    public function updatePassword(string $schoolId, string $newPassword): bool {
        if (strlen($newPassword) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        // Get user_id from students table
        $stmt = $this->conn->prepare("SELECT user_id FROM students WHERE school_id = :school_id LIMIT 1");
        $stmt->execute([':school_id' => $schoolId]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            throw new RuntimeException('Student not found.');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $updateStmt = $this->conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id");
        $updateStmt->execute([
            ':password' => $passwordHash,
            ':user_id' => $userId
        ]);

        return $updateStmt->rowCount() > 0;
    }

    private function studentExists(string $schoolId): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM students WHERE school_id = :school_id");
        $stmt->execute([':school_id' => $schoolId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function getOrCreateSectionId(string $sectionCode): int {
        $stmt = $this->conn->prepare("SELECT section_id FROM sections WHERE section_code = :section_code LIMIT 1");
        $stmt->execute([':section_code' => $sectionCode]);
        $sectionId = $stmt->fetchColumn();

        if ($sectionId) {
            return (int) $sectionId;
        }

        $insert = $this->conn->prepare("
            INSERT INTO sections (section_code, section_name, created_at, updated_at)
            VALUES (:section_code, :section_name, NOW(), NOW())
        ");
        $insert->execute([
            ':section_code' => $sectionCode,
            ':section_name' => $sectionCode
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function generateTemporaryPassword(): string {
        return substr(bin2hex(random_bytes(8)), 0, 10);
    }

    private function normalizeHeader(array $row): array {
        return array_map(function ($value) {
            $value = strtolower(trim($value ?? ''));
            return str_replace([' ', '-'], '_', $value);
        }, $row);
    }

    private function mapRowToData(array $header, array $row): array {
        $data = [];
        foreach ($header as $index => $field) {
            $data[$field] = $row[$index] ?? null;
        }

        return [
            'school_id' => $data['school_id'] ?? '',
            'full_name' => $data['full_name'] ?? '',
            'section_code' => $data['section_code'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'password' => $data['password'] ?? '',
        ];
    }

    private function isRowEmpty(array $row): bool {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}

