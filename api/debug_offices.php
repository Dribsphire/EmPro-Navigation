<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/Database.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();
$auth->requireAdmin();

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Office Database Debug Information</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .section { margin: 30px 0; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4CAF50; }
</style>";

// Check offices table
echo "<div class='section'>";
echo "<h3>1. Offices Table (All Records)</h3>";
try {
    $stmt = $conn->query("SELECT * FROM offices ORDER BY office_id");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($offices) > 0) {
        echo "<p><strong>Total offices found: " . count($offices) . "</strong></p>";
        echo "<table>";
        echo "<tr><th>Office ID</th><th>Category ID</th><th>Office Name</th><th>Description</th><th>Latitude</th><th>Longitude</th><th>Created By</th><th>Created At</th></tr>";
        foreach ($offices as $office) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($office['office_id']) . "</td>";
            echo "<td>" . htmlspecialchars($office['category_id']) . "</td>";
            echo "<td>" . htmlspecialchars($office['office_name']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($office['description'] ?? '', 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($office['location_lat']) . "</td>";
            echo "<td>" . htmlspecialchars($office['location_lng']) . "</td>";
            echo "<td>" . htmlspecialchars($office['created_by']) . "</td>";
            echo "<td>" . htmlspecialchars($office['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>No offices found in the database!</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Check office_categories table
echo "<div class='section'>";
echo "<h3>2. Office Categories Table</h3>";
try {
    $stmt = $conn->query("SELECT * FROM office_categories ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "<p><strong>Total categories found: " . count($categories) . "</strong></p>";
        echo "<table>";
        echo "<tr><th>Category ID</th><th>Name</th><th>Description</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($cat['category_id']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['description'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No categories found!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Check office_images table
echo "<div class='section'>";
echo "<h3>3. Office Images Table</h3>";
try {
    $stmt = $conn->query("SELECT * FROM office_images ORDER BY office_id, is_primary DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($images) > 0) {
        echo "<p><strong>Total images found: " . count($images) . "</strong></p>";
        echo "<table>";
        echo "<tr><th>Image ID</th><th>Office ID</th><th>Image Path</th><th>Is Primary</th><th>Uploaded At</th></tr>";
        foreach ($images as $img) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($img['image_id']) . "</td>";
            echo "<td>" . htmlspecialchars($img['office_id']) . "</td>";
            echo "<td>" . htmlspecialchars($img['image_path']) . "</td>";
            echo "<td>" . ($img['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($img['uploaded_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No images found in office_images table.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test the query used by getAllOffices
echo "<div class='section'>";
echo "<h3>4. Test Query (Same as getAllOffices method)</h3>";
try {
    $sql = "
        SELECT 
            o.office_id,
            o.office_name,
            o.description,
            o.location_lat,
            o.location_lng,
            o.floor_level,
            o.contact_number,
            o.email,
            oc.category_id,
            oc.name as category_name,
            oi.image_path as marker_image
        FROM offices o
        LEFT JOIN office_categories oc ON o.category_id = oc.category_id
        LEFT JOIN office_images oi ON o.office_id = oi.office_id AND oi.is_primary = 1
        ORDER BY o.office_name
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($result) > 0) {
        echo "<p><strong>Query returned: " . count($result) . " offices</strong></p>";
        echo "<table>";
        echo "<tr><th>Office ID</th><th>Office Name</th><th>Category</th><th>Latitude</th><th>Longitude</th><th>Marker Image</th></tr>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['office_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['office_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category_name'] ?? 'No category') . "</td>";
            echo "<td>" . htmlspecialchars($row['location_lat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['location_lng']) . "</td>";
            echo "<td>" . htmlspecialchars($row['marker_image'] ?? 'No image') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Query returned 0 results!</strong></p>";
        echo "<p>This means the query is not finding any offices, even though they exist in the database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Check for common issues
echo "<div class='section'>";
echo "<h3>5. Common Issues Check</h3>";
try {
    // Check if offices have invalid category_ids
    $stmt = $conn->query("
        SELECT o.office_id, o.office_name, o.category_id 
        FROM offices o 
        LEFT JOIN office_categories oc ON o.category_id = oc.category_id 
        WHERE oc.category_id IS NULL
    ");
    $invalidCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($invalidCategories) > 0) {
        echo "<p style='color: orange;'><strong>Warning: " . count($invalidCategories) . " offices have invalid category_id:</strong></p>";
        echo "<ul>";
        foreach ($invalidCategories as $inv) {
            echo "<li>Office ID: " . $inv['office_id'] . " - Name: " . htmlspecialchars($inv['office_name']) . " - Category ID: " . $inv['category_id'] . " (does not exist)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ All offices have valid category_ids</p>";
    }
    
    // Check if offices have marker images
    $stmt = $conn->query("
        SELECT o.office_id, o.office_name 
        FROM offices o 
        LEFT JOIN office_images oi ON o.office_id = oi.office_id AND oi.is_primary = 1 
        WHERE oi.image_id IS NULL
    ");
    $noImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($noImages) > 0) {
        echo "<p style='color: orange;'><strong>Info: " . count($noImages) . " offices don't have marker images (this is OK, will use default):</strong></p>";
        echo "<ul>";
        foreach ($noImages as $noImg) {
            echo "<li>Office ID: " . $noImg['office_id'] . " - Name: " . htmlspecialchars($noImg['office_name']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ All offices have marker images</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><strong>Debug completed at: " . date('Y-m-d H:i:s') . "</strong></p>";
?>

