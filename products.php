<?php
ini_set('display_errors', 0);
ob_start();
require_once 'config.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

function sendResponse($status, $message, $data = []) {
    ob_clean();
    $data = array_map(function($item) {
        if (is_array($item)) {
            return array_map('utf8_encode', $item);
        }
        return utf8_encode($item);
    }, $data);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    ob_end_flush();
    exit;
}

function resizeImage($source, $destination, $maxWidth = 800, $maxHeight = 800, $quality = 85) {
    list($width, $height, $type) = getimagesize($source);
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    $src = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($source),
        IMAGETYPE_PNG => imagecreatefrompng($source),
        IMAGETYPE_GIF => imagecreatefromgif($source),
        default => false
    };
    if (!$src) return false;

    $dst = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $result = imagejpeg($dst, $destination, $quality);
    imagedestroy($src);
    imagedestroy($dst);
    return $result;
}

function clearCategoryCache() {
    if (extension_loaded('apcu')) {
        apcu_delete('product_categories');
    } else {
        $cacheFile = 'cache/categories.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

try {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, title, description, price, category, image, is_top_rated FROM products ORDER BY created_at DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Products retrieved successfully', $products);
    } elseif ($action === 'get' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($id <= 0) {
            sendResponse('error', 'Invalid product ID');
        }
        $stmt = $pdo->prepare("SELECT id, title, description, price, category, image, is_top_rated FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            sendResponse('success', 'Product retrieved successfully', $product);
        } else {
            sendResponse('error', 'Product not found');
        }
    } elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $is_top_rated = isset($_POST['is_top_rated']) ? 1 : 0;

        if (empty($title) || empty($description) || $price <= 0 || empty($category)) {
            sendResponse('error', 'All fields are required');
        }

        $image_path = '';
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "img/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $original_name = basename($_FILES['image']['name']);
            $image_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $normalized_ext = in_array($image_type, ['jpg', 'jpeg']) ? 'jpg' : $image_type;
            $image_name = time() . '_' . pathinfo($original_name, PATHINFO_FILENAME) . '.' . $normalized_ext;
            $target_file = $target_dir . $image_name;

            if (!in_array($image_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                sendResponse('error', 'Invalid image format. Use JPG, PNG, or GIF');
            }

            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                sendResponse('error', 'Image size exceeds 2MB limit');
            }

            if (!getimagesize($_FILES['image']['tmp_name'])) {
                sendResponse('error', 'File is not a valid image');
            }

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                sendResponse('error', 'Failed to upload image');
            }

            if (!resizeImage($target_file, $target_file)) {
                unlink($target_file);
                sendResponse('error', 'Failed to process image');
            }
            $image_path = $target_file;
        } else {
            sendResponse('error', 'Image is required for new products');
        }

        $stmt = $pdo->prepare("INSERT INTO products (title, description, price, category, image, is_top_rated, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $price, $category, $image_path, $is_top_rated]);
        clearCategoryCache();
        sendResponse('success', 'Product added successfully');
    } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $existing_image = trim($_POST['existing_image'] ?? '');
        $is_top_rated = isset($_POST['is_top_rated']) ? 1 : 0;

        if ($id <= 0 || empty($title) || empty($description) || $price <= 0 || empty($category)) {
            sendResponse('error', 'All fields are required');
        }

        $image_path = $existing_image;
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "img/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $original_name = basename($_FILES['image']['name']);
            $image_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $normalized_ext = in_array($image_type, ['jpg', 'jpeg']) ? 'jpg' : $image_type;
            $image_name = time() . '_' . pathinfo($original_name, PATHINFO_FILENAME) . '.' . $normalized_ext;
            $target_file = $target_dir . $image_name;

            if (!in_array($image_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                sendResponse('error', 'Invalid image format. Use JPG, PNG, or GIF');
            }

            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                sendResponse('error', 'Image size exceeds 2MB limit');
            }

            if (!getimagesize($_FILES['image']['tmp_name'])) {
                sendResponse('error', 'File is not a valid image');
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                if (!resizeImage($target_file, $target_file)) {
                    unlink($target_file);
                    sendResponse('error', 'Failed to process image');
                }
                $image_path = $target_file;
                if ($existing_image && file_exists($existing_image) && $existing_image !== $image_path) {
                    @unlink($existing_image);
                }
            } else {
                sendResponse('error', 'Failed to upload image');
            }
        } elseif (empty($image_path)) {
            sendResponse('error', 'An existing image is required if no new image is uploaded');
        }

        $stmt = $pdo->prepare("UPDATE products SET title = ?, description = ?, price = ?, category = ?, image = ?, is_top_rated = ? WHERE id = ?");
        $stmt->execute([$title, $description, $price, $category, $image_path, $is_top_rated, $id]);
        clearCategoryCache();
        if ($stmt->rowCount() > 0) {
            sendResponse('success', 'Product updated successfully');
        } else {
            sendResponse('error', 'No changes made or product not found');
        }
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            sendResponse('error', 'Invalid product ID');
        }

        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if ($product['image'] && file_exists($product['image'])) {
                @unlink($product['image']);
            }
            clearCategoryCache();
            sendResponse('success', 'Product deleted successfully');
        } else {
            sendResponse('error', 'Product not found');
        }
    } elseif ($action === 'list_categories') {
        $categories = [];
        if (extension_loaded('apcu')) {
            $cacheKey = 'product_categories';
            $categories = apcu_fetch($cacheKey);
            if ($categories === false) {
                $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
                $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                apcu_store($cacheKey, $categories, 3600);
            }
        } else {
            $cacheFile = 'cache/categories.json';
            if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
                $categories = json_decode(file_get_contents($cacheFile), true);
            } else {
                $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
                $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!is_dir('cache')) {
                    mkdir('cache', 0755, true);
                }
                file_put_contents($cacheFile, json_encode($categories));
            }
        }
        sendResponse('success', 'Categories retrieved successfully', $categories);
    } elseif ($action === 'edit_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $old_category = trim($_POST['old_category'] ?? '');
        $new_category = trim($_POST['new_category'] ?? '');

        if (empty($old_category) || empty($new_category)) {
            sendResponse('error', 'Both old and new category names are required');
        }

        if ($old_category === $new_category) {
            sendResponse('error', 'New category name must be different');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
        $stmt->execute([$new_category]);
        if ($stmt->fetchColumn() > 0) {
            sendResponse('error', 'Category name already exists');
        }

        $stmt = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
        $stmt->execute([$new_category, $old_category]);
        if ($stmt->rowCount() > 0) {
            clearCategoryCache();
            sendResponse('success', 'Category updated successfully');
        } else {
            sendResponse('error', 'No products found with the specified category');
        }
    } elseif ($action === 'delete_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $category = trim($_POST['category'] ?? '');

        if (empty($category)) {
            sendResponse('error', 'Category name is required');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
        $stmt->execute([$category]);
        if ($stmt->fetchColumn() > 0) {
            sendResponse('error', 'Cannot delete category: It is used by existing products. Please reassign or delete those products first.');
        } else {
            clearCategoryCache();
            sendResponse('success', 'Category deleted successfully');
        }
    } else {
        sendResponse('error', 'Invalid action');
    }
} catch (Exception $e) {
    error_log("Products API error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendResponse('error', 'Server error: Unable to process request');
}
?>