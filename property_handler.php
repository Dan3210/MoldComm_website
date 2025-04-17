<?php
require_once 'config.php';
require_once 'auth.php';

class PropertyHandler {
    private $conn;
    private $upload_dir = 'uploads/properties/';

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create upload directory if it doesn't exist
            if (!file_exists($this->upload_dir)) {
                mkdir($this->upload_dir, 0777, true);
            }
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function addProperty($user_id, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO properties (
                    user_id, title, description, price, property_type, 
                    listing_type, bedrooms, bathrooms, area_sqft, 
                    address, city, state, zip_code, country, 
                    latitude, longitude
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $user_id,
                $data['title'],
                $data['description'],
                $data['price'],
                $data['property_type'],
                $data['listing_type'],
                $data['bedrooms'],
                $data['bathrooms'],
                $data['area_sqft'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['zip_code'],
                $data['country'],
                $data['latitude'],
                $data['longitude']
            ]);

            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function uploadPropertyImage($property_id, $file, $is_primary = false) {
        try {
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                return ['success' => false, 'message' => 'Invalid file type'];
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filepath = $this->upload_dir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'message' => 'Failed to upload file'];
            }

            // Get current image count for ordering
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
            $stmt->execute([$property_id]);
            $image_order = $stmt->fetchColumn();

            // Insert image record
            $stmt = $this->conn->prepare("
                INSERT INTO property_images (property_id, image_path, is_primary, image_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$property_id, $filepath, $is_primary, $image_order]);

            return ['success' => true, 'message' => 'Image uploaded successfully'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function addPropertyFeature($property_id, $feature_name, $feature_value) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO property_features (property_id, feature_name, feature_value)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$property_id, $feature_name, $feature_value]);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function addPropertyAmenity($property_id, $amenity_name) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO property_amenities (property_id, amenity_name)
                VALUES (?, ?)
            ");
            $stmt->execute([$property_id, $amenity_name]);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProperty($property_id) {
        try {
            // Get property details
            $stmt = $this->conn->prepare("
                SELECT p.*, u.username as owner_name
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$property_id]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$property) {
                return null;
            }

            // Get property images
            $stmt = $this->conn->prepare("
                SELECT * FROM property_images
                WHERE property_id = ?
                ORDER BY image_order ASC
            ");
            $stmt->execute([$property_id]);
            $property['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get property features
            $stmt = $this->conn->prepare("
                SELECT * FROM property_features
                WHERE property_id = ?
            ");
            $stmt->execute([$property_id]);
            $property['features'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get property amenities
            $stmt = $this->conn->prepare("
                SELECT * FROM property_amenities
                WHERE property_id = ?
            ");
            $stmt->execute([$property_id]);
            $property['amenities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $property;
        } catch(PDOException $e) {
            return null;
        }
    }

    public function searchProperties($filters = []) {
        try {
            $query = "
                SELECT p.*, u.username as owner_name,
                (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE 1=1
            ";
            $params = [];

            // Add filters
            if (!empty($filters['property_type'])) {
                $query .= " AND p.property_type = ?";
                $params[] = $filters['property_type'];
            }
            if (!empty($filters['listing_type'])) {
                $query .= " AND p.listing_type = ?";
                $params[] = $filters['listing_type'];
            }
            if (!empty($filters['min_price'])) {
                $query .= " AND p.price >= ?";
                $params[] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $query .= " AND p.price <= ?";
                $params[] = $filters['max_price'];
            }
            if (!empty($filters['bedrooms'])) {
                $query .= " AND p.bedrooms = ?";
                $params[] = $filters['bedrooms'];
            }
            if (!empty($filters['city'])) {
                $query .= " AND p.city LIKE ?";
                $params[] = "%{$filters['city']}%";
            }

            $query .= " ORDER BY p.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $propertyHandler = new PropertyHandler();
    $response = [];

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        $response = ['success' => false, 'message' => 'Authentication required'];
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_property':
                    if (isset($_POST['property_data'])) {
                        $property_data = json_decode($_POST['property_data'], true);
                        $property_id = $propertyHandler->addProperty($_SESSION['user_id'], $property_data);
                        if ($property_id) {
                            $response = ['success' => true, 'property_id' => $property_id];
                        } else {
                            $response = ['success' => false, 'message' => 'Failed to add property'];
                        }
                    }
                    break;

                case 'upload_image':
                    if (isset($_FILES['image']) && isset($_POST['property_id'])) {
                        $response = $propertyHandler->uploadPropertyImage(
                            $_POST['property_id'],
                            $_FILES['image'],
                            isset($_POST['is_primary']) ? $_POST['is_primary'] : false
                        );
                    }
                    break;

                case 'add_feature':
                    if (isset($_POST['property_id']) && isset($_POST['feature_name']) && isset($_POST['feature_value'])) {
                        $success = $propertyHandler->addPropertyFeature(
                            $_POST['property_id'],
                            $_POST['feature_name'],
                            $_POST['feature_value']
                        );
                        $response = ['success' => $success];
                    }
                    break;

                case 'add_amenity':
                    if (isset($_POST['property_id']) && isset($_POST['amenity_name'])) {
                        $success = $propertyHandler->addPropertyAmenity(
                            $_POST['property_id'],
                            $_POST['amenity_name']
                        );
                        $response = ['success' => $success];
                    }
                    break;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 