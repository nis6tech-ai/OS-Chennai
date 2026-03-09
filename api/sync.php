<?php
require_once 'connect.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_data':
        // Fetch everything for the frontend/CMS
        $settings = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
        $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
        // Alias category_id as category for frontend compatibility
        $products = $pdo->query("SELECT id, name, category_id as category, price, description, features, media, is_top_selling FROM products ORDER BY created_at DESC")->fetchAll();
        
        // Decode JSON fields & Bridge snake_case to camelCase
        foreach($products as &$p) {
            $p['features'] = json_decode($p['features'] ?? '[]') ?: [];
            $p['media'] = json_decode($p['media'] ?? '[]') ?: [];
            $p['isTopSelling'] = (bool)($p['is_top_selling'] ?? false);
        }


        if ($settings) {
            $settings['siteName'] = $settings['site_name'] ?? '';
            $settings['siteTagline'] = $settings['site_tagline'] ?? '';
            $settings['siteLogo'] = $settings['site_logo'] ?? '';
            $settings['heroImage'] = $settings['hero_image'] ?? '';
            $settings['contactEmail'] = $settings['contact_email'] ?? '';
            $settings['contactPhone'] = $settings['contact_phone'] ?? '';
            $settings['whatsappNumber'] = $settings['whatsapp_number'] ?? '';
            $settings['contactAddress'] = $settings['contact_address'] ?? '';
        }

        
        echo json_encode([
            'settings' => $settings,
            'categories' => $categories,
            'products' => $products
        ]);
        break;

    case 'save_product':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!$data) { echo json_encode(['error' => 'Invalid data']); break; }
        
        $sql = "REPLACE INTO products (id, name, category_id, price, description, features, media, is_top_selling) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['id'],
            $data['name'],
            $data['category'],
            $data['price'],
            $data['description'],
            json_encode($data['features']),
            json_encode($data['media']),
            ($data['isTopSelling'] || ($data['is_top_selling'] ?? false)) ? 1 : 0
        ]);
        
        echo json_encode(['success' => true]);
        break;

    case 'upload':
        // Secure file upload handler
        if (!isset($_FILES['file'])) { echo json_encode(['error' => 'No file']); break; }
        
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];
        
        if (!in_array($ext, $allowed)) { echo json_encode(['error' => 'Invalid type']); break; }
        
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
        $target = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['url' => 'uploads/products/' . $fileName]);
        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
        break;
        
        break;
        
    case 'save_settings':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!$data || !isset($data['settings'])) { echo json_encode(['error' => 'Invalid settings data']); break; }
        $s = $data['settings'];
        
        $sql = "REPLACE INTO site_settings (project_id, site_name, site_tagline, site_logo, hero_image, contact_email, contact_phone, whatsapp_number, contact_address) 
                VALUES ('nutpa', ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $s['siteName'] ?? $s['site_name'] ?? '',
            $s['siteTagline'] ?? $s['site_tagline'] ?? '',
            $s['siteLogo'] ?? $s['site_logo'] ?? '',
            $s['heroImage'] ?? $s['hero_image'] ?? '',
            $s['contactEmail'] ?? $s['contact_email'] ?? '',
            $s['contactPhone'] ?? $s['contact_phone'] ?? '',
            $s['whatsappNumber'] ?? $s['whatsapp_number'] ?? '',
            $s['contactAddress'] ?? $s['contact_address'] ?? ''
        ]);
        
        echo json_encode(['success' => true]);
        break;

    case 'save_category':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!$data || !isset($data['id'])) { 
            echo json_encode(['error' => 'Invalid category data']); 
            break; 
        }
        
        try {
            $sql = "REPLACE INTO categories (id, name, image_url) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['id'],
                $data['name'],
                $data['image_url'] ?? $data['image'] ?? ''
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Category save failed: ' . $e->getMessage()]);
        }
        break;

    case 'delete_product':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        }
        break;
}
?>
