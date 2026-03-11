<?php
require_once 'connect.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_data':
        $projectId = $_GET['project'] ?? 'nutpa';
        try {
            // Fetch settings for specific project
            $stmt = $pdo->prepare("SELECT * FROM site_settings WHERE project_id = ? LIMIT 1");
            $stmt->execute([$projectId]);
            $settings = $stmt->fetch();
            
            // If no settings yet, get the default one
            if (!$settings) {
                $settings = $pdo->query("SELECT * FROM site_settings WHERE project_id = 'nutpa' LIMIT 1")->fetch();
            }

            $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
            $products = $pdo->query("SELECT id, name, category_id as category, price, description, features, media, is_top_selling FROM products ORDER BY created_at DESC")->fetchAll();
            
            foreach($products as &$p) {
                try {
                    $p['features'] = json_decode($p['features'] ?? '[]', true) ?: [];
                    $p['media'] = json_decode($p['media'] ?? '[]', true) ?: [];
                } catch (Exception $e) {
                    $p['features'] = [];
                    $p['media'] = [];
                }
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
            ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Data fetch failed: ' . $e->getMessage()]);
        }
        break;

    case 'save_product':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!$data) { echo json_encode(['error' => 'Invalid data']); break; }
        
        try {
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
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Product save failed: ' . $e->getMessage()]);
        }
        break;

    case 'upload':
        if (!isset($_FILES['file'])) { echo json_encode(['error' => 'No file']); break; }
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];
        if (!in_array($ext, $allowed)) { echo json_encode(['error' => 'Invalid type']); break; }
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            echo json_encode(['url' => 'uploads/products/' . $fileName]);
        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
        break;

    case 'save_settings':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!$data || !isset($data['settings'])) { echo json_encode(['error' => 'Invalid settings data']); break; }
        
        $projectId = $_GET['project'] ?? 'nutpa';
        $s = $data['settings'];
        
        try {
            $sql = "INSERT INTO site_settings (project_id, site_name, site_tagline, site_logo, hero_image, contact_email, contact_phone, whatsapp_number, contact_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    site_name=VALUES(site_name), site_tagline=VALUES(site_tagline), site_logo=VALUES(site_logo), 
                    hero_image=VALUES(hero_image), contact_email=VALUES(contact_email), contact_phone=VALUES(contact_phone), 
                    whatsapp_number=VALUES(whatsapp_number), contact_address=VALUES(contact_address)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $projectId,
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
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Settings sync failed: ' . $e->getMessage()]);
        }
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
