<?php
require_once __DIR__ . '/Database.php';

class OfficeService {
    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new office with images
     * @param array $data Office data (office_name, description, location_lat, location_lng, category_id, created_by)
     * @param array $markerImage Marker image file data
     * @param array $contentImages Array of content image file data
     * @return array Result with status and message
     */
    public function createOffice(array $data, array $markerImage, array $contentImages = []): array {
        try {
            $this->conn->beginTransaction();

            // Insert office
            $sql = "
                INSERT INTO offices (
                    category_id, office_name, description, 
                    location_lat, location_lng, created_by
                ) VALUES (
                    :category_id, :office_name, :description,
                    :location_lat, :location_lng, :created_by
                )
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':category_id' => $data['category_id'],
                ':office_name' => $data['office_name'],
                ':description' => $data['description'] ?? null,
                ':location_lat' => $data['location_lat'],
                ':location_lng' => $data['location_lng'],
                ':created_by' => $data['created_by']
            ]);

            $officeId = (int) $this->conn->lastInsertId();

            // Handle marker image
            if (!empty($markerImage['tmp_name']) && $markerImage['error'] === UPLOAD_ERR_OK) {
                $markerPath = $this->uploadMarkerImage($markerImage, $officeId);
                if ($markerPath) {
                    $this->insertOfficeImage($officeId, $markerPath, true);
                }
            }

            // Handle content images (up to 4)
            $uploadedCount = 0;
            foreach ($contentImages as $image) {
                if ($uploadedCount >= 4) break;
                
                if (!empty($image['tmp_name']) && $image['error'] === UPLOAD_ERR_OK) {
                    $contentPath = $this->uploadContentImage($image, $officeId);
                    if ($contentPath) {
                        $this->insertOfficeImage($officeId, $contentPath, false);
                        $uploadedCount++;
                    }
                }
            }

            $this->conn->commit();

            return [
                'status' => 'success',
                'message' => 'Office created successfully.',
                'office_id' => $officeId
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'status' => 'error',
                'message' => 'Failed to create office: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload marker image to buildings folder
     */
    private function uploadMarkerImage(array $file, int $officeId): ?string {
        $uploadDir = __DIR__ . '/../buildings/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'office_' . $officeId . '_marker.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'buildings/' . $filename;
        }

        return null;
    }

    /**
     * Upload content image to building_content folder
     */
    private function uploadContentImage(array $file, int $officeId): ?string {
        $uploadDir = __DIR__ . '/../building_content/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $filename = 'office_' . $officeId . '_' . $timestamp . '_' . $random . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'building_content/' . $filename;
        }

        return null;
    }

    /**
     * Insert office image record
     */
    private function insertOfficeImage(int $officeId, string $imagePath, bool $isPrimary): void {
        $sql = "
            INSERT INTO office_images (office_id, image_path, is_primary)
            VALUES (:office_id, :image_path, :is_primary)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':office_id' => $officeId,
            ':image_path' => $imagePath,
            ':is_primary' => $isPrimary ? 1 : 0
        ]);
    }

    /**
     * Get all offices with their primary images
     */
    public function getAllOffices(): array {
        try {
            $sql = "
                SELECT 
                    o.office_id,
                    o.office_name,
                    o.description,
                    o.location_lat,
                    o.location_lng,
                    oc.category_id,
                    oc.name as category_name,
                    oi.image_path as marker_image
                FROM offices o
                LEFT JOIN office_categories oc ON o.category_id = oc.category_id
                LEFT JOIN office_images oi ON o.office_id = oi.office_id AND oi.is_primary = 1
                ORDER BY o.office_name
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (Exception $e) {
            error_log('OfficeService::getAllOffices error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all office categories
     */
    public function getCategories(): array {
        $sql = "SELECT category_id, name, description FROM office_categories ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get office content images
     */
    public function getOfficeContentImages(int $officeId): array {
        try {
            $sql = "
                SELECT image_path 
                FROM office_images 
                WHERE office_id = :office_id AND is_primary = 0
                ORDER BY uploaded_at
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':office_id' => $officeId]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (Exception $e) {
            error_log('OfficeService::getOfficeContentImages error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update an office with optional image changes
     * @param array $data Office data (office_id, office_name, description, category_id)
     * @param array|null $markerImage New marker image file data (optional)
     * @param array $contentImages Array of new content image file data (optional)
     * @param array $deletedImages Array of image IDs or paths to delete (optional)
     * @return array Result with status and message
     */
    public function updateOffice(array $data, ?array $markerImage = null, array $contentImages = [], array $deletedImages = []): array {
        try {
            $this->conn->beginTransaction();

            $officeId = (int) $data['office_id'];

            // Update office basic info
            $sql = "
                UPDATE offices 
                SET office_name = :office_name,
                    description = :description,
                    category_id = :category_id
                WHERE office_id = :office_id
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':office_id' => $officeId,
                ':office_name' => $data['office_name'],
                ':description' => $data['description'] ?? null,
                ':category_id' => $data['category_id']
            ]);

            // Handle deleted images
            if (!empty($deletedImages)) {
                foreach ($deletedImages as $imagePath) {
                    // Delete from database
                    $deleteSql = "DELETE FROM office_images WHERE office_id = :office_id AND image_path = :image_path";
                    $deleteStmt = $this->conn->prepare($deleteSql);
                    $deleteStmt->execute([
                        ':office_id' => $officeId,
                        ':image_path' => $imagePath
                    ]);

                    // Delete file from disk
                    $fullPath = __DIR__ . '/../' . $imagePath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }

            // Handle marker image update
            if ($markerImage && !empty($markerImage['tmp_name']) && $markerImage['error'] === UPLOAD_ERR_OK) {
                // Delete old marker image
                $oldMarkerSql = "SELECT image_path FROM office_images WHERE office_id = :office_id AND is_primary = 1";
                $oldMarkerStmt = $this->conn->prepare($oldMarkerSql);
                $oldMarkerStmt->execute([':office_id' => $officeId]);
                $oldMarkerPath = $oldMarkerStmt->fetchColumn();
                
                if ($oldMarkerPath) {
                    $deleteOldMarkerSql = "DELETE FROM office_images WHERE office_id = :office_id AND is_primary = 1";
                    $deleteOldMarkerStmt = $this->conn->prepare($deleteOldMarkerSql);
                    $deleteOldMarkerStmt->execute([':office_id' => $officeId]);
                    
                    $fullPath = __DIR__ . '/../' . $oldMarkerPath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }

                // Upload new marker image
                $markerPath = $this->uploadMarkerImage($markerImage, $officeId);
                if ($markerPath) {
                    $this->insertOfficeImage($officeId, $markerPath, true);
                }
            }

            // Handle new content images (up to 4 total)
            $currentContentCount = $this->getContentImageCount($officeId);
            $availableSlots = 4 - $currentContentCount;
            
            $uploadedCount = 0;
            foreach ($contentImages as $image) {
                if ($uploadedCount >= $availableSlots) break;
                
                if (!empty($image['tmp_name']) && $image['error'] === UPLOAD_ERR_OK) {
                    $contentPath = $this->uploadContentImage($image, $officeId);
                    if ($contentPath) {
                        $this->insertOfficeImage($officeId, $contentPath, false);
                        $uploadedCount++;
                    }
                }
            }

            $this->conn->commit();

            return [
                'status' => 'success',
                'message' => 'Office updated successfully.'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('OfficeService::updateOffice error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to update office: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get count of content images for an office
     */
    private function getContentImageCount(int $officeId): int {
        $sql = "SELECT COUNT(*) FROM office_images WHERE office_id = :office_id AND is_primary = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':office_id' => $officeId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get office by ID with all images
     */
    public function getOfficeById(int $officeId): ?array {
        try {
            $sql = "
                SELECT 
                    o.office_id,
                    o.office_name,
                    o.description,
                    o.location_lat,
                    o.location_lng,
                    o.category_id,
                    oc.name as category_name
                FROM offices o
                LEFT JOIN office_categories oc ON o.category_id = oc.category_id
                WHERE o.office_id = :office_id
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':office_id' => $officeId]);
            $office = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$office) {
                return null;
            }

            // Get marker image
            $markerSql = "SELECT image_path FROM office_images WHERE office_id = :office_id AND is_primary = 1 LIMIT 1";
            $markerStmt = $this->conn->prepare($markerSql);
            $markerStmt->execute([':office_id' => $officeId]);
            $office['marker_image'] = $markerStmt->fetchColumn() ?: null;

            // Get content images
            $office['content_images'] = $this->getOfficeContentImages($officeId);

            return $office;
        } catch (Exception $e) {
            error_log('OfficeService::getOfficeById error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an office and its associated images
     */
    public function deleteOffice(int $officeId): array {
        try {
            $this->conn->beginTransaction();
            
            // Get image paths before deleting (for file cleanup)
            $imageSql = "SELECT image_path FROM office_images WHERE office_id = :office_id";
            $imageStmt = $this->conn->prepare($imageSql);
            $imageStmt->execute([':office_id' => $officeId]);
            $images = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete office images (cascade should handle this, but being explicit)
            $deleteImagesSql = "DELETE FROM office_images WHERE office_id = :office_id";
            $deleteImagesStmt = $this->conn->prepare($deleteImagesSql);
            $deleteImagesStmt->execute([':office_id' => $officeId]);
            
            // Delete the office
            $deleteOfficeSql = "DELETE FROM offices WHERE office_id = :office_id";
            $deleteOfficeStmt = $this->conn->prepare($deleteOfficeSql);
            $deleteOfficeStmt->execute([':office_id' => $officeId]);
            
            if ($deleteOfficeStmt->rowCount() === 0) {
                $this->conn->rollBack();
                return [
                    'status' => 'error',
                    'message' => 'Office not found or already deleted.'
                ];
            }
            
            $this->conn->commit();
            
            // Optionally delete image files from disk
            foreach ($images as $imagePath) {
                $fullPath = __DIR__ . '/../' . $imagePath;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            
            return [
                'status' => 'success',
                'message' => 'Office deleted successfully.'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('OfficeService::deleteOffice error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to delete office: ' . $e->getMessage()
            ];
        }
    }
}
?>

